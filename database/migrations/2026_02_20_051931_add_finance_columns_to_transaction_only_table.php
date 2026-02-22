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
        Schema::table('transaction', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction', 'transaction_cost')) {
                $table->decimal('transaction_cost', 15, 2)->default(0)->after('transaction_total');
            }
            if (!Schema::hasColumn('transaction', 'transaction_profit')) {
                $table->decimal('transaction_profit', 15, 2)->default(0)->after('transaction_cost');
            }
            if (!Schema::hasColumn('transaction', 'points_awarded')) {
                $table->boolean('points_awarded')->default(false)->after('transaction_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn(['transaction_cost', 'transaction_profit', 'points_awarded']);
        });
    }
};
