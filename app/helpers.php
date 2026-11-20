<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim((string)env('APP_URL', ''), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return !empty($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions'], true);
    }
}

if (!function_exists('require_permission')) {
    function require_permission(string $permission): void
    {
        if (!current_user()) {
            redirect('/login');
        }
        if (!can($permission)) {
            http_response_code(403);
            echo '403 - You do not have permission to access this resource.';
            exit;
        }
    }
}

if (!function_exists('money')) {
    function money(int|float|string $amount, string $currency): string
    {
        return e($currency) . ' ' . number_format((float)$amount, 2);
    }
}

if (!function_exists('audit_log')) {
    function audit_log(string $action, string $entity, ?int $id = null, mixed $before = null, mixed $after = null): void
    {
        try {
            $pdo = \App\Core\Database::connection();
            $s = $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,user_agent,before_data,after_data) VALUES(?,?,?,?,?,?,?,?)');
            $s->execute([
                current_user()['id'] ?? null,
                $action,
                $entity,
                $id,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $before === null ? null : json_encode($before),
                $after === null ? null : json_encode($after),
            ]);
        } catch (\Throwable $e) {
            error_log('Audit log: ' . $e->getMessage());
        }
    }
}
