<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotVoucher extends Model
{
    use HasFactory;

    protected $table = 'hotspot_vouchers';

    protected $fillable = [
        'code', 'batch_id', 'profile', 'rate_limit', 'valid_minutes',
        'status', 'used_at', 'expired_at', 'created_by',
    ];

    protected $casts = [
        'used_at'    => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
