<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $rates = [
            [
                'from_currency' => 'USD',
                'to_currency' => 'IDR',
                'rate' => 15000,
                'is_active' => true,
                'effective_date' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'from_currency' => 'IDR',
                'to_currency' => 'USD',
                'rate' => 1 / 15000,
                'is_active' => true,
                'effective_date' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'from_currency' => 'USD',
                'to_currency' => 'EUR',
                'rate' => 0.9,
                'is_active' => true,
                'effective_date' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'from_currency' => 'EUR',
                'to_currency' => 'USD',
                'rate' => 1 / 0.9,
                'is_active' => true,
                'effective_date' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Add more currency pairs as needed
        ];

        DB::table('currency_rates')->insert($rates);
    }
}
