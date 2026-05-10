<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePlan extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'service_plans';

    protected $fillable = [
        'code', 'name', 'service_type', 'rate_limit', 'radius_group',
        'price', 'tax_percent', 'description', 'is_active',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'is_active'   => 'bool',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerProfile::class, 'service_plan_id');
    }
}
