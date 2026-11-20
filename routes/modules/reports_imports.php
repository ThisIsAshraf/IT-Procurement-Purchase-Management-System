<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\ReportController;
use App\Controllers\ImportController;
use App\Controllers\TemplateController;
use App\Controllers\SettingsController;
use App\Controllers\AuditLogController;

return static function (Router $r): void {
    // Reports & Exports
    $r->get('/reports', [ReportController::class, 'index']);
    $r->post('/reports/save', [ReportController::class, 'save']);
    $r->get('/reports/export', [ReportController::class, 'export']);

    // Multi-Entity Bulk Imports & Templates
    $r->get('/imports', [ImportController::class, 'index']);
    $r->post('/imports', [ImportController::class, 'upload']);
    $r->get('/imports/{id}/errors', [ImportController::class, 'downloadErrors']);
    $r->get('/imports/vendor-template', [TemplateController::class, 'vendor']);
    $r->get('/imports/catalog-template', [TemplateController::class, 'catalog']);
    $r->get('/imports/budget-template', [TemplateController::class, 'budget']);

    // System Settings, Email Queue & Audit Logs
    $r->get('/settings', [SettingsController::class, 'index']);
    $r->post('/settings', [SettingsController::class, 'saveSetting']);
    $r->post('/settings/templates', [SettingsController::class, 'saveTemplate']);
    $r->post('/settings/dispatch-queue', [SettingsController::class, 'dispatchQueue']);
    $r->get('/audit-logs', [AuditLogController::class, 'index']);
};
