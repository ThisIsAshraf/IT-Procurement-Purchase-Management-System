<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\CatalogController;

return static function (Router $r): void {
    $r->get('/catalog', [CatalogController::class, 'index']);
    $r->post('/catalog/items', [CatalogController::class, 'storeItem']);
    $r->post('/catalog/items/{id}/update', [CatalogController::class, 'updateItem']);
    $r->post('/catalog/categories', [CatalogController::class, 'storeCategory']);
    $r->post('/catalog/units', [CatalogController::class, 'storeUnit']);
    $r->post('/catalog/exchange-rates', [CatalogController::class, 'storeExchangeRate']);
    $r->post('/catalog/payment-terms', [CatalogController::class, 'storePaymentTerm']);
};
