<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\BranchScopeService;

final class DashboardController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('dashboard.view');

        $pdo = Database::connection();
        $stats = [];
        foreach (
            [
                'purchase_requests' => 'Purchase Requests',
                'requisitions'      => 'Requisitions',
                'vendors'           => 'Vendors',
                'purchase_orders'   => 'Purchase Orders',
                'deliveries'        => 'Deliveries',
                'invoices'          => 'Invoices',
                'payments'          => 'Payments',
            ] as $table => $label
        ) {
            $stats[] = [
                'label' => $label,
                'count' => (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(),
            ];
        }

        $params = [];
        $poSql = 'SELECT po.id, po.po_number, po.currency_code, po.total_amount, po.status, po.order_date, v.legal_name AS vendor_name, b.branch_code
                  FROM purchase_orders po
                  JOIN vendors v ON v.id = po.vendor_id
                  JOIN branches b ON b.id = po.ship_to_branch_id
                  WHERE 1=1' . BranchScopeService::apply('po.ship_to_branch_id', $params)
            . ' ORDER BY po.id DESC LIMIT 8';
        $poStmt = $pdo->prepare($poSql);
        $poStmt->execute($params);
        $recent = $poStmt->fetchAll();

        $pendingApprovals = (int)$pdo->query("SELECT COUNT(*) FROM approval_instances WHERE status = 'pending'")->fetchColumn();
        $upcomingRenewals = $pdo->query(
            "SELECT rp.*, v.legal_name AS vendor_name
             FROM recurring_purchases rp
             JOIN vendors v ON v.id = rp.vendor_id
             WHERE rp.status = 'active' AND rp.next_renewal_date <= DATE_ADD(CURDATE(), INTERVAL 45 DAY)
             ORDER BY rp.next_renewal_date ASC
             LIMIT 6"
        )->fetchAll();

        $this->view('dashboard/index', [
            'stats'            => $stats,
            'recent'           => $recent,
            'pendingApprovals' => $pendingApprovals,
            'upcomingRenewals' => $upcomingRenewals,
        ]);
    }
}
