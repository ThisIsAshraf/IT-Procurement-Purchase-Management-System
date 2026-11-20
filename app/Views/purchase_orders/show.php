<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $po
 * @var array<int, array<string, mixed>> $items
 */
$flash = $flash ?? null;
$error = $error ?? null;
$po = $po ?? [];
$items = $items ?? [];
?>
<p><a href="<?= e(url('/purchase_orders')) ?>">&larr; Back to Purchase Orders</a></p>
<h1><?= e($po['po_number']) ?> — <?= e(ucfirst(str_replace('_', ' ', (string)$po['status']))) ?></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Vendor</h3>
        <p>
            <strong><?= e($po['vendor_name']) ?></strong> (<?= e($po['vendor_code']) ?>)<br>
            Email: <?= e($po['vendor_email'] ?? 'Not set') ?><br>
            Source Req: <a href="<?= e(url('/requisitions/' . $po['requisition_id'])) ?>"><?= e($po['requisition_no']) ?></a>
        </p>
    </section>
    <section class="panel">
        <h3>Delivery Destination</h3>
        <p>
            <strong><?= e($po['branch_name']) ?></strong> (<?= e($po['branch_code']) ?>)<br>
            Dept: <?= e($po['department_name']) ?><br>
            Expected: <?= e($po['expected_delivery_date'] ?? 'TBD') ?>
        </p>
    </section>
    <section class="panel">
        <h3>Financial Totals</h3>
        <p>
            Total: <strong><?= money($po['total_amount'], (string)$po['currency_code']) ?></strong><br>
            Base Equivalent: <strong><?= money($po['base_total_amount'], (string)$po['base_currency_code']) ?></strong><br>
            <small>Locked FX Rate: <?= e(rtrim(rtrim((string)$po['exchange_rate'], '0'), '.')) ?></small>
        </p>
    </section>
</div>

<section class="panel">
    <h2>Workflow &amp; Vendor Dispatch Actions</h2>
    <div class="inline">
        <?php if ($po['status'] === 'draft' && (can('purchase_orders.create') || can('purchase_orders.manage'))): ?>
            <form method="post" action="<?= e(url('/purchase_orders/' . $po['id'] . '/submit')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn primary">Submit PO for Approval &rarr;</button>
            </form>
        <?php endif; ?>

        <?php if ($po['status'] === 'approved' && (can('purchase_orders.manage') || can('purchase_orders.approve'))): ?>
            <form method="post" action="<?= e(url('/purchase_orders/' . $po['id'] . '/send')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn primary">Dispatch PO to Vendor (Email)</button>
            </form>
        <?php endif; ?>

        <?php if (in_array($po['status'], ['sent', 'partially_received'], true) && can('deliveries.manage')): ?>
            <a class="btn primary" href="<?= e(url('/deliveries?purchase_order_id=' . $po['id'])) ?>">Record Goods Delivery &rarr;</a>
        <?php endif; ?>

        <?php if (!in_array($po['status'], ['received', 'closed', 'cancelled'], true) && can('purchase_orders.cancel')): ?>
            <form method="post" action="<?= e(url('/purchase_orders/' . $po['id'] . '/cancel')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn">Cancel PO</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <h2>Purchase Order Line Items</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Description</th>
                    <th>Ordered</th>
                    <th>Received</th>
                    <th>Remaining</th>
                    <th>Unit Price</th>
                    <th>Tax %</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <?php $remaining = max(0, (float)$it['quantity_ordered'] - (float)$it['quantity_received_so_far']); ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><code><?= e($it['sku'] ?? '—') ?></code></td>
                        <td><?= e($it['description']) ?></td>
                        <td><?= e((string)$it['quantity_ordered']) ?> <?= e($it['unit_code']) ?></td>
                        <td><?= e((string)$it['quantity_received_so_far']) ?></td>
                        <td><strong><?= e((string)$remaining) ?></strong></td>
                        <td><?= money($it['unit_price'], (string)$po['currency_code']) ?></td>
                        <td><?= e((string)$it['tax_rate']) ?>%</td>
                        <td><strong><?= money($it['line_total'], (string)$po['currency_code']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>