<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomationService
{
    public function applyOnNewMessage(Conversation $conv, Message $msg): void
    {
        if ($msg->direction !== 'in') {
            return;
        }

        $rules = DB::table('automation_rules')
            ->where('workspace_id', $conv->workspace_id)
            ->where('enabled', true)
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->channel && $rule->channel !== $conv->channel) {
                continue;
            }

            if ($rule->keyword && ! Str::contains(Str::lower($msg->body), Str::lower($rule->keyword))) {
                continue;
            }

            if ($rule->set_priority) {
                $conv->priority = $rule->set_priority;
            }
            if ($rule->set_status) {
                $conv->status = $rule->set_status;
            }
            if ($rule->assign_user_id) {
                $conv->assigned_user_id = $rule->assign_user_id;
            }
            $conv->save();

            if ($rule->add_tag) {
                $tag = Tag::firstOrCreate(
                    ['workspace_id' => $conv->workspace_id, 'name' => $rule->add_tag],
                    ['color' => null],
                );
                $conv->tags()->syncWithoutDetaching([$tag->id]);
            }

            return;
        }
    }
}
