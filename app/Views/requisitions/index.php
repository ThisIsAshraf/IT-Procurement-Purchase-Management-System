<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var string $status
 * @var array<int, array<string, mixed>> $requisitions
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $catalogItems
 * @var array<int, array<string, mixed>> $units
 */
$flash = $flash ?? null;
$error = $error ?? null;
$status = $status ?? '';
$requisitions = $requisitions ?? [];
$branches = $branches ?? [];
$departments = $departments ?? [];
$currencies = $currencies ?? [];
$catalogItems = $catalogItems ?? [];
$units = $units ?? [];
?>
<h1>IT &amp; Procurement Requisitions</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('requisitions.create') || can('requisitions.manage')): ?>
    <section class="panel">
        <h2>Create Direct IT / Procurement Requisition</h2>
        <p><small>Tip: Employee requests can also be converted into Requisitions in one click from the Purchase Request detail page.</small></p>
        <form method="post" action="<?= e(url('/requisitions')) ?>">
            <?= csrf_field() ?>
            <div class="grid three">
                <label>Branch
                    <select name="branch_id" required>
                        <option value="">Select branch</option>
                        <?php foreach ($branches as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Department
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Currency
                    <select name="currency_code" required>
                        <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>" <?= $c['code'] === 'USD' ? 'selected' : '' ?>><?= e($c['code']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Sourcing Notes<textarea name="notes" rows="2"></textarea></label>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Catalog Item</th>
                            <th>Description *</th>
                            <th>Qty *</th>
                            <th>Unit *</th>
                            <th>Target Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < 2; $i++): ?>
                            <tr>
                                <td>
                                    <select name="item_catalog_id[]">
                                        <option value="">Custom item</option>
                                        <?php foreach ($catalogItems as $ci): ?><option value="<?= e($ci['id']) ?>"><?= e($ci['item_name']) ?></option><?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input name="item_description[]" <?= $i === 0 ? 'required' : '' ?>></td>
                                <td><input type="number" step="0.001" min="0.001" name="item_quantity[]" value="<?= $i === 0 ? '1' : '' ?>" <?= $i === 0 ? 'required' : '' ?>></td>
                                <td>
                                    <select name="item_unit_id[]" <?= $i === 0 ? 'required' : '' ?>>
                                        <?php foreach ($units as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['unit_code']) ?></option><?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="item_target_price[]"></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <br>
            <button class="btn primary">Create Requisition</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Requisitions (<?= count($requisitions) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Requisition #</th>
                    <th>Source Request</th>
                    <th>Branch / Dept</th>
                    <th>Prepared By</th>
                    <th>Items</th>
                    <th>Target Total</th>
                    <th>Quotes</th>
                    <th>Status</th>
                    <th>Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requisitions as $req): ?>
                    <tr>
                        <td><strong><a href="<?= e(url('/requisitions/' . $req['id'])) ?>"><?= e($req['requisition_no']) ?></a></strong></td>
                        <td><?= !empty($req['request_no']) ? e($req['request_no']) : 'Direct' ?></td>
                        <td><?= e($req['branch_code']) ?> · <?= e($req['department_name']) ?></td>
                        <td><?= e($req['prepared_by_name']) ?></td>
                        <td><?= e((string)$req['item_count']) ?></td>
                        <td><?= money($req['target_total'], (string)$req['currency_code']) ?></td>
                        <td><?= e((string)$req['quotation_count']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$req['status']))) ?></td>
                        <td><a class="btn" href="<?= e(url('/requisitions/' . $req['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>