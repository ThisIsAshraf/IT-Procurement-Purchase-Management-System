<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\OrganizationModel;
use App\Models\ReportModel;

final class ReportController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('reports.view');

        $reportType = trim((string)$r->input('report_type', 'spend_summary'));
        $allowedTypes = ['spend_summary', 'po_register', 'invoice_aging', 'budget_vs_actual', 'vendor_performance'];
        if (!in_array($reportType, $allowedTypes, true)) {
            $reportType = 'spend_summary';
        }

        $filters = [
            'branch_id'   => trim((string)$r->input('branch_id', '')),
            'date_from'   => trim((string)$r->input('date_from', '')),
            'date_to'     => trim((string)$r->input('date_to', '')),
            'fiscal_year' => trim((string)$r->input('fiscal_year', date('Y'))),
        ];

        $model = new ReportModel();
        $org = new OrganizationModel();

        $this->view('reports/index', [
            'data'         => $model->kpiSummary(),
            'reportType'   => $reportType,
            'filters'      => $filters,
            'rows'         => $model->runReport($reportType, $filters),
            'savedReports' => $model->savedReports((int)current_user()['id']),
            'branches'     => $org->branches(true),
            'flash'        => flash('success'),
            'error'        => flash('error'),
        ]);
    }

    public function save(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('reports.view');

        $name = trim((string)$r->input('report_name', ''));
        $type = trim((string)$r->input('report_type', 'spend_summary'));
        $isShared = (int)$r->input('is_shared', 0) === 1;
        $filters = [
            'branch_id'   => trim((string)$r->input('branch_id', '')),
            'date_from'   => trim((string)$r->input('date_from', '')),
            'date_to'     => trim((string)$r->input('date_to', '')),
            'fiscal_year' => trim((string)$r->input('fiscal_year', date('Y'))),
        ];

        if ($name === '') {
            flash('error', 'Enter a name for your custom report.');
            redirect('/reports');
        }

        $id = (new ReportModel())->saveReport($name, $type, $filters, $isShared, (int)current_user()['id']);
        \audit_log('saved_report_created', 'saved_reports', $id, null, ['name' => $name, 'type' => $type]);
        flash('success', 'Custom report saved.');
        redirect('/reports?report_type=' . urlencode($type) . '&' . http_build_query($filters));
    }

    public function export(Request $r): void
    {
        $this->auth();
        $this->require('reports.export');

        $reportType = trim((string)$r->input('report_type', (string)$r->input('type', 'spend_summary')));
        $legacyMap = [
            'purchase_orders'   => 'po_register',
            'purchase_requests' => 'spend_summary',
            'vendors'           => 'vendor_performance',
            'invoices'          => 'invoice_aging',
            'payments'          => 'spend_summary',
        ];
        if (isset($legacyMap[$reportType])) {
            $reportType = $legacyMap[$reportType];
        }

        $filters = [
            'branch_id'   => trim((string)$r->input('branch_id', '')),
            'date_from'   => trim((string)$r->input('date_from', '')),
            'date_to'     => trim((string)$r->input('date_to', '')),
            'fiscal_year' => trim((string)$r->input('fiscal_year', date('Y'))),
        ];

        $rows = (new ReportModel())->runReport($reportType, $filters);
        $format = strtolower(trim((string)$r->input('format', 'csv')));

        if ($format === 'xlsx') {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                http_response_code(500);
                exit('Install Composer dependencies first.');
            }
            $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $book->getActiveSheet();
            if (!empty($rows)) {
                $sheet->fromArray(array_keys($rows[0]), null, 'A1');
                $sheet->fromArray(array_map('array_values', $rows), null, 'A2');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $reportType . '_' . date('Ymd') . '.xlsx"');
            $writer->save('php://output');
            exit;
        }

        if ($format === 'pdf') {
            if (!class_exists(\Dompdf\Dompdf::class)) {
                http_response_code(500);
                exit('Install Composer dependencies first.');
            }
            $title = htmlspecialchars(ucwords(str_replace('_', ' ', $reportType)));
            $html = '<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:6px;text-align:left;}th{background:#f2f4f8;}</style>'
                . '<h2>Noor Staffing Group — ' . $title . '</h2>'
                . '<p>Generated: ' . date('Y-m-d H:i') . ' UTC</p>'
                . '<table><thead><tr>';
            foreach (array_keys($rows[0] ?? ['Notice' => null]) as $h) {
                $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', (string)$h))) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            if (empty($rows)) {
                $html .= '<tr><td>No records matched the selected filters.</td></tr>';
            } else {
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    foreach ($row as $v) {
                        $html .= '<td>' . htmlspecialchars((string)($v ?? '')) . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table>';

            $pdf = new \Dompdf\Dompdf();
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $reportType . '_' . date('Ymd') . '.pdf"');
            echo $pdf->output();
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $reportType . '_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }
}
