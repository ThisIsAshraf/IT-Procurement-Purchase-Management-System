<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    /**
     * Queue an email in notification_queue.
     *
     * @param array<string, mixed>|null $payload
     */
    public static function queue(
        string $to,
        string $subject,
        string $html,
        ?int $templateId = null,
        ?array $payload = null
    ): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO notification_queue (recipient_email, template_id, subject, body, payload, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($to),
            $templateId,
            $subject,
            $html,
            $payload !== null ? json_encode($payload) : null,
            'pending',
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Send pending/retryable emails using Outgoing SMTP settings from Admin Panel.
     */
    public static function sendQueued(int $limit = 50, ?string &$lastError = null): int
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException('PHPMailer is not installed. Run composer install first.');
        }

        $pdo = Database::connection();
        $lim = max(1, min(200, $limit));
        $q = $pdo->query(
            "SELECT * FROM notification_queue
             WHERE status IN ('pending', 'failed')
               AND attempts < 3
               AND available_at <= NOW()
             ORDER BY id ASC
             LIMIT {$lim}"
        );

        $host       = trim(SettingsService::get('smtp_host', (string)env('MAIL_HOST', '')));
        $port       = (int)SettingsService::get('smtp_port', (string)env('MAIL_PORT', '587'));
        $username   = trim(SettingsService::get('smtp_username', (string)env('MAIL_USERNAME', '')));
        $rawPass    = SettingsService::get('smtp_password', (string)env('MAIL_PASSWORD', ''));
        $password   = preg_replace('/\s+/', '', $rawPass) ?? '';
        $encryption = strtolower(trim(SettingsService::get('smtp_encryption', (string)env('MAIL_ENCRYPTION', 'tls'))));
        $fromEmail  = trim(SettingsService::get('smtp_from_email', (string)env('MAIL_FROM_ADDRESS', $username)));
        $fromName   = trim(SettingsService::get('smtp_from_name', (string)env('MAIL_FROM_NAME', 'NSG Procurement')));

        $sent = 0;
        foreach ($q->fetchAll() as $row) {
            $pdo->prepare("UPDATE notification_queue SET status = 'sending', attempts = attempts + 1 WHERE id = ?")
                ->execute([$row['id']]);

            try {
                if ($host === '' || $fromEmail === '') {
                    throw new \RuntimeException('SMTP Host and From Email Address must be configured in Site Settings -> Outgoing SMTP Mail.');
                }

                $m = new PHPMailer(true);
                $m->CharSet = 'UTF-8';
                $m->isSMTP();
                $m->Host = $host;
                $m->Port = $port > 0 ? $port : 587;
                $m->Timeout = 15;

                if ($username !== '' || $password !== '') {
                    $m->SMTPAuth = true;
                    $m->Username = $username;
                    $m->Password = $password;
                } else {
                    $m->SMTPAuth = false;
                }

                if ($encryption === 'ssl') {
                    $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($encryption === 'tls') {
                    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $m->SMTPSecure = '';
                    $m->SMTPAutoTLS = false;
                }

                // Prevent local XAMPP/Windows CA bundle failures
                $m->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $m->setFrom($fromEmail, $fromName !== '' ? $fromName : 'NSG Procurement');
                $m->addAddress((string)$row['recipient_email']);
                $m->isHTML(true);
                $m->Subject = (string)$row['subject'];
                $m->Body    = (string)$row['body'];
                $m->AltBody = strip_tags((string)$row['body']);

                $m->send();

                $pdo->prepare("UPDATE notification_queue SET status = 'sent', sent_at = NOW(), last_error = NULL WHERE id = ?")
                    ->execute([$row['id']]);
                $sent++;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $pdo->prepare("UPDATE notification_queue SET status = 'failed', last_error = ? WHERE id = ?")
                    ->execute([substr($lastError, 0, 2000), $row['id']]);
            }
        }

        return $sent;
    }
}
