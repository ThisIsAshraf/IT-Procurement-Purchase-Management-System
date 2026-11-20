<?php
declare(strict_types=1);

use App\Core\Application;
use Dotenv\Dotenv;

$base = dirname(__DIR__);
if (file_exists($base . '/.env')) { Dotenv::createImmutable($base)->safeLoad(); }
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
if (env('APP_DEBUG', 'false') === 'true') { ini_set('display_errors', '1'); error_reporting(E_ALL); }
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('NSGPROCSESSID');
    session_set_cookie_params(['httponly'=>true, 'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 'samesite'=>'Lax']);
    session_start();
}
require_once __DIR__ . '/helpers.php';
return new Application($base);
