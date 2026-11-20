<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\ApprovalWorkflowService;

final class ApprovalController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('approvals.view');

        $pdo = Database::connection();
        $userId = (int)current_user()['id'];
        $wfService = new ApprovalWorkflowService();

        $instances = $pdo->query(
            "SELECT ai.*, aw.workflow_name, u.full_name AS initiated_by_name,
                    s.id AS step_id, s.step_name, s.approver_role_id, s.approver_user_id,
                    r.name AS step_role_name, au.full_name AS step_user_name
             FROM approval_instances ai
             JOIN approval_workflows aw ON aw.id = ai.workflow_id
             JOIN users u ON u.id = ai.initiated_by
             LEFT JOIN approval_steps s ON s.workflow_id = ai.workflow_id AND s.step_order = ai.current_step_order
             LEFT JOIN roles r ON r.id = s.approver_role_id
             LEFT JOIN users au ON au.id = s.approver_user_id
             ORDER BY (ai.status = 'pending') DESC, ai.id DESC
             LIMIT 200"
        )->fetchAll();

        foreach ($instances as &$inst) {
            $inst['can_act'] = ($inst['status'] === 'pending' && !empty($inst['step_id']))
                ? $wfService->canUserActOnStep($userId, $inst)
                : false;
        }
        unset($inst);

        $delegations = $pdo->prepare(
            'SELECT ad.*, d1.full_name AS delegator_name, d2.full_name AS delegate_name
             FROM approval_delegations ad
             JOIN users d1 ON d1.id = ad.delegator_user_id
             JOIN users d2 ON d2.id = ad.delegate_user_id
             WHERE ad.delegator_user_id = ? OR ad.delegate_user_id = ?
             ORDER BY ad.starts_at DESC'
        );
        $delegations->execute([$userId, $userId]);

        $this->view('approvals/index', [
            'instances'   => $instances,
            'delegations' => $delegations->fetchAll(),
            'users'       => $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name")->fetchAll(),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function act(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('approvals.act');

        $instanceId = (int)$r->input('instance_id', 0);
        $decision = (string)$r->input('decision', '');
        $comments = trim((string)$r->input('comments', ''));

        try {
            (new ApprovalWorkflowService())->act($instanceId, (int)current_user()['id'], $decision, $comments);
            flash('success', 'Approval decision recorded: ' . ucfirst($decision) . '.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/approvals');
    }

    public function storeDelegation(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('approvals.act');

        $delegatorId = (int)current_user()['id'];
        $delegateId = (int)$r->input('delegate_user_id', 0);
        $startsAt = trim((string)$r->input('starts_at', ''));
        $endsAt = trim((string)$r->input('ends_at', ''));
        $reason = trim((string)$r->input('reason', ''));

        if ($delegateId <= 0 || $delegateId === $delegatorId || $startsAt === '' || $endsAt === '') {
            flash('error', 'Choose a valid colleague and date range for approval delegation.');
            redirect('/approvals');
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO approval_delegations (delegator_user_id, delegate_user_id, starts_at, ends_at, reason)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$delegatorId, $delegateId, $startsAt, $endsAt, $reason ?: null]);
            \audit_log('approval_delegated', 'approval_delegations', null, null, ['delegate_user_id' => $delegateId]);
            flash('success', 'Approval delegation saved.');
        } catch (\Throwable $e) {
            flash('error', 'Could not save delegation: ' . $e->getMessage());
        }
        redirect('/approvals');
    }
}
