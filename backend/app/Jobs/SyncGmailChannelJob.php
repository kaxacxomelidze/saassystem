<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGmailChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $channelId)
    {
    }

    public function handle(GmailService $gmail): void
    {
        $channel = Channel::findOrFail($this->channelId);
        if ($channel->provider !== 'gmail') {
            return;
        }

        $channel = $gmail->ensureValidAccessToken($channel);

        $threads = $gmail->listThreads($channel, null, 15);

        foreach (($threads['threads'] ?? []) as $thread) {
            $threadId = $thread['id'];
            $data = $gmail->getThread($channel, $threadId);

            $conversation = Conversation::firstOrCreate(
                ['workspace_id' => $channel->workspace_id, 'channel' => 'gmail', 'external_thread_id' => $threadId],
                ['status' => 'open', 'priority' => 'normal', 'last_message_at' => now()]
            );

            foreach (($data['messages'] ?? []) as $gmailMessage) {
                $externalMessageId = $gmailMessage['id'] ?? null;
                if (! $externalMessageId) {
                    continue;
                }

                $headers = collect($gmailMessage['payload']['headers'] ?? [])
                    ->mapWithKeys(fn ($header) => [strtolower($header['name']) => $header['value']]);

                $from = $headers->get('from');
                $to = $headers->get('to');
                $subject = $headers->get('subject');

                $sentAt = isset($gmailMessage['internalDate']) ? (int) $gmailMessage['internalDate'] : null;
                $sentAtDt = $sentAt ? now()->createFromTimestampMs($sentAt) : now();

                $body = $this->extractBody($gmailMessage['payload'] ?? []) ?: '[no text body]';

                $direction = 'in';
                if ($channel->account_label && $from && str_contains(strtolower($from), strtolower($channel->account_label))) {
                    $direction = 'out';
                }

                $email = $this->extractEmail($direction === 'in' ? $from : $to);
                if ($email) {
                    $contact = Contact::firstOrCreate(
                        ['workspace_id' => $channel->workspace_id, 'email' => $email],
                        ['name' => null, 'phone' => null, 'custom_fields' => []]
                    );
                    if (! $conversation->contact_id) {
                        $conversation->contact_id = $contact->id;
                    }
                }

                $exists = Message::where('workspace_id', $channel->workspace_id)
                    ->where('conversation_id', $conversation->id)
                    ->where('meta->>external_id', $externalMessageId)
                    ->exists();

                if (! $exists) {
                    Message::create([
                        'workspace_id' => $channel->workspace_id,
                        'conversation_id' => $conversation->id,
                        'direction' => $direction,
                        'sender' => $direction === 'in' ? ($email ?? 'unknown') : ($channel->account_label ?? 'me'),
                        'body' => $body,
                        'meta' => [
                            'external_id' => $externalMessageId,
                            'subject' => $subject,
                            'from' => $from,
                            'to' => $to,
                        ],
                        'sent_at' => $sentAtDt,
                    ]);
                }

                $conversation->last_message_at = max($conversation->last_message_at ?? now(), $sentAtDt);
            }

            $conversation->save();
        }

        $channel->settings = array_merge((array) $channel->settings, ['last_synced_at' => now()->toISOString()]);
        $channel->save();
    }

    private function extractEmail(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        if (preg_match('/<([^>]+)>/', $header, $matches)) {
            return strtolower(trim($matches[1]));
        }

        $header = trim($header);
        if (filter_var($header, FILTER_VALIDATE_EMAIL)) {
            return strtolower($header);
        }

        return null;
    }

    private function extractBody(array $payload): ?string
    {
        if (! empty($payload['body']['data'])) {
            return $this->decodeBody($payload['body']['data']);
        }

        foreach (($payload['parts'] ?? []) as $part) {
            $mime = $part['mimeType'] ?? '';
            if ($mime === 'text/plain' && ! empty($part['body']['data'])) {
                return $this->decodeBody($part['body']['data']);
            }
        }

        foreach (($payload['parts'] ?? []) as $part) {
            $nested = $this->extractBody($part);
            if ($nested) {
                return $nested;
            }
        }

        return null;
    }

    private function decodeBody(string $data): string
    {
        $data = strtr($data, '-_', '+/');

        return base64_decode($data) ?: '';
    }
}
