<?php

declare(strict_types=1);

/**
 * CLI Database Reset Script for NSG Procurement
 * Clears all transactional, master, workflow, and log data while preserving:
 *  - users, roles, permissions, role_permissions, user_roles
 *  - settings, schema_migrations
 *  - countries, currencies, units_of_measure, payment_terms
 *
 * Usage:
 *   php scripts/reset_db.php --force
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be executed from the command line.\n");
}

if (!in_array('--force', $argv ?? [], true)) {
    fwrite(STDOUT, "WARNING: This will wipe all procurement, vendor, catalog, organization, approval, and log data.\n");
    fwrite(STDOUT, "Login users, roles, permissions, settings, and base lookups will be preserved.\n");
    fwrite(STDOUT, "Re-run with --force to execute:\n  php scripts/reset_db.php --force\n");
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/vendor/autoload.php';

// Load bootstrap / env if available
if (is_file($rootDir . '/bootstrap/app.php')) {
    require_once $rootDir . '/bootstrap/app.php';
} elseif (class_exists(\Dotenv\Dotenv::class) && is_file($rootDir . '/.env')) {
    \Dotenv\Dotenv::createImmutable($rootDir)->safeLoad();
}

use App\Core\Database;

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ordered child -> parent to satisfy all foreign key constraints cleanly
$tablesWithAutoIncrement = [
    // 1. Approval Workflow Engine
    'approval_actions',
    'approval_delegations',
    'approval_instances',
    'approval_steps',
    'approval_workflows',

    // 2. Procure-to-Pay (P2P) Transactions
    'payments',
    'invoice_items',
    'invoices',
    'delivery_items',
    'deliveries',
    'purchase_order_items',
    'purchase_orders',
    'quotation_comparison_items',
    'quotation_comparisons',
    'quotation_items',
    'quotations',
    'requisition_items',
    'requisitions',
    'purchase_request_items',
    'purchase_requests',
    'recurring_purchases',

    // 3. Budgets, Vendors & Catalog Master Data
    'budget_allocations',
    'budgets',
    'vendor_performance_reviews',
    'vendor_contacts',
    'vendor_addresses',
    'vendors',
    'catalog_items',
    'item_categories',
    'exchange_rates',

    // 4. Organization Structure
    'employees',
    'departments',
    'branches',

    // 5. Attachments, Imports, Reports, API Keys, Mail Queue & Audit Logs
    'attachments',
    'import_row_errors',
    'import_batches',
    'saved_reports',
    'api_keys',
    'notification_queue',
    'notification_templates',
    'audit_logs',
];

// Pivot / junction tables without AUTO_INCREMENT (must be deleted before their parent tables)
$pivotDeletes = [
    'payment_allocations',
    'user_branch_scopes',
    'attachment_links',
];

try {
    fwrite(STDOUT, "Starting database cleanup...\n");

    // 1. Delete pivot/junction rows first
    foreach ($pivotDeletes as $pivot) {
        $count = $pdo->exec("DELETE FROM `{$pivot}`");
        fwrite(STDOUT, sprintf("  [CLEARED] %-28s (%d rows removed)\n", $pivot, (int)$count));
    }

    // 2. Unlink employee_id from users before deleting employees
    $pdo->exec('UPDATE `users` SET `employee_id` = NULL');
    fwrite(STDOUT, "  [UPDATED] users.employee_id set to NULL\n");

    // 3. Delete rows in child -> parent order and reset AUTO_INCREMENT
    foreach ($tablesWithAutoIncrement as $table) {
        $count = $pdo->exec("DELETE FROM `{$table}`");
        $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        fwrite(STDOUT, sprintf("  [RESET]   %-28s (%d rows removed, AUTO_INCREMENT = 1)\n", $table, (int)$count));
    }

    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
    fwrite(STDOUT, "\nCleanup complete. Preserved {$userCount} user account(s), RBAC roles/permissions, settings, and base lookups.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "\n[ERROR] Cleanup failed: " . $e->getMessage() . "\n");
    exit(1);
}
