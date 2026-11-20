<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var string $q
 * @var string $status
 * @var array<int, array<string, mixed>> $vendors
 */
$flash = $flash ?? null;
$error = $error ?? null;
$q = $q ?? '';
$status = $status ?? '';
$vendors = $vendors ?? [];
?>
<h1>Vendor Directory &amp; Onboarding</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <form method="get" action="<?= e(url('/vendors')) ?>" class="grid three">
        <label>Search Code / Legal Name / Tax ID
            <input name="q" value="<?= e($q) ?>" placeholder="Search vendors...">
        </label>
        <label>Status
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach (['pending', 'approved', 'blocked', 'inactive'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div>
            <button class="btn primary">Filter</button>
            <a href="<?= e(url('/vendors')) ?>" class="btn">Reset</a>
        </div>
    </form>
</section>

<div class="grid two">
    <?php if (can('vendors.create') || can('vendors.manage')): ?>
        <section class="panel">
            <h2>Register New Vendor</h2>
            <form method="post" action="<?= e(url('/vendors')) ?>">
                <?= csrf_field() ?>
                <label>Vendor Code<input name="vendor_code" maxlength="40" placeholder="e.g. VND-DELL-01" required></label>
                <label>Legal Name<input name="legal_name" required></label>
                <label>Tax / VAT Registration No.<input name="tax_registration_no"></label>
                <label>Website<input type="url" name="website" placeholder="https://..."></label>
                <label>Approval Status
                    <select name="status">
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="blocked">Blocked</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label>Notes<textarea name="notes" rows="2"></textarea></label>
                <hr>
                <h3>Primary Contact (Optional)</h3>
                <label>Contact Name<input name="contact_name"></label>
                <label>Contact Email<input type="email" name="contact_email"></label>
                <label>Phone<input name="contact_phone"></label>
                <label>Job Title<input name="contact_title" placeholder="e.g. Enterprise Account Manager"></label>
                <button class="btn primary">Save Vendor</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Vendors (<?= count($vendors) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Vendor</th>
                        <th>Primary Contact</th>
                        <th>POs</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Profile</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $v): ?>
                        <tr>
                            <td><strong><?= e($v['vendor_code']) ?></strong></td>
                            <td>
                                <a href="<?= e(url('/vendors/' . $v['id'])) ?>"><strong><?= e($v['legal_name']) ?></strong></a>
                                <?php if (!empty($v['tax_registration_no'])): ?>
                                    <br><small>Tax ID: <?= e($v['tax_registration_no']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($v['primary_contact'] ?? '—') ?>
                                <?php if (!empty($v['primary_email'])): ?>
                                    <br><small><?= e($v['primary_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string)($v['po_count'] ?? 0)) ?></td>
                            <td><?= $v['avg_rating'] ? e((string)$v['avg_rating']) . ' / 5' : 'Unrated' ?></td>
                            <td><?= e(ucfirst((string)$v['status'])) ?></td>
                            <td><a class="btn" href="<?= e(url('/vendors/' . $v['id'])) ?>">Manage</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>