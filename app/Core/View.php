<?php
declare(strict_types=1);
namespace App\Core;
final class View {public static function render(string $name,array $data=[]):void{$file=dirname(__DIR__).'/Views/'.$name.'.php';if(!is_file($file))throw new \RuntimeException('View not found: '.$name);extract($data,EXTR_SKIP);ob_start();require $file;$content=(string)ob_get_clean();require dirname(__DIR__).'/Views/layout.php';}}
