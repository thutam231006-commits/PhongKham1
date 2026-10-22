<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = strtolower((string) $request->user()?->role);
        $allowed = array_map('strtolower', $roles);

        abort_unless($request->user() && in_array($role, $allowed, true), 403);

        return $next($request);
    }
}
