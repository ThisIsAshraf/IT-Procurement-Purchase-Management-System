<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Models\OrganizationModel;

final class ApprovalWorkflowController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('settings.manage');

        $pdo = Database::connection();
        $workflows = $pdo->query(
            'SELECT w.*, b.branch_code, d.name AS department_name
             FROM approval_workflows w
             LEFT JOIN branches b ON b.id = w.branch_id
             LEFT JOIN departments d ON d.id = w.department_id
             ORDER BY w.document_type ASC, w.id DESC'
        )->fetchAll();

        $steps = $pdo->query(
            'SELECT s.*, r.name AS role_name, u.full_name AS user_name
             FROM approval_steps s
             LEFT JOIN roles r ON r.id = s.approver_role_id
             LEFT JOIN users u ON u.id = s.approver_user_id
             ORDER BY s.workflow_id ASC, s.step_order ASC'
        )->fetchAll();

        $stepsByWf = [];
        foreach ($steps as $s) {
            $stepsByWf[(int)$s['workflow_id']][] = $s;
        }

        $org = new OrganizationModel();
        $this->view('approvals/workflows', [
            'workflows'   => $workflows,
            'stepsByWf'   => $stepsByWf,
            'roles'       => $pdo->query('SELECT id, name FROM roles WHERE is_active = 1 ORDER BY name')->fetchAll(),
            'users'       => $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name")->fetchAll(),
            'branches'    => $org->branches(false),
            'departments' => $org->departments(),
            'currencies'  => $org->activeCurrencies(),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $name = trim((string)$r->input('workflow_name'));
        $type = (string)$r->input('document_type');
        $stepName = trim((string)$r->input('step_name'));
        $roleId = (int)$r->input('approver_role_id') ?: null;
        $userId = (int)$r->input('approver_user_id') ?: null;

        $allowed = ['purchase_request', 'requisition', 'quotation_comparison', 'purchase_order', 'invoice', 'payment'];
        if ($name === '' || $stepName === '' || !in_array($type, $allowed, true) || (!$roleId && !$userId)) {
            flash('error', 'Provide workflow name, document type, step name, and an approver role or user.');
            redirect('/approval-workflows');
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO approval_workflows
                 (workflow_name, document_type, branch_id, department_id, min_amount, max_amount, currency_code, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
            )->execute([
                $name,
                $type,
                (int)$r->input('branch_id') ?: null,
                (int)$r->input('department_id') ?: null,
                $r->input('min_amount') !== '' ? (float)$r->input('min_amount') : null,
                $r->input('max_amount') !== '' ? (float)$r->input('max_amount') : null,
                trim((string)$r->input('currency_code')) ?: null,
            ]);
            $wid = (int)$pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO approval_steps (workflow_id, step_order, approver_role_id, approver_user_id, approval_mode, step_name)
                 VALUES (?, 1, ?, ?, 'any', ?)"
            )->execute([$wid, $roleId, $userId, $stepName]);

            $pdo->commit();
            \audit_log('approval_workflow_created', 'approval_workflows', $wid);
            flash('success', 'Approval workflow created.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
        }
        redirect('/approval-workflows');
    }

    public function addStep(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $workflowId = (int)($r->params()['id'] ?? 0);
        $stepOrder = max(1, (int)$r->input('step_order', 2));
        $stepName = trim((string)$r->input('step_name'));
        $roleId = (int)$r->input('approver_role_id') ?: null;
        $userId = (int)$r->input('approver_user_id') ?: null;

        if ($stepName === '' || (!$roleId && !$userId)) {
            flash('error', 'Step name and an approver role or user are required.');
            redirect('/approval-workflows');
        }

        try {
            Database::connection()->prepare(
                "INSERT INTO approval_steps (workflow_id, step_order, approver_role_id, approver_user_id, approval_mode, step_name)
                 VALUES (?, ?, ?, ?, 'any', ?)"
            )->execute([$workflowId, $stepOrder, $roleId, $userId, $stepName]);
            \audit_log('approval_step_added', 'approval_workflows', $workflowId);
            flash('success', 'Approval step added.');
        } catch (\Throwable $e) {
            flash('error', 'Could not add step: ' . $e->getMessage());
        }
        redirect('/approval-workflows');
    }
}
