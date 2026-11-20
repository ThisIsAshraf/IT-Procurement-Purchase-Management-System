<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var int $vendorId
 * @var array<int, array<string, mixed>> $payments
 * @var array<int, array<string, mixed>> $openInvoices
 * @var array<int, array<string, mixed>> $vendors
 * @var array<int, array<string, mixed>> $currencies
 */
$flash = $flash ?? null;
$error = $error ?? null;
$vendorId = $vendorId ?? 0;
$payments = $payments ?? [];
$openInvoices = $openInvoices ?? [];
$vendors = $vendors ?? [];
$currencies = $currencies ?? [];
?>
<h1>Vendor Payments &amp; Invoice Allocations</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('payments.create') || can('payments.manage')): ?>
    <section class="panel">
        <h2>Record Payment &amp; Allocate Across Open Invoices</h2>
        <form method="get" action="<?= e(url('/payments')) ?>">
            <label>Filter Open Invoices by Vendor
                <select name="vendor_id" onchange="this.form.submit()">
                    <option value="">-- Select Vendor to Pay --</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?= e($v['id']) ?>" <?= $vendorId === (int)$v['id'] ? 'selected' : '' ?>>
                            <?= e($v['legal_name']) ?> (<?= e($v['vendor_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <?php if ($vendorId > 0): ?>
            <hr>
            <form method="post" action="<?= e(url('/payments')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="vendor_id" value="<?= e((string)$vendorId) ?>">
                <div class="grid three">
                    <label>Payment Date *<input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></label>
                    <label>Currency *
                        <select name="currency_code" required>
                            <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Total Payment Amount *<input type="number" step="0.01" min="0.01" name="amount" required></label>
                </div>
                <div class="grid three">
                    <label>Payment Method *
                        <select name="payment_method" required>
                            <option value="bank_transfer">Bank Transfer / Wire</option>
                            <option value="card">Corporate Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online Portal</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <label>Bank / Wire Transaction Ref<input name="transaction_reference" placeholder="e.g. WT-882910"></label>
                    <label>Payment Notes<input name="notes"></label>
                </div>

                <h3>Allocate Payment to Vendor Invoices</h3>
                <?php if (empty($openInvoices)): ?>
                    <p>No open unpaid invoices found for this vendor.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>PO #</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Invoice Total</th>
                                    <th>Balance Due</th>
                                    <th>Amount to Allocate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($openInvoices as $inv): ?>
                                    <?php $invId = (int)$inv['id']; ?>
                                    <tr>
                                        <td><strong><?= e($inv['invoice_no']) ?></strong></td>
                                        <td><?= e($inv['po_number']) ?></td>
                                        <td><?= e($inv['due_date'] ?? '—') ?></td>
                                        <td><?= e($inv['status']) ?></td>
                                        <td><?= money($inv['total_amount'], (string)$inv['currency_code']) ?></td>
                                        <td><strong><?= money($inv['balance_due'], (string)$inv['currency_code']) ?></strong></td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="<?= e((string)$inv['balance_due']) ?>"
                                                name="allocations[<?= $invId ?>]" placeholder="0.00">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <br>
                    <button class="btn primary">Record &amp; Allocate Payment</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Payment Ledger (<?= count($payments) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Payment Ref</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Allocated</th>
                    <th>Invoices Paid</th>
                    <th>Bank Ref</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><strong><?= e($p['payment_reference']) ?></strong></td>
                        <td><?= e($p['vendor_name']) ?></td>
                        <td><?= e($p['payment_date']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$p['payment_method']))) ?></td>
                        <td><strong><?= money($p['amount'], (string)$p['currency_code']) ?></strong></td>
                        <td><?= money($p['allocated_total'], (string)$p['currency_code']) ?></td>
                        <td><?= e($p['invoice_numbers'] ?? '—') ?></td>
                        <td><?= e($p['transaction_reference'] ?? '—') ?></td>
                        <td><?= e($p['recorded_by_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>