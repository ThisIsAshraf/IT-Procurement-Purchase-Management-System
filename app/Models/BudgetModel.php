<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class BudgetModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?int $fiscalYear = null): array
    {
        $params = [];
        $sql = "SELECT b.*,
                       br.branch_code, br.name AS branch_name,
                       d.department_code, d.name AS department_name,
                       COALESCE((SELECT SUM(ba.allocated_amount) FROM budget_allocations ba WHERE ba.budget_id = b.id), 0) AS total_allocated,
                       COALESCE((SELECT SUM(po.total_amount)
                                 FROM purchase_orders po
                                 JOIN requisitions r ON r.id = po.requisition_id
                                 WHERE r.branch_id = b.branch_id
                                   AND r.department_id = b.department_id
                                   AND YEAR(po.order_date) = b.fiscal_year
                                   AND po.status NOT IN ('cancelled', 'draft')), 0) AS committed_spend
                FROM budgets b
                JOIN branches br ON br.id = b.branch_id
                JOIN departments d ON d.id = b.department_id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('b.branch_id', $params);

        if ($fiscalYear !== null && $fiscalYear > 0) {
            $sql .= ' AND b.fiscal_year = ?';
            $params[] = $fiscalYear;
        }

        $sql .= ' ORDER BY b.fiscal_year DESC, br.name ASC, d.name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $alloc = (float)$row['total_allocated'];
            $comm = (float)$row['committed_spend'];
            $row['remaining'] = round($alloc - $comm, 2);
            $row['utilization_pct'] = $alloc > 0 ? round(($comm / $alloc) * 100, 1) : 0.0;
        }
        return $rows;
    }

    public function allocationsByBudget(): array
    {
        $rows = $this->db->query(
            'SELECT ba.*, ic.name AS category_name, ic.category_code
             FROM budget_allocations ba
             LEFT JOIN item_categories ic ON ic.id = ba.category_id
             ORDER BY ba.budget_id ASC, ic.name ASC'
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int)$r['budget_id']][] = $r;
        }
        return $grouped;
    }

    public function recurringPurchases(): array
    {
        $params = [];
        $sql = "SELECT rp.*,
                       v.legal_name AS vendor_name, v.vendor_code,
                       br.branch_code, br.name AS branch_name,
                       d.name AS department_name,
                       ic.name AS category_name,
                       u.full_name AS owner_name
                FROM recurring_purchases rp
                JOIN vendors v ON v.id = rp.vendor_id
                JOIN branches br ON br.id = rp.branch_id
                JOIN departments d ON d.id = rp.department_id
                LEFT JOIN item_categories ic ON ic.id = rp.category_id
                JOIN users u ON u.id = rp.owner_user_id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('rp.branch_id', $params);
        $sql .= ' ORDER BY rp.next_renewal_date ASC, rp.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
