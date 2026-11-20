<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\UserModel;

final class RoleController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('roles.manage');

        $rbac = (new UserModel())->rolesWithPermissions();
        $this->view('users/roles', [
            'roles'              => $rbac['roles'],
            'groupedPermissions' => $rbac['grouped_permissions'],
            'assigned'           => $rbac['assigned'],
            'flash'              => flash('success'),
            'error'              => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('roles.manage');

        $key = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)$r->input('role_key'))));
        $name = trim((string)$r->input('name'));
        $desc = trim((string)$r->input('description'));

        $v = (new Validator())
            ->required('role_key', $key, 'Role key')
            ->required('name', $name, 'Role name');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/roles');
        }

        try {
            $id = (new UserModel())->createRole((string)$key, $name, $desc);
            \audit_log('role_created', 'roles', $id, null, ['role_key' => $key, 'name' => $name]);
            flash('success', 'Custom role created.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create role: ' . $e->getMessage());
        }
        redirect('/roles');
    }

    public function updatePermissions(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('roles.manage');

        $roleId = (int)($r->params()['id'] ?? 0);
        $permissionIds = (array)$r->input('permission_ids', []);

        try {
            (new UserModel())->syncRolePermissions($roleId, $permissionIds);
            \audit_log('role_permissions_updated', 'roles', $roleId, null, ['permission_ids' => $permissionIds]);
            flash('success', 'Role permissions updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update role permissions: ' . $e->getMessage());
        }
        redirect('/roles');
    }
}
