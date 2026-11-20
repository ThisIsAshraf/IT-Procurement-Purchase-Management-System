<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ApprovalWorkflowService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Finds the highest-specificity active workflow matching document type, branch, department, and amount.
     */
    public function matchWorkflow(
        string $documentType,
        ?int $branchId = null,
        ?int $departmentId = null,
        ?float $amount = null,
        ?string $currencyCode = null
    ): ?array {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM approval_workflows
             WHERE document_type = ? AND is_active = 1
             ORDER BY
               (branch_id IS NOT NULL) DESC,
               (department_id IS NOT NULL) DESC,
               (min_amount IS NOT NULL) DESC,
               id DESC'
        );
        $stmt->execute([$documentType]);

        foreach ($stmt->fetchAll() as $wf) {
            if ($wf['branch_id'] !== null && (int)$wf['branch_id'] !== (int)$branchId) {
                continue;
            }
            if ($wf['department_id'] !== null && (int)$wf['department_id'] !== (int)$departmentId) {
                continue;
            }
            if ($wf['currency_code'] !== null && $currencyCode !== null && strtoupper((string)$wf['currency_code']) !== strtoupper($currencyCode)) {
                continue;
            }
            if ($amount !== null) {
                if ($wf['min_amount'] !== null && $amount < (float)$wf['min_amount']) {
                    continue;
                }
                if ($wf['max_amount'] !== null && $amount > (float)$wf['max_amount']) {
                    continue;
                }
            }
            return $wf;
        }
        return null;
    }

    public function initiate(
        string $documentType,
        int $documentId,
        int $initiatedBy,
        ?int $branchId = null,
        ?int $departmentId = null,
        ?float $amount = null,
        ?string $currencyCode = null
    ): ?int {
        $wf = $this->matchWorkflow($documentType, $branchId, $departmentId, $amount, $currencyCode);
        if (!$wf) {
            return null;
        }

        $firstStep = $this->db->prepare(
            'SELECT MIN(step_order) FROM approval_steps WHERE workflow_id = ?'
        );
        $firstStep->execute([(int)$wf['id']]);
        $startOrder = (int)($firstStep->fetchColumn() ?: 1);

        $ins = $this->db->prepare(
            "INSERT INTO approval_instances
             (workflow_id, document_type, document_id, current_step_order, status, initiated_by)
             VALUES (?, ?, ?, ?, 'pending', ?)"
        );
        $ins->execute([(int)$wf['id'], $documentType, $documentId, $startOrder, $initiatedBy]);
        $instanceId = (int)$this->db->lastInsertId();

        \audit_log('approval_initiated', 'approval_instances', $instanceId, null, [
            'workflow_id'   => $wf['id'],
            'document_type' => $documentType,
            'document_id'   => $documentId,
        ]);
        return $instanceId;
    }

    public function act(int $instanceId, int $actorUserId, string $decision, string $comments = ''): void
    {
        $allowed = ['approved', 'rejected', 'returned'];
        if (!in_array($decision, $allowed, true)) {
            throw new \RuntimeException('Invalid approval decision.');
        }

        $instStmt = $this->db->prepare(
            "SELECT ai.*, aw.workflow_name
             FROM approval_instances ai
             JOIN approval_workflows aw ON aw.id = ai.workflow_id
             WHERE ai.id = ?
             LIMIT 1"
        );
        $instStmt->execute([$instanceId]);
        $inst = $instStmt->fetch();

        if (!$inst || $inst['status'] !== 'pending') {
            throw new \RuntimeException('Approval instance is not pending.');
        }

        $stepStmt = $this->db->prepare(
            'SELECT * FROM approval_steps WHERE workflow_id = ? AND step_order = ? LIMIT 1'
        );
        $stepStmt->execute([(int)$inst['workflow_id'], (int)$inst['current_step_order']]);
        $step = $stepStmt->fetch();

        if (!$step) {
            throw new \RuntimeException('Current approval step configuration is missing.');
        }

        if (!$this->canUserActOnStep($actorUserId, $step)) {
            throw new \RuntimeException('You are not an authorized approver or active delegate for this step.');
        }

        $this->db->beginTransaction();
        try {
            $actIns = $this->db->prepare(
                'INSERT INTO approval_actions (approval_instance_id, approval_step_id, actor_user_id, decision, comments)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $actIns->execute([$instanceId, (int)$step['id'], $actorUserId, $decision, $comments !== '' ? $comments : null]);

            if ($decision === 'rejected' || $decision === 'returned') {
                $newInstStatus = $decision === 'rejected' ? 'rejected' : 'cancelled';
                $this->db->prepare('UPDATE approval_instances SET status = ?, completed_at = NOW() WHERE id = ?')
                    ->execute([$newInstStatus, $instanceId]);
                $this->syncDocumentAfterDecision((string)$inst['document_type'], (int)$inst['document_id'], $decision);
            } else {
                // Check if there is a higher step_order
                $nextStmt = $this->db->prepare(
                    'SELECT MIN(step_order) FROM approval_steps WHERE workflow_id = ? AND step_order > ?'
                );
                $nextStmt->execute([(int)$inst['workflow_id'], (int)$inst['current_step_order']]);
                $nextOrder = $nextStmt->fetchColumn();

                if ($nextOrder) {
                    $this->db->prepare('UPDATE approval_instances SET current_step_order = ? WHERE id = ?')
                        ->execute([(int)$nextOrder, $instanceId]);
                } else {
                    $this->db->prepare("UPDATE approval_instances SET status = 'approved', completed_at = NOW() WHERE id = ?")
                        ->execute([$instanceId]);
                    $this->syncDocumentAfterDecision((string)$inst['document_type'], (int)$inst['document_id'], 'approved');
                }
            }

            $this->db->commit();
            \audit_log('approval_' . $decision, 'approval_instances', $instanceId, null, [
                'document_type' => $inst['document_type'],
                'document_id'   => $inst['document_id'],
                'comments'      => $comments,
            ]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function canUserActOnStep(int $userId, array $step): bool
    {
        if (has_role('super_admin')) {
            return true;
        }

        // Collect user ID + any delegators who currently delegated to this user
        $delStmt = $this->db->prepare(
            'SELECT delegator_user_id FROM approval_delegations
             WHERE delegate_user_id = ? AND starts_at <= NOW() AND ends_at >= NOW()'
        );
        $delStmt->execute([$userId]);
        $effectiveUserIds = array_merge([$userId], array_map('intval', $delStmt->fetchAll(PDO::FETCH_COLUMN)));

        if (!empty($step['approver_user_id']) && in_array((int)$step['approver_user_id'], $effectiveUserIds, true)) {
            return true;
        }

        if (!empty($step['approver_role_id'])) {
            $placeholders = implode(',', array_fill(0, count($effectiveUserIds), '?'));
            $params = array_merge([(int)$step['approver_role_id']], $effectiveUserIds);
            $rStmt = $this->db->prepare(
                "SELECT 1 FROM user_roles WHERE role_id = ? AND user_id IN ({$placeholders}) LIMIT 1"
            );
            $rStmt->execute($params);
            if ($rStmt->fetchColumn()) {
                return true;
            }
        }

        return false;
    }

    private function syncDocumentAfterDecision(string $documentType, int $documentId, string $decision): void
    {
        if ($documentType === 'purchase_order') {
            $status = match ($decision) {
                'approved' => 'approved',
                'rejected' => 'cancelled',
                default    => 'draft',
            };
            $this->db->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?')->execute([$status, $documentId]);
        } elseif ($documentType === 'purchase_request') {
            $status = match ($decision) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default    => 'draft',
            };
            $this->db->prepare('UPDATE purchase_requests SET status = ? WHERE id = ?')->execute([$status, $documentId]);
        } elseif ($documentType === 'requisition') {
            $status = match ($decision) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default    => 'sourcing',
            };
            $this->db->prepare('UPDATE requisitions SET status = ? WHERE id = ?')->execute([$status, $documentId]);
        } elseif ($documentType === 'invoice') {
            $status = match ($decision) {
                'approved' => 'approved',
                'rejected' => 'disputed',
                default    => 'under_review',
            };
            $this->db->prepare('UPDATE invoices SET status = ? WHERE id = ?')->execute([$status, $documentId]);
        }
    }
}
