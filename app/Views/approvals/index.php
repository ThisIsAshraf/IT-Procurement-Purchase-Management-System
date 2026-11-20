<?php

/**
 * @var string|null $flash
 * @var string|null $error
 * @var array<int, array<string, mixed>> $instances
 * @var array<int, array<string, mixed>> $delegations
 * @var array<int, array<string, mixed>> $users
 */
$flash = $flash ?? null;
$error = $error ?? null;
$instances = $instances ?? [];
$delegations = $delegations ?? [];
$users = $users ?? [];
?>
<h1>Approval Inbox &amp; Delegations</h1>
<?php if ($flash): ?><div class="alert success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
    <h2>Approval Queue &amp; Decision History</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Workflow</th>
                    <th>Document</th>
                    <th>Current Step</th>
                    <th>Initiated By</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instances as $row): ?>
                    <tr>
                        <td><?= e((string)$row['id']) ?></td>
                        <td><?= e($row['workflow_name']) ?></td>
                        <td>
                            <?php if ($row['document_type'] === 'purchase_order'): ?>
                                <a href="<?= e(url('/purchase_orders/' . $row['document_id'])) ?>">PO #<?= e((string)$row['document_id']) ?></a>
                            <?php elseif ($row['document_type'] === 'purchase_request'): ?>
                                <a href="<?= e(url('/purchase_requests/' . $row['document_id'])) ?>">PR #<?= e((string)$row['document_id']) ?></a>
                            <?php else: ?>
                                <?= e($row['document_type'] . ' #' . $row['document_id']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            Step <?= e((string)$row['current_step_order']) ?>: <?= e($row['step_name'] ?? '—') ?><br>
                            <small><?= e($row['step_role_name'] ?? $row['step_user_name'] ?? '') ?></small>
                        </td>
                        <td><?= e($row['initiated_by_name']) ?></td>
                        <td><strong><?= e(ucfirst((string)$row['status'])) ?></strong></td>
                        <td>
                            <?php if (!empty($row['can_act']) && can('approvals.act')): ?>
                                <form method="post" action="<?= e(url('/approvals/act')) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="instance_id" value="<?= e($row['id']) ?>">
                                    <select name="decision">
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                        <option value="returned">Return</option>
                                    </select>
                                    <input name="comments" placeholder="Optional note">
                                    <button class="btn primary">Submit</button>
                                </form>
                            <?php elseif ($row['status'] === 'pending'): ?>
                                <em>Awaiting assigned approver</em>
                            <?php else: ?>
                                <?= e($row['completed_at'] ?? 'Completed') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (can('approvals.act')): ?>
    <section class="panel">
        <h2>Out-of-Office Approval Delegation</h2>
        <form method="post" action="<?= e(url('/approvals/delegations')) ?>" class="grid three">
            <?= csrf_field() ?>
            <label>Delegate To Colleague *
                <select name="delegate_user_id" required>
                    <option value="">Select colleague</option>
                    <?php foreach ($users as $u): ?>
                        <?php if ((int)$u['id'] !== (int)(current_user()['id'] ?? 0)): ?>
                            <option value="<?= e($u['id']) ?>"><?= e($u['full_name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Starts At *<input type="datetime-local" name="starts_at" required></label>
            <label>Ends At *<input type="datetime-local" name="ends_at" required></label>
            <label>Reason<input name="reason" placeholder="e.g. Annual leave / Travel"></label>
            <div><button class="btn primary">Save Delegation</button></div>
        </form>

        <?php if (!empty($delegations)): ?>
            <hr>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Delegator</th>
                            <th>Delegate</th>
                            <th>From</th>
                            <th>Until</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delegations as $del): ?>
                            <tr>
                                <td><?= e($del['delegator_name']) ?></td>
                                <td><?= e($del['delegate_name']) ?></td>
                                <td><?= e($del['starts_at']) ?></td>
                                <td><?= e($del['ends_at']) ?></td>
                                <td><?= e($del['reason'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>