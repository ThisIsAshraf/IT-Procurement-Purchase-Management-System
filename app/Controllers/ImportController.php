<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ImportService;

final class ImportController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('imports.manage');

        $this->view('imports/index', [
            'batches' => (new ImportService())->batches(),
            'flash'   => flash('success'),
            'error'   => flash('error'),
        ]);
    }

    public function upload(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('imports.manage');

        $entityType = trim((string)$r->input('entity_type', 'vendors'));
        $file = $r->files()['file'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Please select a valid CSV, XLSX, or XLS file.');
            redirect('/imports');
        }

        try {
            $res = (new ImportService())->processUpload($entityType, $file, (int)current_user()['id']);
            flash(
                'success',
                "Batch #{$res['batch_id']} processed ({$res['total']} rows): {$res['success']} imported, {$res['errors']} failed."
            );
        } catch (\Throwable $e) {
            flash('error', 'Import failed: ' . $e->getMessage());
        }
        redirect('/imports');
    }

    public function downloadErrors(Request $r): void
    {
        $this->auth();
        $this->require('imports.manage');

        $batchId = (int)($r->params()['id'] ?? 0);
        $csv = (new ImportService())->errorsCsv($batchId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="import_batch_' . $batchId . '_errors.csv"');
        echo $csv;
        exit;
    }
}
