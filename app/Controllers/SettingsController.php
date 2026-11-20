<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\MailService;

final class SettingsController extends BaseController
{
    public function index(Request $r): void
    {
        $this->auth();
        $this->require('settings.manage');

        $pdo = Database::connection();
        $this->view('settings/index', [
            'settings'  => $pdo->query('SELECT * FROM settings ORDER BY setting_key ASC')->fetchAll(),
            'templates' => $pdo->query('SELECT * FROM notification_templates ORDER BY template_key ASC')->fetchAll(),
            'queue'     => $pdo->query('SELECT * FROM notification_queue ORDER BY id DESC LIMIT 50')->fetchAll(),
            'flash'     => flash('success'),
            'error'     => flash('error'),
        ]);
    }

    public function saveSetting(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        $key = trim((string)$r->input('setting_key', ''));
        $val = trim((string)$r->input('setting_value', ''));
        if ($key === '') {
            flash('error', 'Setting key is required.');
            redirect('/settings');
        }

        Database::connection()->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        )->execute([$key, $val, (int)current_user()['id']]);

        \audit_log('system_setting_updated', 'settings', null, null, ['key' => $key, 'value' => $val]);
        flash('success', "Setting '{$key}' saved.");
        redirect('/settings');
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
            flash('error', 'Template key, subject, and HTML body are required.');
            redirect('/settings');
        }

        Database::connection()->prepare(
            'INSERT INTO notification_templates (template_key, subject_template, body_template, is_active)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE subject_template = VALUES(subject_template), body_template = VALUES(body_template)'
        )->execute([$key, $subject, $body]);

        \audit_log('email_template_saved', 'notification_templates', null, null, ['template_key' => $key]);
        flash('success', "Email template '{$key}' saved.");
        redirect('/settings');
    }

    public function dispatchQueue(Request $r): void
    {
        $this->auth();
        $this->csrf();
        $this->require('settings.manage');

        try {
            $count = MailService::sendQueued(50);
            flash('success', "Processed email queue: {$count} email(s) sent.");
        } catch (\Throwable $e) {
            flash('error', 'Queue dispatch error: ' . $e->getMessage());
        }
        redirect('/settings');
    }
}
