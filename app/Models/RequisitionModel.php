<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class RequisitionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?string $status = null): array
    {
        $params = [];
        $sql = "SELECT r.*,
                       pr.request_no,
                       u.full_name AS prepared_by_name,
                       b.branch_code, b.name AS branch_name,
                       d.department_code, d.name AS department_name,
                       (SELECT COUNT(*) FROM requisition_items ri WHERE ri.requisition_id = r.id) AS item_count,
                       (SELECT COUNT(*) FROM quotations q WHERE q.requisition_id = r.id) AS quotation_count,
                       COALESCE((SELECT SUM(ri.quantity * COALESCE(ri.target_unit_price, 0))
                                 FROM requisition_items ri WHERE ri.requisition_id = r.id), 0) AS target_total
                FROM requisitions r
                LEFT JOIN purchase_requests pr ON pr.id = r.purchase_request_id
                JOIN users u ON u.id = r.prepared_by
                JOIN branches b ON b.id = r.branch_id
                JOIN departments d ON d.id = r.department_id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('r.branch_id', $params);

        if ($status !== null && $status !== '') {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY r.created_at DESC, r.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    pr.request_no, pr.business_justification, pr.required_by,
                    u.full_name AS prepared_by_name,
                    b.branch_code, b.name AS branch_name,
                    d.department_code, d.name AS department_name
             FROM requisitions r
             LEFT JOIN purchase_requests pr ON pr.id = r.purchase_request_id
             JOIN users u ON u.id = r.prepared_by
             JOIN branches b ON b.id = r.branch_id
             JOIN departments d ON d.id = r.department_id
             WHERE r.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $requisitionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ri.*,
                    ci.sku, ci.item_name AS catalog_item_name,
                    uom.unit_code, uom.name AS unit_name,
                    (ri.quantity * COALESCE(ri.target_unit_price, 0)) AS line_target_total
             FROM requisition_items ri
             LEFT JOIN catalog_items ci ON ci.id = ri.catalog_item_id
             JOIN units_of_measure uom ON uom.id = ri.unit_id
             WHERE ri.requisition_id = ?
             ORDER BY ri.id ASC"
        );
        $stmt->execute([$requisitionId]);
        return $stmt->fetchAll();
    }

    public function quotations(int $requisitionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT q.*, v.vendor_code, v.legal_name AS vendor_name, pt.term_name AS payment_term_name,
                    COALESCE((SELECT SUM(qi.quantity * qi.unit_price) FROM quotation_items qi WHERE qi.quotation_id = q.id), 0) AS items_subtotal
             FROM quotations q
             JOIN vendors v ON v.id = q.vendor_id
             LEFT JOIN payment_terms pt ON pt.id = q.payment_term_id
             WHERE q.requisition_id = ?
             ORDER BY q.created_at DESC"
        );
        $stmt->execute([$requisitionId]);
        return $stmt->fetchAll();
    }
}
