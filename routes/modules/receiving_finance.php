<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\DeliveryController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\BudgetController;

return static function (Router $r): void {
    $r->get('/deliveries', [DeliveryController::class, 'index']);
    $r->post('/deliveries', [DeliveryController::class, 'store']);

    $r->get('/invoices', [InvoiceController::class, 'index']);
    $r->post('/invoices', [InvoiceController::class, 'store']);

    $r->get('/payments', [PaymentController::class, 'index']);
    $r->post('/payments', [PaymentController::class, 'store']);

    $r->get('/budgets', [BudgetController::class, 'index']);
    $r->post('/budgets', [BudgetController::class, 'store']);
};
