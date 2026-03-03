<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class AiController extends BaseController
{
    public function draft(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $conversationId = (int)$request->route('conversationId');
        $body = $request->body();
        $tone = (string)($body['tone'] ?? 'professional');
        $language = (string)($body['language'] ?? 'English');

        $draft = "[AI draft placeholder] Conversation #{$conversationId} | tone={$tone} | language={$language}.\nThanks for your message — we will get back to you shortly.";

        $response->json(['draft' => $draft]);
    }
}
