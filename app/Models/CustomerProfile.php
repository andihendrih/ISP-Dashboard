<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'customer_code', 'full_name', 'email', 'phone', 'id_card_number',
        'address', 'latitude', 'longitude', 'package', 'rate_limit',
        'status', 'service_type', 'radius_username', 'mikrotik_device_id',
        'joined_at', 'expired_at', 'notes',
    ];

    protected $casts = [
        'joined_at'  => 'date',
        'expired_at' => 'date',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(DeviceMikrotik::class, 'mikrotik_device_id');
    }
}
