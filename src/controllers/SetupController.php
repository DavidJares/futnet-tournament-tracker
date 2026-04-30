<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuperadminModel;
use Throwable;

final class SetupController extends BaseController
{
    public function index(): void
    {
        $superadminModel = new SuperadminModel($this->db());

        if (!$superadminModel->tableExists()) {
            $this->render('setup/unavailable', [
                'title' => 'Setup unavailable',
                'message' => 'Database tables are missing. Run migrations first (php scripts/migrate.php).',
            ]);
            return;
        }

        if ($superadminModel->hasAny()) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $this->render('setup/index', [
            'title' => 'Initial setup',
        ]);
    }

    public function store(): void
    {
        $superadminModel = new SuperadminModel($this->db());

        if (!$superadminModel->tableExists()) {
            $this->render('setup/unavailable', [
                'title' => 'Setup unavailable',
                'message' => 'Database tables are missing. Run migrations first (php scripts/migrate.php).',
            ]);
            return;
        }

        if ($superadminModel->hasAny()) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '404 Not Found';
            return;
        }

        $username = $this->requestPostString('username');
        $password = $this->requestPostString('password');

        if ($username === '' || $password === '') {
            $this->setFlash('error', 'Username and password are required.');
            $this->redirect('/setup');
        }

        if (strlen($password) < 8) {
            $this->setFlash('error', 'Password must have at least 8 characters.');
            $this->redirect('/setup');
        }

        try {
            $superadminModel->create($username, $password);
        } catch (Throwable $throwable) {
            $this->setFlash('error', 'Superadmin could not be created. Username may already exist.');
            $this->redirect('/setup');
        }

        $this->setFlash('success', 'Superadmin created. You can now sign in.');
        $this->redirect('/admin/login');
    }
}
