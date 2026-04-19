<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\SetupController;
use App\Controllers\AuthController;
use App\Controllers\AdminDashboardController;
use App\Controllers\TournamentController;
use App\Router;

$services = require __DIR__ . '/../src/bootstrap.php';
session_start();

$router = new Router();
$homeController = new HomeController($services);
$setupController = new SetupController($services);
$authController = new AuthController($services);
$adminDashboardController = new AdminDashboardController($services);
$tournamentController = new TournamentController($services);

$router->get('/', [$homeController, 'index']);
$router->get('/setup', [$setupController, 'index']);
$router->post('/setup', [$setupController, 'store']);

$router->get('/admin/login', [$authController, 'loginForm']);
$router->post('/admin/login', [$authController, 'login']);
$router->post('/admin/logout', [$authController, 'logout']);

$router->get('/admin/dashboard', [$adminDashboardController, 'index']);
$router->post('/admin/tournaments/create', [$adminDashboardController, 'createTournament']);
$router->post('/admin/tournaments/delete', [$adminDashboardController, 'deleteTournament']);

$router->get('/admin/tournament', [$tournamentController, 'detail']);
$router->post('/admin/tournament/update', [$tournamentController, 'update']);
$router->post('/admin/tournament/teams/create', [$tournamentController, 'createTeam']);
$router->post('/admin/tournament/teams/update', [$tournamentController, 'updateTeam']);
$router->post('/admin/tournament/teams/delete', [$tournamentController, 'deleteTeam']);
$router->post('/admin/tournament/teams/assign', [$tournamentController, 'assignTeamGroup']);
$router->post('/admin/tournament/teams/assign-auto', [$tournamentController, 'autoAssignTeams']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (!is_string($path) || $path === '') {
    $path = '/';
}

$router->dispatch($method, $path);
