<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$base = dirname(__DIR__);
if (file_exists($base . '/.env')) Dotenv\Dotenv::createImmutable($base)->safeLoad();
require $base . '/app/helpers.php';
require $base . '/app/Core/Database.php';
$email = $argv[1] ?? '';
$name = $argv[2] ?? 'NSG Administrator';
$password = $argv[3] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php email@company.com \"Full Name\" \"PasswordMinimum12\"\n");
    exit(1);
}
$pdo = null;
try {
    $pdo = App\Core\Database::connection();
    $pdo->beginTransaction();
    $q = $pdo->prepare("INSERT INTO users(full_name,email,password_hash,status) VALUES(?,?,?,'active')");
    $q->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int)$pdo->lastInsertId();
    $role = $pdo->query("SELECT id FROM roles WHERE role_key='super_admin'")->fetchColumn();
    if (!$role) throw new RuntimeException('Run migrations first.');
    $pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$id, $role]);
    $pdo->commit();
    echo "Administrator created: {$email}\n";
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
