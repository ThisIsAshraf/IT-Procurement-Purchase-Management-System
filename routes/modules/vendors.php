<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\VendorController;

return static function (Router $r): void {
    $r->get('/vendors', [VendorController::class, 'index']);
    $r->post('/vendors', [VendorController::class, 'store']);
};
