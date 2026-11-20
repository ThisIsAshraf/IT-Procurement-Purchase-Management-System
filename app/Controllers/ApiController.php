<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

final class ApiController
{
    private function authenticate(string $requiredScope = ''): array
    {
        header('Content-Type: application/json; charset=utf-8');
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(nsg_live_[a-f0-9]+)$/i', trim($authHeader), $m)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid Bearer API token.']);
            exit;
        }

        $hash = hash('sha256', $m[1]);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM api_keys WHERE key_hash = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$hash]);
        $key = $stmt->fetch();

        if (!$key) {
            http_response_code(401);
            echo json_encode(['error' => 'API key is invalid or revoked.']);
            exit;
        }

        if (!empty($key['expires_at']) && strtotime((string)$key['expires_at'] . ' 23:59:59') < time()) {
            http_response_code(403);
            echo json_encode(['error' => 'API key has expired.']);
            exit;
        }

        if (!empty($key['allowed_ips'])) {
            $allowed = array_map('trim', explode(',', (string)$key['allowed_ips']));
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!in_array($clientIp, $allowed, true)) {
                http_response_code(403);
                echo json_encode(['error' => 'Client IP not in allowlist.']);
                exit;
            }
        }

        $scopes = json_decode((string)($key['scopes_json'] ?? '[]'), true) ?: [];
        if ($requiredScope !== '' && !in_array($requiredScope, $scopes, true)) {
            http_response_code(403);
            echo json_encode(['error' => "Missing required scope: {$requiredScope}"]);
            exit;
        }

        $pdo->prepare('UPDATE api_keys SET last_used_at = NOW(), request_count = request_count + 1 WHERE id = ?')
            ->execute([(int)$key['id']]);

        return $key;
    }

    public function health(Request $r): void
    {
        $this->authenticate();
        echo json_encode(['status' => 'ok', 'service' => 'NSG Procurement API v1', 'timestamp' => date('c')]);
    }

    public function purchaseOrders(Request $r): void
    {
        $this->authenticate('procurement:read');
        $rows = Database::connection()->query(
            'SELECT po.id, po.po_number, po.order_date, po.status, po.currency_code, po.total_amount, po.base_total_amount, v.legal_name AS vendor_name
             FROM purchase_orders po JOIN vendors v ON v.id = po.vendor_id ORDER BY po.id DESC LIMIT 100'
        )->fetchAll();
        echo json_encode(['data' => $rows]);
    }

    public function vendors(Request $r): void
    {
        $this->authenticate('vendors:read');
        $rows = Database::connection()->query(
            'SELECT id, vendor_code, legal_name, tax_registration_no, website, status, created_at FROM vendors ORDER BY legal_name ASC LIMIT 200'
        )->fetchAll();
        echo json_encode(['data' => $rows]);
    }

    public function summary(Request $r): void
    {
        $this->authenticate('reports:summary');
        $pdo = Database::connection();
        echo json_encode([
            'purchase_requests' => (int)$pdo->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn(),
            'requisitions'      => (int)$pdo->query('SELECT COUNT(*) FROM requisitions')->fetchColumn(),
            'purchase_orders'   => (int)$pdo->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn(),
            'vendors'           => (int)$pdo->query('SELECT COUNT(*) FROM vendors')->fetchColumn(),
            'invoices'          => (int)$pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
        ]);
    }
}
