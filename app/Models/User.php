<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'roles_id',
        'saldo',
        'password',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Store FCM token for the user
     */
    public function updateFcmToken(string $token): void
    {
        $this->update(['fcm_token' => $token]);
    }

    /**
     * Get FCM token for the user
     */
    public function getFcmToken(): ?string
    {
        return $this->fcm_token;
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(TransactionModel::class, 'transaction_user_id');
    }

    /**
     * Get the postpaid transactions for the user.
     */
    public function pascaTransactions()
    {
        return $this->hasMany(\App\Models\PascaTransaction::class, 'user_id');
    }

    /**
     * Get the deposits for the user.
     */
    public function deposits()
    {
        return $this->hasMany(\App\Models\Deposit::class, 'user_id');
    }
}
