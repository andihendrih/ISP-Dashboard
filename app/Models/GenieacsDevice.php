<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenieacsDevice extends Model
{
    use HasFactory;

    protected $table = 'genieacs_devices';

    protected $fillable = [
        'device_id', 'serial_number', 'manufacturer', 'product_class',
        'model_name', 'software_version', 'hardware_version',
        'ssid', 'ip', 'tag', 'status', 'last_inform_at', 'raw',
    ];

    protected $casts = [
        'last_inform_at' => 'datetime',
        'raw'            => 'array',
    ];
}
