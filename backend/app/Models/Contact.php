<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = ['workspace_id', 'name', 'email', 'phone', 'custom_fields'];

    protected $casts = ['custom_fields' => AsArrayObject::class];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
