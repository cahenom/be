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
        // Add indexes to product_prepaid table
        $prepaidIndexes = [
            'product_prepaid_product_category_index' => 'product_category',
            'product_prepaid_product_provider_index' => 'product_provider',
            'product_prepaid_product_type_index'     => 'product_type',
        ];

        foreach ($prepaidIndexes as $indexName => $column) {
            try {
                DB::statement("ALTER TABLE `product_prepaid` ADD INDEX `{$indexName}` (`{$column}`)");
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }

        // Add indexes to product_pasca table
        $pascaIndexes = [
            'product_pasca_product_category_index' => 'product_category',
            'product_pasca_product_provider_index' => 'product_provider',
        ];

        foreach ($pascaIndexes as $indexName => $column) {
            try {
                DB::statement("ALTER TABLE `product_pasca` ADD INDEX `{$indexName}` (`{$column}`)");
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }

        // Add index to prefix_number table
        try {
            DB::statement("ALTER TABLE `prefix_number` ADD INDEX `prefix_number_provider_index` (`provider`)");
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
        // Remove indexes from product_prepaid table
        $prepaidIndexes = [
            'product_prepaid_product_category_index',
            'product_prepaid_product_provider_index',
            'product_prepaid_product_type_index',
        ];

        foreach ($prepaidIndexes as $indexName) {
            try {
                DB::statement("ALTER TABLE `product_prepaid` DROP INDEX `{$indexName}`");
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'check that column/key exists')) {
                    throw $e;
                }
            }
        }

        // Remove indexes from product_pasca table
        $pascaIndexes = [
            'product_pasca_product_category_index',
            'product_pasca_product_provider_index',
        ];

        foreach ($pascaIndexes as $indexName) {
            try {
                DB::statement("ALTER TABLE `product_pasca` DROP INDEX `{$indexName}`");
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'check that column/key exists')) {
                    throw $e;
                }
            }
        }

        // Remove index from prefix_number table
        try {
            DB::statement("ALTER TABLE `prefix_number` DROP INDEX `prefix_number_provider_index`");
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'check that column/key exists')) {
                throw $e;
            }
        }
    }
};
