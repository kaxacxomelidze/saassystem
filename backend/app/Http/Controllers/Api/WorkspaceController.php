<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function create(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:120']]);

        $workspace = Workspace::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name).'-'.Str::lower(Str::random(6)),
            'owner_user_id' => $request->user()->id,
        ]);

        WorkspaceUser::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
        ]);

        return response()->json(['workspace' => $workspace], 201);
    }

    public function my(Request $request)
    {
        $workspaces = Workspace::query()
            ->join('workspace_users', 'workspaces.id', '=', 'workspace_users.workspace_id')
            ->where('workspace_users.user_id', $request->user()->id)
            ->select('workspaces.*', 'workspace_users.role as my_role')
            ->orderByDesc('workspaces.id')
            ->get();

        return response()->json(['workspaces' => $workspaces]);
    }
}
