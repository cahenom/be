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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ref_id')->unique(); // Reference ID from Digiflazz
            $table->string('customer_number')->nullable(); // Customer number
            $table->string('product_name')->nullable(); // Name of the product
            $table->string('product_code')->nullable(); // Product code
            $table->string('status'); // Transaction status
            $table->decimal('amount', 15, 2)->nullable(); // Amount of the transaction
            $table->decimal('price', 15, 2)->nullable(); // Price of the transaction
            $table->timestamp('response_time')->nullable(); // Time of response
            $table->text('note')->nullable(); // Additional notes
            $table->timestamps();

            // Index for faster queries
            $table->index('ref_id');
            $table->index('customer_number');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
