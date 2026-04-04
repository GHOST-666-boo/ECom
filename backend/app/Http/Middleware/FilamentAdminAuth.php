<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilamentAdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Check if user is authenticated and has admin role
        // Only check role if user is authenticated (Authenticate middleware runs first)
        if ($user && $user->role !== 'admin') {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}
