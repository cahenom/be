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

    // Hitung markup
    $markupPercent = $profit->markup_percent;
    $markup = $basePrice * ($markupPercent / 100);
    $sellingPrice = round($basePrice + $markup);

    // 🔥 LOG UNTUK DEV MODE
   // \Log::info("MARKUP DEBUG", [
   //     "role_id"         => $roleId,
   //     "markup_percent"  => $markupPercent,
   //     "base_price"      => $basePrice,
  //      "markup_added"    => $markup,
   //     "selling_price"   => $sellingPrice
    //]);

    return $sellingPrice;
}

}
