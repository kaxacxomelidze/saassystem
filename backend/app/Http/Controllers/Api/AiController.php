<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function draft(Request $request, int $conversationId)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $request->validate([
            'tone' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:40'],
        ]);

        $conversation = Conversation::where('workspace_id', $workspaceId)
            ->with(['messages' => fn ($query) => $query->orderByDesc('id')->limit(20), 'contact'])
            ->findOrFail($conversationId);

        $tone = $request->input('tone', 'professional');
        $language = $request->input('language', 'English');

        $context = $conversation->messages
            ->reverse()
            ->map(fn ($message) => strtoupper($message->direction).': '.$message->body)
            ->implode("\n");

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');

        $resp = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => "You are MOVOER AI assistant. Tone: {$tone}. Language: {$language}."],
                ['role' => 'user', 'content' => "Conversation:\n{$context}\n\nWrite the best next reply."],
            ],
            'temperature' => 0.4,
        ]);

        if (! $resp->ok()) {
            return response()->json(['message' => 'AI request failed', 'detail' => $resp->json()], 500);
        }

        $draft = $resp->json('choices.0.message.content');

        AiLog::create([
            'workspace_id' => $workspaceId,
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'action' => 'draft',
            'input' => ['tone' => $tone, 'language' => $language, 'context' => $context],
            'output' => ['draft' => $draft],
        ]);

        return response()->json(['draft' => $draft]);
    }
}
