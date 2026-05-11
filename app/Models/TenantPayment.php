<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED  = 'rejected';

    public const METHOD_BANK    = 'bank_transfer';
    public const METHOD_VA      = 'va';
    public const METHOD_EWALLET = 'ewallet';
    public const METHOD_CASH    = 'cash';
    public const METHOD_OTHER   = 'other';

    protected $fillable = [
        'invoice_id', 'tenant_id', 'amount', 'method', 'reference',
        'transferred_at', 'proof_path', 'status', 'confirmed_by',
        'confirmed_at', 'note',
    ];

    protected $casts = [
        'amount'         => 'integer',
        'transferred_at' => 'date',
        'confirmed_at'   => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantInvoice::class, 'invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
