<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\OrganizationModel;
use App\Models\VendorModel;
use App\Services\UploadService;

final class VendorController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('vendors.view');

        $q = trim((string)$r->input('q', ''));
        $status = trim((string)$r->input('status', ''));

        $this->view('vendors/index', [
            'vendors' => (new VendorModel())->search($q, $status),
            'q'       => $q,
            'status'  => $status,
            'flash'   => flash('success'),
            'error'   => flash('error'),
        ]);
    }

    public function store(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.create') && !can('vendors.manage')) {
            $this->require('vendors.create');
        }

        $data = [
            'vendor_code'         => trim((string)$r->input('vendor_code')),
            'legal_name'          => trim((string)$r->input('legal_name')),
            'tax_registration_no' => trim((string)$r->input('tax_registration_no')),
            'website'             => trim((string)$r->input('website')),
            'status'              => (string)$r->input('status', 'pending'),
            'notes'               => trim((string)$r->input('notes')),
        ];

        $contact = [
            'contact_name' => trim((string)$r->input('contact_name')),
            'email'        => trim((string)$r->input('contact_email')),
            'phone'        => trim((string)$r->input('contact_phone')),
            'job_title'    => trim((string)$r->input('contact_title')),
        ];

        $v = (new Validator())
            ->required('vendor_code', $data['vendor_code'], 'Vendor code')
            ->maxLength('vendor_code', $data['vendor_code'], 40, 'Vendor code')
            ->required('legal_name', $data['legal_name'], 'Legal name')
            ->inList('status', $data['status'], ['pending', 'approved', 'blocked', 'inactive'], 'Status')
            ->email('contact_email', $contact['email'], 'Contact email');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/vendors');
        }

        try {
            $id = (new VendorModel())->create($data, $contact);
            \audit_log('vendor_created', 'vendors', $id, null, $data);
            flash('success', 'Vendor created.');
            redirect('/vendors/' . $id);
        } catch (\Throwable $e) {
            flash('error', 'Could not create vendor: ' . $e->getMessage());
            redirect('/vendors');
        }
    }

    public function show(Request $r): void
    {
        $this->auth();
        $this->require('vendors.view');

        $id = (int)($r->params()['id'] ?? 0);
        $model = new VendorModel();
        $vendor = $model->find($id);

        if (!$vendor) {
            http_response_code(404);
            exit('Vendor not found.');
        }

        $this->view('vendors/show', [
            'vendor'             => $vendor,
            'contacts'           => $model->contacts($id),
            'addresses'          => $model->addresses($id),
            'attachments'        => UploadService::forEntity('vendors', $id),
            'performanceSummary' => $model->performanceSummary($id),
            'reviews'            => $model->reviews($id),
            'purchaseOrders'     => $model->purchaseOrders($id),
            'quotations'         => $model->quotations($id),
            'countries'          => (new OrganizationModel())->countries(),
            'flash'              => flash('success'),
            'error'              => flash('error'),
        ]);
    }

    public function update(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $id = (int)($r->params()['id'] ?? 0);
        $data = [
            'vendor_code'         => trim((string)$r->input('vendor_code')),
            'legal_name'          => trim((string)$r->input('legal_name')),
            'tax_registration_no' => trim((string)$r->input('tax_registration_no')),
            'website'             => trim((string)$r->input('website')),
            'status'              => (string)$r->input('status', 'pending'),
            'notes'               => trim((string)$r->input('notes')),
        ];

        $v = (new Validator())
            ->required('vendor_code', $data['vendor_code'], 'Vendor code')
            ->required('legal_name', $data['legal_name'], 'Legal name')
            ->inList('status', $data['status'], ['pending', 'approved', 'blocked', 'inactive'], 'Status');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/vendors/' . $id);
        }

        try {
            (new VendorModel())->update($id, $data);
            \audit_log('vendor_updated', 'vendors', $id, null, $data);
            flash('success', 'Vendor details updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update vendor: ' . $e->getMessage());
        }
        redirect('/vendors/' . $id);
    }

    public function addContact(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $data = [
            'contact_name' => trim((string)$r->input('contact_name')),
            'email'        => trim((string)$r->input('email')),
            'phone'        => trim((string)$r->input('phone')),
            'job_title'    => trim((string)$r->input('job_title')),
            'is_primary'   => (int)$r->input('is_primary', 0) === 1 ? 1 : 0,
        ];

        $v = (new Validator())
            ->required('contact_name', $data['contact_name'], 'Contact name')
            ->email('email', $data['email'], 'Email');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/vendors/' . $vendorId);
        }

        try {
            $cid = (new VendorModel())->addContact($vendorId, $data);
            \audit_log('vendor_contact_added', 'vendor_contacts', $cid, null, $data);
            flash('success', 'Contact added.');
        } catch (\Throwable $e) {
            flash('error', 'Could not add contact: ' . $e->getMessage());
        }
        redirect('/vendors/' . $vendorId);
    }

    public function deleteContact(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $contactId = (int)($r->params()['contactId'] ?? 0);
        (new VendorModel())->deleteContact($vendorId, $contactId);
        \audit_log('vendor_contact_deleted', 'vendor_contacts', $contactId);
        flash('success', 'Contact removed.');
        redirect('/vendors/' . $vendorId);
    }

    public function addAddress(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $data = [
            'country_id'    => (int)$r->input('country_id'),
            'address_type'  => (string)$r->input('address_type', 'office'),
            'address_line1' => trim((string)$r->input('address_line1')),
            'address_line2' => trim((string)$r->input('address_line2')),
            'city'          => trim((string)$r->input('city')),
            'region'        => trim((string)$r->input('region')),
            'postal_code'   => trim((string)$r->input('postal_code')),
        ];

        $v = (new Validator())
            ->positiveNumber('country_id', $data['country_id'], 'Country')
            ->inList('address_type', $data['address_type'], ['billing', 'shipping', 'office', 'other'], 'Address type')
            ->required('address_line1', $data['address_line1'], 'Address line 1');

        if ($v->fails()) {
            flash('error', (string)$v->firstError());
            redirect('/vendors/' . $vendorId);
        }

        try {
            $aid = (new VendorModel())->addAddress($vendorId, $data);
            \audit_log('vendor_address_added', 'vendor_addresses', $aid, null, $data);
            flash('success', 'Address added.');
        } catch (\Throwable $e) {
            flash('error', 'Could not add address: ' . $e->getMessage());
        }
        redirect('/vendors/' . $vendorId);
    }

    public function deleteAddress(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $addressId = (int)($r->params()['addressId'] ?? 0);
        (new VendorModel())->deleteAddress($vendorId, $addressId);
        \audit_log('vendor_address_deleted', 'vendor_addresses', $addressId);
        flash('success', 'Address removed.');
        redirect('/vendors/' . $vendorId);
    }

    public function uploadDocument(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $docKind = trim((string)$r->input('document_kind', 'Compliance Document'));
        $file = $r->files()['document'] ?? [];

        try {
            $attId = UploadService::storeAndLink($file, 'vendors', $vendorId, (int)current_user()['id'], $docKind);
            \audit_log('vendor_document_uploaded', 'attachments', $attId, null, ['vendor_id' => $vendorId, 'kind' => $docKind]);
            flash('success', 'Document uploaded.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/vendors/' . $vendorId);
    }

    public function downloadDocument(Request $r): void
    {
        $this->auth();
        $this->require('vendors.view');
        $attachmentId = (int)($r->params()['attachmentId'] ?? 0);
        UploadService::download($attachmentId);
    }

    public function addReview(Request $r): void
    {
        $this->auth();
        $this->csrf();
        if (!can('vendors.edit') && !can('vendors.manage')) {
            $this->require('vendors.edit');
        }

        $vendorId = (int)($r->params()['id'] ?? 0);
        $data = [
            'purchase_order_id'   => (int)$r->input('purchase_order_id') ?: null,
            'delivery_score'      => (int)$r->input('delivery_score', 5),
            'quality_score'       => (int)$r->input('quality_score', 5),
            'pricing_score'       => (int)$r->input('pricing_score', 5),
            'communication_score' => (int)$r->input('communication_score', 5),
            'comments'            => trim((string)$r->input('comments')),
        ];

        foreach (['delivery_score', 'quality_score', 'pricing_score', 'communication_score'] as $field) {
            if ($data[$field] < 1 || $data[$field] > 5) {
                flash('error', 'All performance scores must be between 1 and 5.');
                redirect('/vendors/' . $vendorId);
            }
        }

        try {
            $rid = (new VendorModel())->addReview($vendorId, $data, (int)current_user()['id']);
            \audit_log('vendor_review_added', 'vendor_performance_reviews', $rid, null, $data);
            flash('success', 'Vendor performance review recorded.');
        } catch (\Throwable $e) {
            flash('error', 'Could not save review: ' . $e->getMessage());
        }
        redirect('/vendors/' . $vendorId);
    }
}
