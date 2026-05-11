<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rate;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rates = [
            // Guest Rates
            ['rate_code' => 'GVILLA-WKDAYS', 'rate_value' => 22500.00, 'rate_extra' => 26200.00, 'type' => 'villa', 'description' => 'GUEST VILLAS SUNDAY TO THURSDAY'],
            ['rate_code' => 'GVILLA-WKENDS', 'rate_value' => 29500.00, 'rate_extra' => 33200.00, 'type' => 'villa', 'description' => 'GUEST VILLAS FRIDAY TO SATURDAY'],
            ['rate_code' => 'GSUITE-WKDAYS', 'rate_value' => 32500.00, 'rate_extra' => 36200.00, 'type' => 'suite', 'description' => 'GUEST SUITES SUNDAY TO THURSDAY'],
            ['rate_code' => 'GSUITE-WKENDS', 'rate_value' => 45500.00, 'rate_extra' => 49200.00, 'type' => 'suite', 'description' => 'GUEST SUITES FRIDAY TO SATURDAY'],

            // Member Rates
            ['rate_code' => 'MVILLA-WKDAYS', 'rate_value' => 10000.00, 'rate_extra' => 13700.00, 'type' => 'villa', 'description' => 'MEMBER VILLAS SUNDAY TO THURSDAY'],
            ['rate_code' => 'MVILLA-WKENDS', 'rate_value' => 14000.00, 'rate_extra' => 17700.00, 'type' => 'villa', 'description' => 'MEMBER VILLAS FRIDAY TO SATURDAY'],
            ['rate_code' => 'MSUITE-WKDAYS', 'rate_value' => 15000.00, 'rate_extra' => 18700.00, 'type' => 'suite', 'description' => 'MEMBER SUITES SUNDAY TO THURSDAY'],
            ['rate_code' => 'MSUITE-WKENDS', 'rate_value' => 21000.00, 'rate_extra' => 24700.00, 'type' => 'suite', 'description' => 'MEMBER SUITES FRIDAY TO SATURDAY'],

            // Special Key/FVN Rates
            ['rate_code' => 'KEY-VILLA', 'rate_value' => 0.01, 'rate_extra' => 3700.01, 'type' => 'villa', 'description' => 'MEMBER VILLAS FVN'],
            ['rate_code' => 'KEY-SUITE', 'rate_value' => 0.02, 'rate_extra' => 3700.02, 'type' => 'suite', 'description' => 'MEMBER SUITES FVN'],

            // Half FVN + Cash Rates (.03 suffix)
            ['rate_code' => '.5FVN-M-S-DAY', 'rate_value' => 10000.03, 'rate_extra' => 13700.03, 'type' => 'suite', 'description' => 'MEMBER SUITES HALF FVN + PHP 10,000.00 SUN-THU'],
            ['rate_code' => '.5FVN-M-S-END', 'rate_value' => 14000.03, 'rate_extra' => 17700.03, 'type' => 'suite', 'description' => 'MEMBER SUITES HALF FVN + PHP 14,000.00 FRI-SAT'],
            ['rate_code' => '.5RATE-M-S-DAY', 'rate_value' => 5000.03, 'rate_extra' => 8700.03, 'type' => 'suite', 'description' => 'MEMBER SUITES 1 FVN + PHP 5,000.00 SUN-THU'],
            ['rate_code' => '.5RATE-M-S-END', 'rate_value' => 7000.03, 'rate_extra' => 10700.03, 'type' => 'suite', 'description' => 'MEMBER SUITES 1 FVN + PHP 7,000.00 FRI-SAT'],
            ['rate_code' => '.5FVN-M-V-DAY', 'rate_value' => 5000.03, 'rate_extra' => 8700.03, 'type' => 'villa', 'description' => 'MEMBER VILLAS HALF FVN + PHP 5,000.00 SUN-THU'],
            ['rate_code' => '.5FVN-M-V-END', 'rate_value' => 7000.03, 'rate_extra' => 10700.03, 'type' => 'villa', 'description' => 'MEMBER VILLAS HALF FVN + PHP 7,000.00 FRI-SAT'],

            // Guest Half FVN/Rate Combinations
            ['rate_code' => '.5FVN-G-S-DAY', 'rate_value' => 22500.03, 'rate_extra' => 26200.03, 'type' => 'suite', 'description' => 'GUEST SUITES HALF FVN + PHP 22,500.00 SUN-THU'],
            ['rate_code' => '.5FVN-G-S-END', 'rate_value' => 29500.03, 'rate_extra' => 33200.03, 'type' => 'suite', 'description' => 'GUEST SUITES HALF FVN + PHP 29,500.00 FRI-SAT'],
            ['rate_code' => '.5RATE-G-S-DAY', 'rate_value' => 16250.03, 'rate_extra' => 19950.03, 'type' => 'suite', 'description' => 'GUEST SUITES 1 FVN + PHP 16,250.00 SUN-THU'],
            ['rate_code' => '.5RATE-G-S-END', 'rate_value' => 22750.03, 'rate_extra' => 26450.03, 'type' => 'suite', 'description' => 'GUEST SUITES 1 FVN + PHP 22,750.00 FRI-SAT'],
            ['rate_code' => '.5FVN-G-V-DAY', 'rate_value' => 11250.03, 'rate_extra' => 14950.03, 'type' => 'villa', 'description' => 'GUEST VILLAS HALF FVN + PHP 11,250.00 SUN-THU'],
            ['rate_code' => '.5FVN-G-V-END', 'rate_value' => 14750.03, 'rate_extra' => 18450.03, 'type' => 'villa', 'description' => 'GUEST VILLAS HALF FVN + PHP 14,750.00 FRI-SAT'],
        ];

        foreach ($rates as $rate) {
            Rate::updateOrCreate(['rate_code' => $rate['rate_code']], $rate);
        }
    }
}
