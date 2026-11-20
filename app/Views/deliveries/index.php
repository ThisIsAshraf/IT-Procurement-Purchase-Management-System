<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $deliveries
 * @var array<int, array<string, mixed>> $receivablePos
 * @var array<string, mixed>|null $selectedPo
 * @var array<int, array<string, mixed>> $selectedPoItems
 */
$flash = $flash ?? null;
$error = $error ?? null;
$deliveries = $deliveries ?? [];
$receivablePos = $receivablePos ?? [];
$selectedPo = $selectedPo ?? null;
$selectedPoItems = $selectedPoItems ?? [];
?>
<h1>Deliveries &amp; Goods Receiving</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('deliveries.manage')): ?>
    <section class="panel">
        <h2>Record Goods Receipt (Partial or Full)</h2>
        <form method="get" action="<?= e(url('/deliveries')) ?>">
            <label>Select Open Purchase Order
                <select name="purchase_order_id" onchange="this.form.submit()">
                    <option value="">-- Choose Purchase Order --</option>
                    <?php foreach ($receivablePos as $po): ?>
                        <option value="<?= e($po['id']) ?>" <?= ($selectedPo && (int)$selectedPo['id'] === (int)$po['id']) ? 'selected' : '' ?>>
                            <?= e($po['po_number']) ?> — <?= e($po['vendor_name']) ?> (<?= e($po['branch_code']) ?> · <?= e($po['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <?php if ($selectedPo): ?>
            <hr>
            <form method="post" action="<?= e(url('/deliveries')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="purchase_order_id" value="<?= e($selectedPo['id']) ?>">
                <div class="grid three">
                    <label>Vendor Packing Slip / Waybill Ref<input name="vendor_delivery_ref" placeholder="e.g. DHL-994812"></label>
                    <label>Received Date &amp; Time *<input type="datetime-local" name="received_at" value="<?= date('Y-m-d\TH:i') ?>" required></label>
                    <label>Receiving Notes<input name="notes" placeholder="Warehouse / IT inspection summary"></label>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Ordered</th>
                                <th>Previously Received</th>
                                <th>Open Balance</th>
                                <th>Qty Accepted Now</th>
                                <th>Qty Rejected / Damaged</th>
                                <th>Condition Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedPoItems as $pi): ?>
                                <?php
                                $piId = (int)$pi['id'];
                                $remaining = max(0, round((float)$pi['quantity_ordered'] - (float)$pi['quantity_received_so_far'], 3));
                                ?>
                                <tr>
                                    <td><strong><?= e($pi['description']) ?></strong></td>
                                    <td><?= e((string)$pi['quantity_ordered']) ?> <?= e($pi['unit_code']) ?></td>
                                    <td><?= e((string)$pi['quantity_received_so_far']) ?></td>
                                    <td><strong><?= e((string)$remaining) ?></strong></td>
                                    <td>
                                        <input type="number" step="0.001" min="0" max="<?= e((string)$remaining) ?>"
                                            name="items[<?= $piId ?>][received]" value="<?= e((string)$remaining) ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" min="0"
                                            name="items[<?= $piId ?>][rejected]" value="0">
                                    </td>
                                    <td>
                                        <input name="items[<?= $piId ?>][condition_notes]" placeholder="OK / Serial numbers">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <button class="btn primary">Confirm Goods Receipt</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Recorded Deliveries (<?= count($deliveries) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Delivery #</th>
                    <th>PO Number</th>
                    <th>Vendor</th>
                    <th>Branch</th>
                    <th>Received At</th>
                    <th>Accepted / Rejected</th>
                    <th>Status</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deliveries as $del): ?>
                    <tr>
                        <td><strong><?= e($del['delivery_no']) ?></strong></td>
                        <td><a href="<?= e(url('/purchase_orders/' . $del['purchase_order_id'])) ?>"><?= e($del['po_number']) ?></a></td>
                        <td><?= e($del['vendor_name']) ?></td>
                        <td><?= e($del['branch_code']) ?></td>
                        <td><?= e($del['received_at']) ?></td>
                        <td><?= e((string)$del['total_received']) ?> accepted / <?= e((string)$del['total_rejected']) ?> rejected</td>
                        <td><?= e(ucfirst((string)$del['delivery_status'])) ?></td>
                        <td><?= e($del['received_by_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>