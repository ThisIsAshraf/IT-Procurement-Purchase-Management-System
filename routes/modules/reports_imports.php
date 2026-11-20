<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\ReportController;
use App\Controllers\ImportController;
use App\Controllers\TemplateController;

return static function (Router $r): void {
    $r->get('/reports', [ReportController::class, 'index']);
    $r->get('/reports/export', [ReportController::class, 'export']);

    $r->get('/imports', [ImportController::class, 'index']);
    $r->post('/imports', [ImportController::class, 'upload']);
    $r->get('/imports/vendor-template', [TemplateController::class, 'vendor']);
};
