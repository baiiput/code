<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        // Super admin has access to everything
        if ($userRole === 'super_admin') {
            return $next($request);
        }

        // Check if user has required role
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // Admin has access to admin and viewer routes
        if ($userRole === 'admin' && (in_array('admin', $roles) || in_array('viewer', $roles))) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
