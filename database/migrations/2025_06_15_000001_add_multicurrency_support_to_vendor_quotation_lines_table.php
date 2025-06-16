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
        Schema::table('vendor_quotation_lines', function (Blueprint $table) {
            // Add subtotal if not exists (some systems might not have this)
            if (!Schema::hasColumn('vendor_quotation_lines', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price');
            }
            
            // Add tax fields for line items
            $table->decimal('tax_rate', 5, 2)->default(0)->after('subtotal');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            $table->decimal('line_total', 15, 2)->default(0)->after('tax_amount');
            
            // Base currency calculations
            $table->decimal('base_currency_unit_price', 15, 2)->nullable()->after('line_total');
            $table->decimal('base_currency_subtotal', 15, 2)->nullable()->after('base_currency_unit_price');
            $table->decimal('base_currency_tax_amount', 15, 2)->nullable()->after('base_currency_subtotal');
            $table->decimal('base_currency_line_total', 15, 2)->nullable()->after('base_currency_tax_amount');
            
            // Additional fields
            $table->text('line_notes')->nullable()->after('base_currency_line_total');
            $table->string('manufacturer_part_number')->nullable()->after('line_notes');
            $table->integer('lead_time_days')->nullable()->after('manufacturer_part_number');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('lead_time_days');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percentage');
            
            // Delivery and shipping
            $table->string('shipping_terms')->nullable()->after('discount_amount');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('shipping_terms');
            $table->decimal('base_currency_shipping_cost', 15, 2)->default(0)->after('shipping_cost');
            
            // Add indexes for performance
            $table->index(['quotation_id', 'item_id']);
            $table->index('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_quotation_lines', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['vendor_quotation_lines_quotation_id_item_id_index']);
            $table->dropIndex(['vendor_quotation_lines_delivery_date_index']);
            
            // Drop columns (don't drop subtotal if it existed before)
            $table->dropColumn([
                'tax_rate',
                'tax_amount',
                'line_total',
                'base_currency_unit_price',
                'base_currency_subtotal',
                'base_currency_tax_amount',
                'base_currency_line_total',
                'line_notes',
                'manufacturer_part_number',
                'lead_time_days',
                'discount_percentage',
                'discount_amount',
                'shipping_terms',
                'shipping_cost',
                'base_currency_shipping_cost'
            ]);
        });
    }
};