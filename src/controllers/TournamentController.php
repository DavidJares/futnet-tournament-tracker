<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MatchModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;
use DateInterval;
use DateTimeImmutable;
use Throwable;

final class TournamentController extends BaseController
{
    private const MATCH_MODES = ['fixed_2_sets', 'best_of_3'];
    private const ADMIN_SECTIONS = ['tournament', 'groups', 'matches', 'teams'];

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

        $this->renderTournamentDetail($tournament, 'superadmin', 'tournament');
    }

    public function detailSection(): void
    {
        $this->requireSuperadminAuth();

        $section = $this->sectionFromRoute();
        if ($section === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

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

        $this->renderTournamentDetail($tournament, 'superadmin', $section);
    }

    public function detailBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $context = $this->currentSuperadmin() !== null ? 'superadmin' : 'tournament_admin';
        $this->renderTournamentDetail($tournament, $context, 'tournament');
    }

    public function detailBySlugSection(): void
    {
        $section = $this->sectionFromRoute();
        if ($section === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $context = $this->currentSuperadmin() !== null ? 'superadmin' : 'tournament_admin';
        $this->renderTournamentDetail($tournament, $context, $section);
    }

    public function update(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdate($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function updateBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $redirectSection = $this->sectionFromPost();
        $this->handleUpdate(
            $tournament,
            $this->tournamentAdminSectionRedirectPath((string) $tournament['slug'], $redirectSection),
            true,
            $redirectSection
        );
    }

    public function createTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleCreateTeam($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function createTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleCreateTeam($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    public function updateTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdateTeam($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function updateTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleUpdateTeam($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    public function deleteTeam(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleDeleteTeam($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function deleteTeamBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleDeleteTeam($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    public function assignTeamGroup(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleAssignTeamGroup($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function assignTeamGroupBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleAssignTeamGroup($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    public function autoAssignTeams(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleAutoAssignTeams($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function autoAssignTeamsBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleAutoAssignTeams($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    public function generateGroupMatches(): void
    {
        $this->requireSuperadminAuth();
        $tournament = $this->resolveTournamentByPostForSuperadmin();
        if ($tournament === null) {
            return;
        }

        $this->handleGenerateGroupMatches($tournament, $this->superadminSectionRedirectPath((int) $tournament['id']));
    }

    public function generateGroupMatchesBySlug(): void
    {
        $tournament = $this->resolveTournamentBySlugWithAdminAccess();
        if ($tournament === null) {
            return;
        }

        $this->handleGenerateGroupMatches($tournament, $this->tournamentAdminSectionRedirectPath((string) $tournament['slug']));
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function renderTournamentDetail(array $tournament, string $context, string $section): void
    {
        $tournamentId = (int) ($tournament['id'] ?? 0);
        $tournamentSlug = (string) ($tournament['slug'] ?? '');
        $tournamentModel = new TournamentModel($this->db());
        $teamModel = new TeamModel($this->db());
        $matchModel = new MatchModel($this->db());

        $groups = $tournamentModel->groupsForTournament($tournamentId);
        $teams = $teamModel->allByTournament($tournamentId);
        $groupAssignment = $this->buildGroupAssignmentViewData($groups, $teams);
        $groupMatches = $matchModel->groupMatchesForTournament($tournamentId);
        $hasGroupMatches = count($groupMatches) > 0;

        $isSlugContext = $context === 'tournament_admin';
        $section = $this->normalizeSection($section);
        $baseAdminPath = $isSlugContext
            ? '/tournament/' . $tournamentSlug . '/admin'
            : '/admin/tournament';

        $sectionNav = [
            'tournament' => $this->url($baseAdminPath . ($isSlugContext ? '' : '?id=' . $tournamentId)),
            'groups' => $this->url($baseAdminPath . '/groups' . ($isSlugContext ? '' : '?id=' . $tournamentId)),
            'matches' => $this->url($baseAdminPath . '/matches' . ($isSlugContext ? '' : '?id=' . $tournamentId)),
            'teams' => $this->url($baseAdminPath . '/teams' . ($isSlugContext ? '' : '?id=' . $tournamentId)),
        ];

        $groupFilterOptions = [];
        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $groupFilterOptions[$groupId] = (string) ($group['name'] ?? '');
        }

        $courtFilterSet = [];
        $configuredCourtCount = (int) ($tournament['number_of_courts'] ?? 0);
        for ($court = 1; $court <= $configuredCourtCount; $court++) {
            $courtFilterSet[$court] = true;
        }
        foreach ($groupMatches as $match) {
            $courtNumber = (int) ($match['court_number'] ?? 0);
            if ($courtNumber > 0) {
                $courtFilterSet[$courtNumber] = true;
            }
        }
        ksort($courtFilterSet);
        $courtFilterOptions = array_keys($courtFilterSet);

        $selectedGroupFilter = $this->requestGetInt('group_id');
        if ($selectedGroupFilter > 0 && !isset($groupFilterOptions[$selectedGroupFilter])) {
            $selectedGroupFilter = 0;
        }

        $selectedCourtFilter = $this->requestGetInt('court');
        if ($selectedCourtFilter > 0 && !isset($courtFilterSet[$selectedCourtFilter])) {
            $selectedCourtFilter = 0;
        }

        $filteredGroupMatches = array_values(array_filter(
            $groupMatches,
            static function (array $match) use ($selectedGroupFilter, $selectedCourtFilter): bool {
                $matchGroupId = (int) ($match['group_id'] ?? 0);
                $matchCourt = (int) ($match['court_number'] ?? 0);
                if ($selectedGroupFilter > 0 && $matchGroupId !== $selectedGroupFilter) {
                    return false;
                }
                if ($selectedCourtFilter > 0 && $matchCourt !== $selectedCourtFilter) {
                    return false;
                }

                return true;
            }
        ));

        $this->render('admin/tournament_detail', [
            'title' => 'Tournament detail',
            'tournament' => $tournament,
            'groups' => $groups,
            'teams' => $teams,
            'groupAssignment' => $groupAssignment,
            'groupMatches' => $filteredGroupMatches,
            'groupMatchesTotalCount' => count($groupMatches),
            'hasGroupMatches' => $hasGroupMatches,
            'matchModes' => self::MATCH_MODES,
            'activeSection' => $section,
            'sectionNav' => $sectionNav,
            'matchesFilterActionUrl' => $sectionNav['matches'],
            'groupFilterOptions' => $groupFilterOptions,
            'courtFilterOptions' => $courtFilterOptions,
            'selectedGroupFilter' => $selectedGroupFilter,
            'selectedCourtFilter' => $selectedCourtFilter,
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
            'generateGroupMatchesActionUrl' => $isSlugContext
                ? $this->url('/tournament/' . $tournamentSlug . '/admin/matches/generate')
                : $this->url('/admin/tournament/matches/generate'),
        ]);
    }

    /**
     * @param array<string, mixed> $tournament
     */
    private function handleUpdate(
        array $tournament,
        string $redirectPath,
        bool $redirectByUpdatedSlug = false,
        string $redirectSection = 'tournament'
    ): void
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
            $successRedirectPath = $this->tournamentAdminSectionRedirectPath((string) $data['slug'], $redirectSection);
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
     * @param array<string, mixed> $tournament
     */
    private function handleGenerateGroupMatches(array $tournament, string $redirectPath): void
    {
        $tournamentId = (int) ($tournament['id'] ?? 0);
        if ($tournamentId <= 0) {
            $this->setFlash('error', 'Invalid tournament selected.');
            $this->redirect($redirectPath);
        }

        $eventDate = (string) ($tournament['event_date'] ?? '');
        $startTimeRaw = (string) ($tournament['start_time'] ?? '');
        $startTime = $this->normalizeTimeHHMMOrEmpty($startTimeRaw);
        if ($eventDate === '' || $startTime === '') {
            $this->setFlash('error', 'Set both tournament date and start time before generating matches.');
            $this->redirect($redirectPath);
        }
        if ($startTime === null) {
            $this->setFlash('error', 'Invalid tournament date or start time.');
            $this->redirect($redirectPath);
        }

        $startDateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $eventDate . ' ' . $startTime);
        if (!$startDateTime instanceof DateTimeImmutable) {
            $this->setFlash('error', 'Invalid tournament date or start time.');
            $this->redirect($redirectPath);
        }

        $matchDurationMinutes = (int) ($tournament['match_duration_minutes'] ?? 0);
        $courtCount = (int) ($tournament['number_of_courts'] ?? 0);
        if ($matchDurationMinutes <= 0 || $courtCount <= 0) {
            $this->setFlash('error', 'Tournament courts and match duration must be greater than zero.');
            $this->redirect($redirectPath);
        }

        $tournamentModel = new TournamentModel($this->db());
        $groups = $tournamentModel->groupsForTournament($tournamentId);
        $teamModel = new TeamModel($this->db());
        $teams = $teamModel->allByTournament($tournamentId);
        $groupAssignment = $this->buildGroupAssignmentViewData($groups, $teams);

        $matchModel = new MatchModel($this->db());
        $hasGroupMatches = $matchModel->groupMatchCountForTournament($tournamentId) > 0;

        $confirmUnassigned = $this->requestPostString('confirm_unassigned') === '1';
        if ((int) $groupAssignment['unassigned_count'] > 0 && !$confirmUnassigned) {
            $this->setFlash('error', 'Some teams are unassigned. Confirm generation to proceed with assigned teams only.');
            $this->redirect($redirectPath);
        }

        $confirmRegenerate = $this->requestPostString('confirm_regenerate') === '1';
        if ($hasGroupMatches && !$confirmRegenerate) {
            $this->setFlash('error', 'Group-stage matches already exist. Confirm regeneration to replace them.');
            $this->redirect($redirectPath);
        }

        $teamsByGroupId = [];
        $groupNamesById = [];
        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $teamsByGroupId[$groupId] = [];
            $groupNamesById[$groupId] = (string) ($group['name'] ?? '');
        }

        foreach ($teams as $team) {
            $groupIdRaw = $team['group_id'] ?? null;
            $groupId = is_numeric($groupIdRaw) ? (int) $groupIdRaw : null;
            if ($groupId === null || !isset($teamsByGroupId[$groupId])) {
                continue;
            }

            $teamsByGroupId[$groupId][] = [
                'id' => (int) ($team['id'] ?? 0),
                'name' => (string) ($team['team_name'] ?? ''),
            ];
        }

        $invalidGroups = [];
        foreach ($teamsByGroupId as $groupId => $groupTeams) {
            if (count($groupTeams) < 2) {
                $invalidGroups[] = $groupNamesById[$groupId] ?? ('#' . (string) $groupId);
            }
        }

        if (count($invalidGroups) > 0) {
            $this->setFlash('error', 'Each group must have at least 2 teams. Invalid groups: ' . implode(', ', $invalidGroups) . '.');
            $this->redirect($redirectPath);
        }

        $pairings = [];
        foreach ($teamsByGroupId as $groupId => $groupTeams) {
            usort(
                $groupTeams,
                static function (array $a, array $b): int {
                    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                }
            );

            $groupPairings = $this->createRoundRobinPairingsForGroup($groupId, $groupTeams);
            foreach ($groupPairings as $pairing) {
                $pairings[] = $pairing;
            }
        }

        $scheduledMatches = $this->buildScheduledGroupMatches(
            $pairings,
            $courtCount,
            $matchDurationMinutes,
            $startDateTime
        );

        $matchModel->replaceGroupMatches($tournamentId, $scheduledMatches);

        $this->setFlash('success', sprintf('Generated %d group-stage matches.', count($scheduledMatches)));
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
        $startTimeRaw = $this->requestPostString('start_time');
        $startTime = $this->normalizeTimeHHMMOrEmpty($startTimeRaw);
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

        if ($startTime === null) {
            $this->setFlash('error', 'Start time must use HH:MM format.');
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
            'start_time' => $startTime,
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

    /**
     * @param list<array{id: int, name: string}> $groupTeams
     * @return list<array{group_id: int, team_a_id: int, team_b_id: int}>
     */
    private function createRoundRobinPairingsForGroup(int $groupId, array $groupTeams): array
    {
        $pairings = [];
        $count = count($groupTeams);
        for ($i = 0; $i < $count; $i++) {
            $teamAId = (int) ($groupTeams[$i]['id'] ?? 0);
            if ($teamAId <= 0) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $teamBId = (int) ($groupTeams[$j]['id'] ?? 0);
                if ($teamBId <= 0) {
                    continue;
                }

                $pairings[] = [
                    'group_id' => $groupId,
                    'team_a_id' => $teamAId,
                    'team_b_id' => $teamBId,
                ];
            }
        }

        return $pairings;
    }

    /**
     * @param list<array{group_id: int, team_a_id: int, team_b_id: int}> $pairings
     * @return list<array{
     *     group_id: int,
     *     team_a_id: int,
     *     team_b_id: int,
     *     court_number: int,
     *     schedule_order: int,
     *     planned_start: string
     * }>
     */
    private function buildScheduledGroupMatches(
        array $pairings,
        int $courtCount,
        int $matchDurationMinutes,
        DateTimeImmutable $startDateTime
    ): array {
        $pending = array_values($pairings);
        $schedule = [];
        $lastSlotByTeam = [];
        $slotIndex = 0;
        $order = 1;

        while (count($pending) > 0) {
            $teamsUsedInSlot = [];
            $assignedInSlot = 0;
            $plannedStart = $this->plannedStartAtSlot($startDateTime, $slotIndex, $matchDurationMinutes);

            for ($court = 1; $court <= $courtCount; $court++) {
                if (count($pending) === 0) {
                    break;
                }

                $bestIndex = $this->pickBestMatchIndex($pending, $teamsUsedInSlot, $lastSlotByTeam, $slotIndex);
                if ($bestIndex === null) {
                    break;
                }

                $match = $pending[$bestIndex];
                array_splice($pending, $bestIndex, 1);

                $teamAId = (int) ($match['team_a_id'] ?? 0);
                $teamBId = (int) ($match['team_b_id'] ?? 0);

                $schedule[] = [
                    'group_id' => (int) ($match['group_id'] ?? 0),
                    'team_a_id' => $teamAId,
                    'team_b_id' => $teamBId,
                    'court_number' => $court,
                    'schedule_order' => $order,
                    'planned_start' => $plannedStart->format('Y-m-d H:i:s'),
                ];

                $order++;
                $assignedInSlot++;
                $teamsUsedInSlot[$teamAId] = true;
                $teamsUsedInSlot[$teamBId] = true;
                $lastSlotByTeam[$teamAId] = $slotIndex;
                $lastSlotByTeam[$teamBId] = $slotIndex;
            }

            if ($assignedInSlot === 0 && count($pending) > 0) {
                $match = array_shift($pending);
                if (is_array($match)) {
                    $teamAId = (int) ($match['team_a_id'] ?? 0);
                    $teamBId = (int) ($match['team_b_id'] ?? 0);

                    $schedule[] = [
                        'group_id' => (int) ($match['group_id'] ?? 0),
                        'team_a_id' => $teamAId,
                        'team_b_id' => $teamBId,
                        'court_number' => 1,
                        'schedule_order' => $order,
                        'planned_start' => $plannedStart->format('Y-m-d H:i:s'),
                    ];

                    $order++;
                    $lastSlotByTeam[$teamAId] = $slotIndex;
                    $lastSlotByTeam[$teamBId] = $slotIndex;
                }
            }

            $slotIndex++;
        }

        return $schedule;
    }

    /**
     * @param list<array{group_id: int, team_a_id: int, team_b_id: int}> $pending
     * @param array<int, bool> $teamsUsedInSlot
     * @param array<int, int> $lastSlotByTeam
     */
    private function pickBestMatchIndex(
        array $pending,
        array $teamsUsedInSlot,
        array $lastSlotByTeam,
        int $slotIndex
    ): ?int {
        $bestIndex = null;
        $bestScore = null;

        foreach ($pending as $index => $match) {
            $teamAId = (int) ($match['team_a_id'] ?? 0);
            $teamBId = (int) ($match['team_b_id'] ?? 0);

            if ($teamAId <= 0 || $teamBId <= 0) {
                continue;
            }

            if (isset($teamsUsedInSlot[$teamAId]) || isset($teamsUsedInSlot[$teamBId])) {
                continue;
            }

            $gapA = isset($lastSlotByTeam[$teamAId]) ? $slotIndex - $lastSlotByTeam[$teamAId] : 1000;
            $gapB = isset($lastSlotByTeam[$teamBId]) ? $slotIndex - $lastSlotByTeam[$teamBId] : 1000;
            $minGap = min($gapA, $gapB);
            $score = ($minGap * 1000) + $gapA + $gapB;

            if ($bestScore === null || $score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    private function plannedStartAtSlot(
        DateTimeImmutable $startDateTime,
        int $slotIndex,
        int $matchDurationMinutes
    ): DateTimeImmutable {
        $minutesToAdd = max(0, $slotIndex) * max(1, $matchDurationMinutes);
        return $startDateTime->add(new DateInterval('PT' . $minutesToAdd . 'M'));
    }

    private function normalizeSection(string $section): string
    {
        return in_array($section, self::ADMIN_SECTIONS, true) ? $section : 'tournament';
    }

    private function sectionFromRoute(): ?string
    {
        $section = $this->requestRouteString('section');
        if ($section === '' || !in_array($section, self::ADMIN_SECTIONS, true)) {
            return null;
        }

        return $section;
    }

    private function sectionFromPost(): string
    {
        return $this->normalizeSection($this->requestPostString('return_section'));
    }

    private function sectionPathSuffix(string $section): string
    {
        return $section === 'tournament' ? '' : '/' . $section;
    }

    private function superadminSectionRedirectPath(int $tournamentId, ?string $section = null): string
    {
        $targetSection = $section ?? $this->sectionFromPost();
        return '/admin/tournament' . $this->sectionPathSuffix($targetSection) . '?id=' . $tournamentId;
    }

    private function tournamentAdminSectionRedirectPath(string $slug, ?string $section = null): string
    {
        $targetSection = $section ?? $this->sectionFromPost();
        return '/tournament/' . $slug . '/admin' . $this->sectionPathSuffix($targetSection);
    }
}
