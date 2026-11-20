<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var int $fiscalYear
 * @var array<int, array<string, mixed>> $budgets
 * @var array<int, array<int, array<string, mixed>>> $allocationsByBudget
 * @var array<int, array<string, mixed>> $recurring
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $categories
 * @var array<int, array<string, mixed>> $vendors
 */
$flash = $flash ?? null;
$error = $error ?? null;
$fiscalYear = $fiscalYear ?? (int)date('Y');
$budgets = $budgets ?? [];
$allocationsByBudget = $allocationsByBudget ?? [];
$recurring = $recurring ?? [];
$branches = $branches ?? [];
$departments = $departments ?? [];
$currencies = $currencies ?? [];
$categories = $categories ?? [];
$vendors = $vendors ?? [];
?>
<h1>Department Budgets &amp; Recurring Renewals</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Fiscal Year Budget Utilization (FY <?= e((string)$fiscalYear) ?>)</h2>
    <form method="get" action="<?= e(url('/budgets')) ?>" class="inline">
        <label class="inline">Fiscal Year:
            <input type="number" name="fiscal_year" value="<?= e((string)$fiscalYear) ?>" min="2024" max="2035">
        </label>
        <button class="btn">Filter</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Department</th>
                    <th>Budget Name</th>
                    <th>Allocated</th>
                    <th>Committed (POs)</th>
                    <th>Remaining</th>
                    <th>Utilization</th>
                    <th>Category Breakdown</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($budgets as $bg): ?>
                    <?php $bgId = (int)$bg['id'];
                    $allocs = $allocationsByBudget[$bgId] ?? []; ?>
                    <tr>
                        <td><?= e($bg['branch_code']) ?></td>
                        <td><?= e($bg['department_name']) ?></td>
                        <td><strong><?= e($bg['budget_name']) ?></strong></td>
                        <td><?= money($bg['total_allocated'], (string)$bg['currency_code']) ?></td>
                        <td><?= money($bg['committed_spend'], (string)$bg['currency_code']) ?></td>
                        <td><strong><?= money($bg['remaining'], (string)$bg['currency_code']) ?></strong></td>
                        <td><?= e((string)$bg['utilization_pct']) ?>%</td>
                        <td>
                            <?php foreach ($allocs as $al): ?>
                                <small><?= e($al['category_name'] ?? 'General') ?>: <?= money($al['allocated_amount'], (string)$bg['currency_code']) ?></small><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (can('budgets.manage')): ?>
    <div class="grid two">
        <section class="panel">
            <h2>Create Department Budget</h2>
            <form method="post" action="<?= e(url('/budgets')) ?>">
                <?= csrf_field() ?>
                <label>Branch *
                    <select name="branch_id" required>
                        <?php foreach ($branches as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['name']) ?> (<?= e($b['branch_code']) ?>)</option><?php endforeach; ?>
                    </select>
                </label>
                <label>Department *
                    <select name="department_id" required>
                        <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <div class="grid two">
                    <label>Fiscal Year *<input type="number" name="fiscal_year" value="<?= date('Y') ?>" required></label>
                    <label>Currency *
                        <select name="currency_code">
                            <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <label>Budget Name *<input name="budget_name" placeholder="e.g. FY2026 IT CapEx &amp; OpEx" required></label>
                <h3>Category Allocations</h3>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="grid two">
                        <select name="alloc_category_id[]">
                            <option value="">General / Uncategorized</option>
                            <?php foreach ($categories as $cat): ?><option value="<?= e($cat['id']) ?>"><?= e($cat['name']) ?></option><?php endforeach; ?>
                        </select>
                        <input type="number" step="0.01" min="0" name="alloc_amount[]" placeholder="0.00">
                    </div>
                <?php endfor; ?>
                <br>
                <button class="btn primary">Save Budget</button>
            </form>
        </section>

        <section class="panel">
            <h2>Schedule Recurring Software / Service Renewal</h2>
            <form method="post" action="<?= e(url('/budgets/recurring')) ?>">
                <?= csrf_field() ?>
                <label>Subscription / Contract Title *<input name="title" placeholder="e.g. Microsoft 365 E3 Licenses" required></label>
                <label>Vendor *
                    <select name="vendor_id" required>
                        <option value="">Select vendor</option>
                        <?php foreach ($vendors as $v): ?><option value="<?= e($v['id']) ?>"><?= e($v['legal_name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <div class="grid two">
                    <label>Branch *
                        <select name="branch_id" required>
                            <?php foreach ($branches as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Department *
                        <select name="department_id" required>
                            <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="grid three">
                    <label>Currency
                        <select name="currency_code">
                            <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Renewal Amount *<input type="number" step="0.01" min="0.01" name="amount" required></label>
                    <label>Frequency
                        <select name="frequency">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi_annual">Semi-Annual</option>
                            <option value="annual" selected>Annual</option>
                        </select>
                    </label>
                </div>
                <div class="grid two">
                    <label>Start Date *<input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required></label>
                    <label>Next Renewal Date *<input type="date" name="next_renewal_date" required></label>
                </div>
                <label>Notes<textarea name="notes" rows="2"></textarea></label>
                <button class="btn primary">Save Recurring Schedule</button>
            </form>
        </section>
    </div>
<?php endif; ?>

<section class="panel">
    <h2>Active Recurring Purchases &amp; Renewals (<?= count($recurring) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Vendor</th>
                    <th>Branch / Dept</th>
                    <th>Frequency</th>
                    <th>Amount</th>
                    <th>Next Renewal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recurring as $rec): ?>
                    <tr>
                        <td><strong><?= e($rec['schedule_code']) ?></strong></td>
                        <td><?= e($rec['title']) ?></td>
                        <td><?= e($rec['vendor_name']) ?></td>
                        <td><?= e($rec['branch_code']) ?> · <?= e($rec['department_name']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', (string)$rec['frequency']))) ?></td>
                        <td><?= money($rec['amount'], (string)$rec['currency_code']) ?></td>
                        <td><strong><?= e($rec['next_renewal_date']) ?></strong></td>
                        <td>
                            <?php if (can('budgets.manage')): ?>
                                <form method="post" action="<?= e(url('/budgets/recurring/' . $rec['id'] . '/renew')) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button class="btn">Generate Renewal Requisition &rarr;</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>