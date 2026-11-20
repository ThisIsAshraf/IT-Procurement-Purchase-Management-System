<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\PurchaseOrderModel;
use App\Models\RequisitionModel;
use PDO;

final class PurchaseOrderService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createFromRequisition(
        int $requisitionId,
        ?int $quotationId,
        int $vendorId,
        int $shipToBranchId,
        string $orderDate,
        ?string $expectedDeliveryDate,
        string $terms,
        int $userId
    ): int {
        $reqModel = new RequisitionModel();
        $req = $reqModel->find($requisitionId);
        if (!$req) {
            throw new \RuntimeException('Requisition not found.');
        }

        BranchScopeService::assertBranchAccess($shipToBranchId);

        $poModel = new PurchaseOrderModel();
        $currency = (string)$req['currency_code'];
        $shippingTotal = 0.0;
        $discountTotal = 0.0;
        $headerTax = 0.0;
        $lineItems = [];

        if ($quotationId && $quotationId > 0) {
            $qStmt = $this->db->prepare('SELECT * FROM quotations WHERE id = ? AND requisition_id = ? LIMIT 1');
            $qStmt->execute([$quotationId, $requisitionId]);
            $quote = $qStmt->fetch();
            if (!$quote) {
                throw new \RuntimeException('Selected quotation does not belong to this requisition.');
            }
            $vendorId = (int)$quote['vendor_id'];
            $currency = (string)$quote['currency_code'];
            $shippingTotal = (float)$quote['shipping_amount'];
            $discountTotal = (float)$quote['discount_amount'];
            $headerTax = (float)$quote['tax_amount'];

            $qiStmt = $this->db->prepare(
                'SELECT qi.*, ri.catalog_item_id, ri.unit_id
                 FROM quotation_items qi
                 JOIN requisition_items ri ON ri.id = qi.requisition_item_id
                 WHERE qi.quotation_id = ?
                 ORDER BY qi.id ASC'
            );
            $qiStmt->execute([$quotationId]);
            foreach ($qiStmt->fetchAll() as $qi) {
                $lineItems[] = [
                    'requisition_item_id' => (int)$qi['requisition_item_id'],
                    'catalog_item_id'     => $qi['catalog_item_id'] ?: null,
                    'description'         => $qi['description'],
                    'quantity_ordered'    => (float)$qi['quantity'],
                    'unit_id'             => (int)$qi['unit_id'],
                    'unit_price'          => (float)$qi['unit_price'],
                    'tax_rate'            => (float)$qi['tax_rate'],
                ];
            }
        } else {
            foreach ($reqModel->items($requisitionId) as $ri) {
                $lineItems[] = [
                    'requisition_item_id' => (int)$ri['id'],
                    'catalog_item_id'     => $ri['catalog_item_id'] ?: null,
                    'description'         => $ri['description'],
                    'quantity_ordered'    => (float)$ri['quantity'],
                    'unit_id'             => (int)$ri['unit_id'],
                    'unit_price'          => (float)($ri['target_unit_price'] ?? 0),
                    'tax_rate'            => 0.0,
                ];
            }
        }

        if (empty($lineItems)) {
            throw new \RuntimeException('Cannot create a Purchase Order with zero line items.');
        }

        $subtotal = 0.0;
        $lineTaxTotal = 0.0;
        foreach ($lineItems as &$li) {
            $net = $li['quantity_ordered'] * $li['unit_price'];
            $tax = $net * ($li['tax_rate'] / 100);
            $li['line_total'] = round($net + $tax, 2);
            $subtotal += $net;
            $lineTaxTotal += $tax;
        }
        unset($li);

        $subtotal = round($subtotal, 2);
        $taxTotal = round($lineTaxTotal + $headerTax, 2);
        $totalAmount = round(max(0, $subtotal + $taxTotal + $shippingTotal - $discountTotal), 2);
        $baseCurrency = 'USD';
        $exchangeRate = $poModel->latestExchangeRate($currency, $baseCurrency);
        $baseTotalAmount = round($totalAmount * $exchangeRate, 2);

        $this->db->beginTransaction();
        try {
            $poNumber = DocumentNumberService::next('PO', 'purchase_orders', 'po_number');
            $stmt = $this->db->prepare(
                "INSERT INTO purchase_orders
                 (po_number, requisition_id, quotation_id, vendor_id, ship_to_branch_id, currency_code, exchange_rate,
                  base_currency_code, order_date, expected_delivery_date, status, subtotal, tax_total, shipping_total,
                  discount_total, total_amount, base_total_amount, terms, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $poNumber,
                $requisitionId,
                $quotationId ?: null,
                $vendorId,
                $shipToBranchId,
                $currency,
                $exchangeRate,
                $baseCurrency,
                $orderDate,
                $expectedDeliveryDate !== '' ? $expectedDeliveryDate : null,
                $subtotal,
                $taxTotal,
                $shippingTotal,
                $discountTotal,
                $totalAmount,
                $baseTotalAmount,
                $terms !== '' ? $terms : null,
                $userId,
            ]);
            $poId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO purchase_order_items
                 (purchase_order_id, requisition_item_id, catalog_item_id, description, quantity_ordered, unit_id, unit_price, tax_rate, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($lineItems as $li) {
                $insItem->execute([
                    $poId,
                    $li['requisition_item_id'],
                    $li['catalog_item_id'],
                    $li['description'],
                    $li['quantity_ordered'],
                    $li['unit_id'],
                    $li['unit_price'],
                    $li['tax_rate'],
                    $li['line_total'],
                ]);
            }

            $this->db->prepare("UPDATE requisitions SET status = 'ordered' WHERE id = ?")->execute([$requisitionId]);

            $this->db->commit();
            \audit_log('purchase_order_created', 'purchase_orders', $poId, null, [
                'po_number'    => $poNumber,
                'total_amount' => $totalAmount,
                'currency'     => $currency,
            ]);
            return $poId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function submitForApproval(int $poId, int $userId): string
    {
        $po = (new PurchaseOrderModel())->find($poId);
        if (!$po) {
            throw new \RuntimeException('Purchase Order not found.');
        }

        $wfService = new ApprovalWorkflowService();
        $instanceId = $wfService->initiate(
            'purchase_order',
            $poId,
            $userId,
            (int)$po['ship_to_branch_id'],
            (int)$po['department_id'],
            (float)$po['total_amount'],
            (string)$po['currency_code']
        );

        if ($instanceId !== null) {
            $this->db->prepare("UPDATE purchase_orders SET status = 'pending_approval' WHERE id = ?")->execute([$poId]);
            return 'pending_approval';
        }

        // No workflow matched -> auto-approve
        $this->db->prepare("UPDATE purchase_orders SET status = 'approved' WHERE id = ?")->execute([$poId]);
        \audit_log('purchase_order_auto_approved', 'purchase_orders', $poId);
        return 'approved';
    }

    public function sendToVendor(int $poId): void
    {
        $po = (new PurchaseOrderModel())->find($poId);
        if (!$po || $po['status'] !== 'approved') {
            throw new \RuntimeException('Only approved Purchase Orders can be dispatched to vendors.');
        }

        $this->db->prepare("UPDATE purchase_orders SET status = 'sent' WHERE id = ?")->execute([$poId]);

        if (!empty($po['vendor_email'])) {
            $subject = "Purchase Order {$po['po_number']} — Noor Staffing Group";
            $body = "<p>Dear {$po['vendor_name']},</p>"
                . "<p>Please find our official Purchase Order <strong>{$po['po_number']}</strong> "
                . "dated {$po['order_date']} for a total of <strong>{$po['currency_code']} "
                . number_format((float)$po['total_amount'], 2) . "</strong>.</p>"
                . "<p>Ship-to Branch: {$po['branch_name']}</p>"
                . "<p>Regards,<br>Noor Staffing Group Procurement</p>";
            MailService::queue((string)$po['vendor_email'], $subject, $body);
        }

        \audit_log('purchase_order_sent', 'purchase_orders', $poId);
    }

    public function cancel(int $poId): void
    {
        $this->db->prepare("UPDATE purchase_orders SET status = 'cancelled' WHERE id = ?")->execute([$poId]);
        \audit_log('purchase_order_cancelled', 'purchase_orders', $poId);
    }
}
