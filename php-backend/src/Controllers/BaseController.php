<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

abstract class BaseController
{
    protected function requireUser(Request $request, Response $response): ?array
    {
        $userId = AuthService::parseToken($request->header('Authorization'));
        if (!$userId) {
            $response->json(['message' => 'Unauthenticated'], 401);

            return null;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id,name,email,is_super_admin FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $response->json(['message' => 'User not found'], 401);

            return null;
        }

        return $user;
    }

    protected function requireWorkspace(Request $request, Response $response, int $userId): ?int
    {
        $workspaceId = (int) $request->header('X-Workspace-Id');
        if (!$workspaceId) {
            $response->json(['message' => 'Missing X-Workspace-Id'], 400);

            return null;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM workspace_users WHERE workspace_id=? AND user_id=? LIMIT 1');
        $stmt->execute([$workspaceId, $userId]);
        if (!$stmt->fetch()) {
            $response->json(['message' => 'Not a member of this workspace'], 403);

            return null;
        }

        return $workspaceId;
    }
}
