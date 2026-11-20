<?php

/**
 * @var string $content
 */
$content = $content ?? '';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(env('APP_NAME', 'NSG Procurement')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>

<body>
    <header class="topbar">
        <a href="<?= e(url('/')) ?>" class="brand">NSG <span>Procurement</span></a>
        <?php if (current_user()): ?>
            <nav>
                <?php
                $nav = [
                    ['/', 'Dashboard', 'dashboard.view'],
                    ['/purchase_requests', 'Requests', 'purchase_requests.view'],
                    ['/requisitions', 'Requisitions', 'requisitions.view'],
                    ['/vendors', 'Vendors', 'vendors.view'],
                    ['/quotations', 'Quotations', 'quotations.view'],
                    ['/purchase_orders', 'Purchase Orders', 'purchase_orders.view'],
                    ['/approvals', 'Approvals', 'approvals.view'],
                    ['/deliveries', 'Deliveries', 'deliveries.view'],
                    ['/invoices', 'Invoices', 'invoices.view'],
                    ['/payments', 'Payments', 'payments.view'],
                    ['/budgets', 'Budgets', 'budgets.view'],
                    ['/catalog', 'Catalog & Rates', 'catalog.view'],
                    ['/organization', 'Branches', 'organization.view'],
                    ['/departments', 'Departments', 'organization.view'],
                    ['/employees', 'Employees', 'organization.view'],
                    ['/reports', 'Reports', 'reports.view'],
                    ['/imports', 'Imports', 'imports.view'],
                    ['/users', 'Users', 'users.manage'],
                    ['/roles', 'Roles & RBAC', 'roles.manage'],
                    ['/approval-workflows', 'Workflows', 'settings.manage'],
                ];
                foreach ($nav as [$href, $label, $perm]) {
                    if (can($perm) || $href === '/') {
                        echo '<a href="' . e(url($href)) . '">' . e($label) . '</a>';
                    }
                }
                ?>
            </nav>
            <div class="user">
                <?= e(current_user()['name']) ?>
                <form method="post" action="<?= e(url('/logout')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="link-button">Sign out</button>
                </form>
            </div>
        <?php endif; ?>
    </header>
    <main class="container"><?= $content ?></main>
    <footer>NSG Procurement · Noor Staffing Group</footer>
</body>

</html>