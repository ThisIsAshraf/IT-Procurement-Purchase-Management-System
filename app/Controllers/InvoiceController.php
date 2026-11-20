<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\InvoiceModel;
use App\Models\PurchaseOrderModel;
use App\Services\BranchScopeService;
use App\Services\InvoiceService;
use App\Services\UploadService;

final class InvoiceController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('invoices.view');

        $poId = (int)$r->input('purchase_order_id', 0);
        $status = trim((string)$r->input('status', ''));
        $invModel = new InvoiceModel();
        $poModel = new PurchaseOrderModel();

        $selectedPo = $poId > 0 ? $poModel->find($poId) : null;
        $selectedPoItems = $poId > 0 ? $poModel->items($poId) : [];

        $this->view('invoices/index', [
            'invoices'        => $invModel->all($status),
            'invoiceablePos'  => $invModel->invoiceablePurchaseOrders(),
            'selectedPo'      => $selectedPo,
            'selectedPoItems' => $selectedPoItems,
            'status'          => $status,
            'flash'           => flash('success'),
            'error'           => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('invoices.create') && !can('invoices.manage')) {
            $this->require('invoices.create');
        }

        $poId = (int)$r->input('purchase_order_id', 0);
        $invoiceNo = trim((string)$r->input('invoice_no', ''));
        $invoiceDate = trim((string)$r->input('invoice_date', date('Y-m-d')));
        $dueDate = trim((string)$r->input('due_date', ''));
        $taxTotal = (float)$r->input('tax_total', 0);

        if ($poId <= 0 || $invoiceNo === '') {
            flash('error', 'Purchase Order and Vendor Invoice Number are required.');
            redirect('/invoices?purchase_order_id=' . $poId);
        }

        $poItemIds    = (array)$r->input('po_item_id', []);
        $descriptions = (array)$r->input('item_description', []);
        $quantities   = (array)$r->input('item_quantity', []);
        $unitPrices   = (array)$r->input('item_unit_price', []);

        $items = [];
        foreach ($descriptions as $idx => $desc) {
            $desc = trim((string)$desc);
            $qty = (float)($quantities[$idx] ?? 0);
            $price = (float)($unitPrices[$idx] ?? 0);
            if ($desc === '' || $qty <= 0) {
                continue;
            }
            $items[] = [
                'purchase_order_item_id' => (int)($poItemIds[$idx] ?? 0) ?: null,
                'description'            => $desc,
                'quantity'               => $qty,
                'unit_price'             => $price,
            ];
        }

        try {
            $file = $r->files()['invoice_file'] ?? [];
            $invId = (new InvoiceService())->createFromPo(
                $poId,
                $invoiceNo,
                $invoiceDate,
                $dueDate,
                $taxTotal,
                $items,
                (int)current_user()['id'],
                $file
            );
            flash('success', 'Vendor invoice recorded. Review 3-way match results below.');
            redirect('/invoices/' . $invId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/invoices?purchase_order_id=' . $poId);
        }
    }

    public function show(Request $r): void
    {
        $this->auth();
        $this->require('invoices.view');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new InvoiceModel();
        $invoice = $model->find($id);

        if (!$invoice) {
            http_response_code(404);
            exit('Invoice not found.');
        }

        BranchScopeService::assertBranchAccess((int)$invoice['ship_to_branch_id']);

        $this->view('invoices/show', [
            'invoice'     => $invoice,
            'matchLines'  => $model->threeWayMatchLines($id),
            'attachments' => UploadService::forEntity('invoices', $id),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function updateStatus(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('invoices.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $status = (string)$r->input('status', '');

        try {
            (new InvoiceService())->updateStatus($id, $status);
            flash('success', 'Invoice status updated to ' . str_replace('_', ' ', $status) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/invoices/' . $id);
    }
}
