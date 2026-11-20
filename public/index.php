<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';

if (is_file($base . '/.env')) {
    Dotenv\Dotenv::createImmutable($base)->safeLoad();
}

if (env('APP_DEBUG', 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

require_once $base . '/app/helpers.php';
date_default_timezone_set((string)env('APP_TIMEZONE', 'UTC'));

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('NSGPROCSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

(new App\Core\Application($base))->run();
