<?php

declare(strict_types=1);

return [
    'app' => [
        // Use '' for domain root (https://example.com),
        // or '/my/sub/path' when app is served from subdirectory.
        'base_path' => '',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'ftt',
        'username' => 'ftt_user',
        'password' => 'change-me',
        'charset' => 'utf8mb4',
    ],
];
