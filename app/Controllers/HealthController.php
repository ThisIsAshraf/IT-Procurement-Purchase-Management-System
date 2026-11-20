<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Request;
use App\Core\Database;
final class HealthController extends BaseController { public function status(Request $r): void { try { Database::connection()->query('SELECT 1'); \App\Core\Response::json(['status'=>'ok','database'=>'connected']); } catch (\Throwable $e) { \App\Core\Response::json(['status'=>'degraded','database'=>'unavailable'],503); } } }
