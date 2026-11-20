<?php
declare(strict_types=1);
return ['driver'=>'mysql','host'=>env('DB_HOST','127.0.0.1'),'port'=>env('DB_PORT','3306'),'database'=>env('DB_NAME','nsg_procurement'),'username'=>env('DB_USER','root'),'password'=>env('DB_PASS',''),'charset'=>env('DB_CHARSET','utf8mb4')];
