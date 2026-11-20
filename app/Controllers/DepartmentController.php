<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;

final class DepartmentController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('organization.view');
        $this->view('organization/departments', [
            'departments' => (new OrganizationModel())->departments(),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('organization.manage');

        $code = trim((string)$r->input('department_code'));
        $name = trim((string)$r->input('name'));
        $isActive = (int)$r->input('is_active', 1) === 1 ? 1 : 0;

        $v = (new Validator())
            ->required('department_code', $code, 'Department code')
            ->maxLength('department_code', $code, 30, 'Department code')
            ->required('name', $name, 'Department name');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/departments');
        }

        try {
            $id = (new OrganizationModel())->createDepartment($code, $name, $isActive);
            \audit_log('department_created', 'departments', $id, null, ['code' => $code, 'name' => $name]);
            flash('success', 'Department created.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create department: ' . $e->getMessage());
        }
        redirect('/departments');
    }

    public function update(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('organization.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $code = trim((string)$r->input('department_code'));
        $name = trim((string)$r->input('name'));
        $isActive = (int)$r->input('is_active', 1) === 1 ? 1 : 0;

        try {
            (new OrganizationModel())->updateDepartment($id, $code, $name, $isActive);
            \audit_log('department_updated', 'departments', $id, null, ['code' => $code, 'name' => $name, 'is_active' => $isActive]);
            flash('success', 'Department updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update department: ' . $e->getMessage());
        }
        redirect('/departments');
    }
}
