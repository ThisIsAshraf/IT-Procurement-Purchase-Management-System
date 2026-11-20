<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class DocumentNumberService
{
    public static function next(string $prefix, string $table, string $column): string
    {
        $pdo = Database::connection();
        $year = date('Y');
        $like = "{$prefix}-{$year}-%";

        $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$like]);
        $last = (string)($stmt->fetchColumn() ?: '');

        $seq = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int)$m[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }
}
