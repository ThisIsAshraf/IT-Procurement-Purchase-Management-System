<?php

/**
 * @var string $content
 */

use App\Services\ApprovalNotificationService;
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

$authUser = current_user();
$pendingApprovalsCount = 0;
$pendingApprovalItems = [];
if ($authUser && can('approvals.view')) {
    $uid = (int)($authUser['id'] ?? 0);
    $pendingApprovalsCount = ApprovalNotificationService::pendingCountForUser($uid);
    $pendingApprovalItems = ApprovalNotificationService::pendingItemsForUser($uid, 6);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title><?= e($appTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <style>
        :root {
            --sidebar-bg: <?= e($sidebarBg) ?>;
            --sidebar-text: <?= e($sidebarText) ?>;
            --primary: <?= e($sidebarActive) ?>;
        }

        .topbar-utility {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .notif-wrap {
            position: relative;
        }

        .notif-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            color: #0f172a;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .notif-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .notif-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            background: #e2e8f0;
            color: #475569;
        }

        .notif-badge.has-items {
            background: #dc2626;
            color: #ffffff;
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: 340px;
            max-width: 92vw;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
            z-index: 1000;
            overflow: hidden;
        }

        .notif-dropdown.open {
            display: block;
        }

        .notif-dd-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 0.95rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
        }

        .notif-dd-item {
            display: block;
            padding: 0.7rem 0.95rem;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: #1e293b;
            font-size: 0.83rem;
        }

        .notif-dd-item:hover {
            background: #f8fafc;
        }

        .notif-dd-foot {
            display: block;
            text-align: center;
            padding: 0.65rem;
            background: #f8fafc;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-pill {
            float: right;
            background: #dc2626;
            color: #fff;
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 0.7rem;
            font-weight: 700;
        }
    </style>
</head>

<body class="<?= $authUser ? 'has-sidebar' : 'guest-layout' ?>">
    <?php if ($authUser): ?>
        <header class="mobile-header">
            <button type="button" class="mobile-menu-btn" id="mobile-nav-toggle" aria-label="Open menu">&#9776;</button>
            <a href="<?= e(url('/')) ?>" class="mobile-brand"><?= e($appTitle) ?></a>
            <span class="mobile-user-badge"><?= e($authUser['name']) ?></span>
        </header>
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <div class="app-shell">
            <aside class="sidebar" id="app-sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-header-top">
                        <div>
                            <?php if ($showLogo && $logoPath !== ''): ?>
                                <a href="<?= e(url('/')) ?>" class="sidebar-logo-wrap">
                                    <img src="<?= e(url('/' . ltrim($logoPath, '/'))) ?>" alt="<?= e($appTitle) ?>"
                                        style="max-height: <?= $logoH ?>px; max-width: <?= $logoW ?>px;">
                                </a>
                            <?php endif; ?>
                            <a href="<?= e(url('/')) ?>" class="brand"><?= e($appTitle) ?></a>
                            <small class="sidebar-sub">Noor Staffing Group</small>
                        </div>
                        <button type="button" class="sidebar-close-btn" id="mobile-nav-close" aria-label="Close menu">&times;</button>
                    </div>
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
                                    <?php if ($href === '/approvals' && $pendingApprovalsCount > 0): ?>
                                        <span class="nav-pill"><?= $pendingApprovalsCount ?></span>
                                    <?php endif; ?>
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
                                        ['numbering', 'ID & Code Generators'],
                                        ['master_data', 'Categories, Units & Terms'],
                                        ['geo', 'Countries & Currencies'],
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
                    <div class="user-name"><?= e($authUser['name']) ?></div>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn sidebar-logout">Sign out</button>
                    </form>
                </div>
            </aside>

            <div class="main-shell">
                <div class="topbar-utility">
                    <?php if (can('approvals.view')): ?>
                        <div class="notif-wrap" id="notif-wrap">
                            <button type="button" class="notif-btn" id="notif-toggle" aria-expanded="false" title="Approval Notifications">
                                <span style="font-size:1rem; line-height:1;">&#128276;</span>
                                <span>Notifications</span>
                                <span class="notif-badge <?= $pendingApprovalsCount > 0 ? 'has-items' : '' ?>">
                                    <?= $pendingApprovalsCount ?>
                                </span>
                            </button>
                            <div class="notif-dropdown" id="notif-dropdown">
                                <div class="notif-dd-head">
                                    <span>Pending Approvals</span>
                                    <span><?= $pendingApprovalsCount ?> waiting</span>
                                </div>
                                <?php if (empty($pendingApprovalItems)): ?>
                                    <div style="padding:1rem; text-align:center; color:#64748b; font-size:0.83rem;">
                                        No pending approvals assigned to you.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($pendingApprovalItems as $item): ?>
                                        <a href="<?= e(url('/approvals')) ?>" class="notif-dd-item">
                                            <div style="font-weight:600; color:#0f172a;">
                                                <?= e(ucwords(str_replace('_', ' ', (string)$item['document_type']))) ?> #<?= (int)$item['document_id'] ?>
                                            </div>
                                            <div style="color:#64748b; font-size:0.78rem; margin-top:2px;">
                                                Step <?= (int)$item['current_step_order'] ?>: <?= e((string)$item['step_name']) ?> &middot; <?= e((string)$item['workflow_name']) ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <a href="<?= e(url('/approvals')) ?>" class="notif-dd-foot">Open Approvals Inbox &rarr;</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <main class="container"><?= $content ?></main>
                <footer><?= e($appTitle) ?> · Noor Staffing Group</footer>
            </div>
        </div>

        <script>
            (function() {
                var toggle = document.getElementById('mobile-nav-toggle');
                var closeBtn = document.getElementById('mobile-nav-close');
                var backdrop = document.getElementById('sidebar-backdrop');

                function setNav(open) {
                    document.body.classList.toggle('nav-open', open);
                }
                if (toggle) toggle.addEventListener('click', function() {
                    setNav(true);
                });
                if (closeBtn) closeBtn.addEventListener('click', function() {
                    setNav(false);
                });
                if (backdrop) backdrop.addEventListener('click', function() {
                    setNav(false);
                });

                var notifToggle = document.getElementById('notif-toggle');
                var notifDropdown = document.getElementById('notif-dropdown');
                var notifWrap = document.getElementById('notif-wrap');
                if (notifToggle && notifDropdown && notifWrap) {
                    notifToggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var isOpen = notifDropdown.classList.toggle('open');
                        notifToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });
                    document.addEventListener('click', function(e) {
                        if (!notifWrap.contains(e.target)) {
                            notifDropdown.classList.remove('open');
                            notifToggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            })();
        </script>
    <?php else: ?>
        <?= $content ?>
    <?php endif; ?>
</body>

</html>