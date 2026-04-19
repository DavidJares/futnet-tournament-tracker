<?php

declare(strict_types=1);

use App\Models\MigrationModel;

$services = require __DIR__ . '/../src/bootstrap.php';

try {
    $migrationModel = new MigrationModel($services['db']);
    $applied = $migrationModel->migrate(__DIR__ . '/../src/migrations');

    echo sprintf("Migrations done. Applied: %d\n", $applied);
    exit(0);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Migration failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}