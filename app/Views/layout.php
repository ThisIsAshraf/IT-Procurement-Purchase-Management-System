<?php

/**
 * @var string $content
 */
$content = $content ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = parse_url((string)env('APP_URL', ''), PHP_URL_PATH) ?: '';
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(env('APP_NAME', 'NSG Procurement')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>

<body class="<?= current_user() ? 'has-sidebar' : 'guest-layout' ?>">
    <?php if (current_user()): ?>
        <div class="app-shell">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <a href="<?= e(url('/')) ?>" class="brand">NSG <span>Procurement</span></a>
                    <small class="sidebar-sub">Noor Staffing Group</small>
                </div>
                <nav class="sidebar-nav">
                    <?php
                    $groups = [
                        'Overview' => [
                            ['/', 'Dashboard', 'dashboard.view'],
                        ],
                        'Procurement Operations' => [
                            ['/purchase_requests', 'Purchase Requests', 'purchase_requests.view'],
                            ['/requisitions', 'Requisitions', 'requisitions.view'],
                            ['/vendors', 'Vendors', 'vendors.view'],
                            ['/quotations', 'Quotations', 'quotations.view'],
                            ['/purchase_orders', 'Purchase Orders', 'purchase_orders.view'],
                            ['/approvals', 'Approvals Inbox', 'approvals.view'],
                            ['/deliveries', 'Deliveries', 'deliveries.view'],
                        ],
                        'Finance & Budgets' => [
                            ['/invoices', 'Invoices & 3-Way Match', 'invoices.view'],
                            ['/payments', 'Payments', 'payments.view'],
                            ['/budgets', 'Budgets & Renewals', 'budgets.view'],
                            ['/reports', 'Reports & Exports', 'reports.view'],
                        ],
                        'Master Data & Organization' => [
                            ['/catalog', 'Catalog & FX Rates', 'catalog.view'],
                            ['/organization', 'Countries & Branches', 'organization.view'],
                            ['/departments', 'Departments', 'organization.view'],
                            ['/employees', 'Employees', 'organization.view'],
                            ['/imports', 'Bulk Imports', 'imports.manage'],
                        ],
                        'Administration' => [
                            ['/users', 'Users', 'users.manage'],
                            ['/roles', 'Roles & RBAC', 'roles.manage'],
                            ['/approval-workflows', 'Approval Workflows', 'settings.manage'],
                            ['/settings', 'Settings & Email Queue', 'settings.manage'],
                            ['/audit-logs', 'Audit Logs', 'audit.view'],
                        ],
                    ];

                    foreach ($groups as $groupLabel => $items):
                        $visible = array_filter($items, static fn(array $item): bool => can($item[2]) || $item[0] === '/');
                        if (empty($visible)) {
                            continue;
                        }
                    ?>
                        <div class="nav-group">
                            <div class="nav-group-title"><?= e($groupLabel) ?></div>
                            <?php foreach ($visible as [$href, $label, $perm]): ?>
                                <?php
                                $isActive = ($href === '/')
                                    ? ($currentPath === '/' || $currentPath === '')
                                    : ($currentPath === $href || str_starts_with($currentPath, $href . '/'));
                                ?>
                                <a href="<?= e(url($href)) ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                                    <?= e($label) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <div class="user-name"><?= e(current_user()['name']) ?></div>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn sidebar-logout">Sign out</button>
                    </form>
                </div>
            </aside>

            <div class="main-shell">
                <main class="container"><?= $content ?></main>
                <footer>NSG Procurement · Noor Staffing Group</footer>
            </div>
        </div>
    <?php else: ?>
        <header class="topbar">
            <a href="<?= e(url('/')) ?>" class="brand">NSG <span>Procurement</span></a>
        </header>
        <main class="container"><?= $content ?></main>
        <footer>NSG Procurement · Noor Staffing Group</footer>
    <?php endif; ?>
</body>

</html>