<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\AiController;
use App\Controllers\AuthController;
use App\Controllers\ChannelController;
use App\Controllers\GmailController;
use App\Controllers\InboxController;
use App\Controllers\WorkspaceController;

class App
{
    public function run(): void
    {
        $router = new Router();

        $auth = new AuthController();
        $workspace = new WorkspaceController();
        $inbox = new InboxController();
        $channels = new ChannelController();
        $admin = new AdminController();
        $ai = new AiController();
        $gmail = new GmailController();

        $router->post('/api/register', [$auth, 'register']);
        $router->post('/api/login', [$auth, 'login']);

        $router->get('/api/workspaces', [$workspace, 'my']);
        $router->post('/api/workspaces', [$workspace, 'create']);

        $router->get('/api/inbox', [$inbox, 'list']);
        $router->get('/api/inbox/{id}', [$inbox, 'show']);

        $router->post('/api/ai/{conversationId}/draft', [$ai, 'draft']);

        $router->get('/api/channels/providers', [$channels, 'providers']);
        $router->post('/api/channels/connect', [$channels, 'connect']);

        $router->get('/api/gmail/auth-url', [$gmail, 'authUrl']);
        $router->post('/api/gmail/sync-now', [$gmail, 'syncNow']);

        $router->get('/api/admin/users', [$admin, 'users']);

        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    }
}
