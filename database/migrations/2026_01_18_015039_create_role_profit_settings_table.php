<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('price_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('role_id');
        $table->integer('min_price'); // Price threshold
        $table->integer('markup');    // Final markup for this role and threshold
        $table->timestamps();

        $table->foreign('role_id')
              ->references('id')
              ->on('roles')
              ->onDelete('cascade');
    });

    // ⬇ Default Pricing Settings (9 combinations: 3 Roles x 3 Tiers)
    $tiers = [
        ['min' => 0,       'markups' => [1 => 500,  2 => 200,  3 => 0]],
        ['min' => 700000,  'markups' => [1 => 1000, 2 => 700,  3 => 500]],
        ['min' => 1500000, 'markups' => [1 => 2000, 2 => 1700, 3 => 1500]],
    ];

    foreach ($tiers as $tier) {
        foreach ($tier['markups'] as $roleId => $markup) {
            DB::table('price_settings')->insert([
                'role_id'    => $roleId,
                'min_price'  => $tier['min'],
                'markup'     => $markup,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_profit_settings');
    }
};
