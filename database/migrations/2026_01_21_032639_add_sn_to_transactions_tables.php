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
        // Add transaction_sn column to transaction table
        Schema::table('transaction', function (Blueprint $table) {
            $table->string('transaction_sn')->nullable()->after('transaction_message');
        });

        // Add sn column to pasca_transactions table
        Schema::table('pasca_transactions', function (Blueprint $table) {
            $table->string('sn')->nullable()->after('message_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn('transaction_sn');
        });

        Schema::table('pasca_transactions', function (Blueprint $table) {
            $table->dropColumn('sn');
        });
    }
};
