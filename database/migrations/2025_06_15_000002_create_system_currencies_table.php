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
        // Create system currencies table
        Schema::create('system_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217 currency code
            $table->string('name');
            $table->string('symbol', 10);
            $table->integer('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_base_currency')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('countries')->nullable(); // Countries that use this currency
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('is_base_currency');
            $table->index('sort_order');
        });

        // Create currency exchange providers table
        Schema::create('currency_exchange_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., 'fixer', 'xe', 'bank_indonesia'
            $table->string('api_url')->nullable();
            $table->string('api_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('supported_currencies')->nullable();
            $table->json('configuration')->nullable(); // Additional provider settings
            $table->integer('priority')->default(0); // For fallback order
            $table->decimal('rate_limit_per_day', 8, 0)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('is_default');
            $table->index('priority');
        });

        // Create system settings for currency
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, decimal, json
            $table->text('description')->nullable();
            $table->boolean('is_editable')->default(true);
            $table->timestamps();
        });

        // Insert default currencies
        $this->insertDefaultCurrencies();
        
        // Insert default settings
        $this->insertDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_settings');
        Schema::dropIfExists('currency_exchange_providers');
        Schema::dropIfExists('system_currencies');
    }

    /**
     * Insert default currencies
     */
    private function insertDefaultCurrencies(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => true,
                'sort_order' => 1,
                'countries' => json_encode(['United States'])
            ],
            [
                'code' => 'IDR',
                'name' => 'Indonesian Rupiah',
                'symbol' => 'Rp',
                'decimal_places' => 0,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 2,
                'countries' => json_encode(['Indonesia'])
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 3,
                'countries' => json_encode(['Germany', 'France', 'Italy', 'Spain'])
            ],
            [
                'code' => 'SGD',
                'name' => 'Singapore Dollar',
                'symbol' => 'S$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 4,
                'countries' => json_encode(['Singapore'])
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'decimal_places' => 0,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 5,
                'countries' => json_encode(['Japan'])
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 6,
                'countries' => json_encode(['China'])
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 7,
                'countries' => json_encode(['United Kingdom'])
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 8,
                'countries' => json_encode(['Australia'])
            ],
            [
                'code' => 'CAD',
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
                'sort_order' => 9,
                'countries' => json_encode(['Canada'])
            ]
        ];

        foreach ($currencies as $currency) {
            $currency['created_at'] = now();
            $currency['updated_at'] = now();
            DB::table('system_currencies')->insert($currency);
        }
    }

    /**
     * Insert default currency settings
     */
    private function insertDefaultSettings(): void
    {
        $settings = [
            [
                'key' => 'base_currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Base currency for the system',
                'is_editable' => true
            ],
            [
                'key' => 'auto_update_rates',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Automatically update exchange rates',
                'is_editable' => true
            ],
            [
                'key' => 'rate_update_frequency',
                'value' => 'daily',
                'type' => 'string',
                'description' => 'How often to update exchange rates (hourly, daily, weekly)',
                'is_editable' => true
            ],
            [
                'key' => 'default_exchange_provider',
                'value' => 'fixer',
                'type' => 'string',
                'description' => 'Default exchange rate provider',
                'is_editable' => true
            ],
            [
                'key' => 'rate_cache_duration',
                'value' => '3600',
                'type' => 'integer',
                'description' => 'How long to cache exchange rates (in seconds)',
                'is_editable' => true
            ],
            [
                'key' => 'currency_rounding_precision',
                'value' => '4',
                'type' => 'integer',
                'description' => 'Decimal places for currency calculations',
                'is_editable' => true
            ],
            [
                'key' => 'show_base_currency_amounts',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Show base currency amounts alongside original currency',
                'is_editable' => true
            ],
            [
                'key' => 'allowed_exchange_rate_variance',
                'value' => '5',
                'type' => 'decimal',
                'description' => 'Maximum allowed variance (%) for manual exchange rate entry',
                'is_editable' => true
            ]
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('currency_settings')->insert($setting);
        }
    }
};