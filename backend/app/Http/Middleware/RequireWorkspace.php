<?php

namespace App\Http\Middleware;

use App\Models\WorkspaceUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = (int) $request->header('X-Workspace-Id');
        if (! $workspaceId) {
            return response()->json(['message' => 'Missing X-Workspace-Id header'], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $membership = WorkspaceUser::where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return response()->json(['message' => 'Not a member of this workspace'], 403);
        }

        $request->attributes->set('workspace_id', $workspaceId);
        $request->attributes->set('workspace_role', $membership->role);

        return $next($request);
    }
}
