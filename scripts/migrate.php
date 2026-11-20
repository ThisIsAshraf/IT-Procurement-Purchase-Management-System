<?php

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
if (is_file($base . '/.env')) {
    Dotenv\Dotenv::createImmutable($base)->safeLoad();
}
require_once $base . '/app/helpers.php';

use App\Core\Database;

function executeSqlFile(PDO $pdo, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("Unable to read SQL file: {$filePath}");
    }
    $statements = preg_split('/;\s*(?=\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

try {
    $pdo = Database::connection();
    $fresh = in_array('--fresh', $argv ?? [], true);

    if ($fresh) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "Dropped all existing tables (--fresh).\n";
    }

    // Ensure schema_migrations table exists
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(190) NOT NULL UNIQUE,
            batch INT UNSIGNED NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE = InnoDB'
    );

    // Check if initial schema (countries table) already exists
    $hasCountries = (bool)$pdo->query("SHOW TABLES LIKE 'countries'")->fetchColumn();

    if (!$hasCountries) {
        executeSqlFile($pdo, $base . '/database/schema.sql');
        executeSqlFile($pdo, $base . '/database/seeders/seed.sql');
        $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration, batch) VALUES (?, 1)')
            ->execute(['2026_11_20_000001_initial_schema']);
        echo "Installed initial schema and seed data.\n";
    } else {
        // Ensure baseline migration is marked applied so it is never re-run
        $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration, batch) VALUES (?, 1)')
            ->execute(['2026_11_20_000001_initial_schema']);
    }

    // Fetch already applied migrations
    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $appliedMap = array_flip($applied);

    $currentBatch = (int)$pdo->query('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations')->fetchColumn() + 1;

    $migrationFiles = glob($base . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles);

    $ranCount = 0;
    foreach ($migrationFiles as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        if (isset($appliedMap[$name])) {
            continue;
        }
        echo "Applying migration: {$name}...\n";
        executeSqlFile($pdo, $file);
        $pdo->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (?, ?)')
            ->execute([$name, $currentBatch]);
        $ranCount++;
    }

    if ($ranCount === 0) {
        echo "Nothing to migrate. Database is up to date.\n";
    } else {
        echo "Applied {$ranCount} migration(s) successfully (batch {$currentBatch}).\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration error: ' . $e->getMessage() . "\n");
    exit(1);
}
