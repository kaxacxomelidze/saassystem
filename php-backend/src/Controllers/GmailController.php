<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class GmailController extends BaseController
{
    public function authUrl(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? 'missing-client-id',
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://127.0.0.1:8080/api/gmail/callback',
            'response_type' => 'code',
            'scope' => 'openid email https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => (string)$workspaceId,
        ]);

        $response->json(['url' => $url]);
    }

    public function syncNow(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("UPDATE channels SET settings = JSON_SET(COALESCE(settings, JSON_OBJECT()), '$.last_synced_at', ?) WHERE workspace_id=? AND provider='gmail'");
        $stmt->execute([date(DATE_ATOM), $workspaceId]);

        $response->json(['message' => 'Sync queued (placeholder)']);
    }
}
