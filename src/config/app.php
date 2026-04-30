<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'FTT',
        'env' => getenv('APP_ENV') ?: 'prod',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: '',
        'username' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
];
