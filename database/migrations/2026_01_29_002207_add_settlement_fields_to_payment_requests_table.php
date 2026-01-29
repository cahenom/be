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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable(); // When the payment was settled to merchant
            $table->enum('settlement_status', ['pending_settlement', 'settled', 'cancelled'])->default('pending_settlement'); // Settlement status
            $table->timestamp('settlement_due_date')->nullable(); // Date when settlement is due (3 days after approval)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['settled_at', 'settlement_status', 'settlement_due_date']);
        });
    }
};
