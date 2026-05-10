<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Eloquent global scope yang auto-filter query by tenant_id.
 *
 * Cuma aktif kalau TenantContext::shouldScope() returns true. Kalau
 * superadmin (no active tenant) atau guest (no session) → scope skip.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $ctx = app(TenantContext::class);
        if (!$ctx->shouldScope()) {
            return;
        }
        $builder->where(
            $model->getTable() . '.tenant_id',
            $ctx->tenantId()
        );
    }
}
