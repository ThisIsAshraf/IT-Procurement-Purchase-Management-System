<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $req
 * @var array<int, array<string, mixed>> $items
 * @var array<int, array<string, mixed>> $quotations
 * @var array<string, mixed> $budget
 */
$flash = $flash ?? null;
$error = $error ?? null;
$req = $req ?? [];
$items = $items ?? [];
$quotations = $quotations ?? [];
$budget = $budget ?? [];
?>
<p><a href="<?= e(url('/requisitions')) ?>">&larr; Back to Requisitions</a></p>
<h1><?= e($req['requisition_no']) ?> — <?= e(ucfirst(str_replace('_', ' ', (string)$req['status']))) ?></h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Origin &amp; Scope</h3>
        <p>
            Source Request: <strong><?= e($req['request_no'] ?? 'Standalone Requisition') ?></strong><br>
            <?= e($req['branch_name']) ?> · <?= e($req['department_name']) ?><br>
            Prepared by: <?= e($req['prepared_by_name']) ?>
        </p>
    </section>
    <section class="panel">
        <h3>Sourcing Progress</h3>
        <p><strong><?= count($quotations) ?></strong> vendor quotation(s) collected</p>
        <p><a class="btn primary" href="<?= e(url('/quotations?requisition_id=' . $req['id'])) ?>">Collect / Compare Quotations &rarr;</a></p>
    </section>
    <section class="panel">
        <h3>Department Budget (FY <?= date('Y') ?>)</h3>
        <?php if (!empty($budget['exists'])): ?>
            <p>
                <strong><?= e($budget['budget_name']) ?></strong><br>
                Available: <strong><?= money($budget['available'], (string)$budget['currency_code']) ?></strong>
            </p>
        <?php else: ?>
            <p>No active budget found.</p>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <h2>Requisition Line Items</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Target Unit Price</th>
                    <th>Target Line Total</th>
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
                        <td><?= $it['target_unit_price'] !== null ? money($it['target_unit_price'], (string)$req['currency_code']) : '—' ?></td>
                        <td><?= money($it['line_target_total'], (string)$req['currency_code']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (can('requisitions.manage')): ?>
        <hr>
        <form method="post" action="<?= e(url('/requisitions/' . $req['id'] . '/status')) ?>" class="inline">
            <?= csrf_field() ?>
            <label class="inline">Update Requisition Status:
                <select name="status">
                    <?php foreach (['draft', 'sourcing', 'pending_approval', 'approved', 'rejected', 'ordered', 'cancelled'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $req['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn">Save Status</button>
        </form>
    <?php endif; ?>
</section>