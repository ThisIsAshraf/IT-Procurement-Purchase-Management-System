<?php
declare(strict_types=1);
namespace App\Core;
final class Application {
 private Router $router;
 public function __construct(private string $basePath) { $this->router=new Router(); $routes=require $basePath.'/routes/web.php'; $routes($this->router); }
 public function run(): void { try { $this->router->dispatch(new Request()); } catch (\Throwable $e) { error_log((string)$e); http_response_code(500); echo env('APP_DEBUG','false')==='true' ? '<pre>'.htmlspecialchars($e->getMessage()).'</pre>' : 'Application error. Check the server log.'; } }
}
