<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use App\Models\PaymentRequest;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'username',
        'merchant_id',
        'email',
        'webhook',
        'ip',
        'password',
        'api_key',
        'saldo'
    ];

    protected $hidden = [
        'password',
        'api_key',
    ];

    protected $casts = [
        'ip' => 'array', // Cast IP field to array if storing multiple IPs as JSON
        'saldo' => 'decimal:2', // Cast saldo to decimal with 2 decimal places
    ];

    /**
     * Hash the password when setting it
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Get the total amount of pending settlements for this merchant
     */
    public function getTotalPendingSettlementsAttribute()
    {
        return PaymentRequest::where(function($query) {
                $query->whereRaw('JSON_EXTRACT(metadata, "$.merchant_id") = ?', [$this->id])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('email', $this->email)
                                   ->where(function($nestedQuery) {
                                       $nestedQuery->whereNull('metadata')
                                                   ->orWhereRaw('JSON_EXTRACT(metadata, "$.merchant_id") IS NULL');
                                   });
                      });
            })
            ->where('settlement_status', 'pending_settlement')
            ->where('status', 'success')
            ->sum('price');
    }

    /**
     * Get the available balance (current saldo minus pending settlements)
     */
    public function getAvailableBalanceAttribute()
    {
        return $this->saldo - $this->total_pending_settlements;
    }
}