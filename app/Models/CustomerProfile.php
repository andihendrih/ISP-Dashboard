<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'customer_code', 'full_name', 'email', 'phone', 'id_card_number',
        'address', 'latitude', 'longitude', 'package', 'service_plan_id', 'billing_enabled',
        'rate_limit', 'status', 'service_type', 'radius_username', 'radius_password', 'mikrotik_device_id',
        'joined_at', 'expired_at', 'notes',
    ];

    protected $casts = [
        'joined_at'       => 'date',
        'expired_at'      => 'date',
        'latitude'        => 'float',
        'longitude'       => 'float',
        'billing_enabled' => 'bool',
    ];

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(DeviceMikrotik::class, 'mikrotik_device_id');
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_profile_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'customer_profile_id');
    }
}
