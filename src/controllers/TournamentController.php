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

        $this->renderTournamentDetail($tournament, 'superadmin');
    }

    public function detailBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $context = $this->currentSuperadmin() !== null ? 'superadmin' : 'tournament_admin';
        $this->renderTournamentDetail($tournament, $context);
    }

    public function update(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdate($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function updateBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdate($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin', true);
    }

    public function createTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleCreateTeam($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function createTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleCreateTeam($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin');
    }

    public function updateTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdateTeam($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function updateTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdateTeam($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin');
    }

    public function deleteTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleDeleteTeam($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function deleteTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleDeleteTeam($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin');
    }

    public function assignTeamGroup(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleAssignTeamGroup($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function assignTeamGroupBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleAssignTeamGroup($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin');
    }

    public function autoAssignTeams(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleAutoAssignTeams($tournament, '/admin/tournament?id=' . (int) $tournament['id']);
    }

    public function autoAssignTeamsBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleAutoAssignTeams($tournament, '/tournament/' . (string) $tournament['slug'] . '/admin');
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function renderTournamentDetail(array $tournament, string $context): void
    {
        $tournamentId = (int) ($tournament['id'] ?? 0);
        $tournamentSlug = (string) ($tournament['slug'] ?? '');
        $tournamentModel = new TournamentModel($this->db());
        $teamModel = new TeamModel($this->db());

        $groups = $tournamentModel->groupsForTournament($tournamentId);
        $teams = $teamModel->allByTournament($tournamentId);

        $isSlugContext = $context === 'tournament_admin';
        $this->render('admin/tournament_detail', [
            'title' => 'Tournament detail',
            'tournament' => $tournament,
            'groups' => $groups,
            'teams' => $teams,
            'groupAssignment' => $this->buildGroupAssignmentViewData($groups, $teams),
            'matchModes' => self::MATCH_MODES,
            'backUrl' => $isSlugContext ? null : $this->url('/admin/dashboard'),
            'backLabel' => 'Back to dashboard',
            'settingsActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/update')
                : $this->url('/admin/tournament/update'),
            'createTeamActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/teams/create')
                : $this->url('/admin/tournament/teams/create'),
            'updateTeamActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/teams/update')
                : $this->url('/admin/tournament/teams/update'),
            'deleteTeamActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/teams/delete')
                : $this->url('/admin/tournament/teams/delete'),
            'assignTeamActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/teams/assign')
                : $this->url('/admin/tournament/teams/assign'),
            'autoAssignTeamsActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/teams/assign-auto')
                : $this->url('/admin/tournament/teams/assign-auto'),
        ]);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleUpdate(array $tournament, string $redirectPath, bool $redirectByUpdatedSlug = false): void
    {
        $data = $this->collectTournamentInput();
        if ($data === null) {
            $this->redirect($redirectPath);
        }

        $tournamentModel = new TournamentModel($this->db());

        try {
            $tournamentModel->update((int) $tournament['id'], $data);
        } catch (Throwable $throwable) {
            $this->setFlash('error', 'Tournament could not be updated. Slug may already exist.');
            $this->redirect($redirectPath);
            return;
        }

        $currentTournamentAdmin = $this->currentTournamentAdmin();
        if (is_array($currentTournamentAdmin) && (int) $currentTournamentAdmin['id'] === (int) $tournament['id']) {
            $_SESSION['tournament_admin'] = [
                'id' => (int) $tournament['id'],
                'slug' => (string) $data['slug'],
                'name' => (string) $data['name'],
            ];
        }

        $successRedirectPath = $redirectPath;
        if ($redirectByUpdatedSlug) {
            $successRedirectPath = '/tournament/' . (string) $data['slug'] . '/admin';
        }

        $this->setFlash('success', 'Tournament settings updated.');
        $this->redirect($successRedirectPath);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleCreateTeam(array $tournament, string $redirectPath): void
    {
        $teamName = $this->requestPostString('team_name');
        $description = $this->requestPostString('description');

        if ($teamName === '') {
            $this->setFlash('error', 'Team name is required.');
            $this->redirect($redirectPath);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->create((int) $tournament['id'], $teamName, $description);

        $this->setFlash('success', 'Team added.');
        $this->redirect($redirectPath);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleUpdateTeam(array $tournament, string $redirectPath): void
    {
        $teamId = (int) $this->requestPostString('team_id');
        $teamName = $this->requestPostString('team_name');
        $description = $this->requestPostString('description');

        if ($teamId <= 0 || $teamName === '') {
            $this->setFlash('error', 'Team name is required.');
            $this->redirect($redirectPath);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->update($teamId, (int) $tournament['id'], $teamName, $description);

        $this->setFlash('success', 'Team updated.');
        $this->redirect($redirectPath);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleDeleteTeam(array $tournament, string $redirectPath): void
    {
        $teamId = (int) $this->requestPostString('team_id');
        $confirmation = $this->requestPostString('confirm_delete');

        if ($teamId <= 0) {
            $this->setFlash('error', 'Invalid team selected.');
            $this->redirect($redirectPath);
        }

        if ($confirmation !== '1') {
            $this->setFlash('error', 'Deletion confirmation is required.');
            $this->redirect($redirectPath);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->delete($teamId, (int) $tournament['id']);

        $this->setFlash('success', 'Team deleted.');
        $this->redirect($redirectPath);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleAssignTeamGroup(array $tournament, string $redirectPath): void
    {
        $teamId = (int) $this->requestPostString('team_id');
        $groupIdRaw = $this->requestPostString('group_id');
        $groupId = $groupIdRaw === '' ? null : (int) $groupIdRaw;

        if ($teamId <= 0) {
            $this->setFlash('error', 'Invalid team selected.');
            $this->redirect($redirectPath);
        }

        $tournamentModel = new TournamentModel($this->db());
        $groups = $tournamentModel->groupsForTournament((int) $tournament['id']);
        $validGroupIds = $this->groupIdSet($groups);

        if ($groupId !== null && !isset($validGroupIds[$groupId])) {
            $this->setFlash('error', 'Invalid group selected.');
            $this->redirect($redirectPath);
        }

        $teamModel = new TeamModel($this->db());
        $teamModel->updateGroupAssignment($teamId, (int) $tournament['id'], $groupId);

        $this->setFlash('success', 'Team assignment updated.');
        $this->redirect($redirectPath);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleAutoAssignTeams(array $tournament, string $redirectPath): void
    {
        $overwriteConfirmed = $this->requestPostString('confirm_overwrite') === '1';
        $tournamentId = (int) $tournament['id'];

        $tournamentModel = new TournamentModel($this->db());
        $groups = $tournamentModel->groupsForTournament($tournamentId);
        if (count($groups) === 0) {
            $this->setFlash('error', 'No groups available for this tournament.');
            $this->redirect($redirectPath);
        }

        $teamModel = new TeamModel($this->db());
        $teams = $teamModel->allByTournament($tournamentId);
        if (count($teams) === 0) {
            $this->setFlash('error', 'No teams available to assign.');
            $this->redirect($redirectPath);
        }

        if ($teamModel->hasAnyAssignedTeam($tournamentId) && !$overwriteConfirmed) {
            $this->setFlash('error', 'Some teams already have a group. Confirm overwrite to continue.');
            $this->redirect($redirectPath);
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
        $this->redirect($redirectPath);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTournamentByPostForSuperadmin(): ?array
    {
        $tournamentId = (int) $this->requestPostString('tournament_id');
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

        return $tournament;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveTournamentBySlugWithAdminAccess(): ?array
    {
        $slug = $this->requestRouteString('slug');
        if ($slug === '') {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return null;
        }

        $tournamentModel = new TournamentModel($this->db());
        $tournament = $tournamentModel->findBySlug($slug);
        if ($tournament === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return null;
        }

        if ($this->currentSuperadmin() !== null) {
            return $tournament;
        }

        $tournamentAdmin = $this->currentTournamentAdmin();
        if (is_array($tournamentAdmin) && (int) $tournamentAdmin['id'] === (int) $tournament['id']) {
            return $tournament;
        }

        $this->setFlash('error', 'Please sign in as tournament admin.');
        $this->redirect('/tournament/' . $slug . '/login');
        return null;
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
