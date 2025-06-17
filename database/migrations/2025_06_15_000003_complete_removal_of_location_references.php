<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompleteRemovalOfLocationReferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Remove location_id from stock_adjustment_lines table
        if (Schema::hasColumn('stock_adjustment_lines', 'location_id')) {
            Schema::table('stock_adjustment_lines', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // Remove location_id from stock_transactions table  
        if (Schema::hasColumn('stock_transactions', 'location_id')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // Remove location_id from other related tables if they exist
        $tablesToCheck = [
            'goods_receipt_lines',
            'delivery_lines', 
            'delivery_line',
            'DeliveryLine',
            'cycle_countings',
            'production_consumption',
            'ProductionConsumption',
            'item_batches'
        ];

        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'location_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    // Drop foreign key constraint first if it exists
                    try {
                        $table->dropForeign(['location_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                    
                    $table->dropColumn('location_id');
                });
            }
        }

        // Drop warehouse_locations table if it exists
        if (Schema::hasTable('warehouse_locations')) {
            Schema::dropIfExists('warehouse_locations');
        }

        // Drop warehouse_zones table if it exists  
        if (Schema::hasTable('warehouse_zones')) {
            Schema::dropIfExists('warehouse_zones');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration is not reversible as it removes tables and columns
        // If you need to rollback, you would need to recreate the location system
        throw new \Exception('This migration cannot be reversed. Location system has been permanently removed.');
    }
}