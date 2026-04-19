<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

final class SuperadminModel
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function hasAny(): bool
    {
        try {
            $pdo = $this->database->pdo();
            $statement = $pdo->query('SELECT COUNT(*) FROM superadmins');
            return (int) $statement->fetchColumn() > 0;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function tableExists(): bool
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $statement->execute(['table_name' => 'superadmins']);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $username, string $password): int
    {
        $pdo = $this->database->pdo();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!is_string($passwordHash) || $passwordHash === '') {
            throw new \RuntimeException('Password hashing failed.');
        }

        $statement = $pdo->prepare(
            'INSERT INTO superadmins (username, password_hash, created_at, updated_at)
             VALUES (:username, :password_hash, NOW(), NOW())'
        );

        $statement->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @return array{id: int, username: string, password_hash: string}|null
     */
    public function findByUsername(string $username): ?array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, username, password_hash
             FROM superadmins
             WHERE username = :username
             LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'username' => (string) ($row['username'] ?? ''),
            'password_hash' => (string) ($row['password_hash'] ?? ''),
        ];
    }
}