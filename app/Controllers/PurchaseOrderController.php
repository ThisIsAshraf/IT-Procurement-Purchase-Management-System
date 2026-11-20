<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\OrganizationModel;
use App\Models\PurchaseOrderModel;
use App\Models\QuotationModel;
use App\Services\BranchScopeService;
use App\Services\PurchaseOrderService;

final class PurchaseOrderController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('purchase_orders.view');

        $status = trim((string)$r->input('status', ''));
        $poModel = new PurchaseOrderModel();
        $qModel = new QuotationModel();
        $orgModel = new OrganizationModel();

        $this->view('purchase_orders/index', [
            'orders'            => $poModel->all($status),
            'readyRequisitions' => $poModel->readyRequisitions(),
            'vendors'           => $qModel->approvedVendors(),
            'branches'          => $orgModel->branches(true),
            'status'            => $status,
            'flash'             => flash('success'),
            'error'             => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('purchase_orders.create') && !can('purchase_orders.manage')) {
            $this->require('purchase_orders.create');
        }

        $reqId = (int)$r->input('requisition_id', 0);
        $quoteId = (int)$r->input('quotation_id', 0) ?: null;
        $vendorId = (int)$r->input('vendor_id', 0);
        $branchId = (int)$r->input('ship_to_branch_id', 0);
        $orderDate = trim((string)$r->input('order_date', date('Y-m-d')));
        $expectedDate = trim((string)$r->input('expected_delivery_date', ''));
        $terms = trim((string)$r->input('terms', ''));

        if ($reqId <= 0 || $branchId <= 0 || (!$quoteId && $vendorId <= 0)) {
            flash('error', 'Select a requisition, ship-to branch, and either a selected quotation or vendor.');
            redirect('/purchase_orders');
        }

        try {
            $poId = (new PurchaseOrderService())->createFromRequisition(
                $reqId,
                $quoteId,
                $vendorId,
                $branchId,
                $orderDate,
                $expectedDate,
                $terms,
                (int)current_user()['id']
            );
            flash('success', 'Draft Purchase Order generated.');
            redirect('/purchase_orders/' . $poId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/purchase_orders');
        }
    }

    public function show(Request $r): void
    {
        $this->auth();
        $this->require('purchase_orders.view');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new PurchaseOrderModel();
        $po = $model->find($id);

        if (!$po) {
            http_response_code(404);
            exit('Purchase Order not found.');
        }

        BranchScopeService::assertBranchAccess((int)$po['ship_to_branch_id']);

        $this->view('purchase_orders/show', [
            'po'    => $po,
            'items' => $model->items($id),
            'flash' => flash('success'),
            'error' => flash('error'),
        ]);
    }

    public function submit(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('purchase_orders.create') && !can('purchase_orders.manage')) {
            $this->require('purchase_orders.create');
        }

        $id = (int)($r->params()['id'] ?? 0);
        try {
            $resultingStatus = (new PurchaseOrderService())->submitForApproval($id, (int)current_user()['id']);
            flash('success', $resultingStatus === 'approved'
                ? 'Purchase Order approved (no threshold workflow required).'
                : 'Purchase Order submitted into approval workflow.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/purchase_orders/' . $id);
    }

    public function send(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('purchase_orders.manage') && !can('purchase_orders.approve')) {
            $this->require('purchase_orders.manage');
        }

        $id = (int)($r->params()['id'] ?? 0);
        try {
            (new PurchaseOrderService())->sendToVendor($id);
            flash('success', 'Purchase Order marked as Sent and vendor email queued.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/purchase_orders/' . $id);
    }

    public function cancel(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('purchase_orders.cancel');

        $id = (int)($r->params()['id'] ?? 0);
        (new PurchaseOrderService())->cancel($id);
        flash('success', 'Purchase Order cancelled.');
        redirect('/purchase_orders/' . $id);
    }

    public function downloadPdf(Request $r): void
    {
        $this->auth();
        $this->require('purchase_orders.view');
        $id = (int)($r->params()['id'] ?? 0);
        \App\Services\PdfService::downloadPurchaseOrder($id);
    }
}
