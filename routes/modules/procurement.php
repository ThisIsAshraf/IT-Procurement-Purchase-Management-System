<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\PurchaseRequestController;
use App\Controllers\RequisitionController;
use App\Controllers\QuotationController;
use App\Controllers\PurchaseOrderController;

return static function (Router $r): void {
    // Purchase Requests
    $r->get('/purchase_requests', [PurchaseRequestController::class, 'index']);
    $r->post('/purchase_requests', [PurchaseRequestController::class, 'store']);
    $r->get('/purchase_requests/{id}', [PurchaseRequestController::class, 'show']);
    $r->post('/purchase_requests/{id}/items', [PurchaseRequestController::class, 'addItem']);
    $r->post('/purchase_requests/{id}/act', [PurchaseRequestController::class, 'act']);
    $r->post('/purchase_requests/{id}/documents', [PurchaseRequestController::class, 'uploadDocument']);
    $r->post('/purchase_requests/{requestId}/convert', [RequisitionController::class, 'convert']);

    // Requisitions
    $r->get('/requisitions', [RequisitionController::class, 'index']);
    $r->post('/requisitions', [RequisitionController::class, 'store']);
    $r->get('/requisitions/{id}', [RequisitionController::class, 'show']);
    $r->post('/requisitions/{id}/status', [RequisitionController::class, 'updateStatus']);

    // Quotations & Vendor Comparison
    $r->get('/quotations', [QuotationController::class, 'index']);
    $r->post('/quotations', [QuotationController::class, 'store']);
    $r->get('/quotations/compare/{requisitionId}', [QuotationController::class, 'compare']);
    $r->post('/quotations/compare/{requisitionId}', [QuotationController::class, 'saveComparison']);

    // Purchase Orders
    $r->get('/purchase_orders', [PurchaseOrderController::class, 'index']);
    $r->post('/purchase_orders', [PurchaseOrderController::class, 'store']);
    $r->get('/purchase_orders/{id}', [PurchaseOrderController::class, 'show']);
    $r->post('/purchase_orders/{id}/submit', [PurchaseOrderController::class, 'submit']);
    $r->post('/purchase_orders/{id}/send', [PurchaseOrderController::class, 'send']);
    $r->post('/purchase_orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
};
