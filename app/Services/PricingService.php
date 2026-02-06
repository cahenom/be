<?php

namespace App\Services;

use App\Models\PriceSetting;
use Illuminate\Support\Facades\Cache;

class PricingService
{
    public function applyMarkup($basePrice, int $roleId): float
    {
        $basePrice = floatval($basePrice ?? 0);

        // Fetch markup from single price_settings table
        // We use the highest threshold (min_price) that is less than or equal to the basePrice
        $markup = PriceSetting::where('role_id', $roleId)
            ->where('min_price', '<=', $basePrice)
            ->orderBy('min_price', 'desc')
            ->first()?->markup ?? 0;

        $sellingPrice = round($basePrice + $markup);

        return $sellingPrice;
    }
}
