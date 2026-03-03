<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncChannelJob;
use App\Models\Channel;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function supportedProviders()
    {
        return response()->json([
            'providers' => [
                'gmail',
                'facebook',
                'instagram',
                'whatsapp',
                'telegram',
                'slack',
                'website',
            ],
        ]);
    }

    public function index(Request $request)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $channels = Channel::where('workspace_id', $workspaceId)
            ->orderByDesc('id')
            ->get();

        return response()->json(['channels' => $channels]);
    }

    public function connect(Request $request)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $request->validate([
            'provider' => ['required', 'in:gmail,facebook,instagram,whatsapp,telegram,slack,website'],
            'account_label' => ['nullable', 'string', 'max:190'],
            'settings' => ['nullable', 'array'],
        ]);

        $channel = Channel::updateOrCreate(
            [
                'workspace_id' => $workspaceId,
                'provider' => $request->provider,
                'account_label' => $request->input('account_label'),
            ],
            [
                'status' => 'connected',
                'settings' => array_merge((array) $request->input('settings', []), ['connected_at' => now()->toISOString()]),
            ]
        );

        return response()->json(['channel' => $channel], 201);
    }

    public function syncNow(Request $request, int $channelId)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $channel = Channel::where('workspace_id', $workspaceId)->findOrFail($channelId);
        SyncChannelJob::dispatch($channel->id);

        return response()->json(['message' => 'Sync queued', 'channel_id' => $channel->id, 'provider' => $channel->provider]);
    }
}
