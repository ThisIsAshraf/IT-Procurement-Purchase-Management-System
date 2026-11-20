<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class BudgetService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createBudgetWithAllocations(array $header, array $allocations): int
    {
        BranchScopeService::assertBranchAccess((int)$header['branch_id']);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO budgets (branch_id, department_id, fiscal_year, currency_code, budget_name, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int)$header['branch_id'],
                (int)$header['department_id'],
                (int)$header['fiscal_year'],
                strtoupper((string)$header['currency_code']),
                $header['budget_name'],
                $header['status'] ?: 'active',
            ]);
            $budgetId = (int)$this->db->lastInsertId();

            $insAlloc = $this->db->prepare(
                'INSERT INTO budget_allocations (budget_id, category_id, allocated_amount) VALUES (?, ?, ?)'
            );
            foreach ($allocations as $al) {
                $amt = (float)($al['amount'] ?? 0);
                if ($amt <= 0) {
                    continue;
                }
                $catId = !empty($al['category_id']) ? (int)$al['category_id'] : null;
                $insAlloc->execute([$budgetId, $catId, round($amt, 2)]);
            }

            $this->db->commit();
            \audit_log('budget_created', 'budgets', $budgetId, null, $header);
            return $budgetId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addAllocation(int $budgetId, ?int $categoryId, float $amount): void
    {
        if ($amount <= 0) {
            throw new \RuntimeException('Allocation amount must be greater than zero.');
        }
        $this->db->prepare('INSERT INTO budget_allocations (budget_id, category_id, allocated_amount) VALUES (?, ?, ?)')
            ->execute([$budgetId, $categoryId ?: null, round($amount, 2)]);
        \audit_log('budget_allocation_added', 'budgets', $budgetId, null, ['category_id' => $categoryId, 'amount' => $amount]);
    }

    public function createRecurringPurchase(array $data, int $ownerUserId): int
    {
        BranchScopeService::assertBranchAccess((int)$data['branch_id']);
        $code = DocumentNumberService::next('REC', 'recurring_purchases', 'schedule_code');

        $stmt = $this->db->prepare(
            "INSERT INTO recurring_purchases
             (schedule_code, title, vendor_id, branch_id, department_id, category_id, currency_code, amount,
              frequency, start_date, next_renewal_date, auto_create_requisition, owner_user_id, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)"
        );
        $stmt->execute([
            $code,
            $data['title'],
            (int)$data['vendor_id'],
            (int)$data['branch_id'],
            (int)$data['department_id'],
            !empty($data['category_id']) ? (int)$data['category_id'] : null,
            strtoupper((string)$data['currency_code']),
            round((float)$data['amount'], 2),
            $data['frequency'],
            $data['start_date'],
            $data['next_renewal_date'],
            !empty($data['auto_create_requisition']) ? 1 : 0,
            $ownerUserId,
            $data['notes'] !== '' ? $data['notes'] : null,
        ]);
        $id = (int)$this->db->lastInsertId();
        \audit_log('recurring_purchase_created', 'recurring_purchases', $id, null, ['schedule_code' => $code]);
        return $id;
    }

    public function triggerRenewalRequisition(int $recurringId, int $userId): int
    {
        $stmt = $this->db->prepare('SELECT * FROM recurring_purchases WHERE id = ? LIMIT 1');
        $stmt->execute([$recurringId]);
        $rec = $stmt->fetch();
        if (!$rec) {
            throw new \RuntimeException('Recurring purchase schedule not found.');
        }

        BranchScopeService::assertBranchAccess((int)$rec['branch_id']);

        $unitId = (int)($this->db->query("SELECT id FROM units_of_measure WHERE unit_code IN ('YEAR','MONTH','SERVICE','EA') LIMIT 1")->fetchColumn() ?: 1);
        $reqService = new RequisitionService();
        $reqId = $reqService->createStandalone(
            [
                'branch_id'     => (int)$rec['branch_id'],
                'department_id' => (int)$rec['department_id'],
                'currency_code' => (string)$rec['currency_code'],
                'notes'         => "Scheduled renewal ({$rec['schedule_code']}): {$rec['title']}",
            ],
            [[
                'catalog_item_id'   => null,
                'description'       => "{$rec['title']} ({$rec['frequency']} renewal)",
                'quantity'          => 1,
                'unit_id'           => $unitId,
                'target_unit_price' => (float)$rec['amount'],
            ]],
            $userId
        );

        // Advance next_renewal_date by frequency interval
        $interval = match ($rec['frequency']) {
            'monthly'     => '+1 month',
            'quarterly'   => '+3 months',
            'semi_annual' => '+6 months',
            default       => '+1 year',
        };
        $nextDate = date('Y-m-d', strtotime((string)$rec['next_renewal_date'] . ' ' . $interval));
        $this->db->prepare('UPDATE recurring_purchases SET next_renewal_date = ? WHERE id = ?')
            ->execute([$nextDate, $recurringId]);

        \audit_log('recurring_renewal_triggered', 'recurring_purchases', $recurringId, null, [
            'requisition_id'    => $reqId,
            'next_renewal_date' => $nextDate,
        ]);
        return $reqId;
    }
}
