<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| This file returns database connection settings.
| Values are loaded from .env via Env class.
*/

return [
    'host'    => Env::get('DB_HOST', 'localhost'),
    'db'      => Env::get('DB_NAME', 'zoro_db'),
    'user'    => Env::get('DB_USER', 'root'),
    'pass'    => Env::get('DB_PASS', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
];
