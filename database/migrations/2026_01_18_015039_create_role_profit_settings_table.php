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
    Schema::create('role_profit_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('role_id'); 
        $table->decimal('markup_percent', 5, 2)->default(0);
        $table->decimal('markup_min', 10, 2)->default(0);
        $table->decimal('markup_max', 10, 2)->default(0);
        $table->boolean('is_default')->default(false);
        $table->timestamps();

        $table->foreign('role_id')
              ->references('id')
              ->on('roles')
              ->onDelete('cascade');
    });

    // ⬇ Tambahkan default data DI SINI
    DB::table('role_profit_settings')->insert([
        [
            'role_id'        => 1,  // user
            'markup_percent' => 5,
            'markup_min'     => 0,
            'markup_max'     => 0,
            'is_default'     => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ],
        [
            'role_id'        => 2, // reseller
            'markup_percent' => 3,
            'markup_min'     => 0,
            'markup_max'     => 0,
            'is_default'     => false,
            'created_at'     => now(),
            'updated_at'     => now(),
        ],
        [
            'role_id'        => 3, // agen
            'markup_percent' => 2,
            'markup_min'     => 0,
            'markup_max'     => 0,
            'is_default'     => false,
            'created_at'     => now(),
            'updated_at'     => now(),
        ],
    ]);
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_profit_settings');
    }
};
