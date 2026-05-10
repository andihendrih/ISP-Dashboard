<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'devices_inventory';

    protected $fillable = [
        'type', 'brand', 'model', 'serial_number', 'mac_address',
        'status', 'purchase_price', 'purchased_at', 'warehouse_location',
        'customer_id', 'notes',
    ];

    protected $casts = [
        'purchased_at'   => 'date',
        'purchase_price' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class)->orderByDesc('acted_at');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'onu'    => 'ONU',
            'router' => 'Router',
            'switch' => 'Switch',
            'ap'     => 'Access Point',
            'radio'  => 'Radio / PtP',
            'cable'  => 'Cable',
            default  => 'Lainnya',
        };
    }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'stock'    => ['label' => 'Stock',     'class' => 'bg-sky-100 text-sky-800 border-sky-200'],
            'assigned' => ['label' => 'Terpasang', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
            'rusak'    => ['label' => 'Rusak',     'class' => 'bg-rose-100 text-rose-800 border-rose-200'],
            'hilang'   => ['label' => 'Hilang',    'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
            'retired'  => ['label' => 'Pensiun',   'class' => 'bg-ink/10 text-ink/60 border-ink/15'],
            default    => ['label' => ucfirst($this->status), 'class' => 'bg-ink/10 text-ink/60 border-ink/15'],
        };
    }
}
