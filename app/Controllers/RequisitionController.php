<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;
use App\Models\PurchaseRequestModel;
use App\Models\RequisitionModel;
use App\Services\BranchScopeService;
use App\Services\RequisitionService;

final class RequisitionController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('requisitions.view');

        $status = trim((string)$r->input('status', ''));
        $org = new OrganizationModel();
        $catalog = new CatalogModel();

        $this->view('requisitions/index', [
            'requisitions' => (new RequisitionModel())->all($status),
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

    public function convert(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('requisitions.create') && !can('requisitions.manage')) {
            $this->require('requisitions.create');
        }

        $requestId = (int)($r->params()['requestId'] ?? 0);
        $notes = trim((string)$r->input('notes', ''));

        try {
            $reqId = (new RequisitionService())->convertFromPurchaseRequest($requestId, (int)current_user()['id'], $notes);
            flash('success', 'Purchase request converted into formal Requisition.');
            redirect('/requisitions/' . $reqId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/purchase_requests/' . $requestId);
        }
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('requisitions.create') && !can('requisitions.manage')) {
            $this->require('requisitions.create');
        }

        $header = [
            'branch_id'     => (int)$r->input('branch_id'),
            'department_id' => (int)$r->input('department_id'),
            'currency_code' => trim((string)$r->input('currency_code', 'USD')),
            'notes'         => trim((string)$r->input('notes', '')),
        ];

        $v = (new Validator())
            ->positiveNumber('branch_id', $header['branch_id'], 'Branch')
            ->positiveNumber('department_id', $header['department_id'], 'Department')
            ->required('currency_code', $header['currency_code'], 'Currency');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/requisitions');
        }

        $descriptions = (array)$r->input('item_description', []);
        $catalogIds   = (array)$r->input('item_catalog_id', []);
        $quantities   = (array)$r->input('item_quantity', []);
        $unitIds      = (array)$r->input('item_unit_id', []);
        $targetPrices = (array)$r->input('item_target_price', []);

        $items = [];
        foreach ($descriptions as $idx => $desc) {
            $desc = trim((string)$desc);
            $qty = (float)($quantities[$idx] ?? 0);
            $unitId = (int)($unitIds[$idx] ?? 0);
            if ($desc === '' || $qty <= 0 || $unitId <= 0) {
                continue;
            }
            $items[] = [
                'catalog_item_id'   => (int)($catalogIds[$idx] ?? 0) ?: null,
                'description'       => $desc,
                'quantity'          => $qty,
                'unit_id'           => $unitId,
                'target_unit_price' => $targetPrices[$idx] ?? null,
            ];
        }

        try {
            $reqId = (new RequisitionService())->createStandalone($header, $items, (int)current_user()['id']);
            flash('success', 'Standalone requisition created.');
            redirect('/requisitions/' . $reqId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/requisitions');
        }
    }

    public function show(Request $r): void
    {
        $this->auth();
        $this->require('requisitions.view');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new RequisitionModel();
        $req = $model->find($id);

        if (!$req) {
            http_response_code(404);
            exit('Requisition not found.');
        }

        BranchScopeService::assertBranchAccess((int)$req['branch_id']);
        $prModel = new PurchaseRequestModel();

        $this->view('requisitions/show', [
            'req'        => $req,
            'items'      => $model->items($id),
            'quotations' => $model->quotations($id),
            'budget'     => $prModel->budgetSnapshot((int)$req['branch_id'], (int)$req['department_id'], (int)date('Y')),
            'flash'      => flash('success'),
            'error'      => flash('error'),
        ]);
    }

    public function updateStatus(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('requisitions.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $status = (string)$r->input('status', '');

        try {
            (new RequisitionService())->updateStatus($id, $status);
            flash('success', 'Requisition status updated to ' . str_replace('_', ' ', $status) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/requisitions/' . $id);
    }
}
