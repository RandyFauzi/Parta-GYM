<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            abort(403);
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $cameraAllowed = $request->isMethod('GET') && $request->routeIs('staff.attendance.me')
            && $request->user()?->is_active && in_array($request->user()->role, ['owner', 'receptionist', 'personal_trainer'], true);
        $response->headers->set('Permissions-Policy', ($cameraAllowed ? 'camera=(self)' : 'camera=()').', microphone=(), geolocation=()');
        $response->headers->set('Cache-Control', 'private, no-store');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
