<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class ChannelController extends BaseController
{
    public function providers(Request $request, Response $response): void
    {
        $response->json(['providers' => ['gmail', 'facebook', 'instagram', 'whatsapp', 'telegram', 'slack', 'website']]);
    }

    public function connect(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $body = $request->body();
        $provider = (string)($body['provider'] ?? '');
        $account = (string)($body['account_label'] ?? '');
        if (!in_array($provider, ['gmail', 'facebook', 'instagram', 'whatsapp', 'telegram', 'slack', 'website'], true)) {
            $response->json(['message' => 'Invalid provider'], 422);
            return;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO channels(workspace_id, provider, account_label, status, settings, created_at) VALUES(?,?,?,?,?,NOW())');
        $stmt->execute([$workspaceId, $provider, $account ?: null, 'connected', json_encode(['connected_at' => date(DATE_ATOM)])]);

        $response->json(['message' => 'Channel connected', 'channel_id' => (int)$pdo->lastInsertId()], 201);
    }
}
