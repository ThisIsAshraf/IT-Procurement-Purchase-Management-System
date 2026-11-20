<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $invoice
 * @var array<int, array<string, mixed>> $matchLines
 * @var array<int, array<string, mixed>> $attachments
 */
$flash = $flash ?? null;
$error = $error ?? null;
$invoice = $invoice ?? [];
$matchLines = $matchLines ?? [];
$attachments = $attachments ?? [];

$allMatched = true;
foreach ($matchLines as $ml) {
    if (empty($ml['match_ok'])) {
        $allMatched = false;
        break;
    }
}
?>
<p><a href="<?= e(url('/invoices')) ?>">&larr; Back to Invoices</a></p>
<h1>Invoice <?= e($invoice['invoice_no']) ?> — <?= e(ucfirst(str_replace('_', ' ', (string)$invoice['status']))) ?></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Vendor &amp; PO</h3>
        <p>
            <strong><?= e($invoice['vendor_name']) ?></strong><br>
            Linked PO: <a href="<?= e(url('/purchase_orders/' . $invoice['purchase_order_id'])) ?>"><?= e($invoice['po_number']) ?></a> (<?= e($invoice['po_status']) ?>)<br>
            Branch: <?= e($invoice['branch_name']) ?>
        </p>
    </section>
    <section class="panel">
        <h3>Amounts &amp; Balance</h3>
        <p>
            Invoice Total: <strong><?= money($invoice['total_amount'], (string)$invoice['currency_code']) ?></strong><br>
            Paid: <?= money($invoice['paid_amount'], (string)$invoice['currency_code']) ?><br>
            Balance Due: <strong><?= money($invoice['balance_due'], (string)$invoice['currency_code']) ?></strong>
        </p>
    </section>
    <section class="panel">
        <h3>3-Way Match Verdict</h3>
        <p>
            <?php if ($allMatched): ?>
                <strong>PASSED — PO, Goods Receipt &amp; Invoice Align</strong>
            <?php else: ?>
                <strong>DISCREPANCY DETECTED — Review Variances Below</strong>
            <?php endif; ?>
        </p>
    </section>
</div>

<section class="panel">
    <h2>Three-Way Match Line Verification (PO vs. Goods Receipt vs. Invoice)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>PO Qty</th>
                    <th>Received Qty</th>
                    <th>Invoiced Qty</th>
                    <th>PO Price</th>
                    <th>Invoiced Price</th>
                    <th>Line Total</th>
                    <th>Match Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matchLines as $ml): ?>
                    <tr>
                        <td><?= e($ml['description']) ?></td>
                        <td><?= e((string)($ml['po_qty'] ?? '—')) ?></td>
                        <td><?= e((string)($ml['received_qty'] ?? '—')) ?></td>
                        <td><strong><?= e((string)$ml['quantity']) ?></strong></td>
                        <td><?= $ml['po_unit_price'] !== null ? money($ml['po_unit_price'], (string)$invoice['currency_code']) : '—' ?></td>
                        <td><strong><?= money($ml['unit_price'], (string)$invoice['currency_code']) ?></strong></td>
                        <td><?= money($ml['line_total'], (string)$invoice['currency_code']) ?></td>
                        <td>
                            <?php if (!empty($ml['match_ok'])): ?>
                                <strong>Matched</strong>
                            <?php else: ?>
                                <?= e(implode('; ', $ml['match_issues'])) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (can('invoices.manage') && in_array($invoice['status'], ['received', 'under_review', 'disputed'], true)): ?>
        <hr>
        <form method="post" action="<?= e(url('/invoices/' . $invoice['id'] . '/status')) ?>" class="inline">
            <?= csrf_field() ?>
            <button class="btn primary" name="status" value="approved">Approve Invoice for Payment</button>
            <button class="btn" name="status" value="disputed">Flag as Disputed</button>
            <button class="btn" name="status" value="void">Void Invoice</button>
        </form>
    <?php endif; ?>
</section>