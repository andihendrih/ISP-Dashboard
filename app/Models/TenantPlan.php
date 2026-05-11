<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantPlan extends Model
{
    protected $fillable = [
        'code', 'name', 'price', 'max_customers', 'max_devices',
        'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'         => 'integer',
        'max_customers' => 'integer',
        'max_devices'   => 'integer',
        'features'      => 'array',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }
}
