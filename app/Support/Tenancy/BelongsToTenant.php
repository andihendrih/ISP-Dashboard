<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait untuk model yang per-tenant. Yang dilakukan trait ini:
 *   1. Auto-attach `TenantScope` ke query builder (global scope).
 *   2. Auto-fill `tenant_id` ke current tenant pas model di-create
 *      (kalau attribute belum di-set caller).
 *   3. Sediain helper `tenant()` belongsTo relation + scope helper
 *      `withoutTenantScope()` & `forTenant($id)`.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $ctx = app(TenantContext::class);
                if ($ctx->tenantId()) {
                    $model->tenant_id = $ctx->tenantId();
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Skip tenant scope sementara (untuk admin/cross-tenant queries). */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /** Force query ke tenant tertentu (override scope default). */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable() . '.tenant_id', $tenantId);
    }
}
