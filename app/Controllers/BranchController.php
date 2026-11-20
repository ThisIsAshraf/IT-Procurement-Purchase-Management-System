<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;
use App\Services\BranchScopeService;

final class BranchController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('organization.view');
        $model = new OrganizationModel();

        $this->view('organization/branches', [
            'branches'   => $model->branches(true),
            'countries'  => $model->countries(),
            'currencies' => $model->activeCurrencies(),
            'flash'      => flash('success'),
            'error'      => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('organization.manage');

        $data = [
            'country_id'       => (int)$r->input('country_id'),
            'branch_code'      => trim((string)$r->input('branch_code')),
            'name'             => trim((string)$r->input('name')),
            'address_line1'    => trim((string)$r->input('address_line1')),
            'address_line2'    => trim((string)$r->input('address_line2')),
            'city'             => trim((string)$r->input('city')),
            'region'           => trim((string)$r->input('region')),
            'postal_code'      => trim((string)$r->input('postal_code')),
            'default_currency' => trim((string)$r->input('default_currency')),
            'timezone'         => trim((string)$r->input('timezone', 'UTC')),
            'is_active'        => (int)$r->input('is_active', 1) === 1 ? 1 : 0,
        ];

        $v = (new Validator())
            ->positiveNumber('country_id', $data['country_id'], 'Country')
            ->required('branch_code', $data['branch_code'], 'Branch code')
            ->maxLength('branch_code', $data['branch_code'], 20, 'Branch code')
            ->required('name', $data['name'], 'Branch name')
            ->required('default_currency', $data['default_currency'], 'Default currency');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/organization');
        }

        try {
            $id = (new OrganizationModel())->createBranch($data);
            \audit_log('branch_created', 'branches', $id, null, $data);
            flash('success', 'Branch created successfully.');
        } catch (\Throwable $e) {
            flash('error', 'Could not create branch: ' . $e->getMessage());
        }
        redirect('/organization');
    }

    public function update(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('organization.manage');

        $id = (int)($r->params()['id'] ?? 0);
        BranchScopeService::assertBranchAccess($id);

        $data = [
            'country_id'       => (int)$r->input('country_id'),
            'branch_code'      => trim((string)$r->input('branch_code')),
            'name'             => trim((string)$r->input('name')),
            'address_line1'    => trim((string)$r->input('address_line1')),
            'address_line2'    => trim((string)$r->input('address_line2')),
            'city'             => trim((string)$r->input('city')),
            'region'           => trim((string)$r->input('region')),
            'postal_code'      => trim((string)$r->input('postal_code')),
            'default_currency' => trim((string)$r->input('default_currency')),
            'timezone'         => trim((string)$r->input('timezone', 'UTC')),
            'is_active'        => (int)$r->input('is_active', 1) === 1 ? 1 : 0,
        ];

        try {
            (new OrganizationModel())->updateBranch($id, $data);
            \audit_log('branch_updated', 'branches', $id, null, $data);
            flash('success', 'Branch updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update branch: ' . $e->getMessage());
        }
        redirect('/organization');
    }
}
