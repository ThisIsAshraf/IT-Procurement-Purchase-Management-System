<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class QuotationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(?int $requisitionId = null): array
    {
        $params = [];
        $sql = "SELECT q.*,
                       r.requisition_no, r.branch_id,
                       b.branch_code,
                       v.vendor_code, v.legal_name AS vendor_name,
                       pt.term_name AS payment_term_name,
                       COALESCE((SELECT SUM(qi.quantity * qi.unit_price) FROM quotation_items qi WHERE qi.quotation_id = q.id), 0) AS items_subtotal,
                       COALESCE((SELECT SUM(qi.quantity * qi.unit_price * (qi.tax_rate / 100)) FROM quotation_items qi WHERE qi.quotation_id = q.id), 0) AS items_tax
                FROM quotations q
                JOIN requisitions r ON r.id = q.requisition_id
                JOIN branches b ON b.id = r.branch_id
                JOIN vendors v ON v.id = q.vendor_id
                LEFT JOIN payment_terms pt ON pt.id = q.payment_term_id
                WHERE 1=1";

        $sql .= BranchScopeService::apply('r.branch_id', $params);

        if ($requisitionId !== null && $requisitionId > 0) {
            $sql .= ' AND q.requisition_id = ?';
            $params[] = $requisitionId;
        }

        $sql .= ' ORDER BY q.quote_date DESC, q.id DESC LIMIT 250';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['grand_total'] = (float)$row['items_subtotal']
                + (float)$row['items_tax']
                + (float)$row['tax_amount']
                + (float)$row['shipping_amount']
                - (float)$row['discount_amount'];
        }
        return $rows;
    }

    public function approvedVendors(): array
    {
        return $this->db->query(
            "SELECT id, vendor_code, legal_name, status
             FROM vendors
             WHERE status IN ('approved', 'pending')
             ORDER BY legal_name ASC"
        )->fetchAll();
    }

    public function openRequisitions(): array
    {
        $params = [];
        $sql = "SELECT r.id, r.requisition_no, r.currency_code, r.status, b.branch_code, d.name AS department_name
                FROM requisitions r
                JOIN branches b ON b.id = r.branch_id
                JOIN departments d ON d.id = r.department_id
                WHERE r.status NOT IN ('cancelled', 'rejected') ";
        $sql .= BranchScopeService::apply('r.branch_id', $params);
        $sql .= ' ORDER BY r.id DESC LIMIT 150';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function comparisonMatrix(int $requisitionId): array
    {
        $quotations = $this->all($requisitionId);
        if (empty($quotations)) {
            return ['quotations' => [], 'items_by_quote' => [], 'comparison' => null, 'scores' => []];
        }

        $quoteIds = array_map(static fn(array $q): int => (int)$q['id'], $quotations);
        $placeholders = implode(',', array_fill(0, count($quoteIds), '?'));

        $itemStmt = $this->db->prepare(
            "SELECT qi.*
             FROM quotation_items qi
             WHERE qi.quotation_id IN ({$placeholders})
             ORDER BY qi.id ASC"
        );
        $itemStmt->execute($quoteIds);
        $allItems = $itemStmt->fetchAll();

        $itemsByQuoteAndReqItem = [];
        foreach ($allItems as $qi) {
            $itemsByQuoteAndReqItem[(int)$qi['quotation_id']][(int)$qi['requisition_item_id']] = $qi;
        }

        $compStmt = $this->db->prepare(
            'SELECT qc.*, u.full_name AS prepared_by_name
             FROM quotation_comparisons qc
             JOIN users u ON u.id = qc.prepared_by
             WHERE qc.requisition_id = ?
             ORDER BY qc.id DESC
             LIMIT 1'
        );
        $compStmt->execute([$requisitionId]);
        $comparison = $compStmt->fetch() ?: null;

        $scores = [];
        if ($comparison) {
            $scStmt = $this->db->prepare(
                'SELECT * FROM quotation_comparison_items WHERE comparison_id = ?'
            );
            $scStmt->execute([(int)$comparison['id']]);
            foreach ($scStmt->fetchAll() as $sc) {
                $scores[(int)$sc['quotation_id']] = $sc;
            }
        }

        return [
            'quotations'     => $quotations,
            'items_by_quote' => $itemsByQuoteAndReqItem,
            'comparison'     => $comparison,
            'scores'         => $scores,
        ];
    }
}
