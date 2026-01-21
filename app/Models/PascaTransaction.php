<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PascaTransaction extends Model
{
    use HasFactory;

    protected $table = 'pasca_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'ref_id',
        'user_id',
        'sku_code',
        'customer_no',
        'status_inquiry',
        'status_payment',
        'customer_name',
        'total_periode',
        'amount_bill',
        'amount_admin',
        'amount_denda',
        'amount_total',
        'periode',
        'daya',
        'gol_tarif',
        'message_inquiry',
        'message_payment',
        'sn',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeByRefId($query, $refId)
    {
        return $query->where('ref_id', $refId);
    }

    public function scopeByCustomerNo($query, $customerNo)
    {
        return $query->where('customer_no', $customerNo);
    }

    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithInquirySuccess($query)
    {
        return $query->where('status_inquiry', 'success');
    }

    public function scopeWithPaymentSuccess($query)
    {
        return $query->where('status_payment', 'success');
    }
}
