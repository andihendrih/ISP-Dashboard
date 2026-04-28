<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin,noc')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }
        if (empty($roles)) {
            return $next($request);
        }
        if ($user->role && in_array($user->role->name, $roles, true)) {
            return $next($request);
        }
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
