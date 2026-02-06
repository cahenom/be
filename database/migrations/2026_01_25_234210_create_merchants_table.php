<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('merchant_id')->unique(); // Using merchant_id to avoid confusion with table id
            $table->string('email')->unique();
            $table->text('webhook')->nullable();
            $table->text('ip')->nullable(); // Store IP addresses as text (can be comma-separated)
            $table->string('password'); // Hashed password
            $table->string('api_key')->unique(); // Unique API key for authentication
            $table->decimal('saldo', 15, 2)->default(0); // Consolidated from add_saldo_to_merchants_table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
