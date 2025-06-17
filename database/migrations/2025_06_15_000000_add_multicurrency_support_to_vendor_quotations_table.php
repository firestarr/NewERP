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
        Schema::table('vendor_quotations', function (Blueprint $table) {
            // Multi-currency fields
            $table->string('currency_code', 3)->default('USD')->after('validity_date');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000)->after('currency_code');
            $table->decimal('total_amount', 15, 2)->nullable()->after('exchange_rate');
            $table->decimal('base_currency_total', 15, 2)->nullable()->after('total_amount');
            
            // Additional business fields
            $table->text('notes')->nullable()->after('base_currency_total');
            $table->string('payment_terms')->nullable()->after('notes');
            $table->string('delivery_terms')->nullable()->after('payment_terms');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('delivery_terms');
            $table->decimal('base_currency_tax', 15, 2)->default(0)->after('tax_amount');
            
            // Tracking fields
            $table->timestamp('rate_date')->nullable()->after('base_currency_tax');
            $table->string('rate_source')->nullable()->after('rate_date'); // 'manual', 'api', 'system'
            
            // Add indexes for performance
            $table->index('currency_code');
            $table->index(['rfq_id', 'currency_code']);
            $table->index(['vendor_id', 'currency_code']);
            $table->index(['quotation_date', 'currency_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_quotations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['vendor_quotations_currency_code_index']);
            $table->dropIndex(['vendor_quotations_rfq_id_currency_code_index']);
            $table->dropIndex(['vendor_quotations_vendor_id_currency_code_index']);
            $table->dropIndex(['vendor_quotations_quotation_date_currency_code_index']);
            
            // Drop columns
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'total_amount',
                'base_currency_total',
                'notes',
                'payment_terms',
                'delivery_terms',
                'tax_amount',
                'base_currency_tax',
                'rate_date',
                'rate_source'
            ]);
        });
    }
};