<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceMikrotik extends Model
{
    use HasFactory;

    protected $table = 'devices_mikrotik';

    protected $fillable = [
        'name', 'host', 'api_port', 'username', 'password', 'use_ssl',
        'snmp_community', 'identity', 'board_name', 'version',
        'is_active', 'last_seen_at', 'notes',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'use_ssl'      => 'boolean',
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function snmpLogs(): HasMany
    {
        return $this->hasMany(SnmpLog::class, 'device_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'mikrotik_device_id');
    }
}
