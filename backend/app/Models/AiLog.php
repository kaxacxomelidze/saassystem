<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $fillable = ['workspace_id', 'conversation_id', 'user_id', 'action', 'input', 'output'];

    protected $casts = ['input' => AsArrayObject::class, 'output' => AsArrayObject::class];
}
