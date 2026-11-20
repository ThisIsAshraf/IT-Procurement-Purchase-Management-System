<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;
use App\Services\CodeGeneratorService;

final class DepartmentController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('organization.view');
        $model = new OrganizationModel();

        $this->view('organization/departments', [
            'departments' => $model->departments(),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('organization.manage');

        $name = trim((string)$r->input('name', ''));
        $code = strtoupper(trim((string)$r->input('department_code', '')));
        $isActive = (int)$r->input('is_active', 1) === 1 ? 1 : 0;

        if ($name === '') {
            flash('error', 'Department name is required.');
            redirect('/departments');
        }

        if ($code === '') {
            $code = CodeGeneratorService::nextDepartmentCode($name);
        }

        $v = (new Validator())
            ->required('department_code', $code, 'Department code')
            ->required('name', $name, 'Department name');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/departments');
        }

        try {
            $id = (new OrganizationModel())->createDepartment($code, $name, $isActive);
            \audit_log('department_created', 'departments', $id, null, [
                'department_code' => $code,
                'name'            => $name,
                'is_active'       => $isActive,
            ]);
            flash('success', "Department '{$name}' created with code: {$code}");
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
        $name = trim((string)$r->input('name', ''));
        $code = strtoupper(trim((string)$r->input('department_code', '')));
        $isActive = (int)$r->input('is_active', 1) === 1 ? 1 : 0;

        if ($code === '' && $name !== '') {
            $code = CodeGeneratorService::nextDepartmentCode($name);
        }

        try {
            (new OrganizationModel())->updateDepartment($id, $code, $name, $isActive);
            \audit_log('department_updated', 'departments', $id, null, [
                'department_code' => $code,
                'name'            => $name,
                'is_active'       => $isActive,
            ]);
            flash('success', 'Department updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update department: ' . $e->getMessage());
        }
        redirect('/departments');
    }
}
