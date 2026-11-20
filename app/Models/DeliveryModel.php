<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class DeliveryModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?int $poId = null): array
    {
        $params = [];
        $sql = "SELECT d.*,
                       po.po_number, po.ship_to_branch_id,
                       v.legal_name AS vendor_name,
                       b.branch_code,
                       u.full_name AS received_by_name,
                       COALESCE((SELECT SUM(di.quantity_received) FROM delivery_items di WHERE di.delivery_id = d.id), 0) AS total_received,
                       COALESCE((SELECT SUM(di.quantity_rejected) FROM delivery_items di WHERE di.delivery_id = d.id), 0) AS total_rejected
                FROM deliveries d
                JOIN purchase_orders po ON po.id = d.purchase_order_id
                JOIN vendors v ON v.id = po.vendor_id
                JOIN branches b ON b.id = po.ship_to_branch_id
                JOIN users u ON u.id = d.received_by
                WHERE 1=1";

        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);

        if ($poId !== null && $poId > 0) {
            $sql .= ' AND d.purchase_order_id = ?';
            $params[] = $poId;
        }

        $sql .= ' ORDER BY d.received_at DESC, d.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function receivablePurchaseOrders(): array
    {
        $params = [];
        $sql = "SELECT po.id, po.po_number, po.order_date, po.status, v.legal_name AS vendor_name, b.branch_code
                FROM purchase_orders po
                JOIN vendors v ON v.id = po.vendor_id
                JOIN branches b ON b.id = po.ship_to_branch_id
                WHERE po.status IN ('approved', 'sent', 'partially_received') ";
        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);
        $sql .= ' ORDER BY po.id DESC LIMIT 150';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
