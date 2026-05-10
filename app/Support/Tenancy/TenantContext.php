<?php

namespace App\Support\Tenancy;

/**
 * Holds the active tenant_id for the current request lifecycle.
 *
 * Resolution order (set by TenantResolver middleware):
 *   1. Session "active_tenant_id" — superadmin yang manually switch tenant
 *      lewat tenant switcher UI (Phase 3).
 *   2. auth()->user()->tenant_id — user staff atau customer.
 *   3. null — guest (login page, etc.) atau artisan command.
 *
 * Bypass:
 *   - Superadmin: scope tetep bypassed kecuali mereka eksplisit pilih
 *     tenant tertentu dari switcher. Default mereka liat semua data.
 *   - withoutScope(): caller bisa skip tenant filter sementara, e.g.
 *     pas seeding atau cross-tenant report.
 */
class TenantContext
{
    protected ?int $tenantId = null;
    protected bool $isSuperAdminGlobal = false;
    protected bool $bypassed = false;

    public function setTenantId(?int $id): void
    {
        $this->tenantId = $id;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setSuperAdminGlobal(bool $flag): void
    {
        $this->isSuperAdminGlobal = $flag;
    }

    /**
     * True kalau current user adalah superadmin TANPA active tenant override
     * (alias: liat semua tenant). Scope harus di-skip.
     */
    public function isSuperAdminGlobal(): bool
    {
        return $this->isSuperAdminGlobal;
    }

    public function bypass(bool $flag = true): void
    {
        $this->bypassed = $flag;
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Should the global scope filter queries? Returns true only when we
     * have a concrete tenant context to filter by.
     */
    public function shouldScope(): bool
    {
        if ($this->bypassed) return false;
        if ($this->isSuperAdminGlobal) return false;
        return $this->tenantId !== null;
    }

    /** Run a callback with tenant scope disabled, then restore. */
    public function withoutScope(callable $cb): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;
        try {
            return $cb();
        } finally {
            $this->bypassed = $previous;
        }
    }

    /** Cache tenant code lookup per request (to avoid n+1 select). */
    protected ?string $tenantCodeCache = null;
    protected bool $tenantCodeLoaded = false;

    /**
     * Resolve tenant code (e.g. "ahnet", "padi") for current active tenant.
     * Returns null kalau gak ada tenant aktif (superadmin global).
     */
    public function tenantCode(): ?string
    {
        if ($this->tenantCodeLoaded) return $this->tenantCodeCache;
        $this->tenantCodeLoaded = true;
        if (!$this->tenantId) return $this->tenantCodeCache = null;
        try {
            $this->tenantCodeCache = \DB::table('tenants')->where('id', $this->tenantId)->value('code');
        } catch (\Throwable $e) {
            $this->tenantCodeCache = null;
        }
        return $this->tenantCodeCache;
    }

    /**
     * Prefix yang dipakai untuk username RADIUS, voucher code, dll —
     * supaya isolasi antar-tenant ke-enforce di level data (RADIUS server
     * shared antar-tenant, jadi username harus unique global).
     *
     * Tenant aktif → "<tenant_code>_" (e.g. "padi_").
     * Superadmin global / no tenant → fallback config default.
     */
    public function radiusPrefix(): string
    {
        $code = $this->tenantCode();
        if ($code) return $code . '_';
        return (string) config('ahnet.radius.pppoe_username_prefix', 'ahnet_');
    }
}
