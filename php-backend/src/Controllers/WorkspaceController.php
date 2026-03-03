<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class WorkspaceController extends BaseController
{
    public function my(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT w.*, wu.role as my_role FROM workspaces w JOIN workspace_users wu ON w.id=wu.workspace_id WHERE wu.user_id=? ORDER BY w.id DESC');
        $stmt->execute([(int)$user['id']]);

        $response->json(['workspaces' => $stmt->fetchAll()]);
    }

    public function create(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $name = trim((string)($request->body()['name'] ?? ''));
        if (!$name) {
            $response->json(['message' => 'Name required'], 422);
            return;
        }

        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-')).'-'.substr(bin2hex(random_bytes(4)), 0, 6);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO workspaces(name, slug, owner_user_id, created_at) VALUES(?,?,?,NOW())')->execute([$name, $slug, (int)$user['id']]);
        $workspaceId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO workspace_users(workspace_id, user_id, role, created_at) VALUES(?,?,?,NOW())')->execute([$workspaceId, (int)$user['id'], 'owner']);
        $pdo->commit();

        $response->json(['workspace' => ['id' => $workspaceId, 'name' => $name, 'slug' => $slug]], 201);
    }
}
