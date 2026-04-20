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
$requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
$scriptDirectory = str_replace('\\', '/', dirname($scriptName));
$scriptDirectory = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');

$path = is_string($requestUriPath) && $requestUriPath !== '' ? $requestUriPath : '/';

if ($scriptDirectory !== '' && strncmp($path, $scriptDirectory, strlen($scriptDirectory)) === 0) {
    $path = substr($path, strlen($scriptDirectory));
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
}

if ($path === '/index.php') {
    $path = '/';
} elseif (strncmp($path, '/index.php/', strlen('/index.php/')) === 0) {
    $path = substr($path, strlen('/index.php'));
}

if (!is_string($path) || $path === '') {
    $path = '/';
}

$router->dispatch($method, $path);
