<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Database;

abstract class BaseController
{
    /**
     * @var array<string, mixed>
     */
    protected array $services;

    /**
     * @param array<string, mixed> $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException(sprintf('View "%s" not found.', $view));
        }

        $config = $this->services['config'] ?? [];
        $flash = $this->pullFlash();
        $currentSuperadmin = $this->currentSuperadmin();
        extract($data, EXTR_SKIP);

        require __DIR__ . '/../views/layout.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function db(): Database
    {
        $database = $this->services['db'] ?? null;
        if (!$database instanceof Database) {
            throw new \RuntimeException('Database service is not available.');
        }

        return $database;
    }

    protected function requestPostString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    protected function requestGetInt(string $key): int
    {
        $value = $_GET[$key] ?? null;
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || !ctype_digit($value)) {
            return 0;
        }

        return (int) $value;
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array{type: string, message: string}|null
     */
    private function pullFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        if (!is_array($flash)) {
            return null;
        }

        $type = $flash['type'] ?? '';
        $message = $flash['message'] ?? '';

        if (!is_string($type) || !is_string($message) || $type === '' || $message === '') {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array{id: int, username: string}|null
     */
    protected function currentSuperadmin(): ?array
    {
        $superadmin = $_SESSION['superadmin'] ?? null;
        if (!is_array($superadmin)) {
            return null;
        }

        $id = $superadmin['id'] ?? 0;
        $username = $superadmin['username'] ?? '';

        if (!is_int($id) || $id <= 0 || !is_string($username) || $username === '') {
            return null;
        }

        return [
            'id' => $id,
            'username' => $username,
        ];
    }

    protected function requireSuperadminAuth(): void
    {
        if ($this->currentSuperadmin() !== null) {
            return;
        }

        $this->setFlash('error', 'Please sign in as superadmin.');
        $this->redirect('/admin/login');
    }
}
