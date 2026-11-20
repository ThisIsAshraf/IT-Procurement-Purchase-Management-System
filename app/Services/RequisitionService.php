<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\PurchaseRequestModel;
use PDO;

final class RequisitionService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function convertFromPurchaseRequest(int $requestId, int $preparedByUserId, string $notes = ''): int
    {
        $prModel = new PurchaseRequestModel();
        $pr = $prModel->find($requestId);
        if (!$pr) {
            throw new \RuntimeException('Purchase request not found.');
        }

        BranchScopeService::assertBranchAccess((int)$pr['branch_id']);

        if (!empty($pr['requisition_id'])) {
            return (int)$pr['requisition_id'];
        }
        if ($pr['status'] !== 'approved') {
            throw new \RuntimeException('Only approved purchase requests can be converted into a requisition.');
        }

        $items = $prModel->items($requestId);
        if (empty($items)) {
            throw new \RuntimeException('Cannot convert a purchase request that has no items.');
        }

        $this->db->beginTransaction();
        try {
            $reqNo = DocumentNumberService::next('REQ', 'requisitions', 'requisition_no');
            $stmt = $this->db->prepare(
                "INSERT INTO requisitions
                 (requisition_no, purchase_request_id, prepared_by, branch_id, department_id, currency_code, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, 'sourcing', ?)"
            );
            $stmt->execute([
                $reqNo,
                $requestId,
                $preparedByUserId,
                (int)$pr['branch_id'],
                (int)$pr['department_id'],
                $pr['currency_code'],
                $notes !== '' ? $notes : ($pr['business_justification'] ?? null),
            ]);
            $requisitionId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO requisition_items
                 (requisition_id, source_request_item_id, catalog_item_id, description, quantity, unit_id, target_unit_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $insItem->execute([
                    $requisitionId,
                    (int)$it['id'],
                    $it['catalog_item_id'] ?: null,
                    $it['description'],
                    $it['quantity'],
                    (int)$it['unit_id'],
                    $it['estimated_unit_price'],
                ]);
            }

            $this->db->prepare("UPDATE purchase_requests SET status = 'converted' WHERE id = ?")
                ->execute([$requestId]);

            $this->db->commit();
            \audit_log('purchase_request_converted', 'requisitions', $requisitionId, ['purchase_request_id' => $requestId], ['requisition_no' => $reqNo]);
            return $requisitionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createStandalone(array $header, array $items, int $preparedByUserId): int
    {
        if (empty($items)) {
            throw new \RuntimeException('Add at least one line item to the requisition.');
        }

        BranchScopeService::assertBranchAccess((int)$header['branch_id']);

        $this->db->beginTransaction();
        try {
            $reqNo = DocumentNumberService::next('REQ', 'requisitions', 'requisition_no');
            $stmt = $this->db->prepare(
                "INSERT INTO requisitions
                 (requisition_no, purchase_request_id, prepared_by, branch_id, department_id, currency_code, status, notes)
                 VALUES (?, NULL, ?, ?, ?, ?, 'sourcing', ?)"
            );
            $stmt->execute([
                $reqNo,
                $preparedByUserId,
                (int)$header['branch_id'],
                (int)$header['department_id'],
                strtoupper((string)$header['currency_code']),
                $header['notes'] !== '' ? $header['notes'] : null,
            ]);
            $requisitionId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO requisition_items
                 (requisition_id, source_request_item_id, catalog_item_id, description, quantity, unit_id, target_unit_price)
                 VALUES (?, NULL, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $insItem->execute([
                    $requisitionId,
                    !empty($it['catalog_item_id']) ? (int)$it['catalog_item_id'] : null,
                    $it['description'],
                    (float)$it['quantity'],
                    (int)$it['unit_id'],
                    $it['target_unit_price'] !== '' && $it['target_unit_price'] !== null
                        ? (float)$it['target_unit_price']
                        : null,
                ]);
            }

            $this->db->commit();
            \audit_log('requisition_created', 'requisitions', $requisitionId, null, ['requisition_no' => $reqNo]);
            return $requisitionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $requisitionId, string $status): void
    {
        $allowed = ['draft', 'sourcing', 'pending_approval', 'approved', 'rejected', 'ordered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid requisition status.');
        }
        $this->db->prepare('UPDATE requisitions SET status = ? WHERE id = ?')->execute([$status, $requisitionId]);
        \audit_log('requisition_status_' . $status, 'requisitions', $requisitionId);
    }
}
