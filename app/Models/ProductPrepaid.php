<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProductPrepaid extends Model
{
    use HasFactory;

    protected $table = 'product_prepaid';
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_name',
        'product_desc',
        'product_category',
        'product_provider',
        'product_type',
        'product_seller',
        'product_seller_price',
        'product_buyer_price',
        'product_sku',
        'product_unlimited_stock',
        'product_stock',
        'product_multi',
    ];

    /**
     * Boot the model and attach event listeners
     */
    protected static function booted()
    {
        // Clear cache when a product is created, updated, or deleted
        static::saved(function () {
            self::clearRelatedCache();
        });

        static::deleted(function () {
            self::clearRelatedCache();
        });
    }

    /**
     * Clear cache related to products
     */
    public static function clearRelatedCache()
    {
        // Clear all product-related cache
        Cache::forget('products_emoney');
        Cache::forget('products_games');
        Cache::forget('products_voucher');
        Cache::forget('products_pln');
        Cache::forget('products_tv');
        Cache::forget('products_masa_aktif');
        Cache::forget('product_categories');

        // Note: We don't clear provider-specific caches here as they're more granular
        // Those would need to be cleared individually when needed
    }

    public function scopeFindProductBySKU($query, $value)
{
    return $query->where('product_sku', $value);
}

public function scopeFindProductByProvider($query, $provider, $category = null)
{
    $query->where('product_provider', $provider);

    if (!is_null($category)) {
        $query->where('product_category', $category);
    }

    return $query;
}


    public function insert_data($data)
    {
        $insertData = [];
        foreach ($data as $result) {
            $insertData[] = [
                'product_sku' => $result['buyer_sku_code'],
                'product_name' => $result['product_name'],
                'product_desc' => $result['desc'],
                'product_category' => $result['category'],
                'product_provider' => $result['brand'],
                'product_type' =>  $result['type'],
                'product_seller' => $result['seller_name'],
                'product_seller_price' => $result['price'],
                'product_buyer_price' => $result['price'],
                'product_unlimited_stock' => $result['unlimited_stock'] ? 'Ya' : 'Tidak',
                'product_stock' => $result['stock'],
                'product_multi' => $result['multi'] ? 'Ya' : 'Tidak',
            ];
        }

        self::upsert($insertData, ['product_sku'], [
            'product_name',
            'product_desc',
            'product_category',
            'product_provider',
            'product_type',
            'product_seller',
            'product_seller_price',
            'product_buyer_price',
            'product_unlimited_stock',
            'product_stock',
            'product_multi'
        ]);
    }
}
