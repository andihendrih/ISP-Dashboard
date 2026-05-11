<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantInvoice extends Model
{
    public const STATUS_UNPAID    = 'unpaid';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number', 'tenant_id', 'subscription_id', 'plan_id',
        'period_start', 'period_end', 'prorate_factor', 'amount',
        'status', 'due_date', 'paid_at', 'notes',
    ];

    protected $casts = [
        'period_start'   => 'date',
        'period_end'     => 'date',
        'due_date'       => 'date',
        'paid_at'        => 'datetime',
        'prorate_factor' => 'decimal:4',
        'amount'         => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'invoice_id');
    }

    /** Total payment yang udah confirmed. */
    public function paidAmount(): int
    {
        return (int) $this->payments()->where('status', TenantPayment::STATUS_CONFIRMED)->sum('amount');
    }

    public function remainingAmount(): int
    {
        return max(0, (int) $this->amount - $this->paidAmount());
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmount() >= (int) $this->amount;
    }
}
