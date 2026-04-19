<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /**
     * @var array<string, array<string, callable>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $normalizedPath = $this->normalizePath($path);
        $normalizedMethod = strtoupper($method);

        $this->routes[$normalizedMethod][$normalizedPath] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $normalizedPath = $this->normalizePath($path);
        $normalizedMethod = strtoupper($method);
        $handler = $this->routes[$normalizedMethod][$normalizedPath] ?? null;

        if ($handler === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $handler();
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = '/' . trim($path, '/');
        return $normalizedPath === '//' ? '/' : $normalizedPath;
    }
}
