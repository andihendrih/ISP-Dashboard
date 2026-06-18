<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_BELUM_LUNAS = 'belum_lunas';
    public const STATUS_LUNAS       = 'lunas';
    public const STATUS_TERLAMBAT   = 'terlambat';
    public const STATUS_CANCELLED   = 'cancelled';

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number', 'customer_profile_id', 'service_plan_id',
        'period_year', 'period_month', 'period_start', 'period_end',
        'is_prorated', 'days_charged', 'days_in_month',
        'base_amount', 'discount_amount', 'tax_amount', 'total_amount', 'paid_amount',
        'due_date', 'status', 'paid_at', 'cancelled_at', 'cancelled_reason', 'notes',
    ];

    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'due_date'        => 'date',
        'paid_at'         => 'datetime',
        'cancelled_at'    => 'datetime',
        'is_prorated'     => 'bool',
        'base_amount'     => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function getOutstandingAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_LUNAS       => 'Lunas',
            self::STATUS_TERLAMBAT   => 'Terlambat',
            self::STATUS_CANCELLED   => 'Cancelled',
            default                  => 'Belum Lunas',
        };
    }
}
