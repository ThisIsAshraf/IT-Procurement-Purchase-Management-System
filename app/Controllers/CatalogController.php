<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;

final class CatalogController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('catalog.view');

        $catalog = new CatalogModel();
        $org = new OrganizationModel();

        $this->view('catalog/index', [
            'items'         => $catalog->items(),
            'categories'    => $catalog->categories(),
            'units'         => $catalog->units(),
            'exchangeRates' => $catalog->exchangeRates(),
            'paymentTerms'  => $catalog->paymentTerms(),
            'currencies'    => $org->activeCurrencies(),
            'flash'         => flash('success'),
            'error'         => flash('error'),
        ]);
    }

    public function storeItem(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $categoryId = (int)$r->input('category_id', 0);
        $unitId = (int)$r->input('unit_id', 0);
        $name = trim((string)$r->input('item_name', ''));
        $sku = strtoupper(trim((string)$r->input('sku', '')));

        if ($categoryId <= 0 || $unitId <= 0 || $name === '') {
            flash('error', 'Category, unit of measure, and item name are required.');
            redirect('/catalog');
        }

        if ($sku === '') {
            $sku = \App\Services\CodeGeneratorService::nextSku($categoryId);
        }

        $data = [
            'category_id' => $categoryId,
            'unit_id'     => $unitId,
            'sku'         => $sku,
            'item_name'   => $name,
            'description' => trim((string)$r->input('description', '')),
            'is_active'   => (int)$r->input('is_active', 1) === 1 ? 1 : 0,
        ];

        try {
            $id = (new CatalogModel())->createItem($data);
            \audit_log('catalog_item_created', 'catalog_items', $id, null, $data);
            flash('success', "Catalog item created with SKU: {$sku}");
        } catch (\Throwable $e) {
            flash('error', 'Could not create catalog item: ' . $e->getMessage());
        }
        redirect('/catalog');
    }
    public function updateItem(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $data = [
            'category_id' => (int)$r->input('category_id'),
            'unit_id'     => (int)$r->input('unit_id'),
            'sku'         => trim((string)$r->input('sku')),
            'item_name'   => trim((string)$r->input('item_name')),
            'description' => trim((string)$r->input('description')),
            'is_active'   => (int)$r->input('is_active', 1) === 1 ? 1 : 0,
        ];

        try {
            (new CatalogModel())->updateItem($id, $data);
            \audit_log('catalog_item_updated', 'catalog_items', $id, null, $data);
            flash('success', 'Catalog item updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update item: ' . $e->getMessage());
        }
        redirect('/catalog');
    }

    public function storeCategory(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $name = trim((string)$r->input('name', ''));
        $code = strtoupper(trim((string)$r->input('category_code', '')));
        $parentId = (int)$r->input('parent_id', 0);

        if ($name === '') {
            flash('error', 'Category name is required.');
            redirect('/catalog');
        }

        if ($code === '') {
            $code = \App\Services\CodeGeneratorService::nextCategoryCode($name);
        }

        try {
            (new CatalogModel())->createCategory($parentId > 0 ? $parentId : null, $code, $name);
            \audit_log('item_category_created', 'item_categories', null, null, ['code' => $code, 'name' => $name]);
            flash('success', "Category '{$name}' added with code: {$code}");
        } catch (\Throwable $e) {
            flash('error', 'Could not add category: ' . $e->getMessage());
        }
        redirect('/catalog');
    }

    public function storeUnit(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $code = trim((string)$r->input('unit_code'));
        $name = trim((string)$r->input('name'));

        $v = (new Validator())
            ->required('unit_code', $code, 'Unit code')
            ->required('name', $name, 'Unit name');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/catalog');
        }

        try {
            $id = (new CatalogModel())->createUnit($code, $name);
            \audit_log('unit_created', 'units_of_measure', $id, null, ['code' => $code, 'name' => $name]);
            flash('success', 'Unit of measure created.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create unit: ' . $e->getMessage());
        }
        redirect('/catalog');
    }

    public function storeExchangeRate(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $from = trim((string)$r->input('from_currency'));
        $to = trim((string)$r->input('to_currency'));
        $rate = (float)$r->input('rate');
        $effectiveDate = trim((string)$r->input('effective_date'));
        $source = trim((string)$r->input('source'));

        $v = (new Validator())
            ->required('from_currency', $from, 'From currency')
            ->required('to_currency', $to, 'To currency')
            ->positiveNumber('rate', $rate, 'Exchange rate')
            ->required('effective_date', $effectiveDate, 'Effective date');

        if ($v->fails() || $from === $to) {
            flash('error', $from === $to ? 'From and To currencies must differ.' : (string)$v->firstError());
            redirect('/catalog');
        }

        try {
            $id = (new CatalogModel())->createExchangeRate($from, $to, $rate, $effectiveDate, $source, (int)current_user()['id']);
            \audit_log('exchange_rate_added', 'exchange_rates', $id, null, ['from' => $from, 'to' => $to, 'rate' => $rate, 'date' => $effectiveDate]);
            flash('success', 'Historical exchange rate recorded.');
        } catch (\Throwable $e) {
            flash('error', 'Could not save exchange rate: ' . $e->getMessage());
        }
        redirect('/catalog');
    }

    public function storePaymentTerm(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('catalog.manage');

        $name = trim((string)$r->input('term_name'));
        $netDays = (int)$r->input('net_days', 0);
        $desc = trim((string)$r->input('description'));

        $v = (new Validator())
            ->required('term_name', $name, 'Payment term name')
            ->nonNegativeNumber('net_days', $netDays, 'Net days');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/catalog');
        }

        try {
            $id = (new CatalogModel())->createPaymentTerm($name, $netDays, $desc);
            \audit_log('payment_term_created', 'payment_terms', $id, null, ['term_name' => $name, 'net_days' => $netDays]);
            flash('success', 'Payment term created.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create payment term: ' . $e->getMessage());
        }
        redirect('/catalog');
    }
}
