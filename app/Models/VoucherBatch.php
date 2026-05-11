<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'label', 'profile', 'count', 'prefix', 'code_length',
        'expires_at', 'codes', 'created_by',
    ];

    protected $casts = [
        'codes'      => 'array',
        'expires_at' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
