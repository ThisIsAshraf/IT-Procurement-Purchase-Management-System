<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MailService
{
    public static function queue(string $to, string $subject, string $html): void
    {
        Database::connection()->prepare(
            "INSERT INTO notification_queue(recipient_email, subject, body, status) VALUES(?, ?, ?, 'pending')"
        )->execute([$to, $subject, $html]);
    }

    public static function sendQueued(int $limit = 50): int
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            throw new \RuntimeException('Run composer install first.');
        }

        $host = SettingsService::get('smtp_host', (string)env('MAIL_HOST'));
        $port = (int)SettingsService::get('smtp_port', (string)env('MAIL_PORT', 587));
        $user = SettingsService::get('smtp_username', (string)env('MAIL_USERNAME'));
        $pass = SettingsService::get('smtp_password', (string)env('MAIL_PASSWORD'));
        $enc  = SettingsService::get('smtp_encryption', (string)env('MAIL_ENCRYPTION', 'tls'));
        $from = SettingsService::get('smtp_from_email', (string)env('MAIL_FROM_ADDRESS'));
        $name = SettingsService::get('smtp_from_name', (string)env('MAIL_FROM_NAME', 'NSG Procurement'));

        $pdo = Database::connection();
        $q = $pdo->query(
            "SELECT * FROM notification_queue WHERE status IN ('pending','failed') AND attempts < 3 AND available_at <= NOW() ORDER BY id LIMIT " . max(1, min(200, $limit))
        );

        $sent = 0;
        foreach ($q->fetchAll() as $row) {
            $pdo->prepare("UPDATE notification_queue SET status='sending', attempts=attempts+1 WHERE id=?")->execute([$row['id']]);
            try {
                $m = new \PHPMailer\PHPMailer\PHPMailer(true);
                $m->isSMTP();
                $m->Host = $host;
                $m->SMTPAuth = $user !== '';
                $m->Username = $user;
                $m->Password = $pass;
                if ($enc !== '') {
                    $m->SMTPSecure = $enc;
                }
                $m->Port = $port;
                $m->setFrom($from !== '' ? $from : $user, $name);
                $m->addAddress((string)$row['recipient_email']);
                $m->isHTML(true);
                $m->Subject = (string)$row['subject'];
                $m->Body = (string)$row['body'];
                $m->send();

                $pdo->prepare("UPDATE notification_queue SET status='sent', sent_at=NOW(), last_error=NULL WHERE id=?")->execute([$row['id']]);
                $sent++;
            } catch (\Throwable $e) {
                $pdo->prepare("UPDATE notification_queue SET status='failed', last_error=? WHERE id=?")->execute([substr($e->getMessage(), 0, 2000), $row['id']]);
            }
        }
        return $sent;
    }
}
