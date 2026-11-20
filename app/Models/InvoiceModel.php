<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class InvoiceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?string $status = null): array
    {
        $params = [];
        $sql = "SELECT inv.*,
                       v.vendor_code, v.legal_name AS vendor_name,
                       po.po_number, po.ship_to_branch_id,
                       b.branch_code,
                       COALESCE((SELECT SUM(pa.allocated_amount)
                                 FROM payment_allocations pa
                                 JOIN payments p ON p.id = pa.payment_id
                                 WHERE pa.invoice_id = inv.id AND p.status = 'completed'), 0) AS paid_amount
                FROM invoices inv
                JOIN vendors v ON v.id = inv.vendor_id
                JOIN purchase_orders po ON po.id = inv.purchase_order_id
                JOIN branches b ON b.id = po.ship_to_branch_id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);

        if ($status !== null && $status !== '') {
            $sql .= ' AND inv.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY inv.invoice_date DESC, inv.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['balance_due'] = round(max(0, (float)$r['total_amount'] - (float)$r['paid_amount']), 2);
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT inv.*,
                    v.vendor_code, v.legal_name AS vendor_name,
                    po.po_number, po.ship_to_branch_id, po.total_amount AS po_total_amount, po.status AS po_status,
                    b.branch_code, b.name AS branch_name,
                    COALESCE((SELECT SUM(pa.allocated_amount)
                              FROM payment_allocations pa
                              JOIN payments p ON p.id = pa.payment_id
                              WHERE pa.invoice_id = inv.id AND p.status = 'completed'), 0) AS paid_amount
             FROM invoices inv
             JOIN vendors v ON v.id = inv.vendor_id
             JOIN purchase_orders po ON po.id = inv.purchase_order_id
             JOIN branches b ON b.id = po.ship_to_branch_id
             WHERE inv.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $inv = $stmt->fetch() ?: null;
        if ($inv) {
            $inv['balance_due'] = round(max(0, (float)$inv['total_amount'] - (float)$inv['paid_amount']), 2);
        }
        return $inv;
    }

    public function threeWayMatchLines(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ii.*,
                    poi.quantity_ordered AS po_qty,
                    poi.unit_price AS po_unit_price,
                    poi.tax_rate AS po_tax_rate,
                    COALESCE((SELECT SUM(di.quantity_received)
                              FROM delivery_items di
                              JOIN deliveries del ON del.id = di.delivery_id
                              WHERE di.purchase_order_item_id = ii.purchase_order_item_id
                                AND del.delivery_status <> 'rejected'), 0) AS received_qty,
                    COALESCE((SELECT SUM(ii2.quantity)
                              FROM invoice_items ii2
                              JOIN invoices inv2 ON inv2.id = ii2.invoice_id
                              WHERE ii2.purchase_order_item_id = ii.purchase_order_item_id
                                AND inv2.status <> 'void'), 0) AS total_invoiced_qty
             FROM invoice_items ii
             LEFT JOIN purchase_order_items poi ON poi.id = ii.purchase_order_item_id
             WHERE ii.invoice_id = ?
             ORDER BY ii.id ASC"
        );
        $stmt->execute([$invoiceId]);
        $lines = $stmt->fetchAll();

        foreach ($lines as &$line) {
            $issues = [];
            if (!empty($line['purchase_order_item_id'])) {
                $invQty = (float)$line['quantity'];
                $recQty = (float)$line['received_qty'];
                $ordQty = (float)$line['po_qty'];
                $totInvQty = (float)$line['total_invoiced_qty'];
                $invPrice = (float)$line['unit_price'];
                $poPrice = (float)$line['po_unit_price'];

                if ($totInvQty > $recQty + 0.0001) {
                    $issues[] = "Invoiced qty ({$totInvQty}) exceeds received qty ({$recQty})";
                }
                if ($totInvQty > $ordQty + 0.0001) {
                    $issues[] = "Total invoiced qty ({$totInvQty}) exceeds PO ordered qty ({$ordQty})";
                }
                if (abs($invPrice - $poPrice) > 0.01) {
                    $issues[] = 'Unit price (' . number_format($invPrice, 2) . ') differs from PO price (' . number_format($poPrice, 2) . ')';
                }
            } else {
                $issues[] = 'Unlinked extra charge (not tied to a PO line)';
            }

            $line['match_ok'] = empty($issues);
            $line['match_issues'] = $issues;
        }
        return $lines;
    }

    public function invoiceablePurchaseOrders(): array
    {
        $params = [];
        $sql = "SELECT po.id, po.po_number, po.vendor_id, po.currency_code, po.total_amount, po.status,
                       v.legal_name AS vendor_name, b.branch_code
                FROM purchase_orders po
                JOIN vendors v ON v.id = po.vendor_id
                JOIN branches b ON b.id = po.ship_to_branch_id
                WHERE po.status IN ('approved', 'sent', 'partially_received', 'received') ";
        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);
        $sql .= ' ORDER BY po.id DESC LIMIT 150';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function unpaidInvoicesForVendor(?int $vendorId = null): array
    {
        $params = [];
        $sql = "SELECT inv.id, inv.invoice_no, inv.vendor_id, inv.currency_code, inv.due_date, inv.total_amount, inv.status,
                       v.legal_name AS vendor_name, po.po_number,
                       COALESCE((SELECT SUM(pa.allocated_amount)
                                 FROM payment_allocations pa
                                 JOIN payments p ON p.id = pa.payment_id
                                 WHERE pa.invoice_id = inv.id AND p.status = 'completed'), 0) AS paid_amount
                FROM invoices inv
                JOIN vendors v ON v.id = inv.vendor_id
                JOIN purchase_orders po ON po.id = inv.purchase_order_id
                WHERE inv.status IN ('approved', 'partially_paid', 'received', 'under_review') ";
        $sql .= BranchScopeService::apply('po.ship_to_branch_id', $params);

        if ($vendorId !== null && $vendorId > 0) {
            $sql .= ' AND inv.vendor_id = ?';
            $params[] = $vendorId;
        }

        $sql .= ' ORDER BY inv.due_date ASC, inv.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $open = [];
        foreach ($stmt->fetchAll() as $row) {
            $bal = round((float)$row['total_amount'] - (float)$row['paid_amount'], 2);
            if ($bal > 0.009) {
                $row['balance_due'] = $bal;
                $open[] = $row;
            }
        }
        return $open;
    }
}
