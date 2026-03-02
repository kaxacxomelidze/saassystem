<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'enabled', 'channel', 'keyword',
        'set_priority', 'set_status', 'assign_user_id', 'add_tag',
    ];

    protected $casts = ['enabled' => 'boolean'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_user_id');
    }
}
