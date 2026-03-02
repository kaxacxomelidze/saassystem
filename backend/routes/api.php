<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\GmailController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Jobs\SyncGmailChannelJob;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'min:8'],
    ]);

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

    return response()->json(['token' => $user->createToken('movoer')->plainTextToken, 'user' => $user]);
});

Route::post('/login', function (Request $request) {
    $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

    if (! Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = \App\Models\User::where('email', $request->email)->firstOrFail();

    return response()->json(['token' => $user->createToken('movoer')->plainTextToken, 'user' => $user]);
});

Route::get('/gmail/callback', [GmailController::class, 'callback']);
Route::post('/stripe/webhook', [BillingController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/workspaces', [WorkspaceController::class, 'my']);
    Route::post('/workspaces', [WorkspaceController::class, 'create']);

    Route::middleware('super_admin')->prefix('/admin')->group(function (): void {
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{userId}/super-admin', [AdminController::class, 'setSuperAdmin']);
        Route::post('/workspaces/{workspaceId}/users/{userId}/role', [AdminController::class, 'setUserRole']);
    });

    Route::middleware('workspace')->group(function (): void {
        Route::get('/inbox', [InboxController::class, 'list']);
        Route::get('/inbox/{id}', [InboxController::class, 'show']);
        Route::post('/inbox/{id}/messages', [InboxController::class, 'addMessage']);
        Route::post('/inbox/{id}/assign', [InboxController::class, 'assign']);
        Route::post('/inbox/{id}/priority', [InboxController::class, 'setPriority']);
        Route::post('/inbox/{id}/status', [InboxController::class, 'setStatus']);

        Route::post('/ai/{conversationId}/draft', [AiController::class, 'draft']);

        Route::get('/channels/providers', [ChannelController::class, 'supportedProviders']);
        Route::get('/channels', [ChannelController::class, 'index']);
        Route::post('/channels/connect', [ChannelController::class, 'connect']);
        Route::post('/channels/{channelId}/sync-now', [ChannelController::class, 'syncNow']);

        Route::get('/gmail/auth-url', [GmailController::class, 'authUrl']);
        Route::post('/gmail/sync-now', function (Request $request) {
            $workspaceId = (int) $request->attributes->get('workspace_id');
            $channel = Channel::where('workspace_id', $workspaceId)->where('provider', 'gmail')->firstOrFail();
            SyncGmailChannelJob::dispatch($channel->id);

            return response()->json(['message' => 'Sync queued']);
        });

        Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    });
});
