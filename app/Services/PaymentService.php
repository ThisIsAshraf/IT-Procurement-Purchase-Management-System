<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PaymentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Record a vendor payment and allocate amounts across one or more open invoices.
     *
     * @param array<int|string, mixed> $allocations Map of [invoice_id => amount]
     */
    public function recordAndAllocate(
        int $vendorId,
        string $currencyCode,
        string $paymentDate,
        string $paymentMethod,
        float $amount,
        string $transactionReference,
        string $notes,
        array $allocations,
        int $recordedBy
    ): int {
        if ($vendorId <= 0) {
            throw new \RuntimeException('Select a valid vendor before recording a payment.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('Total payment amount must be greater than zero.');
        }
        if ($paymentDate === '') {
            $paymentDate = date('Y-m-d');
        }

        $allowedMethods = ['bank_transfer', 'check', 'card', 'cash', 'wire', 'ach', 'other'];
        $method = strtolower(trim($paymentMethod));
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'bank_transfer';
        }

        $currency = strtoupper(trim($currencyCode)) ?: 'USD';

        // Filter positive invoice allocations
        $cleanAllocations = [];
        $allocatedSum = 0.0;
        foreach ($allocations as $invId => $allocVal) {
            $id = (int)$invId;
            $val = round((float)$allocVal, 2);
            if ($id > 0 && $val > 0) {
                $cleanAllocations[$id] = $val;
                $allocatedSum = round($allocatedSum + $val, 2);
            }
        }

        if ($allocatedSum > round($amount, 2) + 0.009) {
            throw new \RuntimeException(
                sprintf('Total allocated amount (%.2f) cannot exceed payment amount (%.2f).', $allocatedSum, $amount)
            );
        }

        $this->db->beginTransaction();
        try {
            $vStmt = $this->db->prepare('SELECT id, legal_name FROM vendors WHERE id = ? LIMIT 1');
            $vStmt->execute([$vendorId]);
            if (!$vStmt->fetch()) {
                throw new \RuntimeException('Selected vendor does not exist.');
            }

            $paymentNo = $this->nextPaymentNumber();

            $insPay = $this->db->prepare(
                'INSERT INTO payments
                 (payment_no, vendor_id, currency_code, payment_date, payment_method, amount, transaction_reference, notes, recorded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insPay->execute([
                $paymentNo,
                $vendorId,
                $currency,
                $paymentDate,
                $method,
                round($amount, 2),
                $transactionReference !== '' ? $transactionReference : null,
                $notes !== '' ? $notes : null,
                $recordedBy,
            ]);
            $paymentId = (int)$this->db->lastInsertId();

            if (!empty($cleanAllocations)) {
                $invLookup = $this->db->prepare(
                    "SELECT inv.id, inv.invoice_no, inv.vendor_id, inv.status, inv.total_amount,
                            COALESCE((SELECT SUM(pa.allocated_amount) FROM payment_allocations pa WHERE pa.invoice_id = inv.id), 0) AS paid_so_far
                     FROM invoices inv
                     WHERE inv.id = ?
                     LIMIT 1"
                );
                $insAlloc = $this->db->prepare(
                    'INSERT INTO payment_allocations (payment_id, invoice_id, allocated_amount) VALUES (?, ?, ?)'
                );
                $updInv = $this->db->prepare('UPDATE invoices SET status = ? WHERE id = ?');

                foreach ($cleanAllocations as $invoiceId => $allocAmt) {
                    $invLookup->execute([$invoiceId]);
                    $inv = $invLookup->fetch();

                    if (!$inv || (int)$inv['vendor_id'] !== $vendorId) {
                        throw new \RuntimeException("Invoice #{$invoiceId} does not belong to the selected vendor.");
                    }
                    if (!in_array((string)$inv['status'], ['approved', 'partially_paid'], true)) {
                        throw new \RuntimeException("Invoice {$inv['invoice_no']} must be approved before payment allocation.");
                    }

                    $balanceDue = round((float)$inv['total_amount'] - (float)$inv['paid_so_far'], 2);
                    if ($allocAmt > $balanceDue + 0.009) {
                        throw new \RuntimeException(
                            sprintf('Allocation (%.2f) exceeds open balance (%.2f) on invoice %s.', $allocAmt, $balanceDue, $inv['invoice_no'])
                        );
                    }

                    $insAlloc->execute([$paymentId, $invoiceId, $allocAmt]);

                    $newBalance = round($balanceDue - $allocAmt, 2);
                    $newStatus = $newBalance <= 0.009 ? 'paid' : 'partially_paid';
                    $updInv->execute([$newStatus, $invoiceId]);
                }
            }

            $this->db->commit();

            \audit_log('payment_recorded', 'payments', $paymentId, null, [
                'payment_no'      => $paymentNo,
                'vendor_id'       => $vendorId,
                'amount'          => round($amount, 2),
                'currency_code'   => $currency,
                'allocated_total' => $allocatedSum,
            ]);

            return $paymentId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function nextPaymentNumber(): string
    {
        $prefix = strtoupper(trim(SettingsService::get('global_id_prefix', 'NSG'))) ?: 'NSG';
        $year = date('Y');
        $like = "{$prefix}-PAY-{$year}-%";

        $stmt = $this->db->prepare('SELECT payment_no FROM payments WHERE payment_no LIKE ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$like]);
        $last = (string)($stmt->fetchColumn() ?: '');

        $nextSeq = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $nextSeq = ((int)$m[1]) + 1;
        }

        return sprintf('%s-PAY-%s-%04d', $prefix, $year, $nextSeq);
    }
}
