<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin,noc')
     *
     * Superadmin selalu bisa akses semua route — gak perlu disebut eksplisit
     * di tiap route group. Ini bikin role middleware lebih simple.
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
        // Superadmin bypass — God mode untuk platform owner.
        if ($user->role && $user->role->name === Role::SUPERADMIN) {
            return $next($request);
        }
        if ($user->role && in_array($user->role->name, $roles, true)) {
            return $next($request);
        }
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
