<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
use PDOException;
final class Database {
 private static ?PDO $pdo=null;
 public static function connection(): PDO {
  if (self::$pdo) return self::$pdo;
  $dsn='mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306').';dbname='.env('DB_NAME','nsg_procurement').';charset='.env('DB_CHARSET','utf8mb4');
  self::$pdo=new PDO($dsn,(string)env('DB_USER','root'),(string)env('DB_PASS',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
  return self::$pdo;
 }
}
