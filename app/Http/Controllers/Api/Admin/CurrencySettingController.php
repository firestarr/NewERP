<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CurrencySettingController extends Controller
{
    /**
     * Get currency settings including base currency and available currencies
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrencySettings()
    {
        try {
            // Get base currency from system settings
            $baseCurrency = SystemSetting::getValue('base_currency', 'USD');
            
            // Get available currencies from system_currencies table
            $availableCurrencies = SystemCurrency::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(function ($currency) {
                    return [
                        $currency->code => [
                            'name' => $currency->name,
                            'symbol' => $currency->symbol,
                            'decimal_places' => $currency->decimal_places,
                            'countries' => json_decode($currency->countries, true),
                            'locale' => $this->getCurrencyLocale($currency->code),
                            'special_formatting' => $this->getSpecialFormatting($currency->code)
                        ]
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'base_currency' => $baseCurrency,
                    'available_currencies' => $availableCurrencies
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch currency settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update base currency setting
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateBaseCurrency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3|exists:system_currencies,code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validate currency is active
            $currency = SystemCurrency::where('code', $request->currency)
                ->where('is_active', true)
                ->first();

            if (!$currency) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Selected currency is not active'
                ], 422);
            }

            // Update the base currency setting
            SystemSetting::updateValue(
                'base_currency', 
                $request->currency, 
                'currency', 
                'Base currency for the application'
            );

            // Update the is_base_currency flag in system_currencies
            SystemCurrency::where('is_base_currency', true)
                ->update(['is_base_currency' => false]);
            
            $currency->update(['is_base_currency' => true]);

            // Clear related caches
            Cache::forget('base_currency');
            Cache::forget('currency_settings');
            
            // Update application config
            config(['app.base_currency' => $request->currency]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Base currency updated successfully',
                'data' => [
                    'base_currency' => $request->currency,
                    'currency_info' => [
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'decimal_places' => $currency->decimal_places
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update base currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get currency formatting preview
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCurrencyPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3|exists:system_currencies,code',
            'amounts' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currency = SystemCurrency::where('code', $request->currency)->first();
            $amounts = $request->amounts ?? [1000, 100000, 1000000, 50000000];
            
            $previews = [];
            
            foreach ($amounts as $amount) {
                $previews[] = [
                    'amount' => $amount,
                    'formatted' => $this->formatCurrency($amount, $currency),
                    'special_format' => $this->getSpecialFormat($amount, $currency)
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'currency' => $request->currency,
                    'currency_info' => [
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'decimal_places' => $currency->decimal_places
                    ],
                    'previews' => $previews
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate currency preview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all currency-related settings
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllCurrencySettings()
    {
        try {
            $settings = SystemSetting::where('setting_group', 'currency')->get();
            
            $formatted = $settings->mapWithKeys(function ($setting) {
                // Convert string values to appropriate types
                $value = $setting->setting_value;
                
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = is_float($value) ? (float) $value : (int) $value;
                }
                
                return [
                    $setting->setting_key => [
                        'value' => $value,
                        'description' => $setting->description
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formatted
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch currency settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update multiple currency settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateCurrencySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->settings as $setting) {
                SystemSetting::updateValue(
                    $setting['key'],
                    $setting['value'],
                    'currency'
                );
            }

            // Clear caches
            Cache::flush();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Currency settings updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update currency settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get currency locale mapping
     *
     * @param string $currencyCode
     * @return string
     */
    private function getCurrencyLocale($currencyCode)
    {
        $localeMap = [
            'USD' => 'en-US',
            'EUR' => 'en-EU',
            'IDR' => 'id-ID',
            'GBP' => 'en-GB',
            'JPY' => 'ja-JP',
            'SGD' => 'en-SG',
            'MYR' => 'ms-MY',
            'VND' => 'vi-VN',
            'INR' => 'en-IN',
            'THB' => 'th-TH',
            'PHP' => 'en-PH',
            'KRW' => 'ko-KR',
            'HKD' => 'en-HK',
            'TWD' => 'zh-TW',
            'CAD' => 'en-CA',
            'AUD' => 'en-AU',
            'NZD' => 'en-NZ',
            'CHF' => 'de-CH',
            'SEK' => 'sv-SE'
        ];

        return $localeMap[$currencyCode] ?? 'en-US';
    }

    /**
     * Get special formatting rules for specific currencies
     *
     * @param string $currencyCode
     * @return array|null
     */
    private function getSpecialFormatting($currencyCode)
    {
        $specialFormats = [
            'VND' => [
                'note' => 'No decimal places - typically shows in thousands/millions',
                'separator' => 'dot',
                'no_decimals' => true
            ],
            'INR' => [
                'note' => 'Uses Indian numbering system (lakhs/crores)',
                'special_format' => 'lakh_crore',
                'separator' => 'comma'
            ],
            'IDR' => [
                'note' => 'No decimal places for large amounts',
                'no_decimals' => true
            ],
            'JPY' => [
                'note' => 'No decimal places',
                'no_decimals' => true
            ],
            'KRW' => [
                'note' => 'No decimal places',
                'no_decimals' => true
            ]
        ];

        return $specialFormats[$currencyCode] ?? null;
    }

    /**
     * Format currency amount according to locale and currency rules
     *
     * @param float $amount
     * @param SystemCurrency $currency
     * @return string
     */
    private function formatCurrency($amount, $currency)
    {
        $locale = $this->getCurrencyLocale($currency->code);
        
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $currency->decimal_places);
        
        return $formatter->formatCurrency($amount, $currency->code);
    }

    /**
     * Get special format for specific currencies (like INR lakh/crore)
     *
     * @param float $amount
     * @param SystemCurrency $currency
     * @return string|null
     */
    private function getSpecialFormat($amount, $currency)
    {
        if ($currency->code === 'INR') {
            if ($amount >= 10000000) { // 1 crore
                return '₹' . number_format($amount / 10000000, 2) . ' Cr';
            } elseif ($amount >= 100000) { // 1 lakh
                return '₹' . number_format($amount / 100000, 2) . ' L';
            }
        }
        
        return null;
    }
}

// Add these routes to your routes/api.php file:
/*

*/