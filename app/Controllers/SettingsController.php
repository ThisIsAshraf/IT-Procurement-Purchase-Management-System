<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Models\CatalogModel;
use App\Models\OrganizationModel;
use App\Services\MailService;
use App\Services\SettingsService;

final class SettingsController extends BaseController
{
    private const TABS = [
        'branding'  => 'Branding & Theme',
        'timezone'  => 'Date & Timezone',
        'currency'  => 'Currency & Rates',
        'forms'     => 'Forms & Certificates',
        'templates' => 'Email Templates',
        'smtp'      => 'Outgoing SMTP Mail',
        'sso'       => 'Google & Microsoft SSO',
        'api'       => 'API Keys & RBAC',
        'backup'    => 'Backup & Recovery',
    ];

    public function index(Request $r): void
    {
        $this->auth();
        $this->require('settings.manage');

        $tab = (string)$r->input('tab', 'branding');
        if (!isset(self::TABS[$tab])) {
            $tab = 'branding';
        }

        $pdo = Database::connection();
        $org = new OrganizationModel();
        $cat = new CatalogModel();

        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        $archives = [];
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '/*.{sql,tar.gz,zip}', GLOB_BRACE) ?: [] as $f) {
                $archives[] = [
                    'name' => basename($f),
                    'size' => round(filesize($f) / 1024, 1) . ' KB',
                    'date' => date('Y-m-d h:i A', filemtime($f)),
                ];
            }
            rsort($archives);
        }

        $this->view('settings/index', [
            'tab'           => $tab,
            'tabs'          => self::TABS,
            's'             => SettingsService::all(),
            'currencies' => $org->activeCurrencies(),
            'exchangeRates' => $cat->exchangeRates(),
            'templates'     => $pdo->query('SELECT * FROM notification_templates ORDER BY template_key ASC')->fetchAll(),
            'queue'         => $pdo->query('SELECT * FROM notification_queue ORDER BY id DESC LIMIT 30')->fetchAll(),
            'roles'         => $pdo->query('SELECT id, name FROM roles WHERE is_active = 1 ORDER BY name')->fetchAll(),
            'apiKeys'       => $pdo->query('SELECT ak.*, r.name AS role_name FROM api_keys ak JOIN roles r ON r.id = ak.role_id ORDER BY ak.id DESC')->fetchAll(),
            'archives'      => $archives,
            'flash'         => flash('success'),
            'error'         => flash('error'),
        ]);
    }

    public function saveBranding(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $pairs = [
            'app_title'            => trim((string)$r->input('app_title', 'NSG Procurement')),
            'global_id_prefix'     => strtoupper(trim((string)$r->input('global_id_prefix', 'NSG'))),
            'login_headline'       => trim((string)$r->input('login_headline', '')),
            'login_subtitle'       => trim((string)$r->input('login_subtitle', '')),
            'login_footer_note'    => trim((string)$r->input('login_footer_note', '')),
            'show_logo'            => (int)$r->input('show_logo', 0) === 1 ? '1' : '0',
            'logo_max_height'      => (string)max(20, min(120, (int)$r->input('logo_max_height', 38))),
            'logo_max_width'       => (string)max(60, min(320, (int)$r->input('logo_max_width', 170))),
            'sidebar_bg_color'     => trim((string)$r->input('sidebar_bg_color', '#831B30')),
            'sidebar_text_color'   => trim((string)$r->input('sidebar_text_color', '#E2E8F0')),
            'sidebar_active_color' => trim((string)$r->input('sidebar_active_color', '#0284C7')),
        ];

        if ((int)$r->input('remove_logo', 0) === 1) {
            $pairs['company_logo_path'] = '';
        }

        $logo = $r->files()['logo_file'] ?? null;
        if ($logo && ($logo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo((string)$logo['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $dir = dirname(__DIR__, 2) . '/public/assets/uploads';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $filename = 'company_logo_' . time() . '.' . $ext;
                if (move_uploaded_file((string)$logo['tmp_name'], $dir . '/' . $filename)) {
                    $pairs['company_logo_path'] = 'assets/uploads/' . $filename;
                }
            }
        }

        SettingsService::setMany($pairs, (int)current_user()['id']);
        \audit_log('settings_branding_updated', 'settings');
        flash('success', 'Branding & theme settings updated.');
        redirect('/settings?tab=branding');
    }

    public function saveTimezone(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        SettingsService::setMany([
            'system_timezone' => trim((string)$r->input('system_timezone', 'Asia/Dhaka')),
            'date_format'     => trim((string)$r->input('date_format', 'Y-m-d')),
            'time_format'     => trim((string)$r->input('time_format', 'h:i:s A')),
        ], (int)current_user()['id']);

        \audit_log('settings_timezone_updated', 'settings');
        flash('success', 'Date, time & timezone settings saved.');
        redirect('/settings?tab=timezone');
    }

    public function saveCurrency(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $base = strtoupper(trim((string)$r->input('base_currency', 'USD')));
        SettingsService::setMany([
            'base_currency'          => $base,
            'multi_currency_display' => (int)$r->input('multi_currency_display', 0) === 1 ? '1' : '0',
        ], (int)current_user()['id']);

        $newCode = strtoupper(trim((string)$r->input('new_currency_code', '')));
        $newRate = (float)$r->input('new_currency_rate', 0);
        if ($newCode !== '' && $newCode !== $base && $newRate > 0) {
            (new CatalogModel())->createExchangeRate($base, $newCode, $newRate, date('Y-m-d'), 'Admin Settings', (int)current_user()['id']);
        }

        \audit_log('settings_currency_updated', 'settings');
        flash('success', 'Base currency & exchange rates saved.');
        redirect('/settings?tab=currency');
    }

    public function saveForms(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        SettingsService::setMany([
            'pdf_header_title'    => trim((string)$r->input('pdf_header_title', '')),
            'pdf_subtitle_color'  => trim((string)$r->input('pdf_subtitle_color', '#0D6EFD')),
            'pdf_table_header_bg' => trim((string)$r->input('pdf_table_header_bg', '#F1F3F5')),
            'pdf_footer_note'     => trim((string)$r->input('pdf_footer_note', '')),
            'po_heading_color'    => trim((string)$r->input('po_heading_color', '#831B30')),
            'po_signatory_name'   => trim((string)$r->input('po_signatory_name', '')),
            'po_declaration_text' => trim((string)$r->input('po_declaration_text', '')),
        ], (int)current_user()['id']);

        \audit_log('settings_forms_updated', 'settings');
        flash('success', 'PDF forms & document styles updated.');
        redirect('/settings?tab=forms');
    }

    public function saveTemplate(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $key = trim((string)$r->input('template_key', ''));
        $subject = trim((string)$r->input('subject', ''));
        $body = trim((string)$r->input('body_html', ''));

        if ($key === '' || $subject === '' || $body === '') {
            flash('error', 'Template key, subject, and body are required.');
            redirect('/settings?tab=templates');
        }

        Database::connection()->prepare(
            'INSERT INTO notification_templates (template_key, subject_template, body_template, is_active)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE subject_template = VALUES(subject_template), body_template = VALUES(body_template)'
        )->execute([$key, $subject, $body]);

        \audit_log('email_template_saved', 'notification_templates', null, null, ['template_key' => $key]);
        flash('success', "Email template '{$key}' saved.");
        redirect('/settings?tab=templates');
    }

    public function saveSmtp(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        SettingsService::setMany([
            'smtp_host'       => trim((string)$r->input('smtp_host', '')),
            'smtp_port'       => (string)(int)$r->input('smtp_port', 587),
            'smtp_username'   => trim((string)$r->input('smtp_username', '')),
            'smtp_password'   => trim((string)$r->input('smtp_password', '')),
            'smtp_encryption' => trim((string)$r->input('smtp_encryption', 'tls')),
            'smtp_from_email' => trim((string)$r->input('smtp_from_email', '')),
            'smtp_from_name'  => trim((string)$r->input('smtp_from_name', '')),
        ], (int)current_user()['id']);

        \audit_log('settings_smtp_updated', 'settings');
        flash('success', 'Outgoing SMTP mail configuration saved.');
        redirect('/settings?tab=smtp');
    }

    public function sendTestEmail(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $to = trim((string)$r->input('test_recipient', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid test recipient email address.');
            redirect('/settings?tab=smtp');
        }

        MailService::queue($to, 'NSG Procurement — SMTP Test Message', '<p>Your outgoing SMTP configuration is working properly.</p>');
        try {
            $sent = MailService::sendQueued(5);
            flash('success', $sent > 0 ? "Test email delivered to {$to}." : 'Test email queued (check SMTP credentials if delivery failed).');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/settings?tab=smtp');
    }

    public function saveSso(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        SettingsService::setMany([
            'sso_google_enabled'          => (int)$r->input('sso_google_enabled', 0) === 1 ? '1' : '0',
            'sso_google_client_id'        => trim((string)$r->input('sso_google_client_id', '')),
            'sso_google_client_secret'    => trim((string)$r->input('sso_google_client_secret', '')),
            'sso_microsoft_enabled'       => (int)$r->input('sso_microsoft_enabled', 0) === 1 ? '1' : '0',
            'sso_microsoft_client_id'     => trim((string)$r->input('sso_microsoft_client_id', '')),
            'sso_microsoft_client_secret' => trim((string)$r->input('sso_microsoft_client_secret', '')),
            'sso_microsoft_tenant'        => trim((string)$r->input('sso_microsoft_tenant', 'common')),
        ], (int)current_user()['id']);

        \audit_log('settings_sso_updated', 'settings');
        flash('success', 'Google & Microsoft SSO settings saved.');
        redirect('/settings?tab=sso');
    }

    public function createApiKey(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $clientName = trim((string)$r->input('client_name', ''));
        $roleId = (int)$r->input('role_id', 0);
        $rateLimit = max(10, (int)$r->input('rate_limit_per_min', 120));
        $scopes = (array)$r->input('scopes', []);
        $allowedIps = trim((string)$r->input('allowed_ips', ''));
        $expiresAt = trim((string)$r->input('expires_at', ''));

        if ($clientName === '' || $roleId <= 0) {
            flash('error', 'Integration client name and RBAC role are required.');
            redirect('/settings?tab=api');
        }

        $plainToken = 'nsg_live_' . bin2hex(random_bytes(20));
        $prefix = substr($plainToken, 0, 14) . '...';
        $hash = hash('sha256', $plainToken);

        Database::connection()->prepare(
            'INSERT INTO api_keys (client_name, key_prefix, key_hash, role_id, rate_limit_per_min, scopes_json, allowed_ips, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $clientName,
            $prefix,
            $hash,
            $roleId,
            $rateLimit,
            json_encode(array_values($scopes)),
            $allowedIps !== '' ? $allowedIps : null,
            $expiresAt !== '' ? $expiresAt : null,
            (int)current_user()['id'],
        ]);

        \audit_log('api_key_created', 'api_keys', null, null, ['client_name' => $clientName]);
        flash('success', "API Key generated — copy it now (shown once): {$plainToken}");
        redirect('/settings?tab=api');
    }

    public function revokeApiKey(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $id = (int)($r->params()['id'] ?? 0);
        Database::connection()->prepare('UPDATE api_keys SET is_active = 0 WHERE id = ?')->execute([$id]);
        \audit_log('api_key_revoked', 'api_keys', $id);
        flash('success', 'API key revoked.');
        redirect('/settings?tab=api');
    }

    public function createBackup(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $pdo = Database::connection();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $sqlOut = "-- NSG Procurement SQL Snapshot (" . date('Y-m-d H:i:s') . ")\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(\PDO::FETCH_NUM);
            $sqlOut .= "DROP TABLE IF EXISTS `{$t}`;\n" . ($create[1] ?? '') . ";\n\n";
            $rows = $pdo->query("SELECT * FROM `{$t}`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = implode('`,`', array_keys($row));
                $vals = implode(',', array_map(static fn($v): string => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row)));
                $sqlOut .= "INSERT INTO `{$t}` (`{$cols}`) VALUES ({$vals});\n";
            }
            $sqlOut .= "\n";
        }
        $sqlOut .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $file = 'nsg_procurement_backup_' . date('Ymd_His') . '.sql';
        file_put_contents($backupDir . '/' . $file, $sqlOut);

        \audit_log('database_backup_created', 'settings', null, null, ['file' => $file]);
        flash('success', "Database snapshot created: {$file}");
        redirect('/settings?tab=backup');
    }

    public function saveBackupPolicy(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        SettingsService::setMany([
            'backup_frequency'      => trim((string)$r->input('backup_frequency', 'daily')),
            'backup_hour'           => trim((string)$r->input('backup_hour', '02:00')),
            'backup_retention_days' => (string)max(0, (int)$r->input('backup_retention_days', 30)),
        ], (int)current_user()['id']);

        flash('success', 'Automated backup policy saved.');
        redirect('/settings?tab=backup');
    }

    private function resolveBackupPath(string $filename): string
    {
        $clean = basename($filename);
        if ($clean === '' || !preg_match('/^[A-Za-z0-9._-]+\.(sql|zip|tar\.gz)$/', $clean)) {
            throw new \RuntimeException('Invalid backup filename.');
        }
        return dirname(__DIR__, 2) . '/storage/backups/' . $clean;
    }

    public function downloadBackup(Request $r): void
    {
        $this->auth();
        $this->require('settings.manage');

        try {
            $path = $this->resolveBackupPath((string)$r->input('file', ''));
            if (!is_file($path)) {
                throw new \RuntimeException('Backup file not found.');
            }
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            header('Content-Length: ' . (string)filesize($path));
            readfile($path);
            exit;
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/settings?tab=backup');
        }
    }

    public function deleteBackup(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        try {
            $path = $this->resolveBackupPath((string)$r->input('file', ''));
            if (is_file($path)) {
                unlink($path);
                \audit_log('database_backup_deleted', 'settings', null, null, ['file' => basename($path)]);
                flash('success', 'Backup archive deleted: ' . basename($path));
            }
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/settings?tab=backup');
    }

    public function restoreBackup(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        try {
            $sql = '';
            $uploaded = $r->files()['backup_file'] ?? null;
            if ($uploaded && ($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo((string)$uploaded['name'], PATHINFO_EXTENSION));
                if ($ext !== 'sql') {
                    throw new \RuntimeException('Please upload a valid .sql backup dump.');
                }
                $sql = (string)file_get_contents((string)$uploaded['tmp_name']);
            } else {
                $path = $this->resolveBackupPath((string)$r->input('file', ''));
                if (!is_file($path)) {
                    throw new \RuntimeException('Selected backup file does not exist.');
                }
                $sql = (string)file_get_contents($path);
            }

            if (trim($sql) === '') {
                throw new \RuntimeException('Backup SQL file is empty.');
            }

            $pdo = Database::connection();
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
            foreach (preg_split('/;\s*[\r\n]+/', $sql) ?: [] as $stmt) {
                $stmt = trim($stmt);
                if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                    $pdo->exec($stmt);
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

            \audit_log('database_backup_restored', 'settings');
            flash('success', 'Database restored successfully.');
        } catch (\Throwable $e) {
            flash('error', 'Restore failed: ' . $e->getMessage());
        }
        redirect('/settings?tab=backup');
    }
}
