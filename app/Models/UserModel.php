<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allWithRolesAndScopes(): array
    {
        $users = $this->db->query(
            'SELECT u.id, u.employee_id, u.full_name, u.email, u.status, u.last_login_at, u.created_at,
                    e.employee_number, b.name AS home_branch, d.name AS home_department
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             LEFT JOIN branches b ON b.id = e.branch_id
             LEFT JOIN departments d ON d.id = e.department_id
             ORDER BY u.id DESC'
        )->fetchAll();

        $roleRows = $this->db->query(
            'SELECT ur.user_id, r.id AS role_id, r.name AS role_name, r.role_key
             FROM user_roles ur
             JOIN roles r ON r.id = ur.role_id'
        )->fetchAll();

        $scopeRows = $this->db->query(
            'SELECT ubs.user_id, b.id AS branch_id, b.branch_code, b.name AS branch_name
             FROM user_branch_scopes ubs
             JOIN branches b ON b.id = ubs.branch_id'
        )->fetchAll();

        $rolesByUser = [];
        foreach ($roleRows as $rr) {
            $rolesByUser[(int)$rr['user_id']][] = $rr;
        }

        $scopesByUser = [];
        foreach ($scopeRows as $sr) {
            $scopesByUser[(int)$sr['user_id']][] = $sr;
        }

        foreach ($users as &$u) {
            $uid = (int)$u['id'];
            $u['roles'] = $rolesByUser[$uid] ?? [];
            $u['branch_scopes'] = $scopesByUser[$uid] ?? [];
        }

        return $users;
    }

    public function unlinkedEmployees(): array
    {
        return $this->db->query(
            "SELECT e.id, e.employee_number, e.full_name, e.email, b.name AS branch_name, d.name AS department_name
             FROM employees e
             JOIN branches b ON b.id = e.branch_id
             JOIN departments d ON d.id = e.department_id
             LEFT JOIN users u ON u.employee_id = e.id
             WHERE u.id IS NULL AND e.employment_status = 'active'
             ORDER BY e.full_name ASC"
        )->fetchAll();
    }

    public function createUser(array $data, array $roleIds, array $branchIds, int $actorId): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (employee_id, full_name, email, password_hash, status)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['employee_id'] ?: null,
                $data['full_name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['status'] ?: 'active',
            ]);
            $userId = (int)$this->db->lastInsertId();

            $this->syncUserRoles($userId, $roleIds, $actorId);
            $this->syncUserBranchScopes($userId, $branchIds);

            $this->db->commit();
            return $userId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateUser(int $userId, array $data, array $roleIds, array $branchIds, int $actorId): void
    {
        $this->db->beginTransaction();
        try {
            if (!empty($data['password'])) {
                $stmt = $this->db->prepare(
                    'UPDATE users SET full_name = ?, email = ?, status = ?, password_hash = ? WHERE id = ?'
                );
                $stmt->execute([
                    $data['full_name'],
                    $data['email'],
                    $data['status'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $userId,
                ]);
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE users SET full_name = ?, email = ?, status = ? WHERE id = ?'
                );
                $stmt->execute([
                    $data['full_name'],
                    $data['email'],
                    $data['status'],
                    $userId,
                ]);
            }

            $this->syncUserRoles($userId, $roleIds, $actorId);
            $this->syncUserBranchScopes($userId, $branchIds);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function syncUserRoles(int $userId, array $roleIds, int $actorId): void
    {
        $this->db->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
        $ins = $this->db->prepare('INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)');
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            if ($roleId > 0) {
                $ins->execute([$userId, $roleId, $actorId]);
            }
        }
    }

    private function syncUserBranchScopes(int $userId, array $branchIds): void
    {
        $this->db->prepare('DELETE FROM user_branch_scopes WHERE user_id = ?')->execute([$userId]);
        $ins = $this->db->prepare('INSERT INTO user_branch_scopes (user_id, branch_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $branchIds)) as $branchId) {
            if ($branchId > 0) {
                $ins->execute([$userId, $branchId]);
            }
        }
    }

    public function rolesWithPermissions(): array
    {
        $roles = $this->db->query('SELECT * FROM roles ORDER BY is_system DESC, name ASC')->fetchAll();
        $perms = $this->db->query('SELECT * FROM permissions ORDER BY module ASC, permission_key ASC')->fetchAll();
        $mappings = $this->db->query('SELECT role_id, permission_id FROM role_permissions')->fetchAll();

        $assigned = [];
        foreach ($mappings as $m) {
            $assigned[(int)$m['role_id']][] = (int)$m['permission_id'];
        }

        $groupedPerms = [];
        foreach ($perms as $p) {
            $groupedPerms[$p['module']][] = $p;
        }

        return [
            'roles' => $roles,
            'grouped_permissions' => $groupedPerms,
            'assigned' => $assigned,
        ];
    }

    public function createRole(string $roleKey, string $name, string $description): int
    {
        $stmt = $this->db->prepare('INSERT INTO roles (role_key, name, description, is_system, is_active) VALUES (?, ?, ?, 0, 1)');
        $stmt->execute([strtolower($roleKey), $name, $description ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
            $ins = $this->db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
            foreach (array_unique(array_map('intval', $permissionIds)) as $pid) {
                if ($pid > 0) {
                    $ins->execute([$roleId, $pid]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
