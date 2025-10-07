<?php
return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'development',
        'base_url' => getenv('BASE_URL') ?: 'http://localhost',
        'session_name' => getenv('SESSION_NAME') ?: 'decorpot_session',
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'decorpot',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '12345678',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
];
