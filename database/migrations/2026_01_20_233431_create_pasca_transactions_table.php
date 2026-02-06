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
        Schema::create('pasca_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('sku_code', 50);
            $table->string('customer_no', 50);

            $table->enum('status_inquiry', ['pending', 'success', 'failed'])->default('pending');
            $table->enum('status_payment', ['none', 'pending', 'success', 'failed'])->default('none');

            $table->string('customer_name', 100)->nullable();

            $table->integer('total_periode')->default(1);
            $table->integer('amount_bill')->default(0);
            $table->integer('amount_admin')->default(0);
            $table->integer('amount_denda')->default(0);
            $table->integer('amount_total')->default(0);

            $table->string('periode', 150)->nullable(); // contoh: "202411,202412"
            $table->string('daya', 50)->nullable();
            $table->string('gol_tarif', 50)->nullable();

            $table->string('message_inquiry', 255)->nullable();
            $table->string('message_payment', 255)->nullable();
            $table->string('sn')->nullable()->after('message_payment'); // Consolidated from add_sn_to_transactions_tables

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasca_transactions');
    }
};
