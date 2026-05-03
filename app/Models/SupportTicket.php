<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN              = 'open';
    public const STATUS_IN_PROGRESS       = 'in_progress';
    public const STATUS_PENDING_CUSTOMER  = 'pending_customer';
    public const STATUS_RESOLVED          = 'resolved';
    public const STATUS_CLOSED            = 'closed';

    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_number', 'customer_profile_id', 'subject', 'category', 'priority',
        'status', 'assigned_to', 'created_by', 'description', 'contact_phone',
        'resolution', 'first_response_at', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at'       => 'datetime',
        'closed_at'         => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id')->orderBy('created_at');
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN             => 'Open',
            self::STATUS_IN_PROGRESS      => 'In Progress',
            self::STATUS_PENDING_CUSTOMER => 'Menunggu Pelanggan',
            self::STATUS_RESOLVED         => 'Resolved',
            self::STATUS_CLOSED           => 'Closed',
            default                        => ucfirst($this->status),
        };
    }
}
