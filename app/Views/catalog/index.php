<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $items
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $units
 * @var array<int, array<string, mixed>> $exchangeRates
 * @var array<int, array<string, mixed>> $paymentTerms
 * @var array<int, array<string, mixed>> $currencies
 */

use App\Services\CodeGeneratorService;

$flash = $flash ?? null;
$error = $error ?? null;
$items = $items ?? [];
$categories = $categories ?? [];
$units = $units ?? [];
$exchangeRates = $exchangeRates ?? [];
$paymentTerms = $paymentTerms ?? [];
$currencies = $currencies ?? [];

$skuPreview = CodeGeneratorService::globalPrefix() . '-HARDWARE-0001';
$catPreview = CodeGeneratorService::nextCategoryCode();
?>
<h1>Catalog, Currencies &amp; Master Data</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <?php if (can('catalog.manage')): ?>
        <section class="panel">
            <h2>Add Catalog Item</h2>
            <form method="post" action="<?= e(url('/catalog/items')) ?>">
                <?= csrf_field() ?>
                <label>Category
                    <select name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['id']) ?>"><?= e($cat['name']) ?> (<?= e($cat['category_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Unit of Measure
                    <select name="unit_id" required>
                        <option value="">Select unit</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= e($u['id']) ?>"><?= e($u['name']) ?> (<?= e($u['unit_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>SKU <small class="subtitle">(Leave blank to auto-generate)</small>
                    <input name="sku" placeholder="Auto: e.g. <?= e($skuPreview) ?>">
                </label>
                <label>Item Name<input name="item_name" required></label>
                <label>Description<textarea name="description" rows="2"></textarea></label>
                <label>Status
                    <select name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </label>
                <button class="btn primary">Save Catalog Item</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Catalog Items (<?= count($items) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><code><?= e($it['sku'] ?? '—') ?></code></td>
                            <td><strong><?= e($it['item_name']) ?></strong><br><small><?= e($it['description'] ?? '') ?></small></td>
                            <td><?= e($it['category_name']) ?></td>
                            <td><?= e($it['unit_code']) ?></td>
                            <td><?= (int)$it['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Item Categories &amp; Units</h2>
        <?php if (can('catalog.manage')): ?>
            <form method="post" action="<?= e(url('/catalog/categories')) ?>">
                <?= csrf_field() ?>
                <label>Parent Category (optional)
                    <select name="parent_id">
                        <option value="">Top-level Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['id']) ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Category Code <small class="subtitle">(Leave blank to auto-generate)</small>
                    <input name="category_code" placeholder="Auto: <?= e($catPreview) ?>">
                </label>
                <label>Category Name<input name="name" required></label>
                <button class="btn">Add Category</button>
            </form>
            <hr>
            <form method="post" action="<?= e(url('/catalog/units')) ?>">
                <?= csrf_field() ?>
                <label>Unit Code<input name="unit_code" placeholder="e.g. BOX, SEAT" required></label>
                <label>Unit Name<input name="name" required></label>
                <button class="btn">Add Unit</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Historical Exchange Rates &amp; Payment Terms</h2>
        <?php if (can('catalog.manage')): ?>
            <form method="post" action="<?= e(url('/catalog/exchange-rates')) ?>">
                <?= csrf_field() ?>
                <label>From Currency
                    <select name="from_currency">
                        <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>To Currency
                    <select name="to_currency">
                        <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Rate (1 From = X To)<input type="number" step="0.000001" min="0.000001" name="rate" required></label>
                <label>Effective Date<input type="date" name="effective_date" value="<?= date('Y-m-d') ?>" required></label>
                <label>Source<input name="source" value="Treasury / Central Bank"></label>
                <button class="btn">Record Exchange Rate</button>
            </form>
        <?php endif; ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pair</th>
                        <th>Rate</th>
                        <th>Effective Date</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exchangeRates as $er): ?>
                        <tr>
                            <td><?= e($er['from_currency']) ?> → <?= e($er['to_currency']) ?></td>
                            <td><?= e(rtrim(rtrim((string)$er['rate'], '0'), '.')) ?></td>
                            <td><?= e($er['effective_date']) ?></td>
                            <td><?= e($er['source'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>