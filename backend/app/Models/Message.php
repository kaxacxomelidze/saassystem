<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['workspace_id', 'conversation_id', 'direction', 'sender', 'body', 'meta', 'sent_at'];

    protected $casts = ['meta' => AsArrayObject::class, 'sent_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
