<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $departments
 * @var array<int, array<string, mixed>> $employees
 */
$flash = $flash ?? null;
$error = $error ?? null;
$branches = $branches ?? [];
$departments = $departments ?? [];
$employees = $employees ?? [];
?>
<h1>Employee Directory</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <?php if (can('employees.manage')): ?>
        <section class="panel">
            <h2>Add Employee</h2>
            <form method="post" action="<?= e(url('/employees')) ?>">
                <?= csrf_field() ?>
                <label>Employee ID / Code<input name="employee_number" placeholder="e.g. EMP-1042"></label>
                <label>Full Name<input name="full_name" required></label>
                <label>Work Email<input type="email" name="email" required></label>
                <label>Branch
                    <select name="branch_id" required>
                        <option value="">Select branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= e($b['id']) ?>"><?= e($b['name']) ?> (<?= e($b['branch_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Department
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['id']) ?>"><?= e($d['name']) ?> (<?= e($d['department_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Reporting Manager
                    <select name="manager_employee_id">
                        <option value="">None / Top-level</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= e($emp['id']) ?>"><?= e($emp['full_name']) ?> (<?= e($emp['job_title'] ?? 'Staff') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Job Title<input name="job_title" placeholder="e.g. Systems Administrator"></label>
                <label>Employment Status
                    <select name="employment_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </label>
                <button class="btn primary">Save Employee</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Employees (<?= count($employees) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Emp #</th>
                        <th>Name &amp; Title</th>
                        <th>Branch / Dept</th>
                        <th>Manager</th>
                        <th>Login Account</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?= e($emp['employee_number'] ?? '—') ?></td>
                            <td><strong><?= e($emp['full_name']) ?></strong><br><small><?= e($emp['job_title'] ?? '') ?> · <?= e($emp['email']) ?></small></td>
                            <td><?= e($emp['branch_code']) ?> · <?= e($emp['department_name']) ?></td>
                            <td><?= e($emp['manager_name'] ?? '—') ?></td>
                            <td><?= $emp['user_id'] ? 'Linked (' . e($emp['user_status']) . ')' : 'No login' ?></td>
                            <td><?= e($emp['employment_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>