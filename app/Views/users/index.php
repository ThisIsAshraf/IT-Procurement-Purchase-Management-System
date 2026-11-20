<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $unlinkedEmployees
 * @var array<int, array<string, mixed>> $roles
 * @var array<int, array<string, mixed>> $branches
 * @var array<int, array<string, mixed>> $users
 */
$flash = $flash ?? null;
$error = $error ?? null;
$unlinkedEmployees = $unlinkedEmployees ?? [];
$roles = $roles ?? [];
$branches = $branches ?? [];
$users = $users ?? [];
?>
<h1>Users, Roles &amp; Branch Scopes</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <section class="panel">
        <h2>Create User Account</h2>
        <form method="post" action="<?= e(url('/users')) ?>">
            <?= csrf_field() ?>
            <label>Link to Employee (optional)
                <select name="employee_id">
                    <option value="">Standalone user / No employee record</option>
                    <?php foreach ($unlinkedEmployees as $emp): ?>
                        <option value="<?= e($emp['id']) ?>">
                            <?= e($emp['full_name']) ?> (<?= e($emp['branch_name']) ?> / <?= e($emp['department_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Full Name<input name="full_name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Temporary Password (12+ chars)<input type="password" name="password" minlength="12" required></label>

            <fieldset>
                <legend>Assigned Roles (select one or more)</legend>
                <?php foreach ($roles as $role): ?>
                    <label class="inline">
                        <input type="checkbox" name="role_ids[]" value="<?= e($role['id']) ?>">
                        <?= e($role['name']) ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>

            <fieldset>
                <legend>Branch Access Scope (leave unchecked for Global All-Branch access)</legend>
                <?php foreach ($branches as $b): ?>
                    <label class="inline">
                        <input type="checkbox" name="branch_ids[]" value="<?= e($b['id']) ?>">
                        <?= e($b['branch_code']) ?> — <?= e($b['name']) ?>
                    </label><br>
                <?php endforeach; ?>
            </fieldset>

            <button class="btn primary">Create User</button>
        </form>
    </section>

    <section class="panel">
        <h2>Active &amp; Managed Users (<?= count($users) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Branch Scope</th>
                        <th>Status</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?= e($u['full_name']) ?></strong><br>
                                <small><?= e($u['email']) ?></small>
                                <?php if ($u['home_branch']): ?>
                                    <br><small>Home: <?= e($u['home_branch']) ?> / <?= e($u['home_department']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ($u['roles'] as $r): ?>
                                    <span><?= e($r['role_name']) ?></span><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if (empty($u['branch_scopes'])): ?>
                                    <em>Global (All Branches)</em>
                                <?php else: ?>
                                    <?= e(implode(', ', array_column($u['branch_scopes'], 'branch_code'))) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url('/users/' . $u['id'] . '/update')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="full_name" value="<?= e($u['full_name']) ?>">
                                    <input type="hidden" name="email" value="<?= e($u['email']) ?>">
                                    <?php foreach ($u['roles'] as $r): ?>
                                        <input type="hidden" name="role_ids[]" value="<?= e($r['role_id']) ?>">
                                    <?php endforeach; ?>
                                    <?php foreach ($u['branch_scopes'] as $bs): ?>
                                        <input type="hidden" name="branch_ids[]" value="<?= e($bs['branch_id']) ?>">
                                    <?php endforeach; ?>
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $u['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="locked" <?= $u['status'] === 'locked' ? 'selected' : '' ?>>Locked</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= e($u['last_login_at'] ?? 'Never') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>