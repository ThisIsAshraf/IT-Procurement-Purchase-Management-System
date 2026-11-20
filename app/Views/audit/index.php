<?php

/**
 * @var array<int, array<string, mixed>> $logs
 * @var string $q
 */
$logs = $logs ?? [];
$q = $q ?? '';
?>
<h1>System Audit Trail</h1>
<section class="panel">
    <form method="get" action="<?= e(url('/audit-logs')) ?>" class="inline">
        <input name="q" value="<?= e($q) ?>" placeholder="Search action, table, or user...">
        <button class="btn primary">Filter</button>
        <a class="btn" href="<?= e(url('/audit-logs')) ?>">Reset</a>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Record ID</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>#<?= e((string)$log['id']) ?></td>
                        <td><?= e($log['created_at']) ?></td>
                        <td><?= e($log['user_name'] ?? 'System / CLI') ?></td>
                        <td><strong><?= e($log['action']) ?></strong></td>
                        <td><code><?= e($log['entity_type']) ?></code></td>
                        <td><?= e((string)($log['entity_id'] ?? '—')) ?></td>
                        <td><?= e($log['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>