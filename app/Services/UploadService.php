<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class UploadService
{
    private const ALLOWED_MIMES = [
        'application/pdf'                                                         => 'pdf',
        'image/jpeg'                                                              => 'jpg',
        'image/png'                                                               => 'png',
        'image/webp'                                                              => 'webp',
        'text/csv'                                                                => 'csv',
        'text/plain'                                                              => 'txt',
        'application/msword'                                                      => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel'                                                => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
    ];

    private const MAX_BYTES = 15 * 1024 * 1024; // 15 MB

    public static function storeAndLink(
        array $file,
        string $entityType,
        int $entityId,
        int $uploadedBy,
        ?string $documentKind = null
    ): int {
        if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed or no file was selected.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('File size must be between 1 byte and 15 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new \RuntimeException('Unsupported file type: ' . $mime);
        }

        $ext = self::ALLOWED_MIMES[$mime];
        $subDir = date('Y') . '/' . date('m');
        $baseStorage = dirname(__DIR__, 2) . '/storage/uploads/' . $subDir;
        if (!is_dir($baseStorage) && !mkdir($baseStorage, 0775, true) && !is_dir($baseStorage)) {
            throw new \RuntimeException('Unable to create upload storage directory.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = 'uploads/' . $subDir . '/' . $storedName;
        $fullPath = dirname(__DIR__, 2) . '/storage/' . $relativePath;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Failed to move uploaded file into storage.');
        }

        $sha256 = hash_file('sha256', $fullPath) ?: null;
        $originalName = mb_substr(basename((string)($file['name'] ?? $storedName)), 0, 255);

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO attachments (storage_disk, storage_path, original_name, mime_type, file_size, sha256, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(['local', $relativePath, $originalName, $mime, $size, $sha256, $uploadedBy]);
        $attachmentId = (int)$pdo->lastInsertId();

        $link = $pdo->prepare(
            'INSERT INTO attachment_links (attachment_id, entity_type, entity_id, document_kind)
             VALUES (?, ?, ?, ?)'
        );
        $link->execute([$attachmentId, $entityType, $entityId, $documentKind]);

        return $attachmentId;
    }

    public static function forEntity(string $entityType, int $entityId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT a.*, al.document_kind, u.full_name AS uploaded_by_name
             FROM attachments a
             JOIN attachment_links al ON al.attachment_id = a.id
             LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE al.entity_type = ? AND al.entity_id = ?
             ORDER BY a.created_at DESC'
        );
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll();
    }

    public static function download(int $attachmentId): never
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM attachments WHERE id = ? LIMIT 1');
        $stmt->execute([$attachmentId]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$att) {
            http_response_code(404);
            exit('Attachment not found.');
        }

        $fullPath = dirname(__DIR__, 2) . '/storage/' . ltrim((string)$att['storage_path'], '/');
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit('File missing from storage.');
        }

        header('Content-Type: ' . $att['mime_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string)$att['original_name']) . '"');
        header('Content-Length: ' . (string)$att['file_size']);
        header('X-Content-Type-Options: nosniff');
        readfile($fullPath);
        exit;
    }
}
