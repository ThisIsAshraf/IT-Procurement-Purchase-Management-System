<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
abstract class BaseController {
 protected function view(string $name,array $data=[]): void { View::render($name,$data); }
 protected function auth(): void { AuthMiddleware::check(); }
 protected function csrf(): void { CsrfMiddleware::verify(); }
 protected function require(string $permission): void { \require_permission($permission); }
}
