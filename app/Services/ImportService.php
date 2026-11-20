<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ImportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function batches(): array
    {
        return $this->db->query(
            'SELECT ib.*, u.full_name AS imported_by_name
             FROM import_batches ib
             JOIN users u ON u.id = ib.uploaded_by
             ORDER BY ib.id DESC
             LIMIT 50'
        )->fetchAll();
    }

    public function errorsCsv(int $batchId): string
    {
        $stmt = $this->db->prepare(
            'SELECT row_number, field_name, error_message, row_data
             FROM import_row_errors
             WHERE import_batch_id = ?
             ORDER BY row_number ASC, id ASC'
        );
        $stmt->execute([$batchId]);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['row_number', 'field_name', 'error_message', 'row_data']);
        foreach ($stmt->fetchAll() as $row) {
            fputcsv($out, [$row['row_number'], $row['field_name'], $row['error_message'], $row['row_data']]);
        }
        rewind($out);
        return (string)stream_get_contents($out);
    }

    public function processUpload(string $entityType, array $file, int $userId): array
    {
        $allowedEntities = ['vendors', 'catalog_items', 'budgets'];
        if (!in_array($entityType, $allowedEntities, true)) {
            throw new \RuntimeException('Unsupported import type.');
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            throw new \RuntimeException('Allowed file formats: CSV, XLSX, XLS.');
        }

        $rows = [];
        if ($ext === 'csv') {
            $h = fopen((string)$file['tmp_name'], 'r');
            if (!$h) {
                throw new \RuntimeException('Unable to read uploaded CSV file.');
            }
            while (($row = fgetcsv($h)) !== false) {
                if (count(array_filter($row, static fn($v): bool => trim((string)$v) !== '')) > 0) {
                    $rows[] = $row;
                }
            }
            fclose($h);
        } else {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                throw new \RuntimeException('PhpSpreadsheet is not installed.');
            }
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load((string)$file['tmp_name']);
            foreach ($book->getActiveSheet()->toArray() as $row) {
                if (count(array_filter($row, static fn($v): bool => trim((string)$v) !== '')) > 0) {
                    $rows[] = $row;
                }
            }
        }

        if (count($rows) < 2) {
            throw new \RuntimeException('The uploaded file must contain a header row and at least one data row.');
        }

        $headers = array_map(static fn($h): string => strtolower(trim((string)$h)), array_shift($rows));

        $batchStmt = $this->db->prepare(
            "INSERT INTO import_batches (import_type, original_filename, total_rows, imported_rows, failed_rows, status, uploaded_by)
             VALUES (?, ?, ?, 0, 0, 'previewed', ?)"
        );
        $batchStmt->execute([$entityType, basename((string)$file['name']), count($rows), $userId]);
        $batchId = (int)$this->db->lastInsertId();

        $errStmt = $this->db->prepare(
            'INSERT INTO import_row_errors (import_batch_id, row_number, field_name, error_message, row_data)
             VALUES (?, ?, ?, ?, ?)'
        );

        $ok = 0;
        $bad = 0;

        foreach ($rows as $idx => $rawRow) {
            $lineNo = $idx + 2;
            $assoc = array_combine($headers, array_pad(array_slice($rawRow, 0, count($headers)), count($headers), ''));
            $assoc = array_map(static fn($v): string => trim((string)$v), $assoc ?: []);

            try {
                if ($entityType === 'vendors') {
                    $this->importVendorRow($assoc);
                } elseif ($entityType === 'catalog_items') {
                    $this->importCatalogRow($assoc);
                } elseif ($entityType === 'budgets') {
                    $this->importBudgetRow($assoc);
                }
                $ok++;
            } catch (\Throwable $e) {
                $bad++;
                $errStmt->execute([
                    $batchId,
                    $lineNo,
                    null,
                    mb_substr($e->getMessage(), 0, 490),
                    json_encode($assoc, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        $this->db->prepare(
            "UPDATE import_batches SET imported_rows = ?, failed_rows = ?, status = 'completed' WHERE id = ?"
        )->execute([$ok, $bad, $batchId]);

        \audit_log('bulk_import_completed', 'import_batches', $batchId, null, [
            'import_type' => $entityType,
            'imported'    => $ok,
            'failed'      => $bad,
        ]);

        return ['batch_id' => $batchId, 'total' => count($rows), 'success' => $ok, 'errors' => $bad];
    }

    private function importVendorRow(array $d): void
    {
        $code = strtoupper($d['vendor_code'] ?? '');
        $name = $d['legal_name'] ?? '';
        if ($code === '' || $name === '') {
            throw new \RuntimeException('vendor_code and legal_name are required.');
        }
        $status = $d['status'] !== '' ? strtolower($d['status']) : 'pending';
        if (!in_array($status, ['pending', 'approved', 'blocked', 'inactive'], true)) {
            throw new \RuntimeException("Invalid vendor status '{$status}'.");
        }

        $stmt = $this->db->prepare(
            'INSERT INTO vendors (vendor_code, legal_name, tax_registration_no, website, status, notes)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               legal_name = VALUES(legal_name),
               tax_registration_no = COALESCE(VALUES(tax_registration_no), tax_registration_no),
               website = COALESCE(VALUES(website), website),
               status = VALUES(status),
               notes = COALESCE(VALUES(notes), notes)'
        );
        $stmt->execute([
            $code,
            $name,
            ($d['tax_registration_no'] ?? '') !== '' ? $d['tax_registration_no'] : null,
            ($d['website'] ?? '') !== '' ? $d['website'] : null,
            $status,
            ($d['notes'] ?? '') !== '' ? $d['notes'] : null,
        ]);
    }

    private function importCatalogRow(array $d): void
    {
        $catCode = strtoupper($d['category_code'] ?? '');
        $unitCode = strtoupper($d['unit_code'] ?? '');
        $itemName = $d['item_name'] ?? '';
        $sku = strtoupper($d['sku'] ?? '');

        if ($catCode === '' || $unitCode === '' || $itemName === '') {
            throw new \RuntimeException('category_code, unit_code, and item_name are required.');
        }

        $catStmt = $this->db->prepare('SELECT id FROM item_categories WHERE category_code = ? LIMIT 1');
        $catStmt->execute([$catCode]);
        $catId = $catStmt->fetchColumn();
        if (!$catId) {
            throw new \RuntimeException("Unknown category_code '{$catCode}'.");
        }

        $unitStmt = $this->db->prepare('SELECT id FROM units_of_measure WHERE unit_code = ? LIMIT 1');
        $unitStmt->execute([$unitCode]);
        $unitId = $unitStmt->fetchColumn();
        if (!$unitId) {
            throw new \RuntimeException("Unknown unit_code '{$unitCode}'.");
        }

        $stmt = $this->db->prepare(
            'INSERT INTO catalog_items (category_id, unit_id, sku, item_name, description, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
               category_id = VALUES(category_id),
               unit_id = VALUES(unit_id),
               item_name = VALUES(item_name),
               description = VALUES(description)'
        );
        $stmt->execute([
            (int)$catId,
            (int)$unitId,
            $sku !== '' ? $sku : null,
            $itemName,
            ($d['description'] ?? '') !== '' ? $d['description'] : null,
        ]);
    }

    private function importBudgetRow(array $d): void
    {
        $branchCode = strtoupper($d['branch_code'] ?? '');
        $deptCode = strtoupper($d['department_code'] ?? '');
        $year = (int)($d['fiscal_year'] ?? 0);
        $currency = strtoupper($d['currency_code'] ?? 'USD');
        $budgetName = $d['budget_name'] ?? '';
        $amount = (float)($d['allocated_amount'] ?? 0);

        if ($branchCode === '' || $deptCode === '' || $year < 2020 || $budgetName === '') {
            throw new \RuntimeException('branch_code, department_code, fiscal_year, and budget_name are required.');
        }

        $bStmt = $this->db->prepare('SELECT id FROM branches WHERE branch_code = ? LIMIT 1');
        $bStmt->execute([$branchCode]);
        $branchId = $bStmt->fetchColumn();
        if (!$branchId) {
            throw new \RuntimeException("Unknown branch_code '{$branchCode}'.");
        }

        $dStmt = $this->db->prepare('SELECT id FROM departments WHERE department_code = ? LIMIT 1');
        $dStmt->execute([$deptCode]);
        $deptId = $dStmt->fetchColumn();
        if (!$deptId) {
            throw new \RuntimeException("Unknown department_code '{$deptCode}'.");
        }

        $ins = $this->db->prepare(
            "INSERT INTO budgets (branch_id, department_id, fiscal_year, currency_code, budget_name, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $ins->execute([(int)$branchId, (int)$deptId, $year, $currency, $budgetName]);
        $budgetId = (int)$this->db->lastInsertId();

        if ($amount > 0) {
            $catId = null;
            if (!empty($d['category_code'])) {
                $cStmt = $this->db->prepare('SELECT id FROM item_categories WHERE category_code = ? LIMIT 1');
                $cStmt->execute([strtoupper($d['category_code'])]);
                $catId = $cStmt->fetchColumn() ?: null;
            }
            $this->db->prepare('INSERT INTO budget_allocations (budget_id, category_id, allocated_amount) VALUES (?, ?, ?)')
                ->execute([$budgetId, $catId, round($amount, 2)]);
        }
    }
}
