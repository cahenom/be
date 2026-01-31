<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Since MySQL doesn't support direct enum modification, we need to recreate the column
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed', 'failed', 'success') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the previous enum values
        DB::statement("ALTER TABLE payment_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending'");
    }
};
