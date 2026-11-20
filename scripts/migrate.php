<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$base=dirname(__DIR__); if(file_exists($base.'/.env')) Dotenv\Dotenv::createImmutable($base)->safeLoad();
require $base.'/app/helpers.php'; require $base.'/app/Core/Database.php';
use App\Core\Database;
try { $pdo=Database::connection(); foreach ([$base.'/database/schema.sql', $base.'/database/seeders/seed.sql'] as $sqlFile) { $sql=file_get_contents($sqlFile); $statements=preg_split('/;\\s*(?=\\r?\\n|$)/', $sql); foreach ($statements as $statement) { $statement=trim($statement); if ($statement !== '') $pdo->exec($statement); } } $s=$pdo->prepare('INSERT IGNORE INTO schema_migrations(migration,batch) VALUES(?,1)'); $s->execute(['2026_11_20_000001_initial_schema']); echo "Database schema and seed data installed successfully.\n"; } catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
