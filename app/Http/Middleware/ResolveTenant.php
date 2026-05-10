<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Resolve current tenant context dari user yang login.
 *
 * Resolution order:
 *   1. Session "active_tenant_id" — superadmin yang lagi switch tenant
 *      lewat tenant switcher.
 *   2. auth()->user()->tenant_id — user staff atau customer biasa.
 *   3. null — guest (login page) atau superadmin tanpa active tenant
 *      (liat semua tenant).
 *
 * Middleware ini dipasang di group 'web' supaya semua authenticated
 * request punya context tenant yang konsisten.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $ctx = app(TenantContext::class);
        $user = $request->user();

        if (!$user) {
            // Guest — tidak ada tenant context (login page, public routes).
            return $next($request);
        }

        $isSuperAdmin = $user->role && $user->role->name === Role::SUPERADMIN;

        // Superadmin bisa override tenant aktif lewat session "active_tenant_id"
        // (di-set oleh tenant switcher di Phase 3). Kalau gak ada → liat semua.
        if ($isSuperAdmin) {
            $activeId = (int) session('active_tenant_id');
            if ($activeId > 0) {
                $ctx->setTenantId($activeId);
                $ctx->setSuperAdminGlobal(false);
            } else {
                $ctx->setTenantId(null);
                $ctx->setSuperAdminGlobal(true);
            }
            return $next($request);
        }

        // Non-superadmin: scope ke tenant_id user. Kalau user gak punya
        // tenant_id (data corrupt / bug Phase 1 backfill), force logout
        // demi keamanan.
        if (!$user->tenant_id) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun tidak terhubung ke tenant manapun. Hubungi admin.',
            ]);
        }

        $ctx->setTenantId((int) $user->tenant_id);
        return $next($request);
    }
}
