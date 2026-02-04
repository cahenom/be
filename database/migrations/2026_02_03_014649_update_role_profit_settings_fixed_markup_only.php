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
        // Update role profit settings to use fixed markup values (no percentage)
        // This will ensure consistent markup regardless of product price

        // For role_id 1 (user): Fixed markup of 500 with max 5000
        DB::table('role_profit_settings')
            ->where('role_id', 1)
            ->update([
                'markup_percent' => 0, // Disable percentage-based markup
                'markup_min' => 500,   // Fixed markup of 500
                'markup_max' => 5000,  // Maximum markup of 5000
            ]);

        // For role_id 2 (reseller): Fixed markup of 300 with max 3000
        DB::table('role_profit_settings')
            ->where('role_id', 2)
            ->update([
                'markup_percent' => 0, // Disable percentage-based markup
                'markup_min' => 300,   // Fixed markup of 300
                'markup_max' => 3000,  // Maximum markup of 3000
            ]);

        // For role_id 3 (agen): Fixed markup of 200 with max 2000
        DB::table('role_profit_settings')
            ->where('role_id', 3)
            ->update([
                'markup_percent' => 0, // Disable percentage-based markup
                'markup_min' => 200,   // Fixed markup of 200
                'markup_max' => 2000,  // Maximum markup of 2000
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous values (percentage-based markup)
        DB::table('role_profit_settings')
            ->where('role_id', 1)
            ->update([
                'markup_percent' => 5,  // 5% markup
                'markup_min' => 100,    // Minimum markup of 100
                'markup_max' => 5000,   // Maximum markup of 5000
            ]);

        DB::table('role_profit_settings')
            ->where('role_id', 2)
            ->update([
                'markup_percent' => 3,  // 3% markup
                'markup_min' => 50,     // Minimum markup of 50
                'markup_max' => 3000,   // Maximum markup of 3000
            ]);

        DB::table('role_profit_settings')
            ->where('role_id', 3)
            ->update([
                'markup_percent' => 2,  // 2% markup
                'markup_min' => 25,     // Minimum markup of 25
                'markup_max' => 2000,   // Maximum markup of 2000
            ]);
    }
};
