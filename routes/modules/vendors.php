<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\VendorController;

return static function (Router $r): void {
    $r->get('/vendors', [VendorController::class, 'index']);
    $r->post('/vendors', [VendorController::class, 'store']);
    $r->get('/vendors/{id}', [VendorController::class, 'show']);
    $r->post('/vendors/{id}/update', [VendorController::class, 'update']);
    $r->post('/vendors/{id}/contacts', [VendorController::class, 'addContact']);
    $r->post('/vendors/{id}/contacts/{contactId}/delete', [VendorController::class, 'deleteContact']);
    $r->post('/vendors/{id}/addresses', [VendorController::class, 'addAddress']);
    $r->post('/vendors/{id}/addresses/{addressId}/delete', [VendorController::class, 'deleteAddress']);
    $r->post('/vendors/{id}/documents', [VendorController::class, 'uploadDocument']);
    $r->get('/vendors/{id}/documents/{attachmentId}', [VendorController::class, 'downloadDocument']);
    $r->post('/vendors/{id}/reviews', [VendorController::class, 'addReview']);
};
