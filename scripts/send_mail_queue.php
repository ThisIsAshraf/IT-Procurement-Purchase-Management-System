<?php
declare(strict_types=1);require dirname(__DIR__).'/vendor/autoload.php';$base=dirname(__DIR__);if(file_exists($base.'/.env'))Dotenv\Dotenv::createImmutable($base)->safeLoad();require $base.'/app/helpers.php';require $base.'/app/Core/Database.php';require $base.'/app/Services/MailService.php';echo 'Sent '.App\Services\MailService::sendQueued()." queued messages.\n";
