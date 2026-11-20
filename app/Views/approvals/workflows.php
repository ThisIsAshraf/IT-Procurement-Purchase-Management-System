<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $workflows
 * @var array<int, array<int, array<string, mixed>>> $stepsByWf
 * @var array<int, array<string, mixed>> $roles
 * @var array<int, array<string, mixed>> $users
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $currencies
 */
$flash = $flash ?? null;
$error = $error ?? null;
$workflows = $workflows ?? [];
$stepsByWf = $stepsByWf ?? [];
$roles = $roles ?? [];
$users = $users ?? [];
$branches = $branches ?? [];
$departments = $departments ?? [];
$currencies = $currencies ?? [];
?>
<h1>Approval Workflow Configuration</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Create Approval Rule</h2>
    <form method="post" action="<?= e(url('/approval-workflows')) ?>">
        <?= csrf_field() ?>
        <div class="grid three">
            <label>Workflow Name *<input name="workflow_name" placeholder="e.g. High-Value IT PO Approval" required></label>
            <label>Document Type *
                <select name="document_type" required>
                    <option value="purchase_order">Purchase Order</option>
                    <option value="purchase_request">Purchase Request</option>
                    <option value="requisition">Requisition</option>
                    <option value="quotation_comparison">Quotation Comparison</option>
                    <option value="invoice">Invoice</option>
                    <option value="payment">Payment</option>
                </select>
            </label>
            <label>Branch Scope (Optional)
                <select name="branch_id">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="grid three">
            <label>Department Scope (Optional)
                <select name="department_id">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Min Amount (Optional)<input type="number" step="0.01" min="0" name="min_amount" placeholder="0.00"></label>
            <label>Max Amount (Optional)<input type="number" step="0.01" min="0" name="max_amount" placeholder="Leave blank for unlimited"></label>
        </div>
        <div class="grid three">
            <label>Currency (Optional)
                <select name="currency_code">
                    <option value="">Any Currency</option>
                    <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Step 1 Name *<input name="step_name" placeholder="e.g. Department / IT Manager Sign-off" required></label>
            <label>Step 1 Approver Role
                <select name="approver_role_id">
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $role): ?><option value="<?= e($role['id']) ?>"><?= e($role['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn primary">Save Workflow</button>
    </form>
</section>

<?php foreach ($workflows as $wf): ?>
    <?php $wfId = (int)$wf['id'];
    $wfSteps = $stepsByWf[$wfId] ?? []; ?>
    <section class="panel">
        <h2><?= e($wf['workflow_name']) ?> — <code><?= e($wf['document_type']) ?></code></h2>
        <p>
            Branch: <strong><?= e($wf['branch_code'] ?? 'All') ?></strong> ·
            Dept: <strong><?= e($wf['department_name'] ?? 'All') ?></strong> ·
            Threshold: <strong><?= $wf['min_amount'] !== null ? e((string)$wf['min_amount']) : '0' ?></strong>
            to <strong><?= $wf['max_amount'] !== null ? e((string)$wf['max_amount']) : '∞' ?></strong>
            <?= e($wf['currency_code'] ?? '') ?>
        </p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Step #</th>
                        <th>Step Name</th>
                        <th>Assigned Role</th>
                        <th>Assigned User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wfSteps as $st): ?>
                        <tr>
                            <td><?= e((string)$st['step_order']) ?></td>
                            <td><?= e($st['step_name']) ?></td>
                            <td><?= e($st['role_name'] ?? '—') ?></td>
                            <td><?= e($st['user_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <hr>
        <form method="post" action="<?= e(url('/approval-workflows/' . $wfId . '/steps')) ?>" class="grid three">
            <?= csrf_field() ?>
            <label>Step Order<input type="number" min="1" name="step_order" value="<?= count($wfSteps) + 1 ?>" required></label>
            <label>Step Name<input name="step_name" placeholder="e.g. Finance Director Approval" required></label>
            <label>Approver Role
                <select name="approver_role_id">
                    <option value="">-- Or choose user --</option>
                    <?php foreach ($roles as $role): ?><option value="<?= e($role['id']) ?>"><?= e($role['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Specific Approver User (optional)
                <select name="approver_user_id">
                    <option value="">-- None --</option>
                    <?php foreach ($users as $u): ?><option value="<?= e($u['id']) ?>"><?= e($u['full_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <div><button class="btn">Add Step</button></div>
        </form>
    </section>
<?php endforeach; ?>