<?php

namespace App\Models;

use App\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $fillable = [
        'workspace_id', 'provider', 'account_label', 'status',
        'access_token', 'refresh_token', 'token_expires_at', 'settings',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'settings' => AsArrayObject::class,
        'access_token' => EncryptedString::class,
        'refresh_token' => EncryptedString::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
