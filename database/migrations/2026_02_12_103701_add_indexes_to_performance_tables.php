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
        // Add indexes to transaction table
        $transactionIndexes = [
            'transaction_transaction_number_index' => 'transaction_number',
            'transaction_transaction_sku_index'    => 'transaction_sku',
            'transaction_transaction_status_index' => 'transaction_status',
            'transaction_transaction_code_index'   => 'transaction_code',
            'transaction_created_at_index'         => 'created_at',
        ];

        foreach ($transactionIndexes as $indexName => $column) {
            try {
                DB::statement("ALTER TABLE `transaction` ADD INDEX `{$indexName}` (`{$column}`)");
            } catch (\Exception $e) {
                // Ignore if index already exists
                if (str_contains($e->getMessage(), 'Duplicate key name')) {
                    continue;
                }
                throw $e;
            }
        }

        // Add index to price_settings table
        try {
            DB::statement("ALTER TABLE `price_settings` ADD INDEX `price_settings_role_id_min_price_index` (`role_id`, `min_price`)");
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from transaction table
        $transactionIndexes = [
            'transaction_transaction_number_index',
            'transaction_transaction_sku_index',
            'transaction_transaction_status_index',
            'transaction_transaction_code_index',
            'transaction_created_at_index',
        ];

        foreach ($transactionIndexes as $indexName) {
            try {
                DB::statement("ALTER TABLE `transaction` DROP INDEX `{$indexName}`");
            } catch (\Exception $e) {
                // Ignore if index does not exist
                if (str_contains($e->getMessage(), 'check that column/key exists')) {
                    continue;
                }
                throw $e;
            }
        }

        // Remove index from price_settings table
        try {
            DB::statement("ALTER TABLE `price_settings` DROP INDEX `price_settings_role_id_min_price_index`");
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'check that column/key exists')) {
                throw $e;
            }
        }
    }
};
