<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantSubscription extends Model
{
    public const STATUS_TRIAL     = 'trial';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAST_DUE  = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'started_at', 'next_billing_at',
        'cancelled_at', 'suspended_at', 'price_override', 'grace_days_override',
        'note',
    ];

    protected $casts = [
        'started_at'          => 'date',
        'next_billing_at'     => 'date',
        'cancelled_at'        => 'date',
        'suspended_at'        => 'date',
        'price_override'      => 'integer',
        'grace_days_override' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class, 'subscription_id');
    }

    /** Effective price: override kalau ada, kalo gak pakai plan price. */
    public function effectivePrice(): int
    {
        return (int) ($this->price_override ?? $this->plan?->price ?? 0);
    }

    /** Grace days sebelum auto-suspend (default 7, override per subscription). */
    public function graceDays(): int
    {
        return (int) ($this->grace_days_override ?? config('ahnet.tenant_billing.grace_days', 7));
    }
}
