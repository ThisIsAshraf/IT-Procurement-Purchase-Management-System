<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SettingsService
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = Database::connection()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            $map = [];
            foreach ($rows as $r) {
                $map[(string)$r['setting_key']] = (string)($r['setting_value'] ?? '');
            }
            self::$cache = $map;
            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();
        return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function setMany(array $pairs, ?int $userId = null): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        );
        foreach ($pairs as $k => $v) {
            $stmt->execute([(string)$k, (string)$v, $userId]);
        }
        self::$cache = null;
    }
}
