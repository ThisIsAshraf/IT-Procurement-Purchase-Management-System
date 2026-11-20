<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\RequisitionModel;
use PDO;

final class QuotationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createWithItems(array $header, array $items, int $userId, array $uploadedFile = []): int
    {
        if (empty($items)) {
            throw new \RuntimeException('Provide pricing for at least one requisition line item.');
        }

        $req = (new RequisitionModel())->find((int)$header['requisition_id']);
        if (!$req) {
            throw new \RuntimeException('Requisition not found.');
        }
        BranchScopeService::assertBranchAccess((int)$req['branch_id']);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO quotations
                 (quotation_no, requisition_id, vendor_id, currency_code, payment_term_id, quote_date, valid_until, delivery_days, tax_amount, shipping_amount, discount_amount, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)"
            );
            $stmt->execute([
                $header['quotation_no'],
                (int)$header['requisition_id'],
                (int)$header['vendor_id'],
                strtoupper((string)$header['currency_code']),
                !empty($header['payment_term_id']) ? (int)$header['payment_term_id'] : null,
                $header['quote_date'],
                $header['valid_until'] !== '' ? $header['valid_until'] : null,
                $header['delivery_days'] !== '' && $header['delivery_days'] !== null ? (int)$header['delivery_days'] : null,
                (float)($header['tax_amount'] ?? 0),
                (float)($header['shipping_amount'] ?? 0),
                (float)($header['discount_amount'] ?? 0),
                $header['notes'] !== '' ? $header['notes'] : null,
            ]);
            $quotationId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO quotation_items
                 (quotation_id, requisition_item_id, description, quantity, unit_price, tax_rate, warranty_months)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $insItem->execute([
                    $quotationId,
                    (int)$it['requisition_item_id'],
                    $it['description'],
                    (float)$it['quantity'],
                    (float)$it['unit_price'],
                    (float)($it['tax_rate'] ?? 0),
                    $it['warranty_months'] !== '' && $it['warranty_months'] !== null ? (int)$it['warranty_months'] : null,
                ]);
            }

            if (!empty($uploadedFile['tmp_name']) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                UploadService::storeAndLink($uploadedFile, 'quotations', $quotationId, $userId, 'Vendor Quotation PDF');
            }

            $this->db->commit();
            \audit_log('quotation_created', 'quotations', $quotationId, null, [
                'quotation_no'   => $header['quotation_no'],
                'requisition_id' => $header['requisition_id'],
                'vendor_id'      => $header['vendor_id'],
            ]);
            return $quotationId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function saveComparisonAndSelect(
        int $requisitionId,
        string $title,
        string $recommendation,
        array $scoresByQuoteId,
        int $selectedQuotationId,
        int $userId,
        bool $finalizeSelection = false
    ): int {
        $req = (new RequisitionModel())->find($requisitionId);
        if (!$req) {
            throw new \RuntimeException('Requisition not found.');
        }
        BranchScopeService::assertBranchAccess((int)$req['branch_id']);

        $this->db->beginTransaction();
        try {
            $status = $finalizeSelection ? 'approved' : 'submitted';
            $stmt = $this->db->prepare(
                'INSERT INTO quotation_comparisons (requisition_id, comparison_title, prepared_by, recommendation, status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $requisitionId,
                $title !== '' ? $title : ('Vendor Evaluation — ' . $req['requisition_no']),
                $userId,
                $recommendation !== '' ? $recommendation : null,
                $status,
            ]);
            $comparisonId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare(
                'INSERT INTO quotation_comparison_items
                 (comparison_id, quotation_id, evaluation_score, is_recommended, evaluation_notes)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($scoresByQuoteId as $qid => $row) {
                $qid = (int)$qid;
                if ($qid <= 0) {
                    continue;
                }
                $isRec = ($qid === $selectedQuotationId) ? 1 : 0;
                $scoreVal = isset($row['score']) && $row['score'] !== '' ? (float)$row['score'] : null;
                $notesVal = trim((string)($row['notes'] ?? ''));

                $insItem->execute([
                    $comparisonId,
                    $qid,
                    $scoreVal,
                    $isRec,
                    $notesVal !== '' ? $notesVal : null,
                ]);

                if ($finalizeSelection && $selectedQuotationId > 0) {
                    $newQuoteStatus = ($qid === $selectedQuotationId) ? 'selected' : 'rejected';
                    $this->db->prepare('UPDATE quotations SET status = ? WHERE id = ? AND requisition_id = ?')
                        ->execute([$newQuoteStatus, $qid, $requisitionId]);
                } elseif ($isRec) {
                    $this->db->prepare("UPDATE quotations SET status = 'shortlisted' WHERE id = ? AND requisition_id = ? AND status = 'received'")
                        ->execute([$qid, $requisitionId]);
                }
            }

            if ($finalizeSelection && $selectedQuotationId > 0) {
                $this->db->prepare("UPDATE requisitions SET status = 'approved' WHERE id = ? AND status IN ('draft', 'sourcing', 'pending_approval')")
                    ->execute([$requisitionId]);
            }

            $this->db->commit();
            \audit_log('quotation_comparison_saved', 'quotation_comparisons', $comparisonId, null, [
                'requisition_id'        => $requisitionId,
                'selected_quotation_id' => $selectedQuotationId,
                'finalized'             => $finalizeSelection,
            ]);
            return $comparisonId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
