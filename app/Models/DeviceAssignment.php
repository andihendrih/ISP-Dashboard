<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAssignment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'device_assignments';

    protected $fillable = [
        'device_id', 'customer_id', 'technician_id', 'action', 'acted_at', 'notes',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'install'    => 'Pasang ke Pelanggan',
            'return'     => 'Tarik / Kembali',
            'repair'     => 'Perbaikan',
            'swap'       => 'Swap / Tukar Unit',
            'retire'     => 'Pensiun',
            'mark_stock' => 'Kembali ke Stock',
            'mark_lost'  => 'Tandai Hilang',
            default      => ucfirst($this->action),
        };
    }
}
