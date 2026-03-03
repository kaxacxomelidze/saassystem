<?php

namespace App\Http;

use App\Http\Middleware\RequireSuperAdmin;
use App\Http\Middleware\RequireWorkspace;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'workspace' => RequireWorkspace::class,
        'super_admin' => RequireSuperAdmin::class,
    ];
}
