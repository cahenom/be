<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update role profit settings to use tiered markup system
        // Markup values will be set in the PricingService based on product price
        // These max values act as overall caps if needed
        
        // For role_id 1 (user): Tiered markup system, max cap of 10000
        DB::table('role_profit_settings')
            ->where('role_id', 1)
            ->update([
                'markup_percent' => 0,  // Disable percentage-based markup
                'markup_min' => 0,      // Not used in tiered system
                'markup_max' => 10000,  // Overall maximum markup cap
            ]);

        // For role_id 2 (reseller): Tiered markup system, max cap of 8000
        DB::table('role_profit_settings')
            ->where('role_id', 2)
            ->update([
                'markup_percent' => 0,  // Disable percentage-based markup
                'markup_min' => 0,      // Not used in tiered system
                'markup_max' => 8000,   // Overall maximum markup cap
            ]);

        // For role_id 3 (agen): Tiered markup system, max cap of 5000
        DB::table('role_profit_settings')
            ->where('role_id', 3)
            ->update([
                'markup_percent' => 0,  // Disable percentage-based markup
                'markup_min' => 0,      // Not used in tiered system
                'markup_max' => 5000,   // Overall maximum markup cap
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to fixed markup values
        DB::table('role_profit_settings')
            ->where('role_id', 1)
            ->update([
                'markup_percent' => 0,  // Percentage disabled
                'markup_min' => 500,    // Fixed markup of 500
                'markup_max' => 5000,   // Maximum markup of 5000
            ]);

        DB::table('role_profit_settings')
            ->where('role_id', 2)
            ->update([
                'markup_percent' => 0,  // Percentage disabled
                'markup_min' => 300,    // Fixed markup of 300
                'markup_max' => 3000,   // Maximum markup of 3000
            ]);

        DB::table('role_profit_settings')
            ->where('role_id', 3)
            ->update([
                'markup_percent' => 0,  // Percentage disabled
                'markup_min' => 200,    // Fixed markup of 200
                'markup_max' => 2000,   // Maximum markup of 2000
            ]);
    }
};