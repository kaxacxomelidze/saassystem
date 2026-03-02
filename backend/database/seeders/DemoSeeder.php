<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@movoer.test'],
            ['name' => 'MOVOER Owner', 'password' => bcrypt('password123')],
        );

        User::firstOrCreate(
            ['email' => 'admin@movoer.test'],
            ['name' => 'MOVOER Admin', 'password' => bcrypt('AdminPass123!'), 'is_super_admin' => true],
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'movoer-demo'],
            ['name' => 'MOVOER Demo Workspace', 'owner_user_id' => $owner->id],
        );

        WorkspaceUser::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $owner->id],
            ['role' => 'owner'],
        );

        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::firstOrCreate(
                ['workspace_id' => $workspace->id, 'email' => "client{$i}@example.com"],
                ['name' => "Client {$i}", 'phone' => null, 'custom_fields' => []],
            );

            $conversation = Conversation::create([
                'workspace_id' => $workspace->id,
                'contact_id' => $contact->id,
                'channel' => collect(['gmail', 'telegram', 'website'])->random(),
                'external_thread_id' => (string) Str::uuid(),
                'status' => collect(['open', 'pending', 'closed'])->random(),
                'priority' => collect(['normal', 'important', 'urgent'])->random(),
                'assigned_user_id' => null,
                'last_message_at' => now(),
            ]);

            for ($k = 0; $k < random_int(3, 10); $k++) {
                Message::create([
                    'workspace_id' => $workspace->id,
                    'conversation_id' => $conversation->id,
                    'direction' => $k % 2 === 0 ? 'in' : 'out',
                    'sender' => $k % 2 === 0 ? $contact->email : $owner->email,
                    'body' => fake()->sentence(random_int(10, 20)),
                    'meta' => [],
                    'sent_at' => now()->subMinutes(random_int(1, 2000)),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);
        }
    }
}
