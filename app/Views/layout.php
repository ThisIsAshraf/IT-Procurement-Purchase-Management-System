<?php

/**
 * @var string $content
 */

use App\Services\SettingsService;

$content = $content ?? '';
$s = SettingsService::all();
$appTitle = $s['app_title'] ?? env('APP_NAME', 'NSG Procurement');
$sidebarBg = $s['sidebar_bg_color'] ?? '#831B30';
$sidebarText = $s['sidebar_text_color'] ?? '#E2E8F0';
$sidebarActive = $s['sidebar_active_color'] ?? '#0284C7';
$showLogo = ($s['show_logo'] ?? '1') === '1';
$logoPath = $s['company_logo_path'] ?? '';
$logoH = (int)($s['logo_max_height'] ?? 38);
$logoW = (int)($s['logo_max_width'] ?? 170);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = parse_url((string)env('APP_URL', ''), PHP_URL_PATH) ?: '';
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
$currentTab = (string)($_GET['tab'] ?? 'branding');
$inSettings = ($currentPath === '/settings' || str_starts_with($currentPath, '/settings/'));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($appTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <style>
        :root {
            --sidebar-bg: <?= e($sidebarBg) ?>;
            --sidebar-text: <?= e($sidebarText) ?>;
            --primary: <?= e($sidebarActive) ?>;
        }
    </style>
</head>

<body class="<?= current_user() ? 'has-sidebar' : 'guest-layout' ?>">
    <?php if (current_user()): ?>
        <div class="app-shell">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <?php if ($showLogo && $logoPath !== ''): ?>
                        <a href="<?= e(url('/')) ?>" class="sidebar-logo-wrap">
                            <img src="<?= e(url('/' . ltrim($logoPath, '/'))) ?>" alt="<?= e($appTitle) ?>"
                                style="max-height: <?= $logoH ?>px; max-width: <?= $logoW ?>px;">
                        </a>
                    <?php endif; ?>
                    <a href="<?= e(url('/')) ?>" class="brand"><?= e($appTitle) ?></a>
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

                    <?php if (can('settings.manage')): ?>
                        <div class="nav-group">
                            <details class="nav-accordion" <?= $inSettings ? 'open' : '' ?>>
                                <summary class="nav-link nav-accordion-toggle <?= $inSettings ? 'active-parent' : '' ?>">
                                    <span>&#9881; Site Settings</span>
                                    <span class="chev">&#9662;</span>
                                </summary>
                                <div class="nav-submenu">
                                    <?php
                                    $settingsMenu = [
                                        ['branding', 'Branding & Theme'],
                                        ['timezone', 'Date & Timezone'],
                                        ['currency', 'Currency & Rates'],
                                        ['forms', 'Forms & Certificates'],
                                        ['templates', 'Email Templates'],
                                        ['smtp', 'Outgoing SMTP Mail'],
                                        ['sso', 'Google & Microsoft SSO'],
                                        ['api', 'API Keys & RBAC'],
                                        ['backup', 'Backup & Recovery'],
                                    ];
                                    foreach ($settingsMenu as [$tabKey, $tabLabel]):
                                        $subActive = $inSettings && $currentTab === $tabKey;
                                    ?>
                                        <a href="<?= e(url('/settings?tab=' . $tabKey)) ?>" class="nav-sublink <?= $subActive ? 'active' : '' ?>">
                                            <?= e($tabLabel) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </div>
                    <?php endif; ?>
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
                <footer><?= e($appTitle) ?> · Noor Staffing Group</footer>
            </div>
        </div>
    <?php else: ?>
        <?= $content ?>
    <?php endif; ?>
</body>

</html>