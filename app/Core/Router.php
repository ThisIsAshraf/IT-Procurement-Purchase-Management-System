<?php
declare(strict_types=1);
namespace App\Core;
final class Router {
 private array $routes=[];
 public function get(string $path,callable|array $handler): void {$this->add('GET',$path,$handler);}
 public function post(string $path,callable|array $handler): void {$this->add('POST',$path,$handler);}
 private function add(string $method,string $path,callable|array $handler): void {$this->routes[$method][$path]=$handler;}
 public function dispatch(Request $request): void {
  $path=$request->path(); $method=$request->method();
  $handler=$this->routes[$method][$path]??null;
  if(!$handler){foreach($this->routes[$method]??[] as $route=>$candidate){$pattern='#^'.preg_replace('#\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}#','(?P<$1>[^/]+)',rtrim($route,'/')).'/?$#';if(preg_match($pattern,rtrim($path,'/'),$m)){$_GET['_route_params']=array_filter($m,'is_string',ARRAY_FILTER_USE_KEY);$handler=$candidate;break;}}}
  if(!$handler){http_response_code(404);echo '404 - Page not found';return;}
  if(is_array($handler)){[$class,$action]=$handler;(new $class())->$action($request);}else{$handler($request);}
 }
}
