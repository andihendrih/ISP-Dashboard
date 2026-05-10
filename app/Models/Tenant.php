<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    /** Default tenant code untuk platform owner sendiri (AHNet). */
    public const DEFAULT_CODE = 'ahnet';

    /** Plan tiers. */
    public const PLAN_BASIC      = 'basic';
    public const PLAN_PRO        = 'pro';
    public const PLAN_ENTERPRISE = 'enterprise';

    public const PLANS = [
        self::PLAN_BASIC,
        self::PLAN_PRO,
        self::PLAN_ENTERPRISE,
    ];

    protected $fillable = [
        'code',
        'slug',
        'name',
        'plan',
        'max_customers',
        'is_active',
        'brand_company',
        'brand_phone',
        'brand_email',
        'brand_address',
        'brand_logo_path',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'max_customers' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CustomerProfile::class);
    }

    public function isAtCustomerLimit(): bool
    {
        if (!$this->max_customers) {
            return false;
        }
        return $this->customers()->count() >= $this->max_customers;
    }

    /**
     * Resolve default tenant (platform owner). Idempotent — return existing
     * jika ada, atau bikin baru.
     */
    public static function default(): self
    {
        return static::firstOrCreate(
            ['code' => self::DEFAULT_CODE],
            [
                'slug'      => self::DEFAULT_CODE,
                'name'      => 'AHNet (Default)',
                'plan'      => self::PLAN_ENTERPRISE,
                'is_active' => true,
            ]
        );
    }
}
