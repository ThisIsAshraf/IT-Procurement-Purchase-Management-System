<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\PurchaseOrderModel;
use PDO;

final class InvoiceService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createFromPo(
        int $poId,
        string $invoiceNo,
        string $invoiceDate,
        ?string $dueDate,
        float $taxTotal,
        array $items,
        int $userId,
        array $uploadedFile = []
    ): int {
        $po = (new PurchaseOrderModel())->find($poId);
        if (!$po) {
            throw new \RuntimeException('Purchase Order not found.');
        }

        BranchScopeService::assertBranchAccess((int)$po['ship_to_branch_id']);

        if (empty($items)) {
            throw new \RuntimeException('Provide at least one invoice line item.');
        }

        $subtotal = 0.0;
        foreach ($items as &$it) {
            $lineNet = round((float)$it['quantity'] * (float)$it['unit_price'], 2);
            $it['line_total'] = $lineNet;
            $subtotal += $lineNet;
        }
        unset($it);

        $subtotal = round($subtotal, 2);
        $totalAmount = round($subtotal + $taxTotal, 2);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO invoices
                 (invoice_no, vendor_id, purchase_order_id, currency_code, invoice_date, due_date, subtotal, tax_total, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'under_review')"
            );
            $stmt->execute([
                $invoiceNo,
                (int)$po['vendor_id'],
                $poId,
                $po['currency_code'],
                $invoiceDate,
                $dueDate !== '' ? $dueDate : null,
                $subtotal,
                $taxTotal,
                $totalAmount,
            ]);
            $invoiceId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO invoice_items (invoice_id, purchase_order_item_id, description, quantity, unit_price, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $insItem->execute([
                    $invoiceId,
                    !empty($it['purchase_order_item_id']) ? (int)$it['purchase_order_item_id'] : null,
                    $it['description'],
                    (float)$it['quantity'],
                    (float)$it['unit_price'],
                    $it['line_total'],
                ]);
            }

            if (!empty($uploadedFile['tmp_name']) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                UploadService::storeAndLink($uploadedFile, 'invoices', $invoiceId, $userId, 'Vendor Invoice PDF');
            }

            $this->db->commit();
            \audit_log('invoice_created', 'invoices', $invoiceId, null, [
                'invoice_no'        => $invoiceNo,
                'purchase_order_id' => $poId,
                'total_amount'      => $totalAmount,
            ]);
            return $invoiceId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $invoiceId, string $status): void
    {
        $allowed = ['under_review', 'approved', 'disputed', 'void'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid invoice status.');
        }
        $this->db->prepare('UPDATE invoices SET status = ? WHERE id = ?')->execute([$status, $invoiceId]);
        \audit_log('invoice_' . $status, 'invoices', $invoiceId);
    }
}
