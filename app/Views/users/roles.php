<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $roles
 * @var array<string, array<int, array<string, mixed>>> $groupedPermissions
 * @var array<int, array<int, int>> $assigned
 */
$flash = $flash ?? null;
$error = $error ?? null;
$roles = $roles ?? [];
$groupedPermissions = $groupedPermissions ?? [];
$assigned = $assigned ?? [];
?>
<h1>Roles &amp; Granular Permission Matrix</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Create Custom Role</h2>
    <form method="post" action="<?= e(url('/roles')) ?>" class="grid three">
        <?= csrf_field() ?>
        <label>Role Key (lowercase)<input name="role_key" placeholder="e.g. regional_buyer" required></label>
        <label>Display Name<input name="name" placeholder="e.g. Regional Buyer" required></label>
        <label>Description<input name="description" placeholder="Optional scope summary"></label>
        <div><button class="btn primary">Add Role</button></div>
    </form>
</section>

<?php foreach ($roles as $role): ?>
    <?php $roleId = (int)$role['id'];
    $rolePerms = $assigned[$roleId] ?? []; ?>
    <section class="panel">
        <h2><?= e($role['name']) ?> <code>(<?= e($role['role_key']) ?>)</code></h2>
        <p><?= e($role['description'] ?? '') ?></p>
        <form method="post" action="<?= e(url('/roles/' . $roleId . '/permissions')) ?>">
            <?= csrf_field() ?>
            <div class="grid three">
                <?php foreach ($groupedPermissions as $module => $perms): ?>
                    <div>
                        <strong><?= e(strtoupper($module)) ?></strong><br>
                        <?php foreach ($perms as $p): ?>
                            <label class="inline">
                                <input type="checkbox" name="permission_ids[]" value="<?= e($p['id']) ?>"
                                    <?= in_array((int)$p['id'], $rolePerms, true) ? 'checked' : '' ?>>
                                <?= e($p['permission_key']) ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <br>
            <button class="btn primary">Save Permissions for <?= e($role['name']) ?></button>
        </form>
    </section>
<?php endforeach; ?>