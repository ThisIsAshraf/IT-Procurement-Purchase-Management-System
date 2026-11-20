<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HealthController;

return static function (Router $r): void {
    $r->get('/login', [AuthController::class, 'show']);
    $r->post('/login', [AuthController::class, 'login']);
    $r->post('/logout', [AuthController::class, 'logout']);
    $r->get('/', [DashboardController::class, 'index']);
    $r->get('/health', [HealthController::class, 'status']);

    $modulesDir = __DIR__ . '/modules';
    foreach (
        [
            'organization.php',
            'catalog.php',
            'vendors.php',
            'procurement.php',
            'approvals.php',
            'receiving_finance.php',
            'reports_imports.php',
        ] as $module
    ) {
        $r->loadModule($modulesDir . '/' . $module);
    }
};
