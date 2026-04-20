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
            'SELECT id, name, slug, event_date, location, number_of_groups, number_of_courts,
                    match_duration_minutes, advancing_teams_count, match_mode, created_at
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
                location,
                admin_password_hash,
                number_of_groups,
                number_of_courts,
                match_duration_minutes,
                advancing_teams_count,
                match_mode,
                created_at,
                updated_at
             ) VALUES (
                :name,
                :slug,
                :event_date,
                :location,
                :admin_password_hash,
                :number_of_groups,
                :number_of_courts,
                :match_duration_minutes,
                :advancing_teams_count,
                :match_mode,
                NOW(),
                NOW()
             )'
        );

        $statement->execute([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'event_date' => $this->nullIfEmpty((string) $data['event_date']),
            'location' => $this->nullIfEmpty((string) $data['location']),
            'admin_password_hash' => $adminPasswordHash,
            'number_of_groups' => (int) $data['number_of_groups'],
            'number_of_courts' => (int) $data['number_of_courts'],
            'match_duration_minutes' => (int) $data['match_duration_minutes'],
            'advancing_teams_count' => (int) $data['advancing_teams_count'],
            'match_mode' => (string) $data['match_mode'],
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
            'SELECT id, name, slug, event_date, location, number_of_groups, number_of_courts,
                    match_duration_minutes, advancing_teams_count, match_mode, created_at, updated_at
             FROM tournaments
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
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
            'location' => $this->nullIfEmpty((string) $data['location']),
            'number_of_groups' => (int) $data['number_of_groups'],
            'number_of_courts' => (int) $data['number_of_courts'],
            'match_duration_minutes' => (int) $data['match_duration_minutes'],
            'advancing_teams_count' => (int) $data['advancing_teams_count'],
            'match_mode' => (string) $data['match_mode'],
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
                 location = :location,
                 number_of_groups = :number_of_groups,
                 number_of_courts = :number_of_courts,
                 match_duration_minutes = :match_duration_minutes,
                 advancing_teams_count = :advancing_teams_count,
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

    private function nullIfEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }
}
