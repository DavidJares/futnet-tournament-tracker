<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class MatchModel
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function groupMatchCountForTournament(int $tournamentId): int
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM matches
             WHERE tournament_id = :tournament_id
               AND stage = :stage'
        );
        $statement->execute([
            'tournament_id' => $tournamentId,
            'stage' => 'group',
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function groupMatchesForTournament(int $tournamentId): array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT
                m.id,
                m.group_id,
                g.name AS group_name,
                m.team_a_id,
                ta.team_name AS team_a_name,
                m.team_b_id,
                tb.team_name AS team_b_name,
                m.court_number,
                m.schedule_order,
                m.planned_start,
                m.status
             FROM matches m
             LEFT JOIN tournament_groups g ON g.id = m.group_id
             LEFT JOIN teams ta ON ta.id = m.team_a_id
             LEFT JOIN teams tb ON tb.id = m.team_b_id
             WHERE m.tournament_id = :tournament_id
               AND m.stage = :stage
             ORDER BY
                CASE WHEN m.schedule_order IS NULL THEN 1 ELSE 0 END,
                m.schedule_order ASC,
                CASE WHEN m.planned_start IS NULL THEN 1 ELSE 0 END,
                m.planned_start ASC,
                m.id ASC'
        );
        $statement->execute([
            'tournament_id' => $tournamentId,
            'stage' => 'group',
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param list<array{
     *     group_id: int,
     *     team_a_id: int,
     *     team_b_id: int,
     *     court_number: int,
     *     schedule_order: int,
     *     planned_start: string
     * }> $matches
     */
    public function replaceGroupMatches(int $tournamentId, array $matches): void
    {
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare(
                'DELETE FROM matches
                 WHERE tournament_id = :tournament_id
                   AND stage = :stage'
            );
            $delete->execute([
                'tournament_id' => $tournamentId,
                'stage' => 'group',
            ]);

            if (count($matches) > 0) {
                $insert = $pdo->prepare(
                    'INSERT INTO matches (
                        tournament_id,
                        stage,
                        group_id,
                        round_name,
                        bracket_position,
                        team_a_id,
                        team_b_id,
                        team_a_source,
                        team_b_source,
                        court_number,
                        schedule_order,
                        planned_start,
                        status,
                        winner_team_id,
                        sets_summary_a,
                        sets_summary_b,
                        created_at,
                        updated_at
                     ) VALUES (
                        :tournament_id,
                        :stage,
                        :group_id,
                        NULL,
                        NULL,
                        :team_a_id,
                        :team_b_id,
                        NULL,
                        NULL,
                        :court_number,
                        :schedule_order,
                        :planned_start,
                        :status,
                        NULL,
                        0,
                        0,
                        NOW(),
                        NOW()
                     )'
                );

                foreach ($matches as $match) {
                    $insert->execute([
                        'tournament_id' => $tournamentId,
                        'stage' => 'group',
                        'group_id' => (int) $match['group_id'],
                        'team_a_id' => (int) $match['team_a_id'],
                        'team_b_id' => (int) $match['team_b_id'],
                        'court_number' => (int) $match['court_number'],
                        'schedule_order' => (int) $match['schedule_order'],
                        'planned_start' => (string) $match['planned_start'],
                        'status' => 'scheduled',
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
