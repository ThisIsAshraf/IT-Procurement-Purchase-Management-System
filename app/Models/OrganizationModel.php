<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\BranchScopeService;
use PDO;

final class OrganizationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function countries(): array
    {
        return $this->db->query('SELECT id, iso2, iso3, name FROM countries ORDER BY name')->fetchAll();
    }

    public function activeCurrencies(): array
    {
        return $this->db->query('SELECT code, name, symbol FROM currencies WHERE is_active = 1 ORDER BY code')->fetchAll();
    }

    public function branches(bool $applyScope = true): array
    {
        $params = [];
        $sql = 'SELECT b.*, c.name AS country_name, c.iso2, cur.symbol AS currency_symbol
                FROM branches b
                JOIN countries c ON c.id = b.country_id
                JOIN currencies cur ON cur.code = b.default_currency
                WHERE 1=1';
        if ($applyScope) {
            $sql .= BranchScopeService::apply('b.id', $params);
        }
        $sql .= ' ORDER BY b.name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createBranch(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO branches (country_id, branch_code, name, address_line1, address_line2, city, region, postal_code, default_currency, timezone, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['country_id'],
            strtoupper($data['branch_code']),
            $data['name'],
            $data['address_line1'] ?: null,
            $data['address_line2'] ?: null,
            $data['city'] ?: null,
            $data['region'] ?: null,
            $data['postal_code'] ?: null,
            strtoupper($data['default_currency']),
            $data['timezone'] ?: 'UTC',
            $data['is_active'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateBranch(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE branches
             SET country_id = ?, branch_code = ?, name = ?, address_line1 = ?, address_line2 = ?,
                 city = ?, region = ?, postal_code = ?, default_currency = ?, timezone = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['country_id'],
            strtoupper($data['branch_code']),
            $data['name'],
            $data['address_line1'] ?: null,
            $data['address_line2'] ?: null,
            $data['city'] ?: null,
            $data['region'] ?: null,
            $data['postal_code'] ?: null,
            strtoupper($data['default_currency']),
            $data['timezone'] ?: 'UTC',
            $data['is_active'],
            $id,
        ]);
    }

    public function departments(): array
    {
        return $this->db->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll();
    }

    public function createDepartment(string $code, string $name, int $isActive): int
    {
        $stmt = $this->db->prepare('INSERT INTO departments (department_code, name, is_active) VALUES (?, ?, ?)');
        $stmt->execute([strtoupper($code), $name, $isActive]);
        return (int)$this->db->lastInsertId();
    }

    public function updateDepartment(int $id, string $code, string $name, int $isActive): void
    {
        $stmt = $this->db->prepare('UPDATE departments SET department_code = ?, name = ?, is_active = ? WHERE id = ?');
        $stmt->execute([strtoupper($code), $name, $isActive, $id]);
    }

    public function employees(): array
    {
        $params = [];
        $sql = 'SELECT e.*, b.name AS branch_name, b.branch_code, d.name AS department_name,
                       m.full_name AS manager_name, u.id AS user_id, u.status AS user_status
                FROM employees e
                JOIN branches b ON b.id = e.branch_id
                JOIN departments d ON d.id = e.department_id
                LEFT JOIN employees m ON m.id = e.manager_employee_id
                LEFT JOIN users u ON u.employee_id = e.id
                WHERE 1=1';
        $sql .= BranchScopeService::apply('e.branch_id', $params);
        $sql .= ' ORDER BY e.full_name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createEmployee(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employees (employee_number, full_name, email, branch_id, department_id, manager_employee_id, job_title, employment_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['employee_number'] ?: null,
            $data['full_name'],
            $data['email'],
            $data['branch_id'],
            $data['department_id'],
            $data['manager_employee_id'] ?: null,
            $data['job_title'] ?: null,
            $data['employment_status'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateEmployee(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE employees
             SET employee_number = ?, full_name = ?, email = ?, branch_id = ?, department_id = ?,
                 manager_employee_id = ?, job_title = ?, employment_status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['employee_number'] ?: null,
            $data['full_name'],
            $data['email'],
            $data['branch_id'],
            $data['department_id'],
            $data['manager_employee_id'] ?: null,
            $data['job_title'] ?: null,
            $data['employment_status'],
            $id,
        ]);
    }
}
