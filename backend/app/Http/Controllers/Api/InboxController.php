<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AutomationService;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function list(Request $request)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $query = Conversation::where('workspace_id', $workspaceId)
            ->with(['contact', 'assignedUser:id,name,email', 'tags'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($assigned = $request->query('assigned_user_id')) {
            $query->where('assigned_user_id', (int) $assigned);
        }

        return response()->json(['conversations' => $query->paginate(20)]);
    }

    public function show(Request $request, int $id)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $conversation = Conversation::where('workspace_id', $workspaceId)
            ->with(['contact', 'assignedUser:id,name,email', 'tags', 'messages'])
            ->findOrFail($id);

        return response()->json(['conversation' => $conversation]);
    }

    public function addMessage(Request $request, int $id, AutomationService $automation)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');

        $request->validate([
            'body' => ['required', 'string'],
            'direction' => ['nullable', 'in:in,out,internal'],
            'sender' => ['nullable', 'string', 'max:190'],
        ]);

        $conversation = Conversation::where('workspace_id', $workspaceId)->findOrFail($id);

        $message = Message::create([
            'workspace_id' => $workspaceId,
            'conversation_id' => $conversation->id,
            'direction' => $request->input('direction', 'internal'),
            'sender' => $request->input('sender', $request->user()->email),
            'body' => $request->body,
            'sent_at' => now(),
            'meta' => [],
        ]);

        $conversation->update(['last_message_at' => now()]);
        $automation->applyOnNewMessage($conversation, $message);

        return response()->json(['message' => $message], 201);
    }

    public function assign(Request $request, int $id)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');
        $request->validate(['assigned_user_id' => ['nullable', 'integer', 'exists:users,id']]);

        $conversation = Conversation::where('workspace_id', $workspaceId)->findOrFail($id);
        $conversation->assigned_user_id = $request->assigned_user_id;
        $conversation->save();

        return response()->json(['conversation' => $conversation]);
    }

    public function setPriority(Request $request, int $id)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');
        $request->validate(['priority' => ['required', 'in:normal,important,urgent']]);

        $conversation = Conversation::where('workspace_id', $workspaceId)->findOrFail($id);
        $conversation->priority = $request->priority;
        $conversation->save();

        return response()->json(['conversation' => $conversation]);
    }

    public function setStatus(Request $request, int $id)
    {
        $workspaceId = (int) $request->attributes->get('workspace_id');
        $request->validate(['status' => ['required', 'in:open,pending,closed']]);

        $conversation = Conversation::where('workspace_id', $workspaceId)->findOrFail($id);
        $conversation->status = $request->status;
        $conversation->save();

        return response()->json(['conversation' => $conversation]);
    }
}
