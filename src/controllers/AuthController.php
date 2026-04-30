<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuperadminModel;

final class AuthController extends BaseController
{
    public function loginForm(): void
    {
        $superadminModel = new SuperadminModel($this->db());
        if (!$superadminModel->tableExists() || !$superadminModel->hasAny()) {
            $this->redirect('/setup');
        }

        if ($this->currentSuperadmin() !== null) {
            $this->redirect('/admin/dashboard');
        }

        $this->render('admin/login', [
            'title' => 'Superadmin login',
        ]);
    }

    public function login(): void
    {
        if ($this->isLoginRateLimited('superadmin')) {
            $this->setFlash('error', 'Invalid credentials.');
            $this->redirect('/admin/login');
        }

        $superadminModel = new SuperadminModel($this->db());
        if (!$superadminModel->tableExists() || !$superadminModel->hasAny()) {
            $this->redirect('/setup');
        }

        $username = $this->requestPostString('username');
        $password = $this->requestPostString('password');

        if ($username === '' || $password === '') {
            $this->setFlash('error', 'Username and password are required.');
            $this->redirect('/admin/login');
        }

        $superadmin = $superadminModel->findByUsername($username);
        if ($superadmin === null || !password_verify($password, (string) $superadmin['password_hash'])) {
            $this->recordLoginFailure('superadmin');
            $this->setFlash('error', 'Invalid credentials.');
            $this->redirect('/admin/login');
        }

        session_regenerate_id(true);
        $_SESSION['superadmin'] = [
            'id' => (int) $superadmin['id'],
            'username' => (string) $superadmin['username'],
        ];
        $this->resetLoginThrottle('superadmin');

        $this->setFlash('success', 'You are signed in.');
        $this->redirect('/admin/dashboard');
    }

    public function logout(): void
    {
        unset($_SESSION['superadmin']);
        session_regenerate_id(true);

        $this->setFlash('success', 'You are signed out.');
        $this->redirect('/admin/login');
    }
}
