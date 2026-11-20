<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var string $status
 * @var array<int, array<string, mixed>> $orders
 * @var array<int, array<string, mixed>> $readyRequisitions
 * @var array<int, array<string, mixed>> $vendors
 * @var array<int, array<string, mixed>> $branches
 */
$flash = $flash ?? null;
$error = $error ?? null;
$status = $status ?? '';
$orders = $orders ?? [];
$readyRequisitions = $readyRequisitions ?? [];
$vendors = $vendors ?? [];
$branches = $branches ?? [];
?>
<h1>Purchase Orders</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('purchase_orders.create') || can('purchase_orders.manage')): ?>
    <section class="panel">
        <h2>Generate Purchase Order from Requisition / Awarded Quote</h2>
        <form method="post" action="<?= e(url('/purchase_orders')) ?>">
            <?= csrf_field() ?>
            <div class="grid three">
                <label>Requisition *
                    <select name="requisition_id" required>
                        <option value="">Select requisition</option>
                        <?php foreach ($readyRequisitions as $rq): ?>
                            <option value="<?= e($rq['id']) ?>">
                                <?= e($rq['requisition_no']) ?> (<?= e($rq['branch_code']) ?> · <?= e($rq['department_name']) ?>)
                                <?= !empty($rq['selected_vendor_name']) ? ' — Awarded: ' . e($rq['selected_vendor_name']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Selected Quotation (if awarded)
                    <select name="quotation_id">
                        <option value="">Use requisition target prices (Direct PO)</option>
                        <?php foreach ($readyRequisitions as $rq): ?>
                            <?php if (!empty($rq['selected_quotation_id'])): ?>
                                <option value="<?= e($rq['selected_quotation_id']) ?>">
                                    <?= e($rq['requisition_no']) ?> &rarr; Quote <?= e($rq['quotation_no']) ?> (<?= e($rq['selected_vendor_name']) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Vendor (required if no quotation selected)
                    <select name="vendor_id">
                        <option value="">Auto from selected quote or pick vendor</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= e($v['id']) ?>"><?= e($v['legal_name']) ?> (<?= e($v['vendor_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="grid three">
                <label>Ship-To Branch *
                    <select name="ship_to_branch_id" required>
                        <?php foreach ($branches as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['name']) ?> (<?= e($b['branch_code']) ?>)</option><?php endforeach; ?>
                    </select>
                </label>
                <label>Order Date *<input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required></label>
                <label>Expected Delivery Date<input type="date" name="expected_delivery_date"></label>
            </div>
            <label>PO Terms &amp; Delivery Instructions<textarea name="terms" rows="2"></textarea></label>
            <button class="btn primary">Generate Draft Purchase Order</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Purchase Orders (<?= count($orders) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Vendor</th>
                    <th>Requisition</th>
                    <th>Ship-To</th>
                    <th>Order Date</th>
                    <th>Total</th>
                    <th>Base (USD)</th>
                    <th>Status</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $po): ?>
                    <tr>
                        <td><strong><a href="<?= e(url('/purchase_orders/' . $po['id'])) ?>"><?= e($po['po_number']) ?></a></strong></td>
                        <td><?= e($po['vendor_name']) ?></td>
                        <td><a href="<?= e(url('/requisitions/' . $po['requisition_id'])) ?>"><?= e($po['requisition_no']) ?></a></td>
                        <td><?= e($po['branch_code']) ?></td>
                        <td><?= e($po['order_date']) ?></td>
                        <td><strong><?= money($po['total_amount'], (string)$po['currency_code']) ?></strong></td>
                        <td><?= money($po['base_total_amount'], (string)$po['base_currency_code']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$po['status']))) ?></td>
                        <td><a class="btn" href="<?= e(url('/purchase_orders/' . $po['id'])) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>