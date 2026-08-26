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
        if (str_contains(strtoupper($roomType), '2-BEDROOM') || str_contains(strtoupper($roomType), '2-BR') || str_contains(strtoupper($roomType), 'TWOBED')) {
            return 'Two-Bedroom Suite';
        }
        return 'Studio-Type Suite';
    }

    public function isExtraOccupant(int $occupantIndex, string $roomType = ''): bool
    {
        $is2Bed = str_contains(strtoupper($roomType), '2-BEDROOM') || str_contains(strtoupper($roomType), '2-BR') || str_contains(strtoupper($roomType), 'TWOBED');
        $basePax = $is2Bed ? 6 : 4;
        return $occupantIndex >= $basePax;
    }

    /**
     * Calculate City passenger rates with optional Senior/PWD accommodation discount.
     */
    public function calculatePassengerRates(array $passenger): array
    {
        $gstType = strtolower($passenger['gstType'] ?? $passenger['gsttype'] ?? '');
        $privCard = trim((string) ($passenger['privCard'] ?? $passenger['privcard'] ?? ''));
        $privCardLower = strtolower($privCard);
        $hasPrivCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);
        if ($privCardLower === 'senior citizen' || $privCardLower === 'pwd' || str_contains($gstType, 'senior') || str_contains($gstType, 'pwd')) {
            $hasPrivCard = true;
        }

        $hasVillaSc = isset($passenger['sc_target_villa']) ? (bool) $passenger['sc_target_villa'] : $hasPrivCard;

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
            } elseif ($date) {
                // ── Detect FVN magic fractions: .01 = 1FVN, .02 = 1.5FVN, .03 = 0.5FVN
                $integerPart = (int) floor($val);
                $fraction    = round($val - $integerPart, 2);
                $fvnMapping  = [0.01 => 1.0, 0.02 => 1.5, 0.03 => 0.5];

                if (array_key_exists($fraction, $fvnMapping)) {
                    $fvnRate = $fvnMapping[$fraction];
                    // Base room is FVN (0 accommodation cost)
                    // If integer part > 0 (e.g. 1300.01), it means extra person also has 1300 on top
                    $accDates[$date] = [
                        'val'       => $fraction,
                        'breakdown' => [
                            'gross_share'   => $fraction,
                            'is_discounted' => false,
                            'is_infant'     => false,
                            'divisor'       => 1,
                            'base'          => $fraction,
                            'discount'      => 0,
                            'sc'            => 0,
                            'vat'           => 0,
                            'is_fvn'        => true,
                            'fvn_rate'      => $fvnRate,
                            'fraction'      => $fraction,
                            'paid_amount'   => ($integerPart > 0 && $integerPart !== 1300) ? (float)$integerPart : 0,
                        ],
                    ];
                    // Do NOT add to $acc – FVN = ₱0.00 accommodation
                    continue;
                }

                // ── Regular accommodation rate
                $finalVal = $val;
                $isDiscounted = false;
                $base = $val;
                $discount = 0;
                $sc = 0;
                $vat = 0;

                if ($hasVillaSc && $val > 0) {
                    $vatExemptBase = round($val / 1.12, 2);
                    $discount = round($vatExemptBase * 0.20, 2);
                    $finalVal = round($vatExemptBase - $discount, 2);
                    $base = $vatExemptBase;
                    $isDiscounted = true;
                }

                $acc += $finalVal;
                $accDates[$date] = [
                    'val'       => $finalVal,
                    'breakdown' => [
                        'gross_share'   => $val,
                        'is_discounted' => $isDiscounted,
                        'is_infant'     => false,
                        'divisor'       => 1,
                        'base'          => $base,
                        'discount'      => $discount,
                        'sc'            => $sc,
                        'vat'           => $vat,
                    ],
                ];
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

    /**
     * Calculate Pass-through Rates for Balesin City (raw rates sum without Island fees)
     */
    public function getPassThroughRates(array $passenger): array
    {
        return $this->calculatePassengerRates($passenger);
    }
}
