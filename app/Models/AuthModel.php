<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuthModel
{
    public function byEmail(string $email): ?array
    {
        $sql = "SELECT u.*, e.branch_id, e.department_id
                FROM users u
                LEFT JOIN employees e ON e.id = u.employee_id
                WHERE u.email = ? AND u.status = 'active'
                LIMIT 1";
        $q = Database::connection()->prepare($sql);
        $q->execute([$email]);
        return $q->fetch() ?: null;
    }

    public function permissions(int $userId): array
    {
        $q = Database::connection()->prepare(
            'SELECT DISTINCT p.permission_key
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ?'
        );
        $q->execute([$userId]);
        return array_column($q->fetchAll(), 'permission_key');
    }

    public function roles(int $userId): array
    {
        $q = Database::connection()->prepare(
            'SELECT r.role_key
             FROM roles r
             JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ? AND r.is_active = 1'
        );
        $q->execute([$userId]);
        return array_column($q->fetchAll(), 'role_key');
    }

    public function branchScopes(int $userId): array
    {
        $q = Database::connection()->prepare(
            'SELECT branch_id FROM user_branch_scopes WHERE user_id = ?'
        );
        $q->execute([$userId]);
        return array_map('intval', array_column($q->fetchAll(), 'branch_id'));
    }
}
