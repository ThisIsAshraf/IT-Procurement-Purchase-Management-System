<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ApprovalNotificationService
{
    /**
     * Notify all eligible approvers (and active delegates) for the current step of an approval instance.
     */
    public static function notifyCurrentStep(
        int $instanceId,
        string $documentLabel,
        string $documentNo,
        string $submittedByName,
        string $reviewUrl
    ): void {
        if ($instanceId <= 0) {
            return;
        }

        $pdo = Database::connection();

        $instStmt = $pdo->prepare(
            'SELECT ai.*, aw.workflow_name, aw.branch_id
             FROM approval_instances ai
             JOIN approval_workflows aw ON aw.id = ai.workflow_id
             WHERE ai.id = ? AND ai.status = "pending"'
        );
        $instStmt->execute([$instanceId]);
        $instance = $instStmt->fetch();
        if (!$instance) {
            return;
        }

        $stepStmt = $pdo->prepare(
            'SELECT * FROM approval_steps WHERE workflow_id = ? AND step_order = ? LIMIT 1'
        );
        $stepStmt->execute([(int)$instance['workflow_id'], (int)$instance['current_step_order']]);
        $step = $stepStmt->fetch();
        if (!$step) {
            return;
        }

        $approvers = self::resolveStepUsers(
            $step['approver_user_id'] !== null ? (int)$step['approver_user_id'] : null,
            $step['approver_role_id'] !== null ? (int)$step['approver_role_id'] : null,
            $instance['branch_id'] !== null ? (int)$instance['branch_id'] : null
        );

        if (empty($approvers)) {
            return;
        }

        $appTitle = SettingsService::get('app_title', 'NSG Procurement');
        $stepName = (string)($step['step_name'] ?? ('Step ' . $instance['current_step_order']));
        $workflowName = (string)($instance['workflow_name'] ?? 'Approval Workflow');

        $tplStmt = $pdo->prepare("SELECT * FROM notification_templates WHERE template_key = 'approval_required' AND is_active = 1 LIMIT 1");
        $tplStmt->execute();
        $customTpl = $tplStmt->fetch() ?: null;

        foreach ($approvers as $u) {
            $email = trim((string)($u['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $approverName = (string)($u['full_name'] ?? 'Approver');

            if ($customTpl) {
                $vars = [
                    '{{app_title}}'      => $appTitle,
                    '{{approver_name}}'  => $approverName,
                    '{{document_type}}'  => $documentLabel,
                    '{{document_no}}'    => $documentNo,
                    '{{submitted_by}}'   => $submittedByName,
                    '{{step_name}}'      => $stepName,
                    '{{workflow_name}}'  => $workflowName,
                    '{{review_url}}'     => $reviewUrl,
                ];
                $subject = strtr((string)$customTpl['subject_template'], $vars);
                $body    = strtr((string)$customTpl['body_template'], $vars);
                $tplId   = (int)$customTpl['id'];
            } else {
                $subject = "[{$appTitle}] Approval Required: {$documentLabel} ({$documentNo})";
                $nameEsc = htmlspecialchars($approverName, ENT_QUOTES, 'UTF-8');
                $docEsc  = htmlspecialchars("{$documentLabel} ({$documentNo})", ENT_QUOTES, 'UTF-8');
                $subEsc  = htmlspecialchars($submittedByName, ENT_QUOTES, 'UTF-8');
                $stepEsc = htmlspecialchars($stepName, ENT_QUOTES, 'UTF-8');
                $wfEsc   = htmlspecialchars($workflowName, ENT_QUOTES, 'UTF-8');
                $urlEsc  = htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8');

                $body = "
                    <div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;\">
                        <div style=\"background:#0f172a;color:#ffffff;padding:16px 20px;\">
                            <strong style=\"font-size:16px;\">{$appTitle}</strong>
                        </div>
                        <div style=\"padding:20px;color:#1e293b;line-height:1.5;\">
                            <p style=\"margin-top:0;\">Hello <strong>{$nameEsc}</strong>,</p>
                            <p><strong>{$subEsc}</strong> has submitted <strong>{$docEsc}</strong> for your approval.</p>
                            <table style=\"width:100%;border-collapse:collapse;margin:16px 0;background:#f8fafc;border-radius:6px;\">
                                <tr><td style=\"padding:8px 12px;color:#64748b;width:140px;\">Document</td><td style=\"padding:8px 12px;font-weight:600;\">{$docEsc}</td></tr>
                                <tr><td style=\"padding:8px 12px;color:#64748b;\">Workflow</td><td style=\"padding:8px 12px;\">{$wfEsc}</td></tr>
                                <tr><td style=\"padding:8px 12px;color:#64748b;\">Approval Step</td><td style=\"padding:8px 12px;\">{$stepEsc}</td></tr>
                            </table>
                            <p style=\"margin:24px 0;\">
                                <a href=\"{$urlEsc}\" style=\"display:inline-block;padding:11px 20px;background:#0284c7;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;\">
                                    Open Approvals Inbox
                                </a>
                            </p>
                        </div>
                    </div>
                ";
                $tplId = null;
            }

            MailService::queue($email, $subject, $body, $tplId, [
                'approval_instance_id' => $instanceId,
                'document_no'          => $documentNo,
                'approver_user_id'     => (int)$u['id'],
            ]);
        }

        try {
            $err = null;
            MailService::sendQueued(15, $err);
        } catch (\Throwable $e) {
            // Remains safely queued in notification_queue for retry
        }
    }

    /**
     * Resolve all active users (plus active out-of-office delegates) eligible for a step.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveStepUsers(?int $userId, ?int $roleId, ?int $branchId): array
    {
        $pdo = Database::connection();
        $users = [];

        if ($userId !== null && $userId > 0) {
            $st = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ? AND status = "active"');
            $st->execute([$userId]);
            $users = $st->fetchAll();
        } elseif ($roleId !== null && $roleId > 0) {
            if ($branchId !== null && $branchId > 0) {
                $st = $pdo->prepare(
                    'SELECT DISTINCT u.id, u.full_name, u.email
                     FROM users u
                     JOIN user_roles ur ON ur.user_id = u.id
                     LEFT JOIN user_branch_scopes ubs ON ubs.user_id = u.id
                     WHERE u.status = "active"
                       AND ur.role_id = ?
                       AND (ubs.branch_id = ? OR NOT EXISTS (SELECT 1 FROM user_branch_scopes x WHERE x.user_id = u.id))'
                );
                $st->execute([$roleId, $branchId]);
            } else {
                $st = $pdo->prepare(
                    'SELECT DISTINCT u.id, u.full_name, u.email
                     FROM users u
                     JOIN user_roles ur ON ur.user_id = u.id
                     WHERE u.status = "active" AND ur.role_id = ?'
                );
                $st->execute([$roleId]);
            }
            $users = $st->fetchAll();
        }

        $byId = [];
        foreach ($users as $u) {
            $byId[(int)$u['id']] = $u;
        }

        if (!empty($byId)) {
            $ids = implode(',', array_map('intval', array_keys($byId)));
            $delSql = "SELECT DISTINCT u.id, u.full_name, u.email
                       FROM approval_delegations ad
                       JOIN users u ON u.id = ad.delegate_user_id
                       WHERE ad.delegator_user_id IN ({$ids})
                         AND ad.starts_at <= NOW()
                         AND ad.ends_at >= NOW()
                         AND u.status = 'active'";
            foreach ($pdo->query($delSql)->fetchAll() as $del) {
                $byId[(int)$del['id']] = $del;
            }
        }

        return array_values($byId);
    }

    /**
     * Fetch pending approval items assigned to a user (for the top-bar notification bell).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pendingItemsForUser(int $userId, int $limit = 8): array
    {
        if ($userId <= 0) {
            return [];
        }

        $pdo = Database::connection();
        $lim = max(1, min(30, $limit));
        $sql = "SELECT DISTINCT
                    ai.id AS instance_id,
                    ai.document_type,
                    ai.document_id,
                    ai.current_step_order,
                    aw.workflow_name,
                    ast.step_name
                FROM approval_instances ai
                JOIN approval_workflows aw ON aw.id = ai.workflow_id
                JOIN approval_steps ast
                  ON ast.workflow_id = ai.workflow_id
                 AND ast.step_order = ai.current_step_order
                LEFT JOIN user_branch_scopes ubs ON ubs.user_id = :uid_scope
                WHERE ai.status = 'pending'
                  AND (
                      aw.branch_id IS NULL
                      OR ubs.branch_id = aw.branch_id
                      OR NOT EXISTS (SELECT 1 FROM user_branch_scopes x WHERE x.user_id = :uid_scope2)
                  )
                  AND (
                      ast.approver_user_id = :uid1
                      OR ast.approver_role_id IN (SELECT role_id FROM user_roles WHERE user_id = :uid2)
                      OR ast.approver_user_id IN (
                          SELECT delegator_user_id FROM approval_delegations
                          WHERE delegate_user_id = :uid3 AND starts_at <= NOW() AND ends_at >= NOW()
                      )
                  )
                ORDER BY ai.id DESC
                LIMIT {$lim}";

        $st = $pdo->prepare($sql);
        $st->execute([
            'uid_scope'  => $userId,
            'uid_scope2' => $userId,
            'uid1'       => $userId,
            'uid2'       => $userId,
            'uid3'       => $userId,
        ]);

        return $st->fetchAll();
    }

    /**
     * Count total pending approvals for a user.
     */
    public static function pendingCountForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $pdo = Database::connection();
        $sql = "SELECT COUNT(DISTINCT ai.id)
                FROM approval_instances ai
                JOIN approval_workflows aw ON aw.id = ai.workflow_id
                JOIN approval_steps ast
                  ON ast.workflow_id = ai.workflow_id
                 AND ast.step_order = ai.current_step_order
                LEFT JOIN user_branch_scopes ubs ON ubs.user_id = :uid_scope
                WHERE ai.status = 'pending'
                  AND (
                      aw.branch_id IS NULL
                      OR ubs.branch_id = aw.branch_id
                      OR NOT EXISTS (SELECT 1 FROM user_branch_scopes x WHERE x.user_id = :uid_scope2)
                  )
                  AND (
                      ast.approver_user_id = :uid1
                      OR ast.approver_role_id IN (SELECT role_id FROM user_roles WHERE user_id = :uid2)
                      OR ast.approver_user_id IN (
                          SELECT delegator_user_id FROM approval_delegations
                          WHERE delegate_user_id = :uid3 AND starts_at <= NOW() AND ends_at >= NOW()
                      )
                  )";

        $st = $pdo->prepare($sql);
        $st->execute([
            'uid_scope'  => $userId,
            'uid_scope2' => $userId,
            'uid1'       => $userId,
            'uid2'       => $userId,
            'uid3'       => $userId,
        ]);

        return (int)$st->fetchColumn();
    }
}
