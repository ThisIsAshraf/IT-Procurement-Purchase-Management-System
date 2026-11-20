<?php
declare(strict_types=1);namespace App\Middleware;
final class CsrfMiddleware{public static function verify():void{$sent=(string)($_POST['_csrf']??'');$known=(string)($_SESSION['_csrf']??'');if($sent===''||$known===''||!hash_equals($known,$sent)){http_response_code(419);exit('Invalid or expired security token. Please go back and retry.');}}}
