<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\PurchaseOrderModel;
use PDO;

final class DeliveryService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function recordDelivery(
        int $poId,
        string $vendorDeliveryRef,
        string $receivedAt,
        string $notes,
        array $itemRows,
        int $receivedByUserId
    ): int {
        $poModel = new PurchaseOrderModel();
        $po = $poModel->find($poId);
        if (!$po) {
            throw new \RuntimeException('Purchase Order not found.');
        }

        BranchScopeService::assertBranchAccess((int)$po['ship_to_branch_id']);

        if (!in_array($po['status'], ['approved', 'sent', 'partially_received'], true)) {
            throw new \RuntimeException('Deliveries can only be recorded for approved, sent, or partially received Purchase Orders.');
        }

        $poItems = [];
        foreach ($poModel->items($poId) as $pi) {
            $poItems[(int)$pi['id']] = $pi;
        }

        $validatedLines = [];
        $anyAccepted = false;
        $anyRejected = false;

        foreach ($itemRows as $poItemId => $row) {
            $poItemId = (int)$poItemId;
            if (!isset($poItems[$poItemId])) {
                continue;
            }
            $recQty = (float)($row['received'] ?? 0);
            $rejQty = (float)($row['rejected'] ?? 0);
            $condNotes = trim((string)($row['condition_notes'] ?? ''));

            if ($recQty < 0 || $rejQty < 0) {
                throw new \RuntimeException('Received and rejected quantities cannot be negative.');
            }
            if ($recQty === 0.0 && $rejQty === 0.0) {
                continue;
            }

            $ordered = (float)$poItems[$poItemId]['quantity_ordered'];
            $alreadyReceived = (float)$poItems[$poItemId]['quantity_received_so_far'];
            $remaining = round($ordered - $alreadyReceived, 3);

            if ($recQty > $remaining + 0.0001) {
                throw new \RuntimeException(
                    "Cannot receive {$recQty} for '{$poItems[$poItemId]['description']}'; only {$remaining} remaining open on PO."
                );
            }

            if ($recQty > 0) {
                $anyAccepted = true;
            }
            if ($rejQty > 0) {
                $anyRejected = true;
            }

            $validatedLines[] = [
                'purchase_order_item_id' => $poItemId,
                'quantity_received'      => $recQty,
                'quantity_rejected'      => $rejQty,
                'condition_notes'        => $condNotes !== '' ? $condNotes : null,
            ];
        }

        if (empty($validatedLines)) {
            throw new \RuntimeException('Enter a received or rejected quantity for at least one item.');
        }

        $this->db->beginTransaction();
        try {
            $deliveryNo = DocumentNumberService::next('DEL', 'deliveries', 'delivery_no');
            $initialStatus = (!$anyAccepted && $anyRejected) ? 'rejected' : 'partial';

            $stmt = $this->db->prepare(
                'INSERT INTO deliveries (delivery_no, purchase_order_id, vendor_delivery_ref, received_at, received_by, delivery_status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $deliveryNo,
                $poId,
                $vendorDeliveryRef !== '' ? $vendorDeliveryRef : null,
                str_replace('T', ' ', $receivedAt),
                $receivedByUserId,
                $initialStatus,
                $notes !== '' ? $notes : null,
            ]);
            $deliveryId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO delivery_items (delivery_id, purchase_order_item_id, quantity_received, quantity_rejected, condition_notes)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($validatedLines as $line) {
                $insItem->execute([
                    $deliveryId,
                    $line['purchase_order_item_id'],
                    $line['quantity_received'],
                    $line['quantity_rejected'],
                    $line['condition_notes'],
                ]);
            }

            // Recompute cumulative received quantities across all non-rejected deliveries for this PO
            $allFulfilled = true;
            foreach ($poModel->items($poId) as $updatedItem) {
                if ((float)$updatedItem['quantity_received_so_far'] + 0.0001 < (float)$updatedItem['quantity_ordered']) {
                    $allFulfilled = false;
                    break;
                }
            }

            if ($anyAccepted) {
                $delStatus = $allFulfilled ? 'complete' : 'partial';
                $this->db->prepare('UPDATE deliveries SET delivery_status = ? WHERE id = ?')
                    ->execute([$delStatus, $deliveryId]);

                $newPoStatus = $allFulfilled ? 'received' : 'partially_received';
                $this->db->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?')
                    ->execute([$newPoStatus, $poId]);
            }

            $this->db->commit();
            \audit_log('delivery_recorded', 'deliveries', $deliveryId, null, [
                'delivery_no'       => $deliveryNo,
                'purchase_order_id' => $poId,
                'all_fulfilled'     => $allFulfilled,
            ]);
            return $deliveryId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
