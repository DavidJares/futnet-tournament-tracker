<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo 'DEBUG OK<br>';
echo '__DIR__: ' . __DIR__ . '<br>';
echo 'PHP version: ' . PHP_VERSION . '<br>';

echo 'public/index exists: ';
var_dump(file_exists(__DIR__ . '/public/index.php'));

echo '<br>vendor/autoload exists: ';
var_dump(file_exists(__DIR__ . '/vendor/autoload.php'));

echo '<br>src/bootstrap exists: ';
var_dump(file_exists(__DIR__ . '/src/bootstrap.php'));