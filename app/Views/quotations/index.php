<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $quotations
 * @var array<int, array<string, mixed>> $requisitions
 * @var array<string, mixed>|null $selectedReq
 * @var array<int, array<string, mixed>> $selectedReqItems
 * @var array<int, array<string, mixed>> $vendors
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $paymentTerms
 */
$flash = $flash ?? null;
$error = $error ?? null;
$quotations = $quotations ?? [];
$requisitions = $requisitions ?? [];
$selectedReq = $selectedReq ?? null;
$selectedReqItems = $selectedReqItems ?? [];
$vendors = $vendors ?? [];
$currencies = $currencies ?? [];
$paymentTerms = $paymentTerms ?? [];
?>
<h1>Vendor Quotations &amp; Sourcing</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Select Requisition to Collect or Compare Quotes</h2>
    <form method="get" action="<?= e(url('/quotations')) ?>" class="grid two">
        <label>Requisition
            <select name="requisition_id" onchange="this.form.submit()">
                <option value="">-- All Requisitions --</option>
                <?php foreach ($requisitions as $rq): ?>
                    <option value="<?= e($rq['id']) ?>" <?= ($selectedReq && (int)$selectedReq['id'] === (int)$rq['id']) ? 'selected' : '' ?>>
                        <?= e($rq['requisition_no']) ?> (<?= e($rq['branch_code']) ?> · <?= e($rq['department_name']) ?> — <?= e($rq['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div>
            <?php if ($selectedReq && can('quotations.compare')): ?>
                <a class="btn primary" href="<?= e(url('/quotations/compare/' . $selectedReq['id'])) ?>">
                    Side-by-Side Vendor Comparison &amp; Scoring &rarr;
                </a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if ($selectedReq && (can('quotations.create') || can('quotations.manage'))): ?>
    <section class="panel">
        <h2>Record Vendor Quotation for <?= e($selectedReq['requisition_no']) ?></h2>
        <form method="post" action="<?= e(url('/quotations')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="requisition_id" value="<?= e($selectedReq['id']) ?>">

            <div class="grid three">
                <label>Vendor *
                    <select name="vendor_id" required>
                        <option value="">Select vendor</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= e($v['id']) ?>"><?= e($v['legal_name']) ?> (<?= e($v['vendor_code']) ?> — <?= e($v['status']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Vendor Quote / Reference # *
                    <input name="quotation_no" placeholder="e.g. Q-2026-8841" required>
                </label>
                <label>Currency *
                    <select name="currency_code" required>
                        <?php foreach ($currencies as $c): ?>
                            <option value="<?= e($c['code']) ?>" <?= $c['code'] === $selectedReq['currency_code'] ? 'selected' : '' ?>>
                                <?= e($c['code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="grid three">
                <label>Quote Date *<input type="date" name="quote_date" value="<?= date('Y-m-d') ?>" required></label>
                <label>Valid Until<input type="date" name="valid_until"></label>
                <label>Lead Time (Delivery Days)<input type="number" min="0" name="delivery_days" placeholder="e.g. 7"></label>
            </div>

            <div class="grid three">
                <label>Payment Terms
                    <select name="payment_term_id">
                        <option value="">Standard / Unspecified</option>
                        <?php foreach ($paymentTerms as $pt): ?>
                            <option value="<?= e($pt['id']) ?>"><?= e($pt['term_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Additional Header Tax<input type="number" step="0.01" min="0" name="tax_amount" value="0.00"></label>
                <label>Shipping / Freight<input type="number" step="0.01" min="0" name="shipping_amount" value="0.00"></label>
            </div>

            <div class="grid two">
                <label>Discount Amount<input type="number" step="0.01" min="0" name="discount_amount" value="0.00"></label>
                <label>Attach Official Vendor Quote PDF/Excel<input type="file" name="quote_file"></label>
            </div>

            <h3>Quoted Item Pricing</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Requisition Item</th>
                            <th>Qty</th>
                            <th>Target Price</th>
                            <th>Quoted Unit Price *</th>
                            <th>Tax Rate %</th>
                            <th>Warranty (Months)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedReqItems as $ri): ?>
                            <tr>
                                <td>
                                    <?= e($ri['description']) ?>
                                    <input type="hidden" name="req_item_id[]" value="<?= e($ri['id']) ?>">
                                    <input type="hidden" name="item_description[]" value="<?= e($ri['description']) ?>">
                                </td>
                                <td>
                                    <?= e((string)$ri['quantity']) ?> <?= e($ri['unit_code']) ?>
                                    <input type="hidden" name="item_quantity[]" value="<?= e((string)$ri['quantity']) ?>">
                                </td>
                                <td><?= $ri['target_unit_price'] !== null ? money($ri['target_unit_price'], (string)$selectedReq['currency_code']) : '—' ?></td>
                                <td><input type="number" step="0.0001" min="0" name="item_unit_price[]" required></td>
                                <td><input type="number" step="0.01" min="0" max="100" name="item_tax_rate[]" value="0"></td>
                                <td><input type="number" min="0" name="item_warranty_months[]" placeholder="12"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <label>Vendor Terms / Notes<textarea name="notes" rows="2"></textarea></label>
            <button class="btn primary">Save Vendor Quotation</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Recorded Quotations (<?= count($quotations) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Quote #</th>
                    <th>Requisition</th>
                    <th>Vendor</th>
                    <th>Quote Date</th>
                    <th>Lead Time</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th>Compare</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotations as $q): ?>
                    <tr>
                        <td><strong><?= e($q['quotation_no']) ?></strong></td>
                        <td><a href="<?= e(url('/requisitions/' . $q['requisition_id'])) ?>"><?= e($q['requisition_no']) ?></a></td>
                        <td><?= e($q['vendor_name']) ?> (<?= e($q['vendor_code']) ?>)</td>
                        <td><?= e($q['quote_date']) ?></td>
                        <td><?= $q['delivery_days'] !== null ? e((string)$q['delivery_days']) . ' days' : '—' ?></td>
                        <td><strong><?= money($q['grand_total'], (string)$q['currency_code']) ?></strong></td>
                        <td><?= e(ucfirst((string)$q['status'])) ?></td>
                        <td><a class="btn" href="<?= e(url('/quotations/compare/' . $q['requisition_id'])) ?>">Compare</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>