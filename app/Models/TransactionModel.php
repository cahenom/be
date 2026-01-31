<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
        'transaction_sn',
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
            'transaction_sn'       => $data['sn'] ?? null,  // Add SN field
            'transaction_user_id'  => $user_id
        ]);
    }

    /**
     * Insert merchant payment transaction
     */
    public static function insert_merchant_payment_transaction($external_id, $name, $email, $price, $destination, $status, $user_id)
    {
        // Sanitize the price value to ensure it fits in integer column
        $sanitizedPrice = max(0, min(2147483647, (int)round(floatval($price))));

        // Determine the transaction type and SKU based on status
        $isSuccess = in_array($status, ['completed', 'approved', 'success']);
        $transactionType = $isSuccess ? 'merchant_payment' : 'merchant_payment_failed';
        $transactionSKU = $isSuccess ? 'MERCHANT_PAYMENT' : 'MERCHANT_PAYMENT_FAILED';
        $messagePrefix = $isSuccess ? 'Payment for ' : 'Failed payment for ';

        // Create transaction with only mandatory fields first
        $transaction = new self();
        $transaction->transaction_code = $external_id;
        $transaction->transaction_date = now()->format('Y-m-d');
        $transaction->transaction_time = now()->format('H:i:s');
        $transaction->transaction_type = $transactionType;
        $transaction->transaction_provider = $name;
        $transaction->transaction_number = $email;
        $transaction->transaction_sku = $transactionSKU;
        $transaction->transaction_total = $sanitizedPrice;
        $transaction->transaction_message = $messagePrefix . $destination;
        $transaction->transaction_status = $status;
        $transaction->transaction_user_id = $user_id;

        // Only set transaction_sn if the column exists
        if (Schema::hasColumn('transaction', 'transaction_sn')) {
            $transaction->transaction_sn = null;
        }

        $transaction->save();

        return $transaction;
    }

    /**
     * Format transaction data for API response to match expected structure
     */
    public function formatForApiResponse()
    {
        return [
            'ref' => $this->transaction_code,
            'tujuan' => $this->transaction_number,
            'sku' => $this->transaction_sku,
            'status' => $this->transaction_status,
            'message' => $this->transaction_message,
            'price' => $this->transaction_total,
            'sn' => $this->transaction_sn,
            'type' => strpos($this->transaction_type, 'merchant') !== false ? 'merchant' : 'prepaid',
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'transaction_user_id');
    }

    /**
     * Get the product information for the transaction.
     */
    public function product()
    {
        return $this->belongsTo(ProductPrepaid::class, 'transaction_sku', 'product_sku');
    }

    /**
     * Scope to include user relation for better performance
     */
    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    /**
     * Check for recent transactions with same customer number and SKU within last 10 minutes
     * to prevent double spending
     */
    public function checkRecentTransaction($customerNumber, $sku, $userId, $minutes = 10)
    {
        $tenMinutesAgo = Carbon::now()->subMinutes($minutes);

        return self::where('transaction_number', $customerNumber)
                    ->where('transaction_sku', $sku)
                    ->where('transaction_user_id', $userId)
                    ->where('created_at', '>', $tenMinutesAgo)
                    ->whereIn('transaction_status', ['Pending', 'Sukses', 'Proses']) // Only check active transactions
                    ->exists();
    }
}