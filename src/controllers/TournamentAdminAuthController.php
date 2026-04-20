<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TournamentModel;

final class TournamentAdminAuthController extends BaseController
{
    public function loginForm(): void
    {
        $slug = $this->requestRouteString('slug');
        $tournamentModel = new TournamentModel($this->db());
        $tournament = $tournamentModel->findAuthBySlug($slug);

        if ($tournament === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        if ($this->currentSuperadmin() !== null) {
            $this->redirect('/tournament/' . $slug . '/admin');
        }

        $currentTournamentAdmin = $this->currentTournamentAdmin();
        if (is_array($currentTournamentAdmin) && (int) $currentTournamentAdmin['id'] === (int) $tournament['id']) {
            $this->redirect('/tournament/' . $slug . '/admin');
        }

        $this->render('tournament_admin/login', [
            'title' => 'Tournament admin login',
            'tournamentName' => (string) $tournament['name'],
            'slug' => (string) $tournament['slug'],
        ]);
    }

    public function login(): void
    {
        $slug = $this->requestRouteString('slug');
        $tournamentModel = new TournamentModel($this->db());
        $tournament = $tournamentModel->findAuthBySlug($slug);

        if ($tournament === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $password = $this->requestPostString('password');
        if ($password === '') {
            $this->setFlash('error', 'Password is required.');
            $this->redirect('/tournament/' . $slug . '/login');
        }

        if (!password_verify($password, (string) $tournament['admin_password_hash'])) {
            $this->setFlash('error', 'Invalid tournament password.');
            $this->redirect('/tournament/' . $slug . '/login');
        }

        session_regenerate_id(true);
        $_SESSION['tournament_admin'] = [
            'id' => (int) $tournament['id'],
            'slug' => (string) $tournament['slug'],
            'name' => (string) $tournament['name'],
        ];

        $this->setFlash('success', 'Tournament admin access granted.');
        $this->redirect('/tournament/' . $slug . '/admin');
    }

    public function logout(): void
    {
        $slug = $this->requestRouteString('slug');
        unset($_SESSION['tournament_admin']);

        $this->setFlash('success', 'Tournament admin signed out.');
        $this->redirect('/tournament/' . $slug . '/login');
    }
}
