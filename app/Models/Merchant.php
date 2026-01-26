<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

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
}