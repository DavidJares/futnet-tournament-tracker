<?php

declare(strict_types=1);

use App\Config\Config;
use App\Models\Database;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$config = Config::load(__DIR__ . '/config');

return [
    'config' => $config,
    'db' => new Database($config['db'] ?? []),
];
