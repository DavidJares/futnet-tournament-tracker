<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class TournamentModel
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->query(
            'SELECT id, name, slug, event_date, start_time, location, number_of_groups, number_of_courts,
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode, created_at
             FROM tournaments
             ORDER BY created_at DESC, id DESC'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $pdo = $this->database->pdo();

        $adminPasswordHash = password_hash((string) $data['admin_password'], PASSWORD_DEFAULT);
        if (!is_string($adminPasswordHash) || $adminPasswordHash === '') {
            throw new \RuntimeException('Password hashing failed.');
        }

        $statement = $pdo->prepare(
            'INSERT INTO tournaments (
                name,
                slug,
                event_date,
                start_time,
                location,
                admin_password_hash,
                number_of_groups,
                number_of_courts,
                match_duration_minutes,
                advancing_teams_count,
                group_stage_mode,
                knockout_mode,
                match_mode,
                created_at,
                updated_at
             ) VALUES (
                :name,
                :slug,
                :event_date,
                :start_time,
                :location,
                :admin_password_hash,
                :number_of_groups,
                :number_of_courts,
                :match_duration_minutes,
                :advancing_teams_count,
                :group_stage_mode,
                :knockout_mode,
                :match_mode,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'event_date' => $this->nullIfEmpty((string) $data['event_date']),
            'start_time' => $this->nullIfEmpty((string) $data['start_time']),
            'location' => $this->nullIfEmpty((string) $data['location']),
            'admin_password_hash' => $adminPasswordHash,
            'number_of_groups' => (int) $data['number_of_groups'],
            'number_of_courts' => (int) $data['number_of_courts'],
            'match_duration_minutes' => (int) $data['match_duration_minutes'],
            'advancing_teams_count' => (int) $data['advancing_teams_count'],
            'group_stage_mode' => (string) $data['group_stage_mode'],
            'knockout_mode' => (string) $data['knockout_mode'],
            'match_mode' => (string) $data['group_stage_mode'],
        ]);

        $tournamentId = (int) $pdo->lastInsertId();
        $this->syncGroupNames($tournamentId, (int) $data['number_of_groups']);

        return $tournamentId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, name, slug, event_date, start_time, location, number_of_groups, number_of_courts,
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode, created_at, updated_at
             FROM tournaments
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, name, slug, event_date, start_time, location, number_of_groups, number_of_courts,
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode, created_at, updated_at
             FROM tournaments
             WHERE slug = :slug
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array{id: int, name: string, slug: string, admin_password_hash: string}|null
     */
    public function findAuthBySlug(string $slug): ?array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, name, slug, admin_password_hash
             FROM tournaments
             WHERE slug = :slug
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
            'admin_password_hash' => (string) ($row['admin_password_hash'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $pdo = $this->database->pdo();

        $params = [
            'id' => $id,
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'event_date' => $this->nullIfEmpty((string) $data['event_date']),
            'start_time' => $this->nullIfEmpty((string) $data['start_time']),
            'location' => $this->nullIfEmpty((string) $data['location']),
            'number_of_groups' => (int) $data['number_of_groups'],
            'number_of_courts' => (int) $data['number_of_courts'],
            'match_duration_minutes' => (int) $data['match_duration_minutes'],
            'advancing_teams_count' => (int) $data['advancing_teams_count'],
            'group_stage_mode' => (string) $data['group_stage_mode'],
            'knockout_mode' => (string) $data['knockout_mode'],
            'match_mode' => (string) $data['group_stage_mode'],
        ];

        $passwordClause = '';
        $rawPassword = (string) ($data['admin_password'] ?? '');
        if ($rawPassword !== '') {
            $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);
            if (!is_string($passwordHash) || $passwordHash === '') {
                throw new \RuntimeException('Password hashing failed.');
            }

            $passwordClause = ', admin_password_hash = :admin_password_hash';
            $params['admin_password_hash'] = $passwordHash;
        }

        $statement = $pdo->prepare(
            'UPDATE tournaments
                 SET name = :name,
                 slug = :slug,
                 event_date = :event_date,
                 start_time = :start_time,
                 location = :location,
                 number_of_groups = :number_of_groups,
                 number_of_courts = :number_of_courts,
                 match_duration_minutes = :match_duration_minutes,
                 advancing_teams_count = :advancing_teams_count,
                 group_stage_mode = :group_stage_mode,
                 knockout_mode = :knockout_mode,
                 match_mode = :match_mode,
                 updated_at = NOW()' . $passwordClause . '
             WHERE id = :id'
        );

        $statement->execute($params);

        $this->syncGroupNames($id, (int) $data['number_of_groups']);
    }

    public function deleteById(int $id): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare('DELETE FROM tournaments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function generateUniqueSlug(string $name, ?int $excludeTournamentId = null): string
    {
        $base = self::slugify($name);
        if ($base === '') {
            $base = 'tournament';
        }

        $slug = $base;
        $suffix = 2;
        while ($this->slugExists($slug, $excludeTournamentId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function groupsForTournament(int $tournamentId): array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, name, sort_order
             FROM tournament_groups
             WHERE tournament_id = :tournament_id
             ORDER BY sort_order ASC'
        );

        $statement->execute(['tournament_id' => $tournamentId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function syncGroupNames(int $tournamentId, int $groupCount): void
    {
        $groupCount = max(1, $groupCount);
        $pdo = $this->database->pdo();

        $select = $pdo->prepare(
            'SELECT id, sort_order, name
             FROM tournament_groups
             WHERE tournament_id = :tournament_id
             ORDER BY sort_order ASC'
        );
        $select->execute(['tournament_id' => $tournamentId]);
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);

        $existingByOrder = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sortOrder = (int) ($row['sort_order'] ?? 0);
            if ($sortOrder <= 0) {
                continue;
            }

            $existingByOrder[$sortOrder] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        $insert = $pdo->prepare(
            'INSERT INTO tournament_groups (tournament_id, name, sort_order, created_at, updated_at)
             VALUES (:tournament_id, :name, :sort_order, NOW(), NOW())'
        );

        $update = $pdo->prepare(
            'UPDATE tournament_groups
             SET name = :name, updated_at = NOW()
             WHERE id = :id'
        );

        for ($i = 1; $i <= $groupCount; $i++) {
            $groupName = self::groupNameByIndex($i);
            $existing = $existingByOrder[$i] ?? null;

            if (is_array($existing) && (int) $existing['id'] > 0) {
                if ((string) $existing['name'] !== $groupName) {
                    $update->execute([
                        'id' => (int) $existing['id'],
                        'name' => $groupName,
                    ]);
                }

                continue;
            }

            $insert->execute([
                'tournament_id' => $tournamentId,
                'name' => $groupName,
                'sort_order' => $i,
            ]);
        }

        $delete = $pdo->prepare(
            'DELETE FROM tournament_groups
             WHERE tournament_id = :tournament_id
               AND sort_order > :group_count'
        );
        $delete->execute([
            'tournament_id' => $tournamentId,
            'group_count' => $groupCount,
        ]);
    }

    private static function groupNameByIndex(int $index): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        while ($index > 0) {
            $index--;
            $result = $letters[$index % 26] . $result;
            $index = intdiv($index, 26);
        }

        return $result;
    }

    private function slugExists(string $slug, ?int $excludeTournamentId = null): bool
    {
        $pdo = $this->database->pdo();
        if ($excludeTournamentId !== null && $excludeTournamentId > 0) {
            $statement = $pdo->prepare(
                'SELECT 1
                 FROM tournaments
                 WHERE slug = :slug
                   AND id <> :exclude_id
                 LIMIT 1'
            );
            $statement->execute([
                'slug' => $slug,
                'exclude_id' => $excludeTournamentId,
            ]);
            return $statement->fetchColumn() !== false;
        }

        $statement = $pdo->prepare(
            'SELECT 1
             FROM tournaments
             WHERE slug = :slug
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);

        return $statement->fetchColumn() !== false;
    }

    private static function slugify(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $normalized = $value;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $normalized = $converted;
            }
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');
        $normalized = preg_replace('/-+/', '-', $normalized) ?? '';

        return $normalized;
    }

    private function nullIfEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
