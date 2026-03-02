<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;

class GmailService
{
    public function ensureValidAccessToken(Channel $channel): Channel
    {
        if ($channel->token_expires_at && $channel->token_expires_at->isFuture() && $channel->access_token) {
            return $channel;
        }

        if (! $channel->refresh_token) {
            $channel->status = 'expired';
            $channel->save();
            throw new \RuntimeException('No refresh_token. Reconnect Gmail with prompt=consent.');
        }

        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => $channel->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $resp->ok()) {
            $channel->status = 'error';
            $channel->save();
            throw new \RuntimeException('Refresh failed: '.json_encode($resp->json()));
        }

        $channel->access_token = $resp->json('access_token');
        $channel->token_expires_at = now()->addSeconds((int) $resp->json('expires_in'));
        $channel->status = 'connected';
        $channel->save();

        return $channel;
    }

    public function listThreads(Channel $channel, ?string $pageToken = null, int $max = 15): array
    {
        $params = ['maxResults' => $max, 'q' => 'in:anywhere'];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $resp = Http::withToken($channel->access_token)->get('https://gmail.googleapis.com/gmail/v1/users/me/threads', $params);

        if (! $resp->ok()) {
            throw new \RuntimeException('Threads fetch failed');
        }

        return $resp->json();
    }

    public function getThread(Channel $channel, string $threadId): array
    {
        $resp = Http::withToken($channel->access_token)
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/threads/{$threadId}", ['format' => 'full']);

        if (! $resp->ok()) {
            throw new \RuntimeException('Thread fetch failed');
        }

        return $resp->json();
    }
}
