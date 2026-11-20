<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PurchaseRequestService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createWithItems(array $header, array $items, int $userId, bool $submitNow = false): int
    {
        if (empty($items)) {
            throw new \RuntimeException('Add at least one line item to the purchase request.');
        }

        BranchScopeService::assertBranchAccess((int)$header['branch_id']);

        $this->db->beginTransaction();
        try {
            $requestNo = DocumentNumberService::next('PR', 'purchase_requests', 'request_no');
            $status = $submitNow ? 'submitted' : 'draft';
            $submittedAt = $submitNow ? date('Y-m-d H:i:s') : null;

            $stmt = $this->db->prepare(
                'INSERT INTO purchase_requests
                 (request_no, requester_user_id, branch_id, department_id, currency_code, required_by, business_justification, status, submitted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $requestNo,
                $userId,
                (int)$header['branch_id'],
                (int)$header['department_id'],
                strtoupper((string)$header['currency_code']),
                $header['required_by'] !== '' ? $header['required_by'] : null,
                $header['business_justification'],
                $status,
                $submittedAt,
            ]);
            $requestId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO purchase_request_items
                 (purchase_request_id, catalog_item_id, description, quantity, unit_id, estimated_unit_price)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    $requestId,
                    !empty($item['catalog_item_id']) ? (int)$item['catalog_item_id'] : null,
                    $item['description'],
                    (float)$item['quantity'],
                    (int)$item['unit_id'],
                    $item['estimated_unit_price'] !== '' && $item['estimated_unit_price'] !== null
                        ? (float)$item['estimated_unit_price']
                        : null,
                ]);
            }

            $this->db->commit();
            \audit_log('purchase_request_created', 'purchase_requests', $requestId, null, [
                'request_no' => $requestNo,
                'status'     => $status,
                'item_count' => count($items),
            ]);
            return $requestId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addItem(int $requestId, array $item): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO purchase_request_items
             (purchase_request_id, catalog_item_id, description, quantity, unit_id, estimated_unit_price)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $requestId,
            !empty($item['catalog_item_id']) ? (int)$item['catalog_item_id'] : null,
            $item['description'],
            (float)$item['quantity'],
            (int)$item['unit_id'],
            $item['estimated_unit_price'] !== '' && $item['estimated_unit_price'] !== null
                ? (float)$item['estimated_unit_price']
                : null,
        ]);
    }

    public function transitionStatus(int $requestId, string $newStatus): void
    {
        $allowed = ['submitted', 'under_review', 'approved', 'rejected', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new \RuntimeException('Invalid status transition.');
        }

        $sql = 'UPDATE purchase_requests SET status = ?'
            . ($newStatus === 'submitted' ? ', submitted_at = COALESCE(submitted_at, NOW())' : '')
            . ' WHERE id = ?';
        $this->db->prepare($sql)->execute([$newStatus, $requestId]);
        \audit_log('purchase_request_' . $newStatus, 'purchase_requests', $requestId);
    }
}
