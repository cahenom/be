<?php

namespace App\Services;

use App\Models\RoleProfitSetting;

class PricingService
{
   public function applyMarkup($basePrice, int $roleId): float
{
    $basePrice = floatval($basePrice ?? 0);

    $profit = RoleProfitSetting::where('role_id', $roleId)->first()
        ?? RoleProfitSetting::where('is_default', true)->first();

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
