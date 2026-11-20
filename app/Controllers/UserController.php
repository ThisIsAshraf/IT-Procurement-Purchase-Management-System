<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;
use App\Models\UserModel;

final class UserController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('users.manage');

        $userModel = new UserModel();
        $orgModel = new OrganizationModel();
        $rbac = $userModel->rolesWithPermissions();

        $this->view('users/index', [
            'users'             => $userModel->allWithRolesAndScopes(),
            'roles'             => $rbac['roles'],
            'branches'          => $orgModel->branches(false),
            'unlinkedEmployees' => $userModel->unlinkedEmployees(),
            'flash'             => flash('success'),
            'error'             => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('users.manage');

        $name = trim((string)$r->input('full_name'));
        $email = trim((string)$r->input('email'));
        $password = (string)$r->input('password');
        $employeeId = (int)$r->input('employee_id') ?: null;
        $roleIds = (array)$r->input('role_ids', []);
        $branchIds = (array)$r->input('branch_ids', []);

        $v = (new Validator())
            ->required('full_name', $name, 'Full name')
            ->required('email', $email, 'Email')
            ->email('email', $email, 'Email')
            ->required('password', $password, 'Password')
            ->minLength('password', $password, 12, 'Password');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/users');
        }
        if (empty($roleIds)) {
            flash('error', 'Select at least one role for the user.');
            redirect('/users');
        }

        try {
            $id = (new UserModel())->createUser(
                ['employee_id' => $employeeId, 'full_name' => $name, 'email' => $email, 'password' => $password, 'status' => 'active'],
                $roleIds,
                $branchIds,
                (int)current_user()['id']
            );
            \audit_log('user_created', 'users', $id, null, ['email' => $email, 'roles' => $roleIds, 'branches' => $branchIds]);
            flash('success', 'User account created with assigned roles and branch scopes.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create user: ' . $e->getMessage());
        }
        redirect('/users');
    }

    public function update(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('users.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $name = trim((string)$r->input('full_name'));
        $email = trim((string)$r->input('email'));
        $status = (string)$r->input('status', 'active');
        $password = (string)$r->input('password', '');
        $roleIds = (array)$r->input('role_ids', []);
        $branchIds = (array)$r->input('branch_ids', []);

        $v = (new Validator())
            ->required('full_name', $name, 'Full name')
            ->required('email', $email, 'Email')
            ->email('email', $email, 'Email')
            ->inList('status', $status, ['active', 'inactive', 'locked'], 'Status');

        if ($password !== '') {
            $v->minLength('password', $password, 12, 'New password');
        }
        if ($v->fails() || empty($roleIds)) {
            flash('error', $v->firstError() ?? 'At least one role is required.');
            redirect('/users');
        }

        try {
            (new UserModel())->updateUser(
                $id,
                ['full_name' => $name, 'email' => $email, 'status' => $status, 'password' => $password],
                $roleIds,
                $branchIds,
                (int)current_user()['id']
            );
            \audit_log('user_updated', 'users', $id, null, ['status' => $status, 'roles' => $roleIds, 'branches' => $branchIds]);
            flash('success', 'User updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update user: ' . $e->getMessage());
        }
        redirect('/users');
    }
}
