<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $batches
 */
$flash = $flash ?? null;
$error = $error ?? null;
$batches = $batches ?? [];
?>
<h1>Bulk Data Import &amp; Validation</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <section class="panel">
        <h2>Upload CSV / Excel Spreadsheet</h2>
        <form method="post" enctype="multipart/form-data" action="<?= e(url('/imports')) ?>">
            <?= csrf_field() ?>
            <label>Import Dataset *
                <select name="entity_type" required>
                    <option value="vendors">Vendors (Upsert by vendor_code)</option>
                    <option value="catalog_items">Catalog Items (Upsert by sku)</option>
                    <option value="budgets">Department Budgets &amp; Allocations</option>
                </select>
            </label>
            <label>Spreadsheet File (.csv, .xlsx, .xls) *
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required>
            </label>
            <button class="btn primary">Run Validated Import</button>
        </form>
    </section>

    <section class="panel">
        <h2>Download CSV Templates &amp; Column Specs</h2>
        <ul>
            <li>
                <strong>Vendors:</strong> <code>vendor_code, legal_name</code> (required), <code>tax_registration_no, website, status, notes</code><br>
                <a href="<?= e(url('/imports/vendor-template')) ?>">Download Vendor CSV Template</a>
            </li>
            <li>
                <strong>Catalog Items:</strong> <code>category_code, unit_code, item_name</code> (required), <code>sku, description</code><br>
                <a href="<?= e(url('/imports/catalog-template')) ?>">Download Catalog CSV Template</a>
            </li>
            <li>
                <strong>Budgets:</strong> <code>branch_code, department_code, fiscal_year, currency_code, budget_name</code> (required), <code>category_code, allocated_amount</code><br>
                <a href="<?= e(url('/imports/budget-template')) ?>">Download Budget CSV Template</a>
            </li>
        </ul>
    </section>
</div>

<section class="panel">
    <h2>Import Batch History</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Entity</th>
                    <th>File</th>
                    <th>Total Rows</th>
                    <th>Imported</th>
                    <th>Failed</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Error Log</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                    <tr>
                        <td>#<?= e((string)$b['id']) ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', (string)$b['import_type']))) ?></td>
                        <td><?= e($b['original_filename']) ?></td>
                        <td><?= e((string)$b['total_rows']) ?></td>
                        <td><strong><?= e((string)$b['imported_rows']) ?></strong></td>
                        <td><?= e((string)$b['failed_rows']) ?></td>
                        <td><?= e($b['imported_by_name']) ?></td>
                        <td><?= e($b['created_at']) ?></td>
                        <td>
                            <?php if ((int)$b['failed_rows'] > 0): ?>
                                <a class="btn" href="<?= e(url('/imports/' . $b['id'] . '/errors')) ?>">Download Errors CSV</a>
                            <?php else: ?>
                                Clean
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>