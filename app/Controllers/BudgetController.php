<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\BudgetModel;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;
use App\Models\QuotationModel;
use App\Services\BudgetService;

final class BudgetController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('budgets.view');

        $year = (int)$r->input('fiscal_year', (int)date('Y'));
        $budgetModel = new BudgetModel();
        $orgModel = new OrganizationModel();
        $catModel = new CatalogModel();

        $this->view('budgets/index', [
            'budgets'            => $budgetModel->all($year > 0 ? $year : null),
            'allocationsByBudget' => $budgetModel->allocationsByBudget(),
            'recurring'          => $budgetModel->recurringPurchases(),
            'branches'           => $orgModel->branches(true),
            'departments'        => $orgModel->departments(),
            'currencies'         => $orgModel->activeCurrencies(),
            'categories'         => $catModel->categories(),
            'vendors'            => (new QuotationModel())->approvedVendors(),
            'fiscalYear'         => $year,
            'flash'              => flash('success'),
            'error'              => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('budgets.manage');

        $header = [
            'branch_id'     => (int)$r->input('branch_id'),
            'department_id' => (int)$r->input('department_id'),
            'fiscal_year'   => (int)$r->input('fiscal_year', (int)date('Y')),
            'currency_code' => trim((string)$r->input('currency_code', 'USD')),
            'budget_name'   => trim((string)$r->input('budget_name', '')),
            'status'        => (string)$r->input('status', 'active'),
        ];

        if ($header['branch_id'] <= 0 || $header['department_id'] <= 0 || $header['budget_name'] === '') {
            flash('error', 'Branch, department, and budget name are required.');
            redirect('/budgets');
        }

        $catIds = (array)$r->input('alloc_category_id', []);
        $amounts = (array)$r->input('alloc_amount', []);
        $allocations = [];
        foreach ($amounts as $idx => $amt) {
            $allocations[] = [
                'category_id' => (int)($catIds[$idx] ?? 0) ?: null,
                'amount'      => (float)$amt,
            ];
        }

        try {
            (new BudgetService())->createBudgetWithAllocations($header, $allocations);
            flash('success', 'Department budget and category allocations saved.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create budget: ' . $e->getMessage());
        }
        redirect('/budgets');
    }

    public function storeRecurring(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('budgets.manage');

        $data = [
            'title'                   => trim((string)$r->input('title', '')),
            'vendor_id'               => (int)$r->input('vendor_id', 0),
            'branch_id'               => (int)$r->input('branch_id', 0),
            'department_id'           => (int)$r->input('department_id', 0),
            'category_id'             => (int)$r->input('category_id', 0) ?: null,
            'currency_code'           => trim((string)$r->input('currency_code', 'USD')),
            'amount'                  => (float)$r->input('amount', 0),
            'frequency'               => (string)$r->input('frequency', 'annual'),
            'start_date'              => trim((string)$r->input('start_date', date('Y-m-d'))),
            'next_renewal_date'       => trim((string)$r->input('next_renewal_date', '')),
            'auto_create_requisition' => (int)$r->input('auto_create_requisition', 0),
            'notes'                   => trim((string)$r->input('notes', '')),
        ];

        if ($data['title'] === '' || $data['vendor_id'] <= 0 || $data['branch_id'] <= 0 || $data['next_renewal_date'] === '') {
            flash('error', 'Title, vendor, branch, and next renewal date are required.');
            redirect('/budgets');
        }

        try {
            (new BudgetService())->createRecurringPurchase($data, (int)current_user()['id']);
            flash('success', 'Recurring purchase / subscription renewal scheduled.');
        } catch (\Throwable $e) {
            flash('error', 'Could not schedule renewal: ' . $e->getMessage());
        }
        redirect('/budgets');
    }

    public function triggerRenewal(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('budgets.manage');

        $id = (int)($r->params()['id'] ?? 0);
        try {
            $reqId = (new BudgetService())->triggerRenewalRequisition($id, (int)current_user()['id']);
            flash('success', 'Renewal requisition created and next renewal date advanced.');
            redirect('/requisitions/' . $reqId);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/budgets');
        }
    }
}
