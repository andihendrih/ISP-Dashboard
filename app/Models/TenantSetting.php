<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Settings per-tenant: brand, WA, email SMTP, payment gateway.
 *
 * Credential field di-encrypt via Laravel's encrypted cast.
 */
class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        // Brand
        'brand_name', 'brand_logo_path', 'brand_address', 'brand_phone',
        'brand_email', 'brand_npwp', 'brand_bank_info',
        // WhatsApp
        'wa_provider', 'wa_credentials',
        // Email
        'email_provider', 'email_credentials',
        // Payment gateway
        'payment_gateway_default', 'payment_credentials',
        'payment_midtrans_enabled', 'payment_xendit_enabled',
        'payment_tripay_enabled', 'payment_manual_enabled',
    ];

    protected $casts = [
        'wa_credentials'           => 'encrypted:array',
        'email_credentials'        => 'encrypted:array',
        'payment_credentials'      => 'encrypted:array',
        'payment_midtrans_enabled' => 'boolean',
        'payment_xendit_enabled'   => 'boolean',
        'payment_tripay_enabled'   => 'boolean',
        'payment_manual_enabled'   => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Resolve setting untuk tenant aktif; otomatis create row default kalau belum ada. */
    public static function forTenant(int $tenantId): self
    {
        return static::firstOrCreate(['tenant_id' => $tenantId]);
    }

    /** Helper: WA creds JSON terdecrypt, fallback ke [] */
    public function waCreds(): array { return is_array($this->wa_credentials) ? $this->wa_credentials : []; }

    /** Helper: email creds JSON */
    public function emailCreds(): array { return is_array($this->email_credentials) ? $this->email_credentials : []; }

    /** Helper: payment creds JSON, struktur per-gateway */
    public function paymentCreds(): array { return is_array($this->payment_credentials) ? $this->payment_credentials : []; }

    /** Brand name fallback ke tenant name kalau kosong */
    public function effectiveBrandName(): string
    {
        return $this->brand_name ?: ($this->tenant?->name ?? '—');
    }
}
