<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<string, mixed> $data
 * @var string $reportType
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $rows
 * @var array<int, array<string, mixed>> $savedReports
 * @var array<int, array<string, mixed>> $branches
 */
$flash = $flash ?? null;
$error = $error ?? null;
$data = $data ?? ['requests' => 0, 'orders' => 0, 'vendors' => 0, 'spend' => [], 'statuses' => []];
$reportType = $reportType ?? 'spend_summary';
$filters = $filters ?? ['branch_id' => '', 'date_from' => '', 'date_to' => '', 'fiscal_year' => date('Y')];
$rows = $rows ?? [];
$savedReports = $savedReports ?? [];
$branches = $branches ?? [];
?>
<h1>Procurement Reports &amp; Saved Custom Views</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid three">
    <section class="panel">
        <h3>Total Purchase Requests</h3>
        <p><strong><?= e((string)($data['requests'] ?? 0)) ?></strong></p>
    </section>
    <section class="panel">
        <h3>Total Purchase Orders</h3>
        <p><strong><?= e((string)($data['orders'] ?? 0)) ?></strong></p>
    </section>
    <section class="panel">
        <h3>Registered Vendors</h3>
        <p><strong><?= e((string)($data['vendors'] ?? 0)) ?></strong></p>
    </section>
</div>

<section class="panel">
    <h2>Interactive Report Builder</h2>
    <form method="get" action="<?= e(url('/reports')) ?>" class="grid three">
        <label>Report Dataset
            <select name="report_type">
                <option value="spend_summary" <?= $reportType === 'spend_summary' ? 'selected' : '' ?>>Spend Summary (Branch / Dept / Currency)</option>
                <option value="po_register" <?= $reportType === 'po_register' ? 'selected' : '' ?>>Purchase Order Register</option>
                <option value="invoice_aging" <?= $reportType === 'invoice_aging' ? 'selected' : '' ?>>Vendor Invoice &amp; Balance Aging</option>
                <option value="budget_vs_actual" <?= $reportType === 'budget_vs_actual' ? 'selected' : '' ?>>Budget vs. Committed Spend</option>
                <option value="vendor_performance" <?= $reportType === 'vendor_performance' ? 'selected' : '' ?>>Vendor Spend &amp; Performance Scorecard</option>
            </select>
        </label>
        <label>Branch Filter
            <select name="branch_id">
                <option value="">All Accessible Branches</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= e($b['id']) ?>" <?= (string)($filters['branch_id'] ?? '') === (string)$b['id'] ? 'selected' : '' ?>>
                        <?= e($b['name']) ?> (<?= e($b['branch_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Fiscal Year (Budget Report)
            <input type="number" name="fiscal_year" value="<?= e((string)($filters['fiscal_year'] ?? date('Y'))) ?>">
        </label>
        <label>From Date<input type="date" name="date_from" value="<?= e((string)($filters['date_from'] ?? '')) ?>"></label>
        <label>To Date<input type="date" name="date_to" value="<?= e((string)($filters['date_to'] ?? '')) ?>"></label>
        <div>
            <button class="btn primary">Run Report</button>
            <?php if (can('reports.export')): ?>
                <?php $qs = http_build_query(array_merge(['report_type' => $reportType], $filters)); ?>
                <a class="btn" href="<?= e(url('/reports/export?format=csv&' . $qs)) ?>">CSV</a>
                <a class="btn" href="<?= e(url('/reports/export?format=xlsx&' . $qs)) ?>">Excel</a>
                <a class="btn" href="<?= e(url('/reports/export?format=pdf&' . $qs)) ?>">PDF</a>
            <?php endif; ?>
        </div>
    </form>

    <hr>
    <form method="post" action="<?= e(url('/reports/save')) ?>" class="grid three">
        <?= csrf_field() ?>
        <input type="hidden" name="report_type" value="<?= e($reportType) ?>">
        <input type="hidden" name="branch_id" value="<?= e((string)($filters['branch_id'] ?? '')) ?>">
        <input type="hidden" name="date_from" value="<?= e((string)($filters['date_from'] ?? '')) ?>">
        <input type="hidden" name="date_to" value="<?= e((string)($filters['date_to'] ?? '')) ?>">
        <input type="hidden" name="fiscal_year" value="<?= e((string)($filters['fiscal_year'] ?? date('Y'))) ?>">
        <label>Save Current View As<input name="report_name" placeholder="e.g. Q4 Dhaka IT Spend" required></label>
        <label class="inline"><input type="checkbox" name="is_shared" value="1"> Share with all report viewers</label>
        <div><button class="btn">Save Custom Report</button></div>
    </form>
</section>

<section class="panel">
    <h2>Report Results (<?= count($rows) ?> rows)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($rows[0] ?? ['Status' => null]) as $col): ?>
                        <th><?= e(ucwords(str_replace('_', ' ', (string)$col))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td>No records matched the selected filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= e((string)($val ?? '—')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (!empty($savedReports)): ?>
    <section class="panel">
        <h2>Saved Custom Reports</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Report Name</th>
                        <th>Type</th>
                        <th>Created By</th>
                        <th>Visibility</th>
                        <th>Load</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savedReports as $sr): ?>
                        <?php $decoded = json_decode((string)$sr['filters_json'], true) ?: []; ?>
                        <tr>
                            <td><strong><?= e($sr['report_name']) ?></strong></td>
                            <td><?= e(ucwords(str_replace('_', ' ', (string)$sr['report_type']))) ?></td>
                            <td><?= e($sr['author_name']) ?></td>
                            <td><?= (int)$sr['is_shared'] === 1 ? 'Shared' : 'Private' ?></td>
                            <td>
                                <a class="btn" href="<?= e(url('/reports?' . http_build_query(array_merge(['report_type' => $sr['report_type']], $decoded)))) ?>">
                                    Open Report
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>