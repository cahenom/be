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
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // ID from merchant system
            $table->string('name'); // Name of the payment/service
            $table->string('destination'); // Destination or recipient
            $table->decimal('price', 15, 2); // Price amount
            $table->string('email'); // Email of the user to notify
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Link to user if found
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed', 'failed', 'success'])->default('pending'); // Updated enum consolidated from update_status_enum
            $table->timestamp('expires_at')->nullable(); // When the request expires
            $table->timestamp('settled_at')->nullable(); // Consolidated from add_settlement_fields
            $table->enum('settlement_status', ['pending_settlement', 'settled', 'cancelled'])->default('pending_settlement'); // Consolidated from add_settlement_fields
            $table->timestamp('settlement_due_date')->nullable(); // Consolidated from add_settlement_fields
            $table->text('metadata')->nullable(); // Additional data as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
