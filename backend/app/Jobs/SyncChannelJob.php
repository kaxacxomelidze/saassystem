<?php

namespace App\Jobs;

use App\Models\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $channelId)
    {
    }

    public function handle(): void
    {
        $channel = Channel::find($this->channelId);
        if (! $channel) {
            return;
        }

        if ($channel->provider === 'gmail') {
            SyncGmailChannelJob::dispatchSync($channel->id);

            return;
        }

        $settings = (array) $channel->settings;
        $settings['last_synced_at'] = now()->toISOString();
        $settings['sync_note'] = 'Connector scaffold: implement provider API ingestion.';
        $channel->settings = $settings;
        $channel->save();
    }
}
