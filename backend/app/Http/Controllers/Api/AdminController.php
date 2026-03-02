<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::query()->orderByDesc('id')->get(['id', 'name', 'email', 'is_super_admin', 'created_at']);

        return response()->json(['users' => $users]);
    }

    public function setUserRole(Request $request, int $workspaceId, int $userId)
    {
        $request->validate([
            'role' => ['required', 'in:owner,admin,manager,agent,viewer'],
        ]);

        Workspace::findOrFail($workspaceId);
        User::findOrFail($userId);

        $membership = WorkspaceUser::updateOrCreate(
            ['workspace_id' => $workspaceId, 'user_id' => $userId],
            ['role' => $request->role],
        );

        return response()->json(['membership' => $membership]);
    }

    public function setSuperAdmin(Request $request, int $userId)
    {
        $request->validate([
            'is_super_admin' => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($userId);
        $user->is_super_admin = (bool) $request->is_super_admin;
        $user->save();

        return response()->json(['user' => $user]);
    }
}
