<?php

namespace App\Services;

use App\Models\RoleProfitSetting;
use Illuminate\Support\Facades\Cache;

class PricingService
{
   public function applyMarkup($basePrice, int $roleId): float
{
    $basePrice = floatval($basePrice ?? 0);

    // Cache role profit setting for 1 hour
    $cacheKey = "role_profit_setting_{$roleId}";
    $profit = Cache::remember($cacheKey, 3600, function () use ($roleId) {
        return RoleProfitSetting::where('role_id', $roleId)->first()
            ?? RoleProfitSetting::where('is_default', true)->first();
    });

    if (!$profit) {
        \Log::warning("MARKUP: No profit setting found for role_id = {$roleId}");
        return $basePrice;
    }

    // Tiered markup based on product price
    if ($basePrice < 100000) {
        $markup = 200;  // For products under 100,000
    } elseif ($basePrice >= 100000 && $basePrice < 500000) {
        $markup = 500;  // For products between 100,000 and 499,999
    } else {
        $markup = 1000; // For products 500,000 and above
    }

    // Apply maximum constraint if set (acts as overall cap)
    if ($profit->markup_max > 0 && $markup > $profit->markup_max) {
        $markup = $profit->markup_max;
    }

    $sellingPrice = round($basePrice + $markup);

    // 🔥 LOG UNTUK DEV MODE
   // \Log::info("MARKUP DEBUG", [
   //     "role_id"         => $roleId,
   //     "base_price"      => $basePrice,
   //     "markup_added"    => $markup,
   //     "markup_tier"     => $basePrice < 100000 ? 'under_100k' : ($basePrice < 500000 ? '100k_to_500k' : 'above_500k'),
   //     "selling_price"   => $sellingPrice
   // ]);

    return $sellingPrice;
}

}
