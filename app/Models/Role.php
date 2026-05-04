<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const SUPERADMIN = 'superadmin';
    public const ADMIN      = 'admin';
    public const NOC        = 'noc';
    public const FINANCE    = 'finance';
    public const TEKNISI    = 'teknisi';
    public const CUSTOMER   = 'customer';

    /** Roles that can be assigned to staff users via the Pengaturan UI. */
    public const STAFF_ROLES = [
        self::SUPERADMIN,
        self::ADMIN,
        self::NOC,
        self::FINANCE,
        self::TEKNISI,
    ];

    protected $fillable = ['name', 'label', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
