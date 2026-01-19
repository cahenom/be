<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TransactionModel extends Model
{
    use HasFactory;

    protected $table = 'transaction';
    protected $primaryKey = 'id';
    protected $fillable = [
        'transaction_code',
        'transaction_date',
        'transaction_time',
        'transaction_type',
        'transaction_provider',
        'transaction_number',
        'transaction_sku',
        'transaction_total',
        'transaction_message',
        'transaction_status',
        'transaction_user_id',
    ];


    public function insert_transaction_data($data, $type, $provider, $user_id, $harga_jual)
{
    return self::create([
        'transaction_code'     => $data['ref_id'],
        'transaction_date'     => now()->format('Y-m-d'),
        'transaction_time'     => now()->format('H:i:s'),
        'transaction_type'     => $type,
        'transaction_provider' => $provider,
        'transaction_number'   => $data['customer_no'],
        'transaction_sku'      => $data['buyer_sku_code'],
        'transaction_total'    => $harga_jual,    // <===== HARGA JUAL, BUKAN DARI DIGIFLAZZ
        'transaction_message'  => $data['message'],
        'transaction_status'   => $data['status'],
        'transaction_user_id'  => $user_id
    ]);
}

}
