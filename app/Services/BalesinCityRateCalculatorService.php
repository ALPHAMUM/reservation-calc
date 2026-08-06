<?php

namespace App\Services;

class BalesinCityRateCalculatorService
{
    /** Member Base Rates */
    public const STUDIO_MEMBER_RATE = 6400;
    public const TWOBED_MEMBER_RATE  = 9250;
    public const EXTRA_PERSON_RATE  = 1300;

    protected $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->getSettings();
    }

    /**
     * Resolve whether unit is Studio-Type Suite or 2-Bedroom Suite
     */
    public function resolveCityUnitType(string $roomType = '', string $rateCode = ''): string
    {
        $rt = strtolower($roomType . ' ' . $rateCode);
        if (str_contains($rt, '2') || str_contains($rt, '2-br') || str_contains($rt, '2br') || str_contains($rt, '2-bedroom') || str_contains($rt, 'two bedroom')) {
            return '2-BEDROOM SUITE';
        }
        return 'STUDIO-TYPE SUITE';
    }

    /**
     * Calculate Pass-through Rates for Balesin City (raw rates sum without Island fees)
     */
    public function getPassThroughRates(array $passenger): array
    {
        $acc = 0;
        $accDates = [];
        $air = 0;
        $han = 0;
        $avi = 0;
        $env = 0;

        foreach ($passenger['rate'] ?? [] as $r) {
            $val  = (float) ($r['val'] ?? 0);
            $desc = strtolower($r['desc'] ?? '');
            $date = $r['date'] ?? null;

            if (str_contains($desc, 'airfare')) {
                $air += $val;
            } elseif (str_contains($desc, 'hangar')) {
                $han += $val;
            } elseif (str_contains($desc, 'aviation') || str_contains($desc, 'aof') || str_contains($desc, 'passenger service charge')) {
                $avi += $val;
            } elseif (str_contains($desc, 'environmental')) {
                $env += $val;
            } else {
                if ($date) {
                    $acc += $val;
                    $accDates[$date] = [
                        'val'       => $val,
                        'breakdown' => [
                            'gross_share'   => $val,
                            'is_discounted' => false,
                            'is_infant'     => false,
                            'divisor'       => 1,
                            'base'          => $val,
                            'discount'      => 0,
                            'sc'            => 0,
                            'vat'           => 0
                        ]
                    ];
                }
            }
        }

        return [
            'acc'         => $acc,
            'acc_dates'   => $accDates,
            'air'         => $air,
            'han'         => $han,
            'avi'         => $avi,
            'env'         => $env,
            'total_gross' => $acc + $air + $han + $avi + $env,
            'total_net'   => $acc + $air + $han + $avi + $env,
        ];
    }
}
