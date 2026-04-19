<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class MigrationModel
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function migrate(string $migrationsDirectory): int
    {
        $pdo = $this->database->pdo();
        $this->ensureMigrationTable($pdo);

        $applied = $this->appliedVersions($pdo);
        $files = glob(rtrim($migrationsDirectory, '/\\') . '/*.php');
        if (!is_array($files)) {
            return 0;
        }

        sort($files, SORT_STRING);
        $appliedCount = 0;

        foreach ($files as $file) {
            $migration = require $file;
            if (!is_array($migration)) {
                throw new \RuntimeException(sprintf('Invalid migration file: %s', $file));
            }

            $version = (string) ($migration['version'] ?? '');
            $description = (string) ($migration['description'] ?? '');
            $statements = $migration['statements'] ?? null;

            if ($version === '' || !is_array($statements)) {
                throw new \RuntimeException(sprintf('Migration file is missing metadata: %s', $file));
            }

            if (isset($applied[$version])) {
                continue;
            }

            $pdo->beginTransaction();
            try {
                foreach ($statements as $statement) {
                    if (!is_string($statement) || trim($statement) === '') {
                        continue;
                    }

                    $pdo->exec($statement);
                }

                $insert = $pdo->prepare(
                    'INSERT INTO schema_migrations (version, description, created_at)
                     VALUES (:version, :description, NOW())'
                );
                $insert->execute([
                    'version' => $version,
                    'description' => $description,
                ]);

                $pdo->commit();
            } catch (\Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $throwable;
            }

            $appliedCount++;
        }

        return $appliedCount;
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(64) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<string, bool>
     */
    private function appliedVersions(PDO $pdo): array
    {
        $statement = $pdo->query('SELECT version FROM schema_migrations');
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $versions = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $version = $row['version'] ?? null;
            if (!is_string($version) || $version === '') {
                continue;
            }

            $versions[$version] = true;
        }

        return $versions;
    }
}