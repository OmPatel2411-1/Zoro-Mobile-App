<?php
declare(strict_types=1);

return [
    'env' => \App\Core\Env::get('APP_ENV', 'local'),
    'base_url' => rtrim(\App\Core\Env::get('BASE_URL', 'http://localhost/Zoro/public'), '/'),
    'app_name' => \App\Core\Env::get('APP_NAME', 'Zoro'),

    'supabase' => [
        'url' => rtrim((string)\App\Core\Env::get('SUPABASE_URL', ''), '/'),
        'anon_key' => (string)\App\Core\Env::get('SUPABASE_ANON_KEY', ''),
        'service_role_key' => (string)\App\Core\Env::get('SUPABASE_SERVICE_ROLE_KEY', ''),
        'jwt_secret' => (string)\App\Core\Env::get('SUPABASE_JWT_SECRET', ''),
        'schema' => (string)\App\Core\Env::get('SUPABASE_DB_SCHEMA', 'public'),
    ],
];
