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

    // Site Settings Tabs & Actions
    $r->get('/settings', [SettingsController::class, 'index']);
    $r->post('/settings/branding', [SettingsController::class, 'saveBranding']);
    $r->post('/settings/timezone', [SettingsController::class, 'saveTimezone']);
    $r->post('/settings/currency', [SettingsController::class, 'saveCurrency']);
    $r->post('/settings/forms', [SettingsController::class, 'saveForms']);
    $r->post('/settings/templates', [SettingsController::class, 'saveTemplate']);
    $r->post('/settings/smtp', [SettingsController::class, 'saveSmtp']);
    $r->post('/settings/smtp-test', [SettingsController::class, 'sendTestEmail']);
    $r->post('/settings/sso', [SettingsController::class, 'saveSso']);
    $r->post('/settings/api-keys', [SettingsController::class, 'createApiKey']);
    $r->post('/settings/api-keys/{id}/revoke', [SettingsController::class, 'revokeApiKey']);
    $r->post('/settings/backup/create', [SettingsController::class, 'createBackup']);
    $r->post('/settings/backup/policy', [SettingsController::class, 'saveBackupPolicy']);

    // Backup Download, Restore & Delete
    $r->get('/settings/backup/download', [SettingsController::class, 'downloadBackup']);
    $r->post('/settings/backup/restore', [SettingsController::class, 'restoreBackup']);
    $r->post('/settings/backup/delete', [SettingsController::class, 'deleteBackup']);

    // Google & Microsoft OAuth SSO
    $r->get('/oauth/{provider}', [\App\Controllers\OAuthController::class, 'redirectProvider']);
    $r->get('/oauth/callback/{provider}', [\App\Controllers\OAuthController::class, 'callback']);

    // REST API v1
    $r->get('/api/v1/health', [\App\Controllers\ApiController::class, 'health']);
    $r->get('/api/v1/purchase-orders', [\App\Controllers\ApiController::class, 'purchaseOrders']);
    $r->get('/api/v1/vendors', [\App\Controllers\ApiController::class, 'vendors']);
    $r->get('/api/v1/reports/summary', [\App\Controllers\ApiController::class, 'summary']);

    // Audit Trail
    $r->get('/audit-logs', [AuditLogController::class, 'index']);
};
