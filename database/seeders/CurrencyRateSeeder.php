<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CurrencyRate;
use Carbon\Carbon;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $date = Carbon::now()->format('Y-m-d');

        // Base currency is INR
        $baseCurrency = 'INR';

        // Currency rates relative to INR (sample values)
        $rates = [
            ['code' => 'USD', 'rate' => 0.012],
            ['code' => 'IDR', 'rate' => 175.0],
            ['code' => 'EUR', 'rate' => 0.011],
            ['code' => 'SGD', 'rate' => 0.016],
            ['code' => 'JPY', 'rate' => 1.6],
            ['code' => 'CNY', 'rate' => 0.083],
            ['code' => 'GBP', 'rate' => 0.0095],
            ['code' => 'AUD', 'rate' => 0.018],
            ['code' => 'CAD', 'rate' => 0.015],
            ['code' => 'VND', 'rate' => 280.0],
        ];

        foreach ($rates as $rate) {
            // INR to other currency
            CurrencyRate::updateOrCreate(
                [
                    'from_currency' => $baseCurrency,
                    'to_currency' => $rate['code'],
                    'effective_date' => $date,
                ],
                [
                    'rate' => $rate['rate'],
                    'end_date' => null,
                    'is_active' => true,
                ]
            );

            // Other currency to INR (inverse rate)
            CurrencyRate::updateOrCreate(
                [
                    'from_currency' => $rate['code'],
                    'to_currency' => $baseCurrency,
                    'effective_date' => $date,
                ],
                [
                    'rate' => 1 / $rate['rate'],
                    'end_date' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
