<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class TeamModel
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allByTournament(int $tournamentId): array
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, tournament_id, group_id, team_name, description, created_at, updated_at
             FROM teams
             WHERE tournament_id = :tournament_id
             ORDER BY team_name ASC, id ASC'
        );
        $statement->execute(['tournament_id' => $tournamentId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function create(int $tournamentId, string $teamName, string $description): int
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'INSERT INTO teams (tournament_id, group_id, team_name, description, created_at, updated_at)
             VALUES (:tournament_id, NULL, :team_name, :description, NOW(), NOW())'
        );

        $statement->execute([
            'tournament_id' => $tournamentId,
            'team_name' => $teamName,
            'description' => $description === '' ? null : $description,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $teamId, int $tournamentId, string $teamName, string $description): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'UPDATE teams
             SET team_name = :team_name,
                 description = :description,
                 updated_at = NOW()
             WHERE id = :id
               AND tournament_id = :tournament_id'
        );

        $statement->execute([
            'id' => $teamId,
            'tournament_id' => $tournamentId,
            'team_name' => $teamName,
            'description' => $description === '' ? null : $description,
        ]);
    }

    public function updateGroupAssignment(int $teamId, int $tournamentId, ?int $groupId): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'UPDATE teams
             SET group_id = :group_id,
                 updated_at = NOW()
             WHERE id = :id
               AND tournament_id = :tournament_id'
        );

        $statement->bindValue(':id', $teamId, PDO::PARAM_INT);
        $statement->bindValue(':tournament_id', $tournamentId, PDO::PARAM_INT);
        if ($groupId === null) {
            $statement->bindValue(':group_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        }
        $statement->execute();
    }

    /**
     * @param array<int, int|null> $assignmentByTeamId
     */
    public function bulkUpdateGroupAssignments(int $tournamentId, array $assignmentByTeamId): void
    {
        if (count($assignmentByTeamId) === 0) {
            return;
        }

        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'UPDATE teams
             SET group_id = :group_id,
                 updated_at = NOW()
             WHERE id = :id
               AND tournament_id = :tournament_id'
        );

        foreach ($assignmentByTeamId as $teamId => $groupId) {
            $statement->bindValue(':id', $teamId, PDO::PARAM_INT);
            $statement->bindValue(':tournament_id', $tournamentId, PDO::PARAM_INT);
            if ($groupId === null) {
                $statement->bindValue(':group_id', null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':group_id', $groupId, PDO::PARAM_INT);
            }

            $statement->execute();
        }
    }

    public function hasAnyAssignedTeam(int $tournamentId): bool
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM teams
             WHERE tournament_id = :tournament_id
               AND group_id IS NOT NULL'
        );
        $statement->execute(['tournament_id' => $tournamentId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $teamId, int $tournamentId): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'DELETE FROM teams
             WHERE id = :id
               AND tournament_id = :tournament_id'
        );

        $statement->execute([
            'id' => $teamId,
            'tournament_id' => $tournamentId,
        ]);
    }
}
