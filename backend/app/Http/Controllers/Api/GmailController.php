<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GmailController extends Controller
{
    public function authUrl(Request $request)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $params = [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/userinfo.email',
                'openid',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => (string) $workspaceId,
        ];

        return response()->json([
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params),
        ]);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        $workspaceId = (int) $request->query('state');

        if (! $code || ! $workspaceId) {
            return response()->json(['message' => 'Invalid callback'], 400);
        }

        $tokenResp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResp->ok()) {
            return response()->json(['message' => 'Token exchange failed', 'detail' => $tokenResp->json()], 500);
        }

        $accessToken = $tokenResp->json('access_token');
        $refreshToken = $tokenResp->json('refresh_token');
        $expiresIn = (int) $tokenResp->json('expires_in');

        $me = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        $email = $me->ok() ? $me->json('email') : null;

        $channel = Channel::updateOrCreate(
            ['workspace_id' => $workspaceId, 'provider' => 'gmail', 'account_label' => $email],
            [
                'status' => 'connected',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken ?: null,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'settings' => ['last_synced_at' => null],
            ]
        );

        return response()->json(['message' => 'Gmail connected', 'channel_id' => $channel->id, 'email' => $email]);
    }
}
