<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class CatalogModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function items(): array
    {
        return $this->db->query(
            'SELECT ci.*, ic.name AS category_name, ic.category_code, u.unit_code, u.name AS unit_name
             FROM catalog_items ci
             JOIN item_categories ic ON ic.id = ci.category_id
             JOIN units_of_measure u ON u.id = ci.unit_id
             ORDER BY ci.item_name ASC'
        )->fetchAll();
    }

    public function categories(): array
    {
        return $this->db->query(
            'SELECT c.*, p.name AS parent_name
             FROM item_categories c
             LEFT JOIN item_categories p ON p.id = c.parent_id
             ORDER BY c.name ASC'
        )->fetchAll();
    }

    public function units(): array
    {
        return $this->db->query('SELECT * FROM units_of_measure ORDER BY name ASC')->fetchAll();
    }

    public function exchangeRates(): array
    {
        return $this->db->query(
            'SELECT er.*, u.full_name AS created_by_name
             FROM exchange_rates er
             LEFT JOIN users u ON u.id = er.created_by
             ORDER BY er.effective_date DESC, er.id DESC
             LIMIT 100'
        )->fetchAll();
    }

    public function paymentTerms(): array
    {
        return $this->db->query('SELECT * FROM payment_terms ORDER BY net_days ASC')->fetchAll();
    }

    public function createItem(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO catalog_items (category_id, unit_id, sku, item_name, description, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['unit_id'],
            $data['sku'] !== '' ? strtoupper($data['sku']) : null,
            $data['item_name'],
            $data['description'] !== '' ? $data['description'] : null,
            $data['is_active'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateItem(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE catalog_items
             SET category_id = ?, unit_id = ?, sku = ?, item_name = ?, description = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['category_id'],
            $data['unit_id'],
            $data['sku'] !== '' ? strtoupper($data['sku']) : null,
            $data['item_name'],
            $data['description'] !== '' ? $data['description'] : null,
            $data['is_active'],
            $id,
        ]);
    }

    public function createCategory(?int $parentId, string $code, string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO item_categories (parent_id, category_code, name, is_active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$parentId ?: null, strtoupper($code), $name]);
        return (int)$this->db->lastInsertId();
    }

    public function createUnit(string $code, string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO units_of_measure (unit_code, name) VALUES (?, ?)');
        $stmt->execute([strtoupper($code), $name]);
        return (int)$this->db->lastInsertId();
    }

    public function createExchangeRate(string $from, string $to, float $rate, string $effectiveDate, string $source, int $userId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_date, source, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([strtoupper($from), strtoupper($to), $rate, $effectiveDate, $source ?: 'Manual', $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function createPaymentTerm(string $name, int $netDays, string $description): int
    {
        $stmt = $this->db->prepare('INSERT INTO payment_terms (term_name, net_days, description) VALUES (?, ?, ?)');
        $stmt->execute([$name, $netDays, $description ?: null]);
        return (int)$this->db->lastInsertId();
    }
}
