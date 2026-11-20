<?php
declare(strict_types=1);namespace App\Middleware;final class AuthMiddleware{public static function check():void{if(!current_user()){redirect('/login');}}}
