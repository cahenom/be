<?php

namespace App\Services;

use App\Models\PriceSetting;
use Illuminate\Support\Facades\Cache;

class PricingService
{
    public function applyMarkup($basePrice, int $roleId): float
    {
        $basePrice = floatval($basePrice ?? 0);

        // Use Laravel's array cache to store settings for the duration of the request
        // This is safe for testing as the array cache is cleared between tests
        $cacheKey = "price_settings_{$roleId}";
        $settings = cache()->driver('array')->remember($cacheKey, 60, function () use ($roleId) {
            return PriceSetting::where('role_id', $roleId)
                ->orderBy('min_price', 'desc')
                ->get();
        });

        // Find the markup in-memory
        $markup = 0;
        foreach ($settings as $setting) {
            if ($basePrice >= $setting->min_price) {
                $markup = $setting->markup;
                break;
            }
        }

        $sellingPrice = round($basePrice + $markup);

        return $sellingPrice;
    }
}
