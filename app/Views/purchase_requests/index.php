<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var string $status
 * @var array<int, array<string, mixed>> $requests
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $catalogItems
 * @var array<int, array<string, mixed>> $units
 */
$flash = $flash ?? null;
$error = $error ?? null;
$status = $status ?? '';
$requests = $requests ?? [];
$branches = $branches ?? [];
$departments = $departments ?? [];
$currencies = $currencies ?? [];
$catalogItems = $catalogItems ?? [];
$units = $units ?? [];
$user = current_user() ?? [];
?>
<h1>Purchase Requests</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<?php if (can('purchase_requests.create')): ?>
    <section class="panel">
        <h2>New Multi-Item Purchase Request</h2>
        <form method="post" action="<?= e(url('/purchase_requests')) ?>">
            <?= csrf_field() ?>
            <div class="grid three">
                <label>Branch
                    <select name="branch_id" required>
                        <option value="">Select branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= e($b['id']) ?>" <?= (int)($user['branch_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= e($b['name']) ?> (<?= e($b['branch_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Department
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['id']) ?>" <?= (int)($user['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>>
                                <?= e($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Currency
                    <select name="currency_code" required>
                        <?php foreach ($currencies as $c): ?>
                            <option value="<?= e($c['code']) ?>" <?= $c['code'] === 'USD' ? 'selected' : '' ?>><?= e($c['code']) ?> — <?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="grid two">
                <label>Required By Date<input type="date" name="required_by"></label>
                <label>Business Justification<textarea name="business_justification" rows="2" placeholder="Explain operational need, project, or new hire onboarding..." required></textarea></label>
            </div>

            <h3>Requested Line Items</h3>
            <div class="table-wrap">
                <table id="pr-items-table">
                    <thead>
                        <tr>
                            <th>Catalog Item (Optional)</th>
                            <th>Item / Service Description *</th>
                            <th>Qty *</th>
                            <th>Unit *</th>
                            <th>Est. Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <tr>
                                <td>
                                    <select name="item_catalog_id[]">
                                        <option value="">Custom / Non-catalog</option>
                                        <?php foreach ($catalogItems as $ci): ?>
                                            <option value="<?= e($ci['id']) ?>"><?= e(($ci['sku'] ? '[' . $ci['sku'] . '] ' : '') . $ci['item_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input name="item_description[]" placeholder="<?= $i === 0 ? 'Required item description' : 'Optional additional item' ?>" <?= $i === 0 ? 'required' : '' ?>></td>
                                <td><input type="number" step="0.001" min="0.001" name="item_quantity[]" value="<?= $i === 0 ? '1' : '' ?>" <?= $i === 0 ? 'required' : '' ?>></td>
                                <td>
                                    <select name="item_unit_id[]" <?= $i === 0 ? 'required' : '' ?>>
                                        <?php foreach ($units as $u): ?>
                                            <option value="<?= e($u['id']) ?>"><?= e($u['unit_code']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="item_unit_price[]" placeholder="0.00"></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <br>
            <button class="btn primary" name="submit_action" value="submit">Submit Request</button>
            <button class="btn" name="submit_action" value="draft">Save as Draft</button>
        </form>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Purchase Requests (<?= count($requests) ?>)</h2>
    <form method="get" action="<?= e(url('/purchase_requests')) ?>" class="inline">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['draft', 'submitted', 'under_review', 'approved', 'rejected', 'converted', 'cancelled'] as $st): ?>
                <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Requester</th>
                    <th>Branch / Dept</th>
                    <th>Items</th>
                    <th>Est. Total</th>
                    <th>Required By</th>
                    <th>Status</th>
                    <th>Requisition</th>
                    <th>Open</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $pr): ?>
                    <tr>
                        <td><strong><a href="<?= e(url('/purchase_requests/' . $pr['id'])) ?>"><?= e($pr['request_no']) ?></a></strong></td>
                        <td><?= e($pr['requester_name']) ?></td>
                        <td><?= e($pr['branch_code']) ?> · <?= e($pr['department_name']) ?></td>
                        <td><?= e((string)$pr['item_count']) ?></td>
                        <td><?= money($pr['estimated_total'], (string)$pr['currency_code']) ?></td>
                        <td><?= e($pr['required_by'] ?? '—') ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$pr['status']))) ?></td>
                        <td>
                            <?php if (!empty($pr['requisition_id'])): ?>
                                <a href="<?= e(url('/requisitions/' . $pr['requisition_id'])) ?>"><?= e($pr['requisition_no']) ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><a class="btn" href="<?= e(url('/purchase_requests/' . $pr['id'])) ?>">Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>