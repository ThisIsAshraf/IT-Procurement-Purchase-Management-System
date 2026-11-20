<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function kpiSummary(): array
    {
        return [
            'requests' => (int)$this->db->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn(),
            'orders'   => (int)$this->db->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn(),
            'vendors'  => (int)$this->db->query('SELECT COUNT(*) FROM vendors')->fetchColumn(),
            'spend'    => $this->db->query(
                "SELECT currency_code, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(base_total_amount), 0) AS base_total
                 FROM purchase_orders
                 WHERE status <> 'cancelled'
                 GROUP BY currency_code
                 ORDER BY base_total DESC"
            )->fetchAll(),
            'statuses' => $this->db->query(
                'SELECT status, COUNT(*) AS total FROM purchase_orders GROUP BY status ORDER BY total DESC'
            )->fetchAll(),
        ];
    }

    public function runReport(string $reportType, array $filters = []): array
    {
        $params = [];
        $branchId = !empty($filters['branch_id']) ? (int)$filters['branch_id'] : null;
        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        $dateTo   = trim((string)($filters['date_to'] ?? ''));

        if ($reportType === 'po_register') {
            $sql = "SELECT po.po_number, po.order_date, b.branch_code, d.name AS department_name,
                           v.legal_name AS vendor_name, po.currency_code, po.total_amount, po.base_total_amount, po.status
                    FROM purchase_orders po
                    JOIN branches b ON b.id = po.ship_to_branch_id
                    JOIN requisitions r ON r.id = po.requisition_id
                    JOIN departments d ON d.id = r.department_id
                    JOIN vendors v ON v.id = po.vendor_id
                    WHERE 1=1";
            $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);
            if ($branchId) {
                $sql .= ' AND po.ship_to_branch_id = ?';
                $params[] = $branchId;
            }
            if ($dateFrom !== '') {
                $sql .= ' AND po.order_date >= ?';
                $params[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $sql .= ' AND po.order_date <= ?';
                $params[] = $dateTo;
            }
            $sql .= ' ORDER BY po.order_date DESC, po.id DESC LIMIT 500';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($reportType === 'invoice_aging') {
            $sql = "SELECT inv.invoice_no, v.legal_name AS vendor_name, po.po_number, b.branch_code,
                           inv.invoice_date, inv.due_date, inv.currency_code, inv.total_amount,
                           COALESCE((SELECT SUM(pa.allocated_amount) FROM payment_allocations pa JOIN payments p ON p.id = pa.payment_id WHERE pa.invoice_id = inv.id AND p.status = 'completed'), 0) AS paid_amount,
                           inv.status
                    FROM invoices inv
                    JOIN vendors v ON v.id = inv.vendor_id
                    JOIN purchase_orders po ON po.id = inv.purchase_order_id
                    JOIN branches b ON b.id = po.ship_to_branch_id
                    WHERE inv.status <> 'void'";
            $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);
            if ($branchId) {
                $sql .= ' AND po.ship_to_branch_id = ?';
                $params[] = $branchId;
            }
            $sql .= ' ORDER BY inv.due_date ASC, inv.id DESC LIMIT 500';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($reportType === 'budget_vs_actual') {
            $year = !empty($filters['fiscal_year']) ? (int)$filters['fiscal_year'] : (int)date('Y');
            $sql = "SELECT bg.fiscal_year, br.branch_code, d.name AS department_name, bg.budget_name, bg.currency_code,
                           COALESCE((SELECT SUM(ba.allocated_amount) FROM budget_allocations ba WHERE ba.budget_id = bg.id), 0) AS allocated_amount,
                           COALESCE((SELECT SUM(po.total_amount) FROM purchase_orders po JOIN requisitions r ON r.id = po.requisition_id
                                     WHERE r.branch_id = bg.branch_id AND r.department_id = bg.department_id
                                       AND YEAR(po.order_date) = bg.fiscal_year AND po.status NOT IN ('cancelled','draft')), 0) AS committed_amount
                    FROM budgets bg
                    JOIN branches br ON br.id = bg.branch_id
                    JOIN departments d ON d.id = bg.department_id
                    WHERE bg.fiscal_year = ?";
            $params[] = $year;
            $sql .= BranchScopeService::apply('bg.branch_id', $params);
            if ($branchId) {
                $sql .= ' AND bg.branch_id = ?';
                $params[] = $branchId;
            }
            $sql .= ' ORDER BY br.branch_code ASC, d.name ASC';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($reportType === 'vendor_performance') {
            return $this->db->query(
                "SELECT v.vendor_code, v.legal_name, v.status,
                        COUNT(DISTINCT po.id) AS total_orders,
                        COALESCE(SUM(po.base_total_amount), 0) AS total_usd_spend,
                        COUNT(DISTINCT vpr.id) AS review_count,
                        ROUND(AVG((vpr.delivery_score + vpr.quality_score + vpr.pricing_score + vpr.communication_score) / 4), 2) AS avg_score
                 FROM vendors v
                 LEFT JOIN purchase_orders po ON po.vendor_id = v.id AND po.status <> 'cancelled'
                 LEFT JOIN vendor_performance_reviews vpr ON vpr.vendor_id = v.id
                 GROUP BY v.id
                 ORDER BY total_usd_spend DESC"
            )->fetchAll();
        }

        // Default: spend_summary by Branch, Department & Currency
        $sql = "SELECT b.branch_code, b.name AS branch_name, d.name AS department_name,
                       po.currency_code, COUNT(po.id) AS po_count,
                       COALESCE(SUM(po.total_amount), 0) AS local_total,
                       COALESCE(SUM(po.base_total_amount), 0) AS usd_total
                FROM purchase_orders po
                JOIN branches b ON b.id = po.ship_to_branch_id
                JOIN requisitions r ON r.id = po.requisition_id
                JOIN departments d ON d.id = r.department_id
                WHERE po.status <> 'cancelled'";
        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);
        if ($branchId) {
            $sql .= ' AND po.ship_to_branch_id = ?';
            $params[] = $branchId;
        }
        if ($dateFrom !== '') {
            $sql .= ' AND po.order_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= ' AND po.order_date <= ?';
            $params[] = $dateTo;
        }
        $sql .= ' GROUP BY b.id, d.id, po.currency_code ORDER BY usd_total DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function savedReports(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sr.*, u.full_name AS author_name
             FROM saved_reports sr
             JOIN users u ON u.id = sr.created_by
             WHERE sr.created_by = ? OR sr.is_shared = 1
             ORDER BY sr.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function saveReport(string $name, string $type, array $filters, bool $isShared, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO saved_reports (report_name, report_type, filters_json, is_shared, created_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $type,
            json_encode($filters, JSON_UNESCAPED_UNICODE),
            $isShared ? 1 : 0,
            $userId,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
