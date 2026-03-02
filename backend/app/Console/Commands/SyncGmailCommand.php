<?php

namespace App\Console\Commands;

use App\Jobs\SyncGmailChannelJob;
use App\Models\Channel;
use Illuminate\Console\Command;

class SyncGmailCommand extends Command
{
    protected $signature = 'movoer:sync-gmail';

    protected $description = 'Dispatch Gmail sync jobs for all connected channels';

    public function handle(): int
    {
        $channels = Channel::where('provider', 'gmail')->where('status', 'connected')->get();
        foreach ($channels as $channel) {
            SyncGmailChannelJob::dispatch($channel->id);
        }

        $this->info('Dispatched '.$channels->count().' jobs');

        return self::SUCCESS;
    }
}
