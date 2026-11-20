<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;
use App\Services\BranchScopeService;

final class EmployeeController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('organization.view');
        $model = new OrganizationModel();
        $employees = $model->employees();

        $this->view('organization/employees', [
            'employees'   => $employees,
            'branches'    => $model->branches(true),
            'departments' => $model->departments(),
            'flash'       => flash('success'),
            'error'       => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('employees.manage');

        $branchId = (int)$r->input('branch_id');
        BranchScopeService::assertBranchAccess($branchId);

        $data = [
            'employee_number'     => trim((string)$r->input('employee_number')),
            'full_name'           => trim((string)$r->input('full_name')),
            'email'               => trim((string)$r->input('email')),
            'branch_id'           => $branchId,
            'department_id'       => (int)$r->input('department_id'),
            'manager_employee_id' => (int)$r->input('manager_employee_id') ?: null,
            'job_title'           => trim((string)$r->input('job_title')),
            'employment_status'   => (string)$r->input('employment_status', 'active'),
        ];

        $v = (new Validator())
            ->required('full_name', $data['full_name'], 'Full name')
            ->required('email', $data['email'], 'Email')
            ->email('email', $data['email'], 'Email')
            ->positiveNumber('branch_id', $data['branch_id'], 'Branch')
            ->positiveNumber('department_id', $data['department_id'], 'Department')
            ->inList('employment_status', $data['employment_status'], ['active', 'inactive', 'terminated'], 'Status');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/employees');
        }

        try {
            $id = (new OrganizationModel())->createEmployee($data);
            \audit_log('employee_created', 'employees', $id, null, $data);
            flash('success', 'Employee added.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create employee: ' . $e->getMessage());
        }
        redirect('/employees');
    }

    public function update(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('employees.manage');

        $id = (int)($r->params()['id'] ?? 0);
        $branchId = (int)$r->input('branch_id');
        BranchScopeService::assertBranchAccess($branchId);

        $data = [
            'employee_number'     => trim((string)$r->input('employee_number')),
            'full_name'           => trim((string)$r->input('full_name')),
            'email'               => trim((string)$r->input('email')),
            'branch_id'           => $branchId,
            'department_id'       => (int)$r->input('department_id'),
            'manager_employee_id' => (int)$r->input('manager_employee_id') ?: null,
            'job_title'           => trim((string)$r->input('job_title')),
            'employment_status'   => (string)$r->input('employment_status', 'active'),
        ];

        try {
            (new OrganizationModel())->updateEmployee($id, $data);
            \audit_log('employee_updated', 'employees', $id, null, $data);
            flash('success', 'Employee updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update employee: ' . $e->getMessage());
        }
        redirect('/employees');
    }
}
