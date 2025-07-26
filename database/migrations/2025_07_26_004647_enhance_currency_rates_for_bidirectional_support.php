<?php
// database/migrations/2025_07_26_000001_enhance_currency_rates_for_bidirectional_support.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            // Add performance indexes
            $table->index(['from_currency', 'to_currency', 'effective_date'], 'currency_pair_date_idx');
            $table->index(['is_active', 'effective_date'], 'active_date_idx');
            
            // Add bidirectional support flag
            $table->boolean('is_bidirectional_enabled')->default(true)->after('is_active');
            
            // Add conversion notes for debugging
            $table->text('conversion_notes')->nullable()->after('is_bidirectional_enabled');
            
            // Add rate direction indicator (optional, for clarity)
            $table->enum('rate_type', ['direct', 'cross', 'manual'])->default('direct')->after('conversion_notes');
        });
    }

    public function down()
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex('currency_pair_date_idx');
            $table->dropIndex('active_date_idx');
            $table->dropColumn(['is_bidirectional_enabled', 'conversion_notes', 'rate_type']);
        });
    }
};