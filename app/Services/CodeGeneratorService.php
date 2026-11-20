<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class CodeGeneratorService
{
    public static function globalPrefix(): string
    {
        $p = strtoupper(trim(SettingsService::get('global_id_prefix', 'NSG')));
        return $p !== '' ? $p : 'NSG';
    }

    public static function nextSku(int $categoryId): string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT category_code FROM item_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$categoryId]);
        $catCode = strtoupper(trim((string)($stmt->fetchColumn() ?: 'ITEM')));

        $usePrefix = SettingsService::get('sku_include_prefix', '1') === '1';
        $pad = max(2, min(8, (int)SettingsService::get('sku_seq_digits', '4')));
        $base = ($usePrefix ? self::globalPrefix() . '-' : '') . $catCode . '-';

        return self::nextSequence('catalog_items', 'sku', $base, $pad);
    }

    public static function nextCategoryCode(string $categoryName = ''): string
    {
        $mode = SettingsService::get('cat_code_mode', 'prefix_seq'); // prefix_seq | name_slug
        $pad = max(2, min(6, (int)SettingsService::get('cat_seq_digits', '3')));

        if ($mode === 'name_slug' && trim($categoryName) !== '') {
            $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $categoryName) ?: 'CAT');
            $slug = substr($slug, 0, 8);
            $pdo = Database::connection();
            $chk = $pdo->prepare('SELECT COUNT(*) FROM item_categories WHERE category_code = ?');
            $chk->execute([$slug]);
            if ((int)$chk->fetchColumn() === 0) {
                return $slug;
            }
            return self::nextSequence('item_categories', 'category_code', $slug . '-', $pad);
        }

        $catPrefix = strtoupper(trim(SettingsService::get('cat_code_prefix', 'CAT')));
        $useGlobal = SettingsService::get('cat_include_global_prefix', '0') === '1';
        $base = ($useGlobal ? self::globalPrefix() . '-' : '') . ($catPrefix !== '' ? $catPrefix : 'CAT') . '-';

        return self::nextSequence('item_categories', 'category_code', $base, $pad);
    }

    public static function nextEmployeeNumber(int $branchId = 0): string
    {
        $useGlobal = SettingsService::get('emp_include_global_prefix', '1') === '1';
        $useBranch = SettingsService::get('emp_include_branch_code', '1') === '1';
        $empTag = strtoupper(trim(SettingsService::get('emp_code_tag', 'EMP')));
        $pad = max(2, min(8, (int)SettingsService::get('emp_seq_digits', '4')));

        $parts = [];
        if ($useGlobal) {
            $parts[] = self::globalPrefix();
        }
        if ($useBranch && $branchId > 0) {
            $stmt = Database::connection()->prepare('SELECT branch_code FROM branches WHERE id = ? LIMIT 1');
            $stmt->execute([$branchId]);
            $bCode = strtoupper(trim((string)($stmt->fetchColumn() ?: '')));
            if ($bCode !== '') {
                $parts[] = $bCode;
            }
        }
        if (!$useBranch || empty($parts)) {
            $parts[] = $empTag !== '' ? $empTag : 'EMP';
        } elseif ($empTag !== '' && SettingsService::get('emp_include_tag_with_branch', '0') === '1') {
            $parts[] = $empTag;
        }

        $base = implode('-', $parts) . '-';
        return self::nextSequence('employees', 'employee_number', $base, $pad);
    }

    private static function nextSequence(string $table, string $column, string $prefix, int $digits): string
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC");
        $stmt->execute([$prefix . '%']);

        $max = 0;
        $prefixLen = strlen($prefix);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $existing) {
            $tail = substr((string)$existing, $prefixLen);
            if ($tail !== '' && ctype_digit($tail)) {
                $num = (int)$tail;
                if ($num > $max) {
                    $max = $num;
                }
            }
        }

        return $prefix . str_pad((string)($max + 1), $digits, '0', STR_PAD_LEFT);
    }
}
