<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'destination',
        'price',
        'email',
        'user_id',
        'status',
        'expires_at',
        'metadata',
        'settled_at',
        'settlement_status',
        'settlement_due_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
        'settled_at' => 'datetime',
        'settlement_due_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the possible statuses for the payment request
     */
    public static function getStatusOptions()
    {
        return [
            'pending',
            'success',      // previously 'approved' or 'completed'
            'failed',       // previously 'rejected' or 'failed_notification'
            'cancelled',
            'pending_with_notification_error'
        ];
    }

    /**
     * Get the possible settlement statuses
     */
    public static function getSettlementStatusOptions()
    {
        return [
            'pending_settlement',  // Waiting for 3-day settlement period
            'settled',             // Funds have been settled to merchant
            'cancelled'            // Settlement was cancelled
        ];
    }

    /**
     * Get the user that owns the payment request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to include user relation for better performance
     */
    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    /**
     * Scope to get pending payment requests with user relation
     */
    public function scopePendingWithUser($query)
    {
        return $query->where('status', 'pending')->with('user');
    }

    /**
     * Check if the payment request is eligible for settlement
     */
    public function isEligibleForSettlement()
    {
        return $this->settlement_status === 'pending_settlement' &&
               $this->status === 'success' &&
               $this->settlement_due_date &&
               $this->settlement_due_date->lte(now());
    }
}