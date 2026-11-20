<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $departments
 */
$flash = $flash ?? null;
$error = $error ?? null;
$departments = $departments ?? [];
?>
<h1>Departments</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <?php if (can('organization.manage')): ?>
        <section class="panel">
            <h2>Add Department</h2>
            <form method="post" action="<?= e(url('/departments')) ?>">
                <?= csrf_field() ?>
                <label>Department Code<input name="department_code" maxlength="30" placeholder="e.g. IT-INFRA" required></label>
                <label>Department Name<input name="name" placeholder="e.g. IT Infrastructure" required></label>
                <label>Status
                    <select name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </label>
                <button class="btn primary">Save Department</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Departments (<?= count($departments) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Status</th>
                        <?php if (can('organization.manage')): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $d): ?>
                        <tr>
                            <td><strong><?= e($d['department_code']) ?></strong></td>
                            <td><?= e($d['name']) ?></td>
                            <td><?= (int)$d['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                            <?php if (can('organization.manage')): ?>
                                <td>
                                    <form method="post" action="<?= e(url('/departments/' . $d['id'] . '/update')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="department_code" value="<?= e($d['department_code']) ?>">
                                        <input type="hidden" name="name" value="<?= e($d['name']) ?>">
                                        <input type="hidden" name="is_active" value="<?= (int)$d['is_active'] === 1 ? 0 : 1 ?>">
                                        <button class="btn"><?= (int)$d['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>