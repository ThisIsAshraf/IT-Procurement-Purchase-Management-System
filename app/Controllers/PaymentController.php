<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\InvoiceModel;
use App\Models\OrganizationModel;
use App\Models\PaymentModel;
use App\Models\QuotationModel;
use App\Services\PaymentService;

final class PaymentController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('payments.view');

        $vendorId = (int)$r->input('vendor_id', 0);
        $payModel = new PaymentModel();
        $invModel = new InvoiceModel();

        $this->view('payments/index', [
            'payments'     => $payModel->all(),
            'openInvoices' => $invModel->unpaidInvoicesForVendor($vendorId > 0 ? $vendorId : null),
            'vendors'      => (new QuotationModel())->approvedVendors(),
            'currencies'   => (new OrganizationModel())->activeCurrencies(),
            'vendorId'     => $vendorId,
            'flash'        => flash('success'),
            'error'        => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('payments.create') && !can('payments.manage')) {
            $this->require('payments.create');
        }

        $vendorId = (int)$r->input('vendor_id', 0);
        $currency = trim((string)$r->input('currency_code', 'USD'));
        $paymentDate = trim((string)$r->input('payment_date', date('Y-m-d')));
        $method = trim((string)$r->input('payment_method', 'bank_transfer'));
        $amount = (float)$r->input('amount', 0);
        $txRef = trim((string)$r->input('transaction_reference', ''));
        $notes = trim((string)$r->input('notes', ''));
        $allocations = (array)$r->input('allocations', []);

        try {
            (new PaymentService())->recordAndAllocate(
                $vendorId,
                $currency,
                $paymentDate,
                $method,
                $amount,
                $txRef,
                $notes,
                $allocations,
                (int)current_user()['id']
            );
            flash('success', 'Vendor payment recorded and allocated.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/payments?vendor_id=' . $vendorId);
    }
}
