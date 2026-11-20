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
?>
<h1>Procurement Control Center</h1>

<div class="grid three">
    <?php foreach ($stats as $s): ?>
        <section class="panel">
            <h3><?= e($s['label']) ?></h3>
            <p><strong><?= e((string)$s['count']) ?></strong></p>
        </section>
    <?php endforeach; ?>
    <section class="panel">
        <h3>Pending Approvals</h3>
        <p><strong><?= e((string)$pendingApprovals) ?></strong> · <a href="<?= e(url('/approvals')) ?>">Open Inbox</a></p>
    </section>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Recent Purchase Orders</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Vendor</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $po): ?>
                        <tr>
                            <td><a href="<?= e(url('/purchase_orders/' . $po['id'])) ?>"><strong><?= e($po['po_number']) ?></strong></a></td>
                            <td><?= e($po['vendor_name']) ?></td>
                            <td><?= e($po['branch_code']) ?></td>
                            <td><?= e($po['order_date']) ?></td>
                            <td><?= money($po['total_amount'], (string)$po['currency_code']) ?></td>
                            <td><?= e(ucfirst(str_replace('_', ' ', (string)$po['status']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Upcoming Software &amp; Service Renewals (Next 45 Days)</h2>
        <?php if (empty($upcomingRenewals)): ?>
            <p>No recurring renewals due within the next 45 days.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Schedule</th>
                            <th>Vendor</th>
                            <th>Renewal Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingRenewals as $up): ?>
                            <tr>
                                <td><?= e($up['title']) ?></td>
                                <td><?= e($up['vendor_name']) ?></td>
                                <td><strong><?= e($up['next_renewal_date']) ?></strong></td>
                                <td><?= money($up['amount'], (string)$up['currency_code']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>