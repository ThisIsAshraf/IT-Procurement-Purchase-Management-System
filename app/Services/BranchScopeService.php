<?php

declare(strict_types=1);

namespace App\Services;

final class BranchScopeService
{
    /**
     * Appends SQL WHERE clause and parameter bindings for branch scoping.
     *
     * @param string $column Qualified SQL column name (e.g., "pr.branch_id" or "b.id")
     * @param array<int, mixed> $params Reference to query parameter array
     */
    public static function apply(string $column, array &$params): string
    {
        $scopes = branch_scope_ids();
        if ($scopes === null) {
            return '';
        }
        $placeholders = implode(',', array_fill(0, count($scopes), '?'));
        foreach ($scopes as $branchId) {
            $params[] = $branchId;
        }
        return " AND {$column} IN ({$placeholders})";
    }

    public static function canAccessBranch(int $branchId): bool
    {
        $scopes = branch_scope_ids();
        if ($scopes === null) {
            return true;
        }
        return in_array($branchId, $scopes, true);
    }

    public static function assertBranchAccess(int $branchId): void
    {
        if (!self::canAccessBranch($branchId)) {
            http_response_code(403);
            exit('403 - You do not have access to records in this branch.');
        }
    }
}
