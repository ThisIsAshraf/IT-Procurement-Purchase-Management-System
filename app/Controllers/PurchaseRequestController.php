<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;
use App\Models\PurchaseRequestModel;
use App\Services\BranchScopeService;
use App\Services\PurchaseRequestService;
use App\Services\UploadService;

final class PurchaseRequestController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('purchase_requests.view');

        $status = trim((string)$r->input('status', ''));
        $user = current_user() ?? [];
        $org = new OrganizationModel();
        $catalog = new CatalogModel();

        $this->view('purchase_requests/index', [
            'requests'     => (new PurchaseRequestModel())->listForUser($user, $status),
            'branches'     => $org->branches(true),
            'departments'  => $org->departments(),
            'currencies'   => $org->activeCurrencies(),
            'catalogItems' => $catalog->items(),
            'units'        => $catalog->units(),
            'status'       => $status,
            'flash'        => flash('success'),
            'error'        => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('purchase_requests.create');

        $header = [
            'branch_id'              => (int)$r->input('branch_id'),
            'department_id'          => (int)$r->input('department_id'),
            'currency_code'          => trim((string)$r->input('currency_code', 'USD')),
            'required_by'            => trim((string)$r->input('required_by', '')),
            'business_justification' => trim((string)$r->input('business_justification', '')),
        ];

        $v = (new Validator())
            ->positiveNumber('branch_id', $header['branch_id'], 'Branch')
            ->positiveNumber('department_id', $header['department_id'], 'Department')
            ->required('currency_code', $header['currency_code'], 'Currency')
            ->required('business_justification', $header['business_justification'], 'Business justification');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/purchase_requests');
        }

        $descriptions = (array)$r->input('item_description', []);
        $catalogIds   = (array)$r->input('item_catalog_id', []);
        $quantities   = (array)$r->input('item_quantity', []);
        $unitIds      = (array)$r->input('item_unit_id', []);
        $unitPrices   = (array)$r->input('item_unit_price', []);

        $items = [];
        foreach ($descriptions as $idx => $desc) {
            $desc = trim((string)$desc);
            $qty = (float)($quantities[$idx] ?? 0);
            $unitId = (int)($unitIds[$idx] ?? 0);
            if ($desc === '' || $qty <= 0 || $unitId <= 0) {
                continue;
            }
            $items[] = [
                'catalog_item_id'      => (int)($catalogIds[$idx] ?? 0) ?: null,
                'description'          => $desc,
                'quantity'             => $qty,
                'unit_id'              => $unitId,
                'estimated_unit_price' => $unitPrices[$idx] ?? null,
            ];
        }

        try {
            $submitNow = (string)$r->input('submit_action', 'submit') === 'submit';
            $id = (new PurchaseRequestService())->createWithItems($header, $items, (int)current_user()['id'], $submitNow);
            flash('success', $submitNow ? 'Purchase request submitted.' : 'Draft purchase request saved.');
            redirect('/purchase_requests/' . $id);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/purchase_requests');
        }
    }

    public function show(Request $r): void
    {
        $this->auth();
        $this->require('purchase_requests.view');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new PurchaseRequestModel();
        $pr = $model->find($id);

        if (!$pr) {
            http_response_code(404);
            exit('Purchase request not found.');
        }

        BranchScopeService::assertBranchAccess((int)$pr['branch_id']);
        if (!can('purchase_requests.manage') && !can('purchase_requests.approve') && (int)$pr['requester_user_id'] !== (int)current_user()['id']) {
            http_response_code(403);
            exit('403 - You can only view your own purchase requests.');
        }

        $catalog = new CatalogModel();
        $this->view('purchase_requests/show', [
            'pr'           => $pr,
            'items'        => $model->items($id),
            'budget'       => $model->budgetSnapshot((int)$pr['branch_id'], (int)$pr['department_id'], (int)date('Y')),
            'attachments'  => UploadService::forEntity('purchase_requests', $id),
            'catalogItems' => $catalog->items(),
            'units'        => $catalog->units(),
            'flash'        => flash('success'),
            'error'        => flash('error'),
        ]);
    }

    public function addItem(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('purchase_requests.create');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new PurchaseRequestModel();
        $pr = $model->find($id);
        if (!$pr || !in_array($pr['status'], ['draft', 'submitted', 'under_review'], true)) {
            flash('error', 'Items can only be added while request is open.');
            redirect('/purchase_requests/' . $id);
        }

        $desc = trim((string)$r->input('description'));
        $qty = (float)$r->input('quantity', 0);
        $unitId = (int)$r->input('unit_id', 0);

        if ($desc === '' || $qty <= 0 || $unitId <= 0) {
            flash('error', 'Description, positive quantity, and unit are required.');
            redirect('/purchase_requests/' . $id);
        }

        (new PurchaseRequestService())->addItem($id, [
            'catalog_item_id'      => (int)$r->input('catalog_item_id') ?: null,
            'description'          => $desc,
            'quantity'             => $qty,
            'unit_id'              => $unitId,
            'estimated_unit_price' => $r->input('estimated_unit_price'),
        ]);
        \audit_log('purchase_request_item_added', 'purchase_requests', $id);
        flash('success', 'Item added to purchase request.');
        redirect('/purchase_requests/' . $id);
    }

    public function act(Request $r): void
    {
        $this->auth();
        $this->csrf();

        $id = (int)($r->params()['id'] ?? 0);
        $decision = (string)$r->input('decision', '');

        if (in_array($decision, ['approved', 'rejected', 'under_review'], true)) {
            $this->require('purchase_requests.approve');
        } else {
            $this->require('purchase_requests.create');
        }

        try {
            (new PurchaseRequestService())->transitionStatus($id, $decision);
            flash('success', 'Purchase request marked as ' . str_replace('_', ' ', $decision) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/purchase_requests/' . $id);
    }

    public function uploadDocument(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('purchase_requests.create');

        $id = (int)($r->params()['id'] ?? 0);
        try {
            UploadService::storeAndLink($r->files()['document'] ?? [], 'purchase_requests', $id, (int)current_user()['id'], 'Supporting Specification');
            flash('success', 'Attachment uploaded.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/purchase_requests/' . $id);
    }
}
