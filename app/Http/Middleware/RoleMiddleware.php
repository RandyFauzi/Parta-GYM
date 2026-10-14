<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $ownerWorkspaceAccess = $user?->role === 'owner' && ! in_array('member', $roles, true);
        abort_unless($user?->is_active && ($ownerWorkspaceAccess || in_array($user->role, $roles, true)), 403);

        return $next($request);
    }
}
