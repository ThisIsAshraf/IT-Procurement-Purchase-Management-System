<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $settings
 * @var array<int, array<string, mixed>> $templates
 * @var array<int, array<string, mixed>> $queue
 */
$flash = $flash ?? null;
$error = $error ?? null;
$settings = $settings ?? [];
$templates = $templates ?? [];
$queue = $queue ?? [];
?>
<h1>System Settings, Email Templates &amp; Notification Queue</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="grid two">
    <section class="panel">
        <h2>System Configuration</h2>
        <form method="post" action="<?= e(url('/settings')) ?>">
            <?= csrf_field() ?>
            <label>Setting Key<input name="setting_key" placeholder="e.g. default_base_currency" required></label>
            <label>Setting Value<input name="setting_value" placeholder="e.g. USD" required></label>
            <button class="btn primary">Save Setting</button>
        </form>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $s): ?>
                        <tr>
                            <td><code><?= e($s['setting_key']) ?></code></td>
                            <td><?= e($s['setting_value'] ?? '') ?></td>
                            <td><?= e($s['updated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>SMTP Notification Queue (<?= count($queue) ?>)</h2>
        <form method="post" action="<?= e(url('/settings/dispatch-queue')) ?>">
            <?= csrf_field() ?>
            <button class="btn primary">Send Queued Emails Now</button>
        </form>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Attempts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue as $q): ?>
                        <tr>
                            <td>#<?= e((string)$q['id']) ?></td>
                            <td><?= e($q['recipient_email']) ?></td>
                            <td><?= e($q['subject']) ?></td>
                            <td><?= e($q['status']) ?></td>
                            <td><?= e((string)$q['attempts']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="panel">
    <h2>Email Notification Templates</h2>
    <form method="post" action="<?= e(url('/settings/templates')) ?>" class="grid two">
        <?= csrf_field() ?>
        <label>Template Key<input name="template_key" placeholder="e.g. po_dispatched" required></label>
        <label>Subject Template<input name="subject" placeholder="Purchase Order {{po_number}}" required></label>
        <label>Body HTML Template<textarea name="body_html" rows="3" required></textarea></label>
        <div><button class="btn primary">Save Email Template</button></div>
    </form>
    <?php if (!empty($templates)): ?>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Subject</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><code><?= e($t['template_key']) ?></code></td>
                            <td><?= e($t['subject_template']) ?></td>
                            <td><?= (int)$t['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>