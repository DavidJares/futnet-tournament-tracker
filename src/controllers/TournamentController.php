<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TeamModel;
use App\Models\TournamentModel;
use Throwable;

final class TournamentController extends BaseController
{
    private const MATCH_MODES = ['fixed_2_sets', 'best_of_3'];

    public function detail(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = $this->requestGetInt('id');
        if ($tournamentId <= 0) {
            $this->setFlash('error', 'Invalid tournament selected.');
            $this->redirect('/admin/dashboard');
        }

        $tournamentModel = new TournamentModel($this->db());
        $tournament = $tournamentModel->findById($tournamentId);

        if ($tournament === null) {
            $this->setFlash('error', 'Tournament not found.');
            $this->redirect('/admin/dashboard');
        }

        $teamModel = new TeamModel($this->db());
        $groups = $tournamentModel->groupsForTournament($tournamentId);
        $teams = $teamModel->allByTournament($tournamentId);

        $this->render('admin/tournament_detail', [
            'title' => 'Tournament detail',
            'tournament' => $tournament,
            'groups' => $groups,
            'teams' => $teams,
            'groupAssignment' => $this->buildGroupAssignmentViewData($groups, $teams),
            'matchModes' => self::MATCH_MODES,
        ]);
    }

    public function update(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        if ($tournamentId <= 0) {
            $this->setFlash('error', 'Invalid tournament selected.');
            $this->redirect('/admin/dashboard');
        }

        $data = $this->collectTournamentInput();
        if ($data === null) {
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $tournamentModel = new TournamentModel($this->db());

        try {
            $tournamentModel->update($tournamentId, $data);
        } catch (Throwable $throwable) {
            $this->setFlash('error', 'Tournament could not be updated. Slug may already exist.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
            return;
        }

        $this->setFlash('success', 'Tournament settings updated.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function createTeam(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        $teamName = $this->requestPostString('team_name');
        $description = $this->requestPostString('description');

        if ($tournamentId <= 0 || $teamName === '') {
            $this->setFlash('error', 'Team name is required.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->create($tournamentId, $teamName, $description);

        $this->setFlash('success', 'Team added.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function updateTeam(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        $teamId = (int) $this->requestPostString('team_id');
        $teamName = $this->requestPostString('team_name');
        $description = $this->requestPostString('description');

        if ($tournamentId <= 0 || $teamId <= 0 || $teamName === '') {
            $this->setFlash('error', 'Team name is required.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->update($teamId, $tournamentId, $teamName, $description);

        $this->setFlash('success', 'Team updated.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function deleteTeam(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        $teamId = (int) $this->requestPostString('team_id');
        $confirmation = $this->requestPostString('confirm_delete');

        if ($tournamentId <= 0 || $teamId <= 0) {
            $this->setFlash('error', 'Invalid team selected.');
            $this->redirect('/admin/dashboard');
        }

        if ($confirmation !== '1') {
            $this->setFlash('error', 'Deletion confirmation is required.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->delete($teamId, $tournamentId);

        $this->setFlash('success', 'Team deleted.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function assignTeamGroup(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        $teamId = (int) $this->requestPostString('team_id');
        $groupIdRaw = $this->requestPostString('group_id');
        $groupId = $groupIdRaw === '' ? null : (int) $groupIdRaw;

        if ($tournamentId <= 0 || $teamId <= 0) {
            $this->setFlash('error', 'Invalid team selected.');
            $this->redirect('/admin/dashboard');
        }

        $tournamentModel = new TournamentModel($this->db());
        $groups = $tournamentModel->groupsForTournament($tournamentId);
        $validGroupIds = $this->groupIdSet($groups);

        if ($groupId !== null && !isset($validGroupIds[$groupId])) {
            $this->setFlash('error', 'Invalid group selected.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->updateGroupAssignment($teamId, $tournamentId, $groupId);

        $this->setFlash('success', 'Team assignment updated.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function autoAssignTeams(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        if ($tournamentId <= 0) {
            $this->setFlash('error', 'Invalid tournament selected.');
            $this->redirect('/admin/dashboard');
        }

        $overwriteConfirmed = $this->requestPostString('confirm_overwrite') === '1';

        $tournamentModel = new TournamentModel($this->db());
        $groups = $tournamentModel->groupsForTournament($tournamentId);
        if (count($groups) === 0) {
            $this->setFlash('error', 'No groups available for this tournament.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamModel = new TeamModel($this->db());
        $teams = $teamModel->allByTournament($tournamentId);
        if (count($teams) === 0) {
            $this->setFlash('error', 'No teams available to assign.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        if ($teamModel->hasAnyAssignedTeam($tournamentId) && !$overwriteConfirmed) {
            $this->setFlash('error', 'Some teams already have a group. Confirm overwrite to continue.');
            $this->redirect('/admin/tournament?id=' . $tournamentId);
        }

        $teamIds = [];
        foreach ($teams as $team) {
            $teamIds[] = (int) ($team['id'] ?? 0);
        }

        shuffle($teamIds);

        $groupIds = [];
        foreach ($groups as $group) {
            $groupIds[] = (int) ($group['id'] ?? 0);
        }

        $groupCount = count($groupIds);
        $teamCount = count($teamIds);
        $baseSize = intdiv($teamCount, $groupCount);
        $extra = $teamCount % $groupCount;

        $assignments = [];
        $teamIndex = 0;
        for ($i = 0; $i < $groupCount; $i++) {
            $capacity = $baseSize + ($i < $extra ? 1 : 0);
            for ($slot = 0; $slot < $capacity; $slot++) {
                if (!isset($teamIds[$teamIndex])) {
                    break;
                }

                $assignments[$teamIds[$teamIndex]] = $groupIds[$i];
                $teamIndex++;
            }
        }

        $teamModel->bulkUpdateGroupAssignments($tournamentId, $assignments);

        $this->setFlash('success', 'Teams were automatically assigned to groups.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function collectTournamentInput(): ?array
    {
        $name = $this->requestPostString('name');
        $slug = $this->requestPostString('slug');
        $eventDate = $this->requestPostString('event_date');
        $location = $this->requestPostString('location');
        $adminPassword = $this->requestPostString('admin_password');
        $numberOfGroups = (int) $this->requestPostString('number_of_groups');
        $numberOfCourts = (int) $this->requestPostString('number_of_courts');
        $matchDurationMinutes = (int) $this->requestPostString('match_duration_minutes');
        $advancingTeamsCount = (int) $this->requestPostString('advancing_teams_count');
        $matchMode = $this->requestPostString('match_mode');

        if ($name === '' || $slug === '') {
            $this->setFlash('error', 'Tournament name and slug are required.');
            return null;
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->setFlash('error', 'Slug must contain only lowercase letters, numbers and dashes.');
            return null;
        }

        if ($adminPassword !== '' && strlen($adminPassword) < 8) {
            $this->setFlash('error', 'Tournament admin password must have at least 8 characters.');
            return null;
        }

        if ($eventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $this->setFlash('error', 'Event date must use YYYY-MM-DD format.');
            return null;
        }

        if ($numberOfGroups < 1 || $numberOfGroups > 52) {
            $this->setFlash('error', 'Number of groups must be between 1 and 52.');
            return null;
        }

        if ($numberOfCourts < 1 || $numberOfCourts > 99) {
            $this->setFlash('error', 'Number of courts must be between 1 and 99.');
            return null;
        }

        if ($matchDurationMinutes < 1 || $matchDurationMinutes > 240) {
            $this->setFlash('error', 'Match duration must be between 1 and 240 minutes.');
            return null;
        }

        if ($advancingTeamsCount < 1 || $advancingTeamsCount > 64) {
            $this->setFlash('error', 'Advancing teams count must be between 1 and 64.');
            return null;
        }

        if (!in_array($matchMode, self::MATCH_MODES, true)) {
            $this->setFlash('error', 'Invalid match mode selected.');
            return null;
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'event_date' => $eventDate,
            'location' => $location,
            'admin_password' => $adminPassword,
            'number_of_groups' => $numberOfGroups,
            'number_of_courts' => $numberOfCourts,
            'match_duration_minutes' => $matchDurationMinutes,
            'advancing_teams_count' => $advancingTeamsCount,
            'match_mode' => $matchMode,
        ];
    }

    /**
     * @param list<array<string, mixed>> $groups
     * @param list<array<string, mixed>> $teams
     * @return array{
     *     total_teams: int,
     *     group_count: int,
     *     unassigned_count: int,
     *     teams_per_group: array<int, int>,
     *     grouped_teams: array<int, list<array<string, mixed>>>,
     *     unassigned_teams: list<array<string, mixed>>
     * }
     */
    private function buildGroupAssignmentViewData(array $groups, array $teams): array
    {
        $groupedTeams = [];
        $teamsPerGroup = [];

        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $groupedTeams[$groupId] = [];
            $teamsPerGroup[$groupId] = 0;
        }

        $unassignedTeams = [];
        foreach ($teams as $team) {
            $groupId = $team['group_id'] ?? null;
            $groupId = is_numeric($groupId) ? (int) $groupId : null;

            if ($groupId !== null && isset($groupedTeams[$groupId])) {
                $groupedTeams[$groupId][] = $team;
                $teamsPerGroup[$groupId]++;
                continue;
            }

            $unassignedTeams[] = $team;
        }

        return [
            'total_teams' => count($teams),
            'group_count' => count($groups),
            'unassigned_count' => count($unassignedTeams),
            'teams_per_group' => $teamsPerGroup,
            'grouped_teams' => $groupedTeams,
            'unassigned_teams' => $unassignedTeams,
        ];
    }

    /**
     * @param list<array<string, mixed>> $groups
     * @return array<int, bool>
     */
    private function groupIdSet(array $groups): array
    {
        $set = [];
        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId > 0) {
                $set[$groupId] = true;
            }
        }

        return $set;
    }
}
