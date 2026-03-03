<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AdminController extends BaseController
{
    public function users(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        if ((int)$user['is_super_admin'] !== 1) {
            $response->json(['message' => 'Super admin only'], 403);
            return;
        }

        $pdo = Database::pdo();
        $users = $pdo->query('SELECT id,name,email,is_super_admin,created_at FROM users ORDER BY id DESC')->fetchAll();
        $response->json(['users' => $users]);
    }
}
