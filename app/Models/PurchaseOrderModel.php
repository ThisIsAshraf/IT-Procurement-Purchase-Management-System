<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class PurchaseOrderModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?string $status = null): array
    {
        $params = [];
        $sql = "SELECT po.*,
                       v.vendor_code, v.legal_name AS vendor_name,
                       r.requisition_no,
                       b.branch_code, b.name AS branch_name,
                       u.full_name AS created_by_name
                FROM purchase_orders po
                JOIN vendors v ON v.id = po.vendor_id
                JOIN requisitions r ON r.id = po.requisition_id
                JOIN branches b ON b.id = po.ship_to_branch_id
                JOIN users u ON u.id = po.created_by
                WHERE 1=1";

        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);

        if ($status !== null && $status !== '') {
            $sql .= ' AND po.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY po.order_date DESC, po.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT po.*,
                    v.vendor_code, v.legal_name AS vendor_name, v.tax_registration_no AS vendor_tax_id,
                    (SELECT vc.email FROM vendor_contacts vc WHERE vc.vendor_id = v.id ORDER BY vc.is_primary DESC, vc.id ASC LIMIT 1) AS vendor_email,
                    r.requisition_no, r.department_id,
                    d.name AS department_name,
                    b.branch_code, b.name AS branch_name, b.address_line1, b.city,
                    q.quotation_no,
                    u.full_name AS created_by_name
             FROM purchase_orders po
             JOIN vendors v ON v.id = po.vendor_id
             JOIN requisitions r ON r.id = po.requisition_id
             JOIN departments d ON d.id = r.department_id
             JOIN branches b ON b.id = po.ship_to_branch_id
             LEFT JOIN quotations q ON q.id = po.quotation_id
             JOIN users u ON u.id = po.created_by
             WHERE po.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $poId): array
    {
        $stmt = $this->db->prepare(
            "SELECT poi.*,
                    ci.sku,
                    uom.unit_code,
                    COALESCE((SELECT SUM(di.quantity_received)
                              FROM delivery_items di
                              JOIN deliveries del ON del.id = di.delivery_id
                              WHERE di.purchase_order_item_id = poi.id AND del.delivery_status <> 'rejected'), 0) AS quantity_received_so_far
             FROM purchase_order_items poi
             LEFT JOIN catalog_items ci ON ci.id = poi.catalog_item_id
             JOIN units_of_measure uom ON uom.id = poi.unit_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id ASC"
        );
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function readyRequisitions(): array
    {
        $params = [];
        $sql = "SELECT r.id, r.requisition_no, r.currency_code, r.branch_id,
                       b.branch_code, b.name AS branch_name, d.name AS department_name,
                       q.id AS selected_quotation_id, q.quotation_no, q.vendor_id, v.legal_name AS selected_vendor_name
                FROM requisitions r
                JOIN branches b ON b.id = r.branch_id
                JOIN departments d ON d.id = r.department_id
                LEFT JOIN quotations q ON q.requisition_id = r.id AND q.status = 'selected'
                LEFT JOIN vendors v ON v.id = q.vendor_id
                WHERE r.status IN ('approved', 'sourcing') ";
        $sql .= BranchScopeService::apply('r.branch_id', $params);
        $sql .= ' ORDER BY r.id DESC LIMIT 100';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function latestExchangeRate(string $fromCurrency, string $toCurrency = 'USD'): float
    {
        $from = strtoupper($fromCurrency);
        $to = strtoupper($toCurrency);
        if ($from === $to) {
            return 1.0;
        }
        $stmt = $this->db->prepare(
            'SELECT rate FROM exchange_rates
             WHERE from_currency = ? AND to_currency = ?
             ORDER BY effective_date DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([$from, $to]);
        $rate = $stmt->fetchColumn();
        return $rate ? (float)$rate : 1.0;
    }
}
