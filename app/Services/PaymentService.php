<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\InvoiceModel;
use PDO;

final class PaymentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function recordAndAllocate(
        int $vendorId,
        string $currencyCode,
        string $paymentDate,
        string $paymentMethod,
        float $totalAmount,
        string $transactionRef,
        string $notes,
        array $allocationsByInvoiceId,
        int $userId
    ): int {
        if ($totalAmount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }

        $invModel = new InvoiceModel();
        $cleanAllocs = [];
        $sumAllocated = 0.0;

        foreach ($allocationsByInvoiceId as $invoiceId => $allocAmt) {
            $invoiceId = (int)$invoiceId;
            $amt = round((float)$allocAmt, 2);
            if ($invoiceId <= 0 || $amt <= 0) {
                continue;
            }
            $inv = $invModel->find($invoiceId);
            if (!$inv) {
                throw new \RuntimeException("Invoice #{$invoiceId} not found.");
            }
            if ((int)$inv['vendor_id'] !== $vendorId) {
                throw new \RuntimeException("Invoice {$inv['invoice_no']} does not belong to the selected vendor.");
            }
            if (strtoupper((string)$inv['currency_code']) !== strtoupper($currencyCode)) {
                throw new \RuntimeException("Invoice {$inv['invoice_no']} currency ({$inv['currency_code']}) must match payment currency ({$currencyCode}).");
            }
            if ($amt > (float)$inv['balance_due'] + 0.01) {
                throw new \RuntimeException("Allocation ({$amt}) exceeds remaining balance ({$inv['balance_due']}) on invoice {$inv['invoice_no']}.");
            }

            $cleanAllocs[] = [
                'invoice_id'        => $invoiceId,
                'purchase_order_id' => (int)$inv['purchase_order_id'],
                'allocated_amount'  => $amt,
                'total_amount'      => (float)$inv['total_amount'],
                'prior_paid'        => (float)$inv['paid_amount'],
            ];
            $sumAllocated = round($sumAllocated + $amt, 2);
        }

        if (empty($cleanAllocs)) {
            throw new \RuntimeException('Allocate the payment to at least one open invoice.');
        }
        if ($sumAllocated > $totalAmount + 0.01) {
            throw new \RuntimeException("Total allocated ({$sumAllocated}) cannot exceed total payment amount ({$totalAmount}).");
        }

        $this->db->beginTransaction();
        try {
            $payRef = DocumentNumberService::next('PAY', 'payments', 'payment_reference');
            $stmt = $this->db->prepare(
                "INSERT INTO payments
                 (payment_reference, vendor_id, currency_code, payment_date, payment_method, amount, transaction_reference, status, recorded_by, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)"
            );
            $stmt->execute([
                $payRef,
                $vendorId,
                strtoupper($currencyCode),
                $paymentDate,
                $paymentMethod,
                $totalAmount,
                $transactionRef !== '' ? $transactionRef : null,
                $userId,
                $notes !== '' ? $notes : null,
            ]);
            $paymentId = (int)$this->db->lastInsertId();

            $insAlloc = $this->db->prepare(
                'INSERT INTO payment_allocations (payment_id, invoice_id, allocated_amount) VALUES (?, ?, ?)'
            );

            $touchedPoIds = [];
            foreach ($cleanAllocs as $al) {
                $insAlloc->execute([$paymentId, $al['invoice_id'], $al['allocated_amount']]);

                $newPaid = round($al['prior_paid'] + $al['allocated_amount'], 2);
                $newInvStatus = ($newPaid + 0.01 >= $al['total_amount']) ? 'paid' : 'partially_paid';
                $this->db->prepare('UPDATE invoices SET status = ? WHERE id = ?')
                    ->execute([$newInvStatus, $al['invoice_id']]);

                $touchedPoIds[$al['purchase_order_id']] = true;
            }

            // Auto-close POs that are fully received AND have all non-void invoices paid
            foreach (array_keys($touchedPoIds) as $poId) {
                $unpaidStmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM invoices
                     WHERE purchase_order_id = ? AND status NOT IN ('paid', 'void')"
                );
                $unpaidStmt->execute([$poId]);
                $unpaidCount = (int)$unpaidStmt->fetchColumn();

                if ($unpaidCount === 0) {
                    $this->db->prepare("UPDATE purchase_orders SET status = 'closed' WHERE id = ? AND status = 'received'")
                        ->execute([$poId]);
                }
            }

            $this->db->commit();
            \audit_log('payment_recorded', 'payments', $paymentId, null, [
                'payment_reference' => $payRef,
                'amount'            => $totalAmount,
                'allocations'       => count($cleanAllocs),
            ]);
            return $paymentId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
