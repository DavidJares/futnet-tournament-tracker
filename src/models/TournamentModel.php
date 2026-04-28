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
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode,
                    public_view_enabled, autoplay_enabled, rotation_interval_seconds,
                    public_title_override, public_description, public_logo_path, public_map_url, public_map_embed_url,
                    created_at
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
                public_view_enabled,
                autoplay_enabled,
                rotation_interval_seconds,
                public_title_override,
                public_description,
                public_logo_path,
                public_map_url,
                public_map_embed_url,
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
                :public_view_enabled,
                :autoplay_enabled,
                :rotation_interval_seconds,
                :public_title_override,
                :public_description,
                :public_logo_path,
                :public_map_url,
                :public_map_embed_url,
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
            'public_view_enabled' => (int) ($data['public_view_enabled'] ?? 0),
            'autoplay_enabled' => (int) ($data['autoplay_enabled'] ?? 1),
            'rotation_interval_seconds' => (int) ($data['rotation_interval_seconds'] ?? 15),
            'public_title_override' => $this->nullIfEmpty((string) ($data['public_title_override'] ?? '')),
            'public_description' => $this->nullIfEmpty((string) ($data['public_description'] ?? '')),
            'public_logo_path' => $this->nullIfEmpty((string) ($data['public_logo_path'] ?? '')),
            'public_map_url' => $this->nullIfEmpty((string) ($data['public_map_url'] ?? '')),
            'public_map_embed_url' => $this->nullIfEmpty((string) ($data['public_map_embed_url'] ?? '')),
        ]);

        $tournamentId = (int) $pdo->lastInsertId();
        $this->syncGroupNames($tournamentId, (int) $data['number_of_groups']);
        $this->upsertPublicScreens(
            $tournamentId,
            $this->normalizePublicScreens($data['public_screens'] ?? [])
        );

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
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode,
                    public_view_enabled, autoplay_enabled, rotation_interval_seconds,
                    public_title_override, public_description, public_logo_path, public_map_url, public_map_embed_url,
                    created_at, updated_at
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
                    match_duration_minutes, advancing_teams_count, group_stage_mode, knockout_mode, match_mode,
                    public_view_enabled, autoplay_enabled, rotation_interval_seconds,
                    public_title_override, public_description, public_logo_path, public_map_url, public_map_embed_url,
                    created_at, updated_at
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
        $existing = $this->findById($id) ?? [];

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
            'public_view_enabled' => array_key_exists('public_view_enabled', $data)
                ? (int) $data['public_view_enabled']
                : ((int) ($existing['public_view_enabled'] ?? 0)),
            'autoplay_enabled' => array_key_exists('autoplay_enabled', $data)
                ? (int) $data['autoplay_enabled']
                : ((int) ($existing['autoplay_enabled'] ?? 1)),
            'rotation_interval_seconds' => array_key_exists('rotation_interval_seconds', $data)
                ? (int) $data['rotation_interval_seconds']
                : ((int) ($existing['rotation_interval_seconds'] ?? 15)),
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
                 public_view_enabled = :public_view_enabled,
                 autoplay_enabled = :autoplay_enabled,
                 rotation_interval_seconds = :rotation_interval_seconds,
                 match_mode = :match_mode,
                 updated_at = NOW()' . $passwordClause . '
             WHERE id = :id'
        );

        $statement->execute($params);

        $this->syncGroupNames($id, (int) $data['number_of_groups']);
        if (array_key_exists('public_screens', $data)) {
            $this->upsertPublicScreens(
                $id,
                $this->normalizePublicScreens($data['public_screens'])
            );
        }
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

    /**
     * @return list<array{screen_key: string, is_enabled: int, sort_order: int}>
     */
    public function publicScreensForTournament(int $tournamentId): array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT screen_key, is_enabled, sort_order
             FROM tournament_public_screens
             WHERE tournament_id = :tournament_id
             ORDER BY sort_order ASC, screen_key ASC'
        );
        $statement->execute(['tournament_id' => $tournamentId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $screenKey = (string) ($row['screen_key'] ?? '');
            if ($screenKey === '') {
                continue;
            }

            $result[] = [
                'screen_key' => $screenKey,
                'is_enabled' => (int) ($row['is_enabled'] ?? 0),
                'sort_order' => (int) ($row['sort_order'] ?? 1),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, array{is_enabled: int, sort_order: int}>|null $screensByKey
     */
    public function savePublicViewSettings(
        int $tournamentId,
        bool $publicViewEnabled,
        bool $autoplayEnabled,
        int $rotationIntervalSeconds,
        string $publicTitleOverride,
        string $publicDescription,
        string $publicLogoPath,
        string $publicMapUrl,
        string $publicMapEmbedUrl,
        ?array $screensByKey
    ): void {
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();

        try {
            $updateTournament = $pdo->prepare(
                'UPDATE tournaments
                 SET public_view_enabled = :public_view_enabled,
                     autoplay_enabled = :autoplay_enabled,
                     rotation_interval_seconds = :rotation_interval_seconds,
                     public_title_override = :public_title_override,
                     public_description = :public_description,
                     public_logo_path = :public_logo_path,
                     public_map_url = :public_map_url,
                     public_map_embed_url = :public_map_embed_url,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $updateTournament->execute([
                'public_view_enabled' => $publicViewEnabled ? 1 : 0,
                'autoplay_enabled' => $autoplayEnabled ? 1 : 0,
                'rotation_interval_seconds' => $rotationIntervalSeconds,
                'public_title_override' => $this->nullIfEmpty($publicTitleOverride),
                'public_description' => $this->nullIfEmpty($publicDescription),
                'public_logo_path' => $this->nullIfEmpty($publicLogoPath),
                'public_map_url' => $this->nullIfEmpty($publicMapUrl),
                'public_map_embed_url' => $this->nullIfEmpty($publicMapEmbedUrl),
                'id' => $tournamentId,
            ]);

            if (is_array($screensByKey)) {
                $this->upsertPublicScreens($tournamentId, $screensByKey, $pdo);
            }
            $pdo->commit();
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
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

    /**
     * @param mixed $raw
     * @return array<string, array{is_enabled: int, sort_order: int}>
     */
    private function normalizePublicScreens($raw): array
    {
        $defaults = [
            'overview' => ['is_enabled' => 1, 'sort_order' => 1],
            'next_matches' => ['is_enabled' => 1, 'sort_order' => 2],
            'standings' => ['is_enabled' => 1, 'sort_order' => 3],
            'group_schedule' => ['is_enabled' => 1, 'sort_order' => 4],
            'knockout' => ['is_enabled' => 1, 'sort_order' => 5],
            'recent_results' => ['is_enabled' => 1, 'sort_order' => 6],
        ];

        if (!is_array($raw)) {
            return $defaults;
        }

        foreach ($defaults as $screenKey => $screenDefaults) {
            $candidate = $raw[$screenKey] ?? null;
            if (!is_array($candidate)) {
                continue;
            }

            $isEnabled = (int) ($candidate['is_enabled'] ?? $screenDefaults['is_enabled']);
            $sortOrder = (int) ($candidate['sort_order'] ?? $screenDefaults['sort_order']);
            $defaults[$screenKey] = [
                'is_enabled' => $isEnabled > 0 ? 1 : 0,
                'sort_order' => max(1, min(99, $sortOrder)),
            ];
        }

        return $defaults;
    }

    /**
     * @param array<string, array{is_enabled: int, sort_order: int}> $screensByKey
     */
    private function upsertPublicScreens(int $tournamentId, array $screensByKey, ?PDO $pdo = null): void
    {
        $pdo = $pdo ?? $this->database->pdo();
        $delete = $pdo->prepare('DELETE FROM tournament_public_screens WHERE tournament_id = :tournament_id');
        $delete->execute(['tournament_id' => $tournamentId]);

        $insert = $pdo->prepare(
            'INSERT INTO tournament_public_screens (
                tournament_id,
                screen_key,
                is_enabled,
                sort_order,
                created_at,
                updated_at
             ) VALUES (
                :tournament_id,
                :screen_key,
                :is_enabled,
                :sort_order,
                NOW(),
                NOW()
             )'
        );

        foreach ($screensByKey as $screenKey => $screenSettings) {
            $insert->execute([
                'tournament_id' => $tournamentId,
                'screen_key' => $screenKey,
                'is_enabled' => (int) ($screenSettings['is_enabled'] ?? 0) > 0 ? 1 : 0,
                'sort_order' => max(1, min(99, (int) ($screenSettings['sort_order'] ?? 1))),
            ]);
        }
    }
}
