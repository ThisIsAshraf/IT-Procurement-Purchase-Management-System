<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $countries
 * @var array<int, array<string, mixed>> $currencies
 * @var array<int, array<string, mixed>> $branches
 */
$flash = $flash ?? null;
$error = $error ?? null;
$countries = $countries ?? [];
$currencies = $currencies ?? [];
$branches = $branches ?? [];

$branchTimezones = [
    'Asia/Dhaka'          => 'Asia/Dhaka (UTC+06:00 — Bangladesh)',
    'America/New_York'    => 'America/New_York (UTC-05:00 — US Eastern)',
    'America/Chicago'     => 'America/Chicago (UTC-06:00 — US Central)',
    'America/Los_Angeles' => 'America/Los_Angeles (UTC-08:00 — US Pacific)',
    'Europe/London'       => 'Europe/London (UTC+00:00 — UK)',
    'Asia/Dubai'          => 'Asia/Dubai (UTC+04:00 — UAE)',
    'Asia/Singapore'      => 'Asia/Singapore (UTC+08:00 — Singapore)',
    'Australia/Sydney'    => 'Australia/Sydney (UTC+10:00 — Australia)',
    'UTC'                 => 'UTC (Coordinated Universal Time)',
];
?>
<h1>Global Branches</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <?php if (can('organization.manage')): ?>
        <section class="panel">
            <h2>Add Branch</h2>
            <form method="post" action="<?= e(url('/organization')) ?>">
                <?= csrf_field() ?>
                <label>Country
                    <select name="country_id" required>
                        <option value="">Select country</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?> (<?= e($c['iso2']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Branch Code<input name="branch_code" maxlength="20" placeholder="e.g. NYC-HQ, DAC-01" required></label>
                <label>Branch Name<input name="name" placeholder="e.g. Dhaka Operations Center" required></label>
                <label>Address Line 1<input name="address_line1"></label>
                <label>Address Line 2<input name="address_line2"></label>
                <label>City<input name="city"></label>
                <label>Region / State<input name="region"></label>
                <label>Postal Code<input name="postal_code"></label>
                <label>Default Currency
                    <select name="default_currency" required>
                        <?php foreach ($currencies as $cur): ?>
                            <option value="<?= e($cur['code']) ?>"><?= e($cur['code']) ?> — <?= e($cur['name']) ?> (<?= e($cur['symbol']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Timezone
                    <select name="timezone" required>
                        <?php foreach ($branchTimezones as $tzVal => $tzLabel): ?>
                            <option value="<?= e($tzVal) ?>"><?= e($tzLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Status
                    <select name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </label>
                <button class="btn primary">Save Branch</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Configured Branches (<?= count($branches) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Branch</th>
                        <th>Location</th>
                        <th>Currency</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        <?php if (can('organization.manage')): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $b): ?>
                        <tr>
                            <td><strong><?= e($b['branch_code']) ?></strong></td>
                            <td><?= e($b['name']) ?></td>
                            <td><?= e(trim(($b['city'] ?? '') . ', ' . $b['country_name'], ', ')) ?></td>
                            <td><?= e($b['default_currency']) ?> (<?= e($b['currency_symbol']) ?>)</td>
                            <td>
                                <?php if (can('organization.manage')): ?>
                                    <form method="post" action="<?= e(url('/organization/' . $b['id'] . '/update')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="country_id" value="<?= e($b['country_id']) ?>">
                                        <input type="hidden" name="branch_code" value="<?= e($b['branch_code']) ?>">
                                        <input type="hidden" name="name" value="<?= e($b['name']) ?>">
                                        <input type="hidden" name="address_line1" value="<?= e($b['address_line1'] ?? '') ?>">
                                        <input type="hidden" name="address_line2" value="<?= e($b['address_line2'] ?? '') ?>">
                                        <input type="hidden" name="city" value="<?= e($b['city'] ?? '') ?>">
                                        <input type="hidden" name="region" value="<?= e($b['region'] ?? '') ?>">
                                        <input type="hidden" name="postal_code" value="<?= e($b['postal_code'] ?? '') ?>">
                                        <input type="hidden" name="default_currency" value="<?= e($b['default_currency']) ?>">
                                        <input type="hidden" name="is_active" value="<?= e((string)$b['is_active']) ?>">
                                        <select name="timezone" onchange="this.form.submit()">
                                            <?php
                                            $currentTz = (string)($b['timezone'] ?? 'UTC');
                                            if (!isset($branchTimezones[$currentTz])):
                                            ?>
                                                <option value="<?= e($currentTz) ?>" selected><?= e($currentTz) ?></option>
                                            <?php endif; ?>
                                            <?php foreach ($branchTimezones as $tzVal => $tzLabel): ?>
                                                <option value="<?= e($tzVal) ?>" <?= $currentTz === $tzVal ? 'selected' : '' ?>><?= e($tzVal) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <?= e($b['timezone']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$b['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                            <?php if (can('organization.manage')): ?>
                                <td>
                                    <form method="post" action="<?= e(url('/organization/' . $b['id'] . '/update')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="country_id" value="<?= e($b['country_id']) ?>">
                                        <input type="hidden" name="branch_code" value="<?= e($b['branch_code']) ?>">
                                        <input type="hidden" name="name" value="<?= e($b['name']) ?>">
                                        <input type="hidden" name="address_line1" value="<?= e($b['address_line1'] ?? '') ?>">
                                        <input type="hidden" name="address_line2" value="<?= e($b['address_line2'] ?? '') ?>">
                                        <input type="hidden" name="city" value="<?= e($b['city'] ?? '') ?>">
                                        <input type="hidden" name="region" value="<?= e($b['region'] ?? '') ?>">
                                        <input type="hidden" name="postal_code" value="<?= e($b['postal_code'] ?? '') ?>">
                                        <input type="hidden" name="default_currency" value="<?= e($b['default_currency']) ?>">
                                        <input type="hidden" name="timezone" value="<?= e($b['timezone']) ?>">
                                        <input type="hidden" name="is_active" value="<?= (int)$b['is_active'] === 1 ? 0 : 1 ?>">
                                        <button class="btn"><?= (int)$b['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
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