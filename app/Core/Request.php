<?php
declare(strict_types=1);
namespace App\Core;
final class Request {
 public function method(): string{return strtoupper($_SERVER['REQUEST_METHOD']??'GET');}
 public function path(): string{$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';$base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/');if($base&&str_starts_with($path,$base))$path=substr($path,strlen($base))?:'/';return '/'.trim($path,'/');}
 public function input(?string $key=null,mixed $default=null): mixed {$data=$this->method()==='POST'?$_POST:$_GET;return $key===null?$data:($data[$key]??$default);}
 public function files(): array{return $_FILES;}
 public function params(): array{return $_GET['_route_params']??[];}
}
