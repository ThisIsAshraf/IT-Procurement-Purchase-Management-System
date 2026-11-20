<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

final class AuditLogController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('audit.view');

        $q = trim((string)$r->input('q', ''));
        $params = [];
        $sql = 'SELECT al.*, u.full_name AS user_name, u.email AS user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1';

        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (al.action LIKE ? OR al.entity_type LIKE ? OR u.full_name LIKE ?)';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY al.id DESC LIMIT 300';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->view('audit/index', [
            'logs' => $stmt->fetchAll(),
            'q'    => $q,
        ]);
    }
}
