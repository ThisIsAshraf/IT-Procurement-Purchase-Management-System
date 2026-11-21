<?php

declare(strict_types=1);

/**
 * CLI Dummy Data Seeder for NSG Procurement (No Pre-Approved Transactions)
 *
 * Usage:
 *   php scripts/seed_dummy.php
 *   php scripts/seed_dummy.php --fresh   (resets non-user tables first, then seeds)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be executed from the command line.\n");
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/vendor/autoload.php';

if (is_file($rootDir . '/bootstrap/app.php')) {
    require_once $rootDir . '/bootstrap/app.php';
} elseif (class_exists(\Dotenv\Dotenv::class) && is_file($rootDir . '/.env')) {
    \Dotenv\Dotenv::createImmutable($rootDir)->safeLoad();
}

use App\Core\Database;

if (in_array('--fresh', $argv ?? [], true)) {
    fwrite(STDOUT, "Running reset_db.php --force first...\n");
    passthru('php ' . escapeshellarg(__DIR__ . '/reset_db.php') . ' --force', $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "Reset failed. Aborting seed.\n");
        exit(1);
    }
    fwrite(STDOUT, "\n");
}

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Resolve existing user IDs (fallback to first active user if specific IDs differ)
$adminId = (int)($pdo->query("SELECT id FROM users WHERE email = 'admin@noorstaffing.com' LIMIT 1")->fetchColumn()
    ?: $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn());

if ($adminId <= 0) {
    fwrite(STDERR, "No user account found in `users` table. Create an admin user first.\n");
    exit(1);
}

$itMgrUserId = (int)($pdo->query("SELECT id FROM users WHERE email = 'alamshihab526@gmail.com' LIMIT 1")->fetchColumn() ?: 0);

// Resolve country & unit IDs dynamically
$usId = (int)($pdo->query("SELECT id FROM countries WHERE iso2 = 'US' LIMIT 1")->fetchColumn() ?: 1);
$bdId = (int)($pdo->query("SELECT id FROM countries WHERE iso2 = 'BD' LIMIT 1")->fetchColumn() ?: 2);

$unitEaId  = (int)($pdo->query("SELECT id FROM units_of_measure WHERE unit_code = 'EA' LIMIT 1")->fetchColumn() ?: 1);
$unitBoxId = (int)($pdo->query("SELECT id FROM units_of_measure WHERE unit_code = 'BOX' LIMIT 1")->fetchColumn() ?: 1);
$unitLicId = (int)($pdo->query("SELECT id FROM units_of_measure WHERE unit_code = 'LICENSE' LIMIT 1")->fetchColumn() ?: 1);

// Resolve role IDs dynamically
$roleItMgrId   = (int)($pdo->query("SELECT id FROM roles WHERE role_key = 'it_manager' LIMIT 1")->fetchColumn() ?: 2);
$roleDeptMgrId = (int)($pdo->query("SELECT id FROM roles WHERE role_key = 'department_manager' LIMIT 1")->fetchColumn() ?: 5);

try {
    $pdo->beginTransaction();

    // 1. Branches
    $branchStmt = $pdo->prepare(
        'INSERT INTO branches (country_id, branch_code, name, city, default_currency, timezone, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)'
    );
    $branchStmt->execute([$bdId, 'DHK', 'Dhaka Operation Center', 'Dhaka', 'BDT', 'Asia/Dhaka']);
    $dhkBranchId = (int)$pdo->lastInsertId();

    $branchStmt->execute([$usId, 'NYC', 'New York Office', 'New York', 'USD', 'America/New_York']);
    $nycBranchId = (int)$pdo->lastInsertId();

    // 2. Departments
    $deptStmt = $pdo->prepare('INSERT INTO departments (name, department_code, is_active) VALUES (?, ?, 1)');
    $deptIds = [];
    foreach (
        [
            ['IT Enterprise', 'ITENTERP'],
            ['Finance', 'FINANCE'],
            ['Accounts', 'ACCOUNTS'],
            ['Sales', 'SALES'],
            ['HR', 'HR'],
        ] as [$dName, $dCode]
    ) {
        $deptStmt->execute([$dName, $dCode]);
        $deptIds[$dCode] = (int)$pdo->lastInsertId();
    }

    // 3. Employees
    $empStmt = $pdo->prepare(
        'INSERT INTO employees (employee_number, full_name, email, branch_id, department_id, job_title, employment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $empStmt->execute(['NSG-DHK-0001', 'Tanvir Ahmed', 'tanvir.ahmed@noorstaffing.com', $dhkBranchId, $deptIds['ITENTERP'], 'Systems Engineer', 'active']);
    $empStmt->execute(['NSG-NYC-0002', 'Sarah Jenkins', 'sarah.jenkins@noorstaffing.com', $nycBranchId, $deptIds['FINANCE'], 'Financial Analyst', 'active']);

    // 4. Exchange Rate
    $pdo->prepare(
        'INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_date, source, created_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute(['USD', 'BDT', 124.50, date('Y-m-d'), 'Treasury / Central Bank', $adminId]);

    // 5. Item Categories
    $catStmt = $pdo->prepare('INSERT INTO item_categories (parent_id, category_code, name, is_active) VALUES (?, ?, ?, 1)');
    $catStmt->execute([null, 'COMPUTER', 'Computer Hardware']);
    $catCompId = (int)$pdo->lastInsertId();

    $catStmt->execute([$catCompId, 'LAPTOP', 'Laptop']);
    $catLaptopId = (int)$pdo->lastInsertId();

    $catStmt->execute([$catCompId, 'MONITOR', 'Monitor']);
    $catMonId = (int)$pdo->lastInsertId();

    $catStmt->execute([$catCompId, 'KEYBOARD', 'Keyboard & Mouse Combo']);
    $catKbId = (int)$pdo->lastInsertId();

    $catStmt->execute([$catCompId, 'HEADPHON', 'Headphone / Headset']);
    $catHeadId = (int)$pdo->lastInsertId();

    // 6. Catalog Items
    $itemStmt = $pdo->prepare(
        'INSERT INTO catalog_items (category_id, unit_id, sku, item_name, description, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $itemStmt->execute([$catLaptopId, $unitEaId, 'NSG-COMPUTER-0001', 'Dell Latitude 5550 Business Laptop', 'Core Ultra 7, 16GB RAM, 512GB NVMe SSD']);
    $itemLaptopId = (int)$pdo->lastInsertId();

    $itemStmt->execute([$catMonId, $unitEaId, 'NSG-COMPUTER-0002', 'Dell UltraSharp 27" 4K USB-C Monitor', 'U2723QE IPS Hub Monitor']);
    $itemMonId = (int)$pdo->lastInsertId();

    $itemStmt->execute([$catKbId, $unitBoxId, 'NSG-COMPUTER-0003', 'Logitech MK540 Wireless Keyboard & Mouse', 'Full-size wireless combo']);
    $itemKbId = (int)$pdo->lastInsertId();

    $itemStmt->execute([$catHeadId, $unitEaId, 'NSG-COMPUTER-0004', 'Jabra Evolve2 65 UC Wireless Headset', 'Noise-cancelling stereo headset']);
    $itemHeadId = (int)$pdo->lastInsertId();

    // 7. Vendors (2 approved for quoting + 1 pending review)
    $vndStmt = $pdo->prepare(
        'INSERT INTO vendors (vendor_code, legal_name, tax_registration_no, website, status, notes)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $vndStmt->execute(['AIM', 'Aim Computer and Communication System', 'BIN-004512980', 'https://aimcomputer.example.com', 'approved', 'Primary hardware & accessories supplier']);
    $vndAimId = (int)$pdo->lastInsertId();

    $vndStmt->execute(['RYAN', 'Ryans Computer Ltd.', 'BIN-001928374', 'https://ryans.example.com', 'approved', 'Corporate IT equipment distributor']);
    $vndRyanId = (int)$pdo->lastInsertId();

    $vndStmt->execute(['STARLINK', 'Starlink Enterprise Solutions', 'BIN-009988776', 'https://starlink-it.example.com', 'pending', 'New vendor awaiting compliance review']);
    $vndStarId = (int)$pdo->lastInsertId();

    $vcStmt = $pdo->prepare(
        'INSERT INTO vendor_contacts (vendor_id, contact_name, email, phone, job_title, is_primary)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $vcStmt->execute([$vndAimId, 'Tapas Kumar', 'sales@aimcomputer.example.com', '+880 1818-171717', 'Corporate Account Manager']);
    $vcStmt->execute([$vndRyanId, 'Rayhan Kabir', 'corporate@ryans.example.com', '+880 1799-589913', 'B2B Sales Lead']);
    $vcStmt->execute([$vndStarId, 'Nadia Islam', 'bids@starlink-it.example.com', '+880 1711-223344', 'Enterprise Bids Executive']);

    // 8. Budgets (Draft status — not pre-activated)
    $budStmt = $pdo->prepare(
        'INSERT INTO budgets (branch_id, department_id, fiscal_year, currency_code, budget_name, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $budStmt->execute([$dhkBranchId, $deptIds['ITENTERP'], (int)date('Y'), 'USD', 'Dhaka IT Hardware CapEx FY' . date('Y'), 'draft']);
    $budItId = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO budget_allocations (budget_id, category_id, allocated_amount) VALUES (?, ?, ?)')->execute([$budItId, $catCompId, 45000.00]);

    // 9. Approval Workflows (Active Rules for Quotation Comparison & Purchase Requests)
    $wfStmt = $pdo->prepare(
        'INSERT INTO approval_workflows (workflow_name, document_type, branch_id, department_id, min_amount, max_amount, currency_code, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $wfStmt->execute(['IT Quotation Selection Approval', 'quotation_comparison', $dhkBranchId, $deptIds['ITENTERP'], null, null, 'USD']);
    $wfQuoteId = (int)$pdo->lastInsertId();

    $stepStmt = $pdo->prepare(
        'INSERT INTO approval_steps (workflow_id, step_order, approver_role_id, approver_user_id, approval_mode, step_name)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stepStmt->execute([$wfQuoteId, 1, $roleDeptMgrId, null, 'any', 'Department Manager Review']);
    $stepStmt->execute([$wfQuoteId, 2, $roleItMgrId, $itMgrUserId > 0 ? $itMgrUserId : null, 'any', 'IT Manager Final Sign-off']);

    $wfStmt->execute(['Standard Purchase Request Approval', 'purchase_request', null, null, 0.00, null, null]);
    $wfPrId = (int)$pdo->lastInsertId();
    $stepStmt->execute([$wfPrId, 1, $roleDeptMgrId, null, 'any', 'Department Manager Sign-off']);

    // 10. Unapproved Purchase Requests (1 Submitted, 1 Draft)
    $yr = date('Y');
    $prStmt = $pdo->prepare(
        'INSERT INTO purchase_requests (request_no, requester_user_id, branch_id, department_id, currency_code, required_by, business_justification, status, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    // PR #1: Submitted (waiting for approval)
    $prStmt->execute([
        "PR-{$yr}-0001",
        $adminId,
        $dhkBranchId,
        $deptIds['ITENTERP'],
        'USD',
        date('Y-m-d', strtotime('+14 days')),
        'Q4 workstation upgrades for incoming software engineering cohort',
        'submitted',
        date('Y-m-d H:i:s'),
    ]);
    $pr1Id = (int)$pdo->lastInsertId();

    $priStmt = $pdo->prepare(
        'INSERT INTO purchase_request_items (purchase_request_id, catalog_item_id, description, quantity, unit_id, estimated_unit_price)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $priStmt->execute([$pr1Id, $itemMonId, 'Dell UltraSharp 27" 4K USB-C Monitor', 4, $unitEaId, 480.00]);
    $priStmt->execute([$pr1Id, $itemHeadId, 'Jabra Evolve2 65 UC Wireless Headset', 6, $unitEaId, 165.00]);

    // PR #2: Draft (not yet submitted)
    $prStmt->execute([
        "PR-{$yr}-0002",
        $adminId,
        $nycBranchId,
        $deptIds['FINANCE'],
        'USD',
        date('Y-m-d', strtotime('+21 days')),
        'Peripheral replacements for Finance & Audit team',
        'draft',
        null,
    ]);
    $pr2Id = (int)$pdo->lastInsertId();
    $priStmt->execute([$pr2Id, $itemKbId, 'Logitech MK540 Wireless Keyboard & Mouse', 10, $unitBoxId, 55.00]);

    // 11. Requisition in 'sourcing' status + 2 Received Vendor Quotations (ready to compare & submit for approval!)
    $pdo->prepare(
        'INSERT INTO requisitions (requisition_no, purchase_request_id, prepared_by, branch_id, department_id, currency_code, status, notes)
         VALUES (?, NULL, ?, ?, ?, ?, ?, ?)'
    )->execute([
        "REQ-{$yr}-0001",
        $adminId,
        $dhkBranchId,
        $deptIds['ITENTERP'],
        'USD',
        'sourcing',
        'Sourcing 5 business laptops for Dhaka IT Enterprise team',
    ]);
    $req1Id = (int)$pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO requisition_items (requisition_id, source_request_item_id, catalog_item_id, description, quantity, unit_id, target_unit_price)
         VALUES (?, NULL, ?, ?, ?, ?, ?)'
    )->execute([$req1Id, $itemLaptopId, 'Dell Latitude 5550 Business Laptop', 5, $unitEaId, 1050.00]);
    $reqItem1Id = (int)$pdo->lastInsertId();

    $qStmt = $pdo->prepare(
        'INSERT INTO quotations (quotation_no, requisition_id, vendor_id, currency_code, payment_term_id, quote_date, valid_until, delivery_days, tax_amount, shipping_amount, discount_amount, status, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $qiStmt = $pdo->prepare(
        'INSERT INTO quotation_items (quotation_id, requisition_item_id, description, quantity, unit_price, tax_rate, warranty_months)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    // Quote 1: Aim Computer ($1,035 x 5 = $5,175, 7 days delivery)
    $qStmt->execute([
        "QT-AIM-{$yr}-101",
        $req1Id,
        $vndAimId,
        'USD',
        3, // Net 30
        date('Y-m-d'),
        date('Y-m-d', strtotime('+15 days')),
        7,
        0.00,
        0.00,
        0.00,
        'received',
        'Includes 3-year Dell ProSupport onsite warranty',
    ]);
    $q1Id = (int)$pdo->lastInsertId();
    $qiStmt->execute([$q1Id, $reqItem1Id, 'Dell Latitude 5550 Business Laptop', 5, 1035.00, 0.00, 36]);

    // Quote 2: Ryans Computer ($1,120 x 5 = $5,600, 10 days delivery)
    $qStmt->execute([
        "QT-RYAN-{$yr}-202",
        $req1Id,
        $vndRyanId,
        'USD',
        2, // Net 15
        date('Y-m-d'),
        date('Y-m-d', strtotime('+15 days')),
        10,
        0.00,
        50.00,
        0.00,
        'received',
        'Standard 2-year manufacturer warranty',
    ]);
    $q2Id = (int)$pdo->lastInsertId();
    $qiStmt->execute([$q2Id, $reqItem1Id, 'Dell Latitude 5550 Business Laptop', 5, 1120.00, 0.00, 24]);

    $pdo->commit();

    fwrite(STDOUT, "Dummy data seeded successfully (0 pre-approved transactions):\n");
    fwrite(STDOUT, "  - Branches:          Dhaka Operation Center (DHK), New York Office (NYC)\n");
    fwrite(STDOUT, "  - Departments:       IT Enterprise, Finance, Accounts, Sales, HR\n");
    fwrite(STDOUT, "  - Catalog Items:     4 active hardware items\n");
    fwrite(STDOUT, "  - Vendors:           Aim Computer (approved), Ryans Computer (approved), Starlink (pending)\n");
    fwrite(STDOUT, "  - Approval Rules:    IT Quotation Selection Approval (2 steps), Standard PR Approval (1 step)\n");
    fwrite(STDOUT, "  - Purchase Requests: PR-{$yr}-0001 (submitted), PR-{$yr}-0002 (draft)\n");
    fwrite(STDOUT, "  - Requisition:       REQ-{$yr}-0001 (sourcing) with 2 received quotes (QT-AIM-{$yr}-101 & QT-RYAN-{$yr}-202)\n");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "[ERROR] Seeding failed: " . $e->getMessage() . "\n");
    exit(1);
}
