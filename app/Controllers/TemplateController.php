<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class TemplateController extends BaseController
{
    public function vendor(Request $r): void
    {
        $this->auth();
        $this->require('imports.manage');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vendor_import_template.csv"');
        $o = fopen('php://output', 'w');
        fputcsv($o, ['vendor_code', 'legal_name', 'tax_registration_no', 'website', 'status', 'notes']);
        fputcsv($o, ['VND-0001', 'Example Vendor Corp', 'EIN-99-1234567', 'https://example.com', 'approved', 'Sample row']);
        fclose($o);
        exit;
    }

    public function catalog(Request $r): void
    {
        $this->auth();
        $this->require('imports.manage');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="catalog_import_template.csv"');
        $o = fopen('php://output', 'w');
        fputcsv($o, ['category_code', 'unit_code', 'sku', 'item_name', 'description']);
        fputcsv($o, ['IT-HW', 'EA', 'LAP-LAT-5550', 'Dell Latitude 5550 Business Laptop', '16GB RAM / 512GB NVMe SSD']);
        fclose($o);
        exit;
    }

    public function budget(Request $r): void
    {
        $this->auth();
        $this->require('imports.manage');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="budget_import_template.csv"');
        $o = fopen('php://output', 'w');
        fputcsv($o, ['branch_code', 'department_code', 'fiscal_year', 'currency_code', 'budget_name', 'category_code', 'allocated_amount']);
        fputcsv($o, ['NYC-HQ', 'IT', date('Y'), 'USD', 'FY' . date('Y') . ' IT Hardware Budget', 'IT-HW', '50000.00']);
        fclose($o);
        exit;
    }
}
