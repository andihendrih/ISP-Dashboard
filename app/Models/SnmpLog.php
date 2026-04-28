<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnmpLog extends Model
{
    use HasFactory;

    protected $table = 'snmp_logs';

    protected $fillable = [
        'device_id', 'if_index', 'if_name',
        'in_octets', 'out_octets', 'in_bps', 'out_bps',
        'oper_status', 'polled_at',
    ];

    protected $casts = [
        'oper_status' => 'boolean',
        'polled_at'   => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(DeviceMikrotik::class, 'device_id');
    }
}
