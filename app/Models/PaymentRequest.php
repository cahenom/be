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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'expires_at' => 'datetime',
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
     * Get the user that owns the payment request.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}