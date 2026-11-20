<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\DeliveryController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\BudgetController;

return static function (Router $r): void {
    // Deliveries & Goods Receiving
    $r->get('/deliveries', [DeliveryController::class, 'index']);
    $r->post('/deliveries', [DeliveryController::class, 'store']);

    // Invoices & 3-Way Match
    $r->get('/invoices', [InvoiceController::class, 'index']);
    $r->post('/invoices', [InvoiceController::class, 'store']);
    $r->get('/invoices/{id}', [InvoiceController::class, 'show']);
    $r->post('/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);

    // Payments & Multi-Invoice Allocations
    $r->get('/payments', [PaymentController::class, 'index']);
    $r->post('/payments', [PaymentController::class, 'store']);

    // Budgets & Recurring Purchases
    $r->get('/budgets', [BudgetController::class, 'index']);
    $r->post('/budgets', [BudgetController::class, 'store']);
    $r->post('/budgets/recurring', [BudgetController::class, 'storeRecurring']);
    $r->post('/budgets/recurring/{id}/renew', [BudgetController::class, 'triggerRenewal']);
};
