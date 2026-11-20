<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class PurchaseRequestModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function listForUser(array $user, ?string $status = null): array
    {
        $params = [];
        $sql = "SELECT pr.*,
                       u.full_name AS requester_name,
                       b.branch_code, b.name AS branch_name,
                       d.department_code, d.name AS department_name,
                       req.id AS requisition_id, req.requisition_no,
                       COALESCE((SELECT SUM(pri.quantity * COALESCE(pri.estimated_unit_price, 0))
                                 FROM purchase_request_items pri
                                 WHERE pri.purchase_request_id = pr.id), 0) AS estimated_total,
                       (SELECT COUNT(*) FROM purchase_request_items pri WHERE pri.purchase_request_id = pr.id) AS item_count
                FROM purchase_requests pr
                JOIN users u ON u.id = pr.requester_user_id
                JOIN branches b ON b.id = pr.branch_id
                JOIN departments d ON d.id = pr.department_id
                LEFT JOIN requisitions req ON req.purchase_request_id = pr.id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('pr.branch_id', $params);

        // Employees without manage/approve permission only see their own requests
        if (!can('purchase_requests.manage') && !can('purchase_requests.approve')) {
            $sql .= ' AND pr.requester_user_id = ?';
            $params[] = (int)$user['id'];
        }

        if ($status !== null && $status !== '') {
            $sql .= ' AND pr.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY pr.created_at DESC, pr.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*,
                    u.full_name AS requester_name, u.email AS requester_email,
                    b.branch_code, b.name AS branch_name,
                    d.department_code, d.name AS department_name,
                    req.id AS requisition_id, req.requisition_no
             FROM purchase_requests pr
             JOIN users u ON u.id = pr.requester_user_id
             JOIN branches b ON b.id = pr.branch_id
             JOIN departments d ON d.id = pr.department_id
             LEFT JOIN requisitions req ON req.purchase_request_id = pr.id
             WHERE pr.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $requestId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pri.*,
                    ci.sku, ci.item_name AS catalog_item_name,
                    uom.unit_code, uom.name AS unit_name,
                    (pri.quantity * COALESCE(pri.estimated_unit_price, 0)) AS line_estimated_total
             FROM purchase_request_items pri
             LEFT JOIN catalog_items ci ON ci.id = pri.catalog_item_id
             JOIN units_of_measure uom ON uom.id = pri.unit_id
             WHERE pri.purchase_request_id = ?
             ORDER BY pri.id ASC"
        );
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    public function budgetSnapshot(int $branchId, int $departmentId, int $fiscalYear): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.id, b.budget_name, b.currency_code,
                    COALESCE((SELECT SUM(ba.allocated_amount) FROM budget_allocations ba WHERE ba.budget_id = b.id), 0) AS total_allocated
             FROM budgets b
             WHERE b.branch_id = ? AND b.department_id = ? AND b.fiscal_year = ? AND b.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([$branchId, $departmentId, $fiscalYear]);
        $budget = $stmt->fetch();

        if (!$budget) {
            return ['exists' => false];
        }

        $spentStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(po.total_amount), 0)
             FROM purchase_orders po
             JOIN requisitions r ON r.id = po.requisition_id
             WHERE r.branch_id = ? AND r.department_id = ?
               AND YEAR(po.order_date) = ?
               AND po.status NOT IN ('cancelled', 'draft')"
        );
        $spentStmt->execute([$branchId, $departmentId, $fiscalYear]);
        $committed = (float)$spentStmt->fetchColumn();
        $allocated = (float)$budget['total_allocated'];

        return [
            'exists'          => true,
            'budget_name'     => $budget['budget_name'],
            'currency_code'   => $budget['currency_code'],
            'allocated'       => $allocated,
            'committed'       => $committed,
            'available'       => $allocated - $committed,
        ];
    }
}
