<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $req
 * @var array<int, array<string, mixed>> $reqItems
 * @var array<int, array<string, mixed>> $quotations
 * @var array<int, array<int, array<string, mixed>>> $itemsByQuote
 * @var array<string, mixed>|null $comparison
 * @var array<int, array<string, mixed>> $scores
 */
$flash = $flash ?? null;
$error = $error ?? null;
$req = $req ?? [];
$reqItems = $reqItems ?? [];
$quotations = $quotations ?? [];
$itemsByQuote = $itemsByQuote ?? [];
$comparison = $comparison ?? null;
$scores = $scores ?? [];

$lowestTotal = null;
foreach ($quotations as $q) {
    $gt = (float)$q['grand_total'];
    if ($lowestTotal === null || $gt < $lowestTotal) {
        $lowestTotal = $gt;
    }
}
?>
<p><a href="<?= e(url('/quotations?requisition_id=' . ($req['id'] ?? 0))) ?>">&larr; Back to Quotations</a></p>
<h1>Vendor Quotation Comparison — <?= e($req['requisition_no'] ?? '') ?></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (empty($quotations)): ?>
    <section class="panel">
        <p>No vendor quotations have been recorded for this requisition yet.</p>
    </section>
<?php else: ?>
    <form method="post" action="<?= e(url('/quotations/compare/' . $req['id'])) ?>">
        <?= csrf_field() ?>
        <section class="panel">
            <h2>Side-by-Side Line Item &amp; Commercial Matrix</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Line Item</th>
                            <th>Req Qty</th>
                            <?php foreach ($quotations as $q): ?>
                                <th>
                                    <?= e($q['vendor_name']) ?><br>
                                    <small>Quote: <?= e($q['quotation_no']) ?> (<?= e($q['status']) ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reqItems as $ri): ?>
                            <?php $riId = (int)$ri['id']; ?>
                            <tr>
                                <td><strong><?= e($ri['description']) ?></strong></td>
                                <td><?= e((string)$ri['quantity']) ?> <?= e($ri['unit_code']) ?></td>
                                <?php foreach ($quotations as $q): ?>
                                    <?php $qid = (int)$q['id'];
                                    $qi = $itemsByQuote[$qid][$riId] ?? null; ?>
                                    <td>
                                        <?php if ($qi): ?>
                                            <strong><?= money($qi['unit_price'], (string)$q['currency_code']) ?></strong> / unit<br>
                                            <small>
                                                Line: <?= money((float)$qi['quantity'] * (float)$qi['unit_price'], (string)$q['currency_code']) ?>
                                                <?= $qi['warranty_months'] ? ' · ' . e((string)$qi['warranty_months']) . 'm warranty' : '' ?>
                                            </small>
                                        <?php else: ?>
                                            <em>Not quoted</em>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2"><strong>Header Tax / Shipping / Discount</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <td>
                                    Tax: <?= money($q['tax_amount'], (string)$q['currency_code']) ?><br>
                                    Shipping: <?= money($q['shipping_amount'], (string)$q['currency_code']) ?><br>
                                    Discount: -<?= money($q['discount_amount'], (string)$q['currency_code']) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Grand Total</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <?php $isLowest = ($lowestTotal !== null && abs((float)$q['grand_total'] - $lowestTotal) < 0.01); ?>
                                <td>
                                    <strong><?= money($q['grand_total'], (string)$q['currency_code']) ?></strong>
                                    <?= $isLowest ? ' <strong>(Lowest Price)</strong>' : '' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Lead Time &amp; Terms</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <td>
                                    Delivery: <?= $q['delivery_days'] !== null ? e((string)$q['delivery_days']) . ' days' : 'N/A' ?><br>
                                    Terms: <?= e($q['payment_term_name'] ?? 'Standard') ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Evaluation Score (0–100)</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <?php $qid = (int)$q['id'];
                                $sc = $scores[$qid] ?? []; ?>
                                <td>
                                    <input type="number" step="0.1" min="0" max="100" name="scores[<?= $qid ?>]"
                                        value="<?= e((string)($sc['evaluation_score'] ?? '')) ?>" placeholder="e.g. 92.5">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Evaluator Notes</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <?php $qid = (int)$q['id'];
                                $sc = $scores[$qid] ?? []; ?>
                                <td>
                                    <input name="eval_notes[<?= $qid ?>]" value="<?= e((string)($sc['evaluation_notes'] ?? '')) ?>" placeholder="Technical / SLA notes">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Recommended / Winning Vendor</strong></td>
                            <?php foreach ($quotations as $q): ?>
                                <?php $qid = (int)$q['id'];
                                $sc = $scores[$qid] ?? []; ?>
                                <td>
                                    <label class="inline">
                                        <input type="radio" name="selected_quotation_id" value="<?= $qid ?>"
                                            <?= (!empty($sc['is_recommended']) || $q['status'] === 'selected') ? 'checked' : '' ?>>
                                        Select This Quote
                                    </label>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Comparison Summary &amp; Award Recommendation</h2>
            <div class="grid two">
                <label>Comparison Title
                    <input name="comparison_title" value="<?= e((string)($comparison['comparison_title'] ?? ('Vendor Comparison — ' . $req['requisition_no']))) ?>">
                </label>
                <label>Justification / Recommendation Notes
                    <textarea name="recommendation" rows="2" placeholder="Explain why selected vendor offers best total value, SLA, or delivery schedule..."><?= e((string)($comparison['recommendation'] ?? '')) ?></textarea>
                </label>
            </div>
            <button class="btn" name="action_mode" value="save">Save Evaluation Scores</button>
            <button class="btn primary" name="action_mode" value="select">Finalize Award &amp; Approve for PO &rarr;</button>
        </section>
    </form>
<?php endif; ?>