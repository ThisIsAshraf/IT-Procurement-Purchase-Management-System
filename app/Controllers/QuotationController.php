<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;
use App\Models\QuotationModel;
use App\Models\RequisitionModel;
use App\Services\BranchScopeService;
use App\Services\QuotationService;

final class QuotationController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('quotations.view');

        $reqId = (int)$r->input('requisition_id', 0);
        $qModel = new QuotationModel();
        $reqModel = new RequisitionModel();
        $orgModel = new OrganizationModel();
        $catModel = new CatalogModel();

        $selectedReq = $reqId > 0 ? $reqModel->find($reqId) : null;
        $selectedReqItems = $reqId > 0 ? $reqModel->items($reqId) : [];

        $this->view('quotations/index', [
            'quotations'       => $qModel->all($reqId > 0 ? $reqId : null),
            'requisitions'     => $qModel->openRequisitions(),
            'selectedReq'      => $selectedReq,
            'selectedReqItems' => $selectedReqItems,
            'vendors'          => $qModel->approvedVendors(),
            'currencies'       => $orgModel->activeCurrencies(),
            'paymentTerms'     => $catModel->paymentTerms(),
            'flash'            => flash('success'),
            'error'            => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('quotations.create') && !can('quotations.manage')) {
            $this->require('quotations.create');
        }

        $reqId = (int)$r->input('requisition_id');
        $header = [
            'quotation_no'    => trim((string)$r->input('quotation_no')),
            'requisition_id'  => $reqId,
            'vendor_id'       => (int)$r->input('vendor_id'),
            'currency_code'   => trim((string)$r->input('currency_code', 'USD')),
            'payment_term_id' => (int)$r->input('payment_term_id') ?: null,
            'quote_date'      => trim((string)$r->input('quote_date', date('Y-m-d'))),
            'valid_until'     => trim((string)$r->input('valid_until', '')),
            'delivery_days'   => trim((string)$r->input('delivery_days', '')),
            'tax_amount'      => (float)$r->input('tax_amount', 0),
            'shipping_amount' => (float)$r->input('shipping_amount', 0),
            'discount_amount' => (float)$r->input('discount_amount', 0),
            'notes'           => trim((string)$r->input('notes', '')),
        ];

        $v = (new Validator())
            ->positiveNumber('requisition_id', $header['requisition_id'], 'Requisition')
            ->positiveNumber('vendor_id', $header['vendor_id'], 'Vendor')
            ->required('quotation_no', $header['quotation_no'], 'Vendor quotation number')
            ->required('quote_date', $header['quote_date'], 'Quote date');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/quotations?requisition_id=' . $reqId);
        }

        $reqItemIds   = (array)$r->input('req_item_id', []);
        $descriptions = (array)$r->input('item_description', []);
        $quantities   = (array)$r->input('item_quantity', []);
        $unitPrices   = (array)$r->input('item_unit_price', []);
        $taxRates     = (array)$r->input('item_tax_rate', []);
        $warranties   = (array)$r->input('item_warranty_months', []);

        $items = [];
        foreach ($reqItemIds as $idx => $riId) {
            $riId = (int)$riId;
            $qty = (float)($quantities[$idx] ?? 0);
            $price = $unitPrices[$idx] ?? '';
            if ($riId <= 0 || $qty <= 0 || $price === '' || (float)$price < 0) {
                continue;
            }
            $items[] = [
                'requisition_item_id' => $riId,
                'description'         => trim((string)($descriptions[$idx] ?? '')),
                'quantity'            => $qty,
                'unit_price'          => (float)$price,
                'tax_rate'            => (float)($taxRates[$idx] ?? 0),
                'warranty_months'     => $warranties[$idx] ?? null,
            ];
        }

        try {
            $file = $r->files()['quote_file'] ?? [];
            (new QuotationService())->createWithItems($header, $items, (int)current_user()['id'], $file);
            flash('success', 'Vendor quotation recorded.');
        } catch (\Throwable $e) {
            flash('error', 'Could not save quotation: ' . $e->getMessage());
        }
        redirect('/quotations?requisition_id=' . $reqId);
    }

    public function compare(Request $r): void
    {
        $this->auth();
        $this->require('quotations.compare');

        $reqId = (int)($r->params()['requisitionId'] ?? 0);
        $reqModel = new RequisitionModel();
        $req = $reqModel->find($reqId);

        if (!$req) {
            http_response_code(404);
            exit('Requisition not found.');
        }
        BranchScopeService::assertBranchAccess((int)$req['branch_id']);

        $matrix = (new QuotationModel())->comparisonMatrix($reqId);

        $this->view('quotations/compare', [
            'req'          => $req,
            'reqItems'     => $reqModel->items($reqId),
            'quotations'   => $matrix['quotations'],
            'itemsByQuote' => $matrix['items_by_quote'],
            'comparison'   => $matrix['comparison'],
            'scores'       => $matrix['scores'],
            'flash'        => flash('success'),
            'error'        => flash('error'),
        ]);
    }

    public function saveComparison(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('quotations.compare');

        $reqId = (int)($r->params()['requisitionId'] ?? 0);
        $title = trim((string)$r->input('comparison_title', ''));
        $recommendation = trim((string)$r->input('recommendation', ''));
        $selectedQuotationId = (int)$r->input('selected_quotation_id', 0);
        $finalize = (string)$r->input('action_mode', 'save') === 'select';

        $scoreInputs = (array)$r->input('scores', []);
        $noteInputs  = (array)$r->input('eval_notes', []);

        $scoresByQuote = [];
        foreach ($scoreInputs as $qid => $sc) {
            $scoresByQuote[(int)$qid] = [
                'score' => $sc,
                'notes' => $noteInputs[$qid] ?? '',
            ];
        }

        if ($finalize && $selectedQuotationId <= 0) {
            flash('error', 'Choose a winning vendor quotation before finalizing selection.');
            redirect('/quotations/compare/' . $reqId);
        }

        try {
            $result = (new QuotationService())->saveComparisonAndSelect(
                $reqId,
                $title,
                $recommendation,
                $scoresByQuote,
                $selectedQuotationId,
                (int)current_user()['id'],
                $finalize
            );

            if ($finalize && !empty($result['approval_instance_id'])) {
                flash('success', 'Quotation selection submitted for approval and approver(s) notified via email.');
            } elseif ($finalize) {
                flash('success', 'Winning quotation selected and Requisition approved for Purchase Order generation.');
            } else {
                flash('success', 'Vendor comparison scores saved.');
            }
        } catch (\Throwable $e) {
            flash('error', 'Could not save comparison: ' . $e->getMessage());
        }
        redirect('/quotations/compare/' . $reqId);
    }
}
