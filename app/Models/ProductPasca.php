<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPasca extends Model
{
    use HasFactory;

    protected $table = 'product_pasca';
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_name',
        'product_category',
        'product_provider',
        'product_seller',
        'product_transaction_admin',
        'product_transaction_fee',
        'product_sku',
    ];



    public function scopeFindBySKU($query, $value)
    {
        $query->where('product_sku', $value);
    }

    public function insert_data($data)
{
    // kalau bukan array, gagal
    if (!is_array($data)) return false;

    $insertData = [];

    foreach ($data as $result) {

        // INI KUNCI: skip kalau string / bukan array
        if (!is_array($result)) {
            continue;
        }

        // key wajib (biar gak undefined index)
        if (!isset($result['buyer_sku_code'])) {
            continue;
        }

        $insertData[] = [
            'product_sku' => $result['buyer_sku_code'],
            'product_name' => $result['product_name'] ?? null,
            'product_category' => $result['category'] ?? null,
            'product_provider' => $result['brand'] ?? null,
            'product_seller' => $result['seller_name'] ?? null,
            'product_transaction_admin' => $result['admin'] ?? 0,
            'product_transaction_fee' => $result['commission'] ?? 0,
        ];
    }

    // kalau tidak ada data valid, anggap gagal
    if (count($insertData) === 0) return false;

    try {
        self::upsert($insertData, ['product_sku'], [
            'product_name',
            'product_category',
            'product_provider',
            'product_seller',
            'product_transaction_admin',
            'product_transaction_fee'
        ]);
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

}
