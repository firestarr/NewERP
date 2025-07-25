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
        // Add bidirectional support columns to currency_rates table
        Schema::table('currency_rates', function (Blueprint $table) {
            // Track bidirectional capability
            $table->boolean('is_bidirectional')->default(false)->after('is_active');
            
            // Track rate calculation method for transparency
            $table->enum('calculation_method', ['direct', 'inverse', 'cross'])->default('direct')->after('is_bidirectional');
            
            // Reference to source rate for calculated rates
            $table->unsignedBigInteger('source_rate_id')->nullable()->after('calculation_method');
            
            // Confidence level for the rate
            $table->enum('confidence_level', ['high', 'medium', 'low'])->default('high')->after('source_rate_id');
            
            // Additional metadata
            $table->json('metadata')->nullable()->after('confidence_level'); // Store calculation path, etc.
            
            // Provider information
            $table->string('provider_code')->nullable()->after('metadata');
            
            // Indexes for bidirectional lookups
            $table->index(['from_currency', 'to_currency', 'effective_date', 'is_active'], 'idx_rates_bidirectional_lookup');
            $table->index(['to_currency', 'from_currency', 'effective_date', 'is_active'], 'idx_rates_reverse_lookup');
            $table->index(['is_bidirectional', 'is_active'], 'idx_rates_bidirectional_flag');
            $table->index(['confidence_level', 'is_active'], 'idx_rates_confidence');
            
            // Foreign key for source rate reference
            $table->foreign('source_rate_id')->references('rate_id')->on('currency_rates')->onDelete('set null');
        });

        // Create bidirectional rate cache table for performance
        Schema::create('currency_rate_cache', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->date('cache_date');
            $table->decimal('rate', 15, 6);
            $table->enum('calculation_method', ['direct', 'inverse', 'cross']);
            $table->enum('confidence_level', ['high', 'medium', 'low']);
            $table->json('calculation_path')->nullable();
            $table->unsignedBigInteger('source_rate_id')->nullable();
            $table->timestamp('cached_at');
            $table->timestamp('expires_at');
            
            // Indexes
            $table->unique(['from_currency', 'to_currency', 'cache_date'], 'idx_cache_unique_rate');
            $table->index(['expires_at'], 'idx_cache_expiry');
            $table->index(['cached_at'], 'idx_cache_created');
            
            $table->foreign('source_rate_id')->references('rate_id')->on('currency_rates')->onDelete('cascade');
        });

        // Add bidirectional settings to currency_settings
        $this->insertBidirectionalSettings();
        
        // Mark existing rates as bidirectional where appropriate
        $this->markExistingBidirectionalRates();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropForeign(['source_rate_id']);
        });

        // Drop cache table
        Schema::dropIfExists('currency_rate_cache');

        // Remove bidirectional columns from currency_rates
        Schema::table('currency_rates', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_rates_bidirectional_lookup');
            $table->dropIndex('idx_rates_reverse_lookup');
            $table->dropIndex('idx_rates_bidirectional_flag');
            $table->dropIndex('idx_rates_confidence');
            
            // Drop columns
            $table->dropColumn([
                'is_bidirectional',
                'calculation_method',
                'source_rate_id',
                'confidence_level',
                'metadata',
                'provider_code'
            ]);
        });

        // Remove bidirectional settings
        DB::table('currency_settings')->whereIn('key', [
            'bidirectional_enabled',
            'cross_currency_enabled',
            'rate_cache_enabled',
            'rate_cache_ttl',
            'max_cross_currency_hops',
            'default_confidence_threshold'
        ])->delete();
    }

    /**
     * Insert bidirectional-specific settings
     */
    private function insertBidirectionalSettings(): void
    {
        $settings = [
            [
                'key' => 'bidirectional_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable bidirectional currency rate lookup',
                'is_editable' => true
            ],
            [
                'key' => 'cross_currency_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable cross-currency conversion via base currency',
                'is_editable' => true
            ],
            [
                'key' => 'rate_cache_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable currency rate caching for performance',
                'is_editable' => true
            ],
            [
                'key' => 'rate_cache_ttl',
                'value' => '300',
                'type' => 'integer',
                'description' => 'Currency rate cache TTL in seconds (default: 5 minutes)',
                'is_editable' => true
            ],
            [
                'key' => 'max_cross_currency_hops',
                'value' => '2',
                'type' => 'integer',
                'description' => 'Maximum hops for cross-currency conversion',
                'is_editable' => true
            ],
            [
                'key' => 'default_confidence_threshold',
                'value' => 'medium',
                'type' => 'string',
                'description' => 'Minimum confidence level for rate usage',
                'is_editable' => true
            ],
            [
                'key' => 'inverse_rate_precision',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Decimal places for inverse rate calculations',
                'is_editable' => true
            ],
            [
                'key' => 'bidirectional_logging_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Log bidirectional rate calculations for audit',
                'is_editable' => true
            ]
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            
            // Only insert if not exists
            $exists = DB::table('currency_settings')
                ->where('key', $setting['key'])
                ->exists();
                
            if (!$exists) {
                DB::table('currency_settings')->insert($setting);
            }
        }
    }

    /**
     * Mark existing rates as bidirectional where reverse rates exist
     */
    private function markExistingBidirectionalRates(): void
    {
        // Find rates that have corresponding reverse rates
        $bidirectionalPairs = DB::select("
            SELECT DISTINCT 
                r1.rate_id as rate_id_1,
                r2.rate_id as rate_id_2
            FROM currency_rates r1
            INNER JOIN currency_rates r2 ON (
                r1.from_currency = r2.to_currency 
                AND r1.to_currency = r2.from_currency
                AND r1.effective_date = r2.effective_date
                AND r1.is_active = r2.is_active
            )
            WHERE r1.rate_id < r2.rate_id  -- Avoid duplicates
        ");

        // Mark these rates as bidirectional
        foreach ($bidirectionalPairs as $pair) {
            DB::table('currency_rates')
                ->whereIn('rate_id', [$pair->rate_id_1, $pair->rate_id_2])
                ->update([
                    'is_bidirectional' => true,
                    'calculation_method' => 'direct',
                    'confidence_level' => 'high',
                    'updated_at' => now()
                ]);
        }

        // Log the migration results
        $bidirectionalCount = DB::table('currency_rates')
            ->where('is_bidirectional', true)
            ->count();

        \Log::info("Bidirectional migration completed. Marked {$bidirectionalCount} rates as bidirectional.");
    }
};