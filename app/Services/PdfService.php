<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrderModel;

final class PdfService
{
    public static function downloadPurchaseOrder(int $poId): never
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            http_response_code(500);
            exit('Dompdf dependency not installed.');
        }

        $model = new PurchaseOrderModel();
        $po = $model->find($poId);
        if (!$po) {
            http_response_code(404);
            exit('Purchase Order not found.');
        }
        BranchScopeService::assertBranchAccess((int)$po['ship_to_branch_id']);
        $items = $model->items($poId);

        $rowsHtml = '';
        foreach ($items as $idx => $it) {
            $rowsHtml .= '<tr>'
                . '<td>' . ($idx + 1) . '</td>'
                . '<td>' . htmlspecialchars((string)$it['description']) . '</td>'
                . '<td>' . htmlspecialchars((string)$it['quantity_ordered']) . ' ' . htmlspecialchars((string)$it['unit_code']) . '</td>'
                . '<td>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$it['unit_price'], 2) . '</td>'
                . '<td>' . htmlspecialchars((string)$it['tax_rate']) . '%</td>'
                . '<td>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$it['line_total'], 2) . '</td>'
                . '</tr>';
        }

        $html = '
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
            .hdr { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
            .hdr h1 { margin: 0; font-size: 20px; color: #0f172a; }
            .meta { width: 100%; margin-bottom: 16px; }
            .meta td { vertical-align: top; width: 50%; }
            table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table.items th, table.items td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
            table.items th { background: #f1f5f9; }
            .totals { width: 40%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
            .totals td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; }
        </style>
        <div class="hdr">
            <h1>NOOR STAFFING GROUP — PURCHASE ORDER</h1>
            <p><strong>PO Number:</strong> ' . htmlspecialchars((string)$po['po_number']) . ' &nbsp;|&nbsp;
               <strong>Date:</strong> ' . htmlspecialchars((string)$po['order_date']) . ' &nbsp;|&nbsp;
               <strong>Status:</strong> ' . htmlspecialchars(strtoupper((string)$po['status'])) . '</p>
        </div>
        <table class="meta">
            <tr>
                <td>
                    <strong>VENDOR</strong><br>'
            . htmlspecialchars((string)$po['vendor_name']) . ' (' . htmlspecialchars((string)$po['vendor_code']) . ')<br>'
            . 'Tax ID: ' . htmlspecialchars((string)($po['vendor_tax_id'] ?? 'N/A')) . '<br>'
            . 'Email: ' . htmlspecialchars((string)($po['vendor_email'] ?? 'N/A')) . '
                </td>
                <td>
                    <strong>SHIP TO BRANCH</strong><br>'
            . htmlspecialchars((string)$po['branch_name']) . ' (' . htmlspecialchars((string)$po['branch_code']) . ')<br>'
            . htmlspecialchars((string)($po['address_line1'] ?? '')) . ' ' . htmlspecialchars((string)($po['city'] ?? '')) . '<br>'
            . 'Department: ' . htmlspecialchars((string)$po['department_name']) . '<br>'
            . 'Expected Delivery: ' . htmlspecialchars((string)($po['expected_delivery_date'] ?? 'TBD')) . '
                </td>
            </tr>
        </table>
        <table class="items">
            <thead>
                <tr><th>#</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Tax</th><th>Line Total</th></tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
        </table>
        <table class="totals">
            <tr><td>Subtotal</td><td><strong>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$po['subtotal'], 2) . '</strong></td></tr>
            <tr><td>Tax</td><td>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$po['tax_total'], 2) . '</td></tr>
            <tr><td>Shipping</td><td>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$po['shipping_total'], 2) . '</td></tr>
            <tr><td>Discount</td><td>-' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$po['discount_total'], 2) . '</td></tr>
            <tr><td><strong>Grand Total</strong></td><td><strong>' . htmlspecialchars((string)$po['currency_code']) . ' ' . number_format((float)$po['total_amount'], 2) . '</strong></td></tr>
        </table>
        <p style="margin-top:24px;"><strong>Terms &amp; Instructions:</strong><br>' . nl2br(htmlspecialchars((string)($po['terms'] ?? 'Standard Noor Staffing Group procurement terms apply.'))) . '</p>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$po['po_number']) . '.pdf"');
        echo $dompdf->output();
        exit;
    }
}
