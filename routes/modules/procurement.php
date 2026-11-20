<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\PurchaseRequestController;
use App\Controllers\RequisitionController;
use App\Controllers\QuotationController;
use App\Controllers\PurchaseOrderController;

return static function (Router $r): void {
    $r->get('/purchase_requests', [PurchaseRequestController::class, 'index']);
    $r->post('/purchase_requests', [PurchaseRequestController::class, 'store']);

    $r->get('/requisitions', [RequisitionController::class, 'index']);
    $r->post('/requisitions', [RequisitionController::class, 'store']);

    $r->get('/quotations', [QuotationController::class, 'index']);
    $r->post('/quotations', [QuotationController::class, 'store']);

    $r->get('/purchase_orders', [PurchaseOrderController::class, 'index']);
    $r->post('/purchase_orders', [PurchaseOrderController::class, 'store']);
};
