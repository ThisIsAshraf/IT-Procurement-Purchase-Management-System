<?php

/**
 * @var array<int, array<string, mixed>> $stats
 * @var array<int, array<string, mixed>> $recent
 * @var int $pendingApprovals
 * @var array<int, array<string, mixed>> $upcomingRenewals
 */
$stats = $stats ?? [];
$recent = $recent ?? [];
$pendingApprovals = $pendingApprovals ?? 0;
$upcomingRenewals = $upcomingRenewals ?? [];

$routeMap = [
    'Purchase Requests' => '/purchase_requests',
    'Requisitions'      => '/requisitions',
    'Vendors'           => '/vendors',
    'Purchase Orders'   => '/purchase_orders',
    'Deliveries'        => '/deliveries',
    'Invoices'          => '/invoices',
    'Payments'          => '/payments',
];
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Noor Staffing Group</p>
        <h1>Procurement Control Center</h1>
        <p class="subtitle">Global purchasing, approvals, receiving, and vendor operations.</p>
    </div>
    <div class="inline">
        <?php if (can('purchase_requests.create')): ?>
            <a class="btn primary" href="<?= e(url('/purchase_requests')) ?>">+ New Purchase Request</a>
        <?php endif; ?>
        <?php if (can('purchase_orders.create') || can('purchase_orders.manage')): ?>
            <a class="btn" href="<?= e(url('/purchase_orders')) ?>">+ Create PO</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid four">
    <?php foreach ($stats as $s): ?>
        <?php $href = $routeMap[$s['label']] ?? '/'; ?>
        <div class="kpi-card">
            <span class="kpi-label"><?= e($s['label']) ?></span>
            <div class="kpi-value"><?= e((string)$s['count']) ?></div>
            <div class="kpi-foot"><a href="<?= e(url($href)) ?>">View records &rarr;</a></div>
        </div>
    <?php endforeach; ?>
    <div class="kpi-card">
        <span class="kpi-label">Pending Approvals</span>
        <div class="kpi-value"><?= e((string)$pendingApprovals) ?></div>
        <div class="kpi-foot"><a href="<?= e(url('/approvals')) ?>">Open approval inbox &rarr;</a></div>
    </div>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Recent Purchase Orders</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr>
                            <td colspan="6" class="subtitle">No purchase orders recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent as $po): ?>
                            <tr>
                                <td><a href="<?= e(url('/purchase_orders/' . $po['id'])) ?>"><strong><?= e($po['po_number']) ?></strong></a></td>
                                <td><?= e($po['vendor_name']) ?></td>
                                <td><?= e($po['branch_code']) ?></td>
                                <td><?= e($po['order_date']) ?></td>
                                <td><strong><?= money($po['total_amount'], (string)$po['currency_code']) ?></strong></td>
                                <td><span class="badge"><?= e(str_replace('_', ' ', (string)$po['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Upcoming Renewals (Next 45 Days)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Subscription / Service</th>
                        <th>Vendor</th>
                        <th>Renewal Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($upcomingRenewals)): ?>
                        <tr>
                            <td colspan="4" class="subtitle">No recurring renewals due within the next 45 days.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($upcomingRenewals as $up): ?>
                            <tr>
                                <td><strong><?= e($up['title']) ?></strong></td>
                                <td><?= e($up['vendor_name']) ?></td>
                                <td><?= e($up['next_renewal_date']) ?></td>
                                <td><?= money($up['amount'], (string)$up['currency_code']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>