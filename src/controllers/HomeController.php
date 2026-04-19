<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuperadminModel;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $superadminModel = new SuperadminModel($this->db());

        if (!$superadminModel->tableExists() || !$superadminModel->hasAny()) {
            $this->redirect('/setup');
        }

        if ($this->currentSuperadmin() !== null) {
            $this->redirect('/admin/dashboard');
        }

        $this->redirect('/admin/login');
    }
}
