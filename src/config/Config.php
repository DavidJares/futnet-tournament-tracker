<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $configDirectory): array
    {
        $base = self::loadFile($configDirectory . '/app.php');
        $localPath = $configDirectory . '/local.php';

        if (is_file($localPath)) {
            $local = self::loadFile($localPath);
            return self::mergeRecursive($base, $local);
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadFile(string $path): array
    {
        $config = require $path;

        if (!is_array($config)) {
            throw new \RuntimeException(sprintf('Config file "%s" must return an array.', $path));
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private static function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                /** @var array<string, mixed> $baseItem */
                $baseItem = $base[$key];
                /** @var array<string, mixed> $overrideItem */
                $overrideItem = $value;
                $base[$key] = self::mergeRecursive($baseItem, $overrideItem);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
