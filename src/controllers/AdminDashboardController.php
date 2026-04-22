<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TournamentModel;
use Throwable;

final class AdminDashboardController extends BaseController
{
    private const MATCH_MODES = ['fixed_2_sets', 'best_of_3'];

    public function index(): void
    {
        $this->requireSuperadminAuth();

        $tournamentModel = new TournamentModel($this->db());
        $tournaments = $tournamentModel->all();

        $this->render('admin/dashboard', [
            'title' => 'Superadmin dashboard',
            'tournaments' => $tournaments,
            'matchModes' => self::MATCH_MODES,
        ]);
    }

    public function createTournament(): void
    {
        $this->requireSuperadminAuth();

        $data = $this->collectTournamentInput(true);
        if ($data === null) {
            $this->redirect('/admin/dashboard');
        }

        $tournamentModel = new TournamentModel($this->db());
        $data['slug'] = $tournamentModel->generateUniqueSlug((string) $data['name']);

        try {
            $tournamentId = $tournamentModel->create($data);
        } catch (Throwable $throwable) {
            $this->setFlash('error', 'Tournament could not be created. Slug may already exist.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->setFlash('success', 'Tournament created.');
        $this->redirect('/admin/tournament?id=' . $tournamentId);
    }

    public function deleteTournament(): void
    {
        $this->requireSuperadminAuth();

        $tournamentId = (int) $this->requestPostString('tournament_id');
        if ($tournamentId <= 0) {
            $this->setFlash('error', 'Invalid tournament selected.');
            $this->redirect('/admin/dashboard');
        }

        $confirmation = $this->requestPostString('confirm_delete');
        if ($confirmation !== '1') {
            $this->setFlash('error', 'Deletion confirmation is required.');
            $this->redirect('/admin/dashboard');
        }

        $tournamentModel = new TournamentModel($this->db());
        $tournamentModel->deleteById($tournamentId);

        $this->setFlash('success', 'Tournament deleted.');
        $this->redirect('/admin/dashboard');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function collectTournamentInput(bool $requirePassword): ?array
    {
        $name = $this->requestPostString('name');
        $eventDate = $this->requestPostString('event_date');
        $startTimeRaw = $this->requestPostString('start_time');
        $startTime = $this->normalizeTimeHHMMOrEmpty($startTimeRaw);
        $location = $this->requestPostString('location');
        $adminPassword = $this->requestPostString('admin_password');
        $numberOfGroups = (int) $this->requestPostString('number_of_groups');
        $numberOfCourts = (int) $this->requestPostString('number_of_courts');
        $matchDurationMinutes = (int) $this->requestPostString('match_duration_minutes');
        $advancingTeamsCount = (int) $this->requestPostString('advancing_teams_count');
        $groupStageMode = $this->requestPostString('group_stage_mode');
        $knockoutMode = $this->requestPostString('knockout_mode');

        if ($name === '') {
            $this->setFlash('error', 'Tournament name is required.');
            return null;
        }

        if ($requirePassword && $adminPassword === '') {
            $this->setFlash('error', 'Tournament admin password is required.');
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

        if (!in_array($groupStageMode, self::MATCH_MODES, true)) {
            $this->setFlash('error', 'Invalid group stage mode selected.');
            return null;
        }

        if (!in_array($knockoutMode, self::MATCH_MODES, true)) {
            $this->setFlash('error', 'Invalid knockout mode selected.');
            return null;
        }

        return [
            'name' => $name,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'location' => $location,
            'admin_password' => $adminPassword,
            'number_of_groups' => $numberOfGroups,
            'number_of_courts' => $numberOfCourts,
            'match_duration_minutes' => $matchDurationMinutes,
            'advancing_teams_count' => $advancingTeamsCount,
            'group_stage_mode' => $groupStageMode,
            'knockout_mode' => $knockoutMode,
        ];
    }
}
