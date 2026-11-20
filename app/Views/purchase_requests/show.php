<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $pr
 * @var array<int, array<string, mixed>> $items
 * @var array<string, mixed> $budget
 * @var array<int, array<string, mixed>> $attachments
 * @var array<int, array<string, mixed>> $catalogItems
 * @var array<int, array<string, mixed>> $units
 */
$flash = $flash ?? null;
$error = $error ?? null;
$pr = $pr ?? [];
$items = $items ?? [];
$budget = $budget ?? [];
$attachments = $attachments ?? [];
$catalogItems = $catalogItems ?? [];
$units = $units ?? [];

$totalEst = 0.0;
foreach ($items as $it) {
    $totalEst += (float)$it['line_estimated_total'];
}
?>
<p><a href="<?= e(url('/purchase_requests')) ?>">&larr; Back to Purchase Requests</a></p>
<h1><?= e($pr['request_no']) ?> — <?= e(ucfirst(str_replace('_', ' ', (string)$pr['status']))) ?></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Requester &amp; Scope</h3>
        <p>
            <strong><?= e($pr['requester_name']) ?></strong><br>
            <?= e($pr['branch_name']) ?> (<?= e($pr['branch_code']) ?>) · <?= e($pr['department_name']) ?><br>
            Required by: <?= e($pr['required_by'] ?? 'Not specified') ?>
        </p>
    </section>
    <section class="panel">
        <h3>Estimated Request Total</h3>
        <p><strong><?= money($totalEst, (string)$pr['currency_code']) ?></strong> (<?= count($items) ?> items)</p>
    </section>
    <section class="panel">
        <h3>FY <?= date('Y') ?> Department Budget Check</h3>
        <?php if (!empty($budget['exists'])): ?>
            <p>
                <strong><?= e($budget['budget_name']) ?></strong><br>
                Available: <strong><?= money($budget['available'], (string)$budget['currency_code']) ?></strong>
                of <?= money($budget['allocated'], (string)$budget['currency_code']) ?>
            </p>
        <?php else: ?>
            <p>No active FY <?= date('Y') ?> budget configured for this branch/department.</p>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <h2>Business Justification</h2>
    <p><?= nl2br(e((string)$pr['business_justification'])) ?></p>

    <div class="inline">
        <?php if ($pr['status'] === 'draft' && can('purchase_requests.create')): ?>
            <form method="post" action="<?= e(url('/purchase_requests/' . $pr['id'] . '/act')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn primary" name="decision" value="submitted">Submit for Review</button>
            </form>
        <?php endif; ?>

        <?php if (in_array($pr['status'], ['submitted', 'under_review'], true) && can('purchase_requests.approve')): ?>
            <form method="post" action="<?= e(url('/purchase_requests/' . $pr['id'] . '/act')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn primary" name="decision" value="approved">Approve Request</button>
                <button class="btn" name="decision" value="under_review">Mark Under Review</button>
                <button class="btn" name="decision" value="rejected">Reject</button>
            </form>
        <?php endif; ?>

        <?php if ($pr['status'] === 'approved' && empty($pr['requisition_id']) && (can('requisitions.create') || can('requisitions.manage'))): ?>
            <form method="post" action="<?= e(url('/purchase_requests/' . $pr['id'] . '/convert')) ?>" class="inline">
                <?= csrf_field() ?>
                <button class="btn primary">Convert to Formal Requisition &rarr;</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($pr['requisition_id'])): ?>
            <a class="btn primary" href="<?= e(url('/requisitions/' . $pr['requisition_id'])) ?>">
                Open Linked Requisition (<?= e($pr['requisition_no']) ?>) &rarr;
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <h2>Line Items</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Est. Unit Price</th>
                    <th>Est. Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><code><?= e($it['sku'] ?? '—') ?></code></td>
                        <td><?= e($it['description']) ?></td>
                        <td><?= e((string)$it['quantity']) ?></td>
                        <td><?= e($it['unit_code']) ?></td>
                        <td><?= $it['estimated_unit_price'] !== null ? money($it['estimated_unit_price'], (string)$pr['currency_code']) : '—' ?></td>
                        <td><?= money($it['line_estimated_total'], (string)$pr['currency_code']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (in_array($pr['status'], ['draft', 'submitted', 'under_review'], true) && can('purchase_requests.create')): ?>
        <hr>
        <h3>Add Another Item</h3>
        <form method="post" action="<?= e(url('/purchase_requests/' . $pr['id'] . '/items')) ?>" class="grid three">
            <?= csrf_field() ?>
            <label>Catalog Item
                <select name="catalog_item_id">
                    <option value="">Custom / Non-catalog</option>
                    <?php foreach ($catalogItems as $ci): ?>
                        <option value="<?= e($ci['id']) ?>"><?= e($ci['item_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Description<input name="description" required></label>
            <label>Quantity<input type="number" step="0.001" min="0.001" name="quantity" value="1" required></label>
            <label>Unit
                <select name="unit_id" required>
                    <?php foreach ($units as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['unit_code']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Est. Unit Price<input type="number" step="0.01" min="0" name="estimated_unit_price"></label>
            <div><button class="btn">Add Item</button></div>
        </form>
    <?php endif; ?>
</section>