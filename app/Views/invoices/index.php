<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var string $status
 * @var array<int, array<string, mixed>> $invoices
 * @var array<int, array<string, mixed>> $invoiceablePos
 * @var array<string, mixed>|null $selectedPo
 * @var array<int, array<string, mixed>> $selectedPoItems
 */
$flash = $flash ?? null;
$error = $error ?? null;
$status = $status ?? '';
$invoices = $invoices ?? [];
$invoiceablePos = $invoiceablePos ?? [];
$selectedPo = $selectedPo ?? null;
$selectedPoItems = $selectedPoItems ?? [];
?>
<h1>Vendor Invoices &amp; Three-Way Matching</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('invoices.create') || can('invoices.manage')): ?>
    <section class="panel">
        <h2>Record Vendor Invoice Against Purchase Order</h2>
        <form method="get" action="<?= e(url('/invoices')) ?>">
            <label>Select Purchase Order
                <select name="purchase_order_id" onchange="this.form.submit()">
                    <option value="">-- Choose Purchase Order --</option>
                    <?php foreach ($invoiceablePos as $po): ?>
                        <option value="<?= e($po['id']) ?>" <?= ($selectedPo && (int)$selectedPo['id'] === (int)$po['id']) ? 'selected' : '' ?>>
                            <?= e($po['po_number']) ?> — <?= e($po['vendor_name']) ?> (<?= money($po['total_amount'], (string)$po['currency_code']) ?> · <?= e($po['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <?php if ($selectedPo): ?>
            <hr>
            <form method="post" action="<?= e(url('/invoices')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="purchase_order_id" value="<?= e($selectedPo['id']) ?>">
                <div class="grid three">
                    <label>Vendor Invoice # *<input name="invoice_no" placeholder="e.g. INV-2026-501" required></label>
                    <label>Invoice Date *<input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required></label>
                    <label>Payment Due Date<input type="date" name="due_date"></label>
                </div>
                <div class="grid two">
                    <label>Total Tax Amount (<?= e($selectedPo['currency_code']) ?>)
                        <input type="number" step="0.01" min="0" name="tax_total" value="<?= e((string)$selectedPo['tax_total']) ?>">
                    </label>
                    <label>Attach Vendor Invoice PDF<input type="file" name="invoice_file"></label>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>PO Ordered</th>
                                <th>Goods Received</th>
                                <th>PO Unit Price</th>
                                <th>Invoiced Qty *</th>
                                <th>Invoiced Unit Price *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedPoItems as $pi): ?>
                                <tr>
                                    <td>
                                        <?= e($pi['description']) ?>
                                        <input type="hidden" name="po_item_id[]" value="<?= e($pi['id']) ?>">
                                        <input type="hidden" name="item_description[]" value="<?= e($pi['description']) ?>">
                                    </td>
                                    <td><?= e((string)$pi['quantity_ordered']) ?> <?= e($pi['unit_code']) ?></td>
                                    <td><strong><?= e((string)$pi['quantity_received_so_far']) ?></strong></td>
                                    <td><?= money($pi['unit_price'], (string)$selectedPo['currency_code']) ?></td>
                                    <td><input type="number" step="0.001" min="0" name="item_quantity[]" value="<?= e((string)$pi['quantity_received_so_far']) ?>" required></td>
                                    <td><input type="number" step="0.0001" min="0" name="item_unit_price[]" value="<?= e((string)$pi['unit_price']) ?>" required></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <br>
                <button class="btn primary">Save Invoice &amp; Run 3-Way Match</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Invoices (<?= count($invoices) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Vendor</th>
                    <th>PO Number</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance Due</th>
                    <th>Status</th>
                    <th>3-Way Match</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><a href="<?= e(url('/invoices/' . $inv['id'])) ?>"><?= e($inv['invoice_no']) ?></a></strong></td>
                        <td><?= e($inv['vendor_name']) ?></td>
                        <td><a href="<?= e(url('/purchase_orders/' . $inv['purchase_order_id'])) ?>"><?= e($inv['po_number']) ?></a></td>
                        <td><?= e($inv['invoice_date']) ?></td>
                        <td><?= e($inv['due_date'] ?? '—') ?></td>
                        <td><?= money($inv['total_amount'], (string)$inv['currency_code']) ?></td>
                        <td><?= money($inv['paid_amount'], (string)$inv['currency_code']) ?></td>
                        <td><strong><?= money($inv['balance_due'], (string)$inv['currency_code']) ?></strong></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$inv['status']))) ?></td>
                        <td><a class="btn" href="<?= e(url('/invoices/' . $inv['id'])) ?>">Inspect Match</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>