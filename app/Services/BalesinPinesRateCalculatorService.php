<?php

namespace App\Services;

class BalesinPinesRateCalculatorService
{
    /** Extra person charge for 3rd occupant in Balesin Pines */
    public const EXTRA_PERSON_CHARGE = 3700;

    /** Balesin Pines Base Rates */
    public const DELUXE_MEMBER_RATE = 10000;
    public const DELUXE_GUEST_RATE  = 14500;

    public const SUITE_MEMBER_RATE  = 15000;
    public const SUITE_GUEST_RATE   = 21500;

    protected $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->getSettings();
    }

    /**
     * Resolve whether unit is Deluxe Room or Junior Suite from roomType/rate desc
     */
    public function resolvePinesUnitType(string $roomType = '', string $rateCode = ''): string
    {
        $rt = strtolower($roomType . ' ' . $rateCode);
        if (str_contains($rt, 'suite') || str_contains($rt, 'js') || str_contains($rt, 'junior')) {
            return 'JUNIOR SUITE';
        }
        return 'DELUXE ROOM';
    }

    /**
     * Get Base Nightly Rate for Pines Unit Type based on Member vs Guest status and member unit count limit (max 3)
     */
    public function getBasePinesRate(string $unitType, bool $isMember = true, int $memberUnitCount = 1, bool $fvnUtilized = true): float
    {
        // Member rate is only applicable once member has utilized all Free Villa Nights (FVN).
        // Max 3 rooms/suites at member rate per member (with direct relative present). Exceeding units get guest rate.
        $eligibleForMemberRate = $isMember && $fvnUtilized && ($memberUnitCount <= 3);

        if ($unitType === 'JUNIOR SUITE') {
            return $eligibleForMemberRate ? self::SUITE_MEMBER_RATE : self::SUITE_GUEST_RATE;
        }

        return $eligibleForMemberRate ? self::DELUXE_MEMBER_RATE : self::DELUXE_GUEST_RATE;
    }

    /**
     * Calculate Pass-through Rates for Pines (raw rates sum without Island fees)
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
            'is_employee' => false,
            'base_pax'    => 2
        ];
    }

    /**
     * Calculate Pines Passenger Rates applying Pines Occupancy, Senior/PWD, and FVN conversion rules
     */
    public function calculatePassengerRates(
        array $passenger,
        int $paxIndex = 0,
        string $roomType = '',
        int $totalBillable = 2,
        ?int $occupantIndex = null,
        float $remainingFvn = 0.0,
        int $memberUnitCount = 1
    ): array {
        if ($occupantIndex === null) {
            $occupantIndex = $paxIndex;
        }

        $gstType = strtolower($passenger['gstType'] ?? $passenger['gsttype'] ?? '');
        $isMember = str_contains($gstType, 'member') || str_contains($gstType, 'spouse') || str_contains($gstType, 'dependent');

        $privCard = trim((string) ($passenger['privCard'] ?? $passenger['privcard'] ?? ''));
        $privCardLower = strtolower($privCard);
        $hasPrivCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);
        if ($privCardLower === 'senior citizen' || $privCardLower === 'pwd' || str_contains($gstType, 'senior') || str_contains($gstType, 'pwd')) {
            $hasPrivCard = true;
        }

        $unitType = $this->resolvePinesUnitType($roomType, $passenger['rateCode'] ?? '');
        $isExtraPerson = ($occupantIndex >= 2); // 3rd occupant is index 2

        $accDates = [];
        $totalAcc = 0;

        foreach ($passenger['rate'] ?? [] as $r) {
            $val  = (float) ($r['val'] ?? 0);
            $date = $r['date'] ?? null;
            if (!$date) continue;

            $grossVal = $val;

            // Handle 1.5 FVN & 0.5 FVN suite fallback rule:
            // If member stays in a suite with only 0.5 or 1.0 FVN remaining, apply member villa rate per night
            if ($unitType === 'JUNIOR SUITE' && $isMember && ($remainingFvn == 0.5 || $remainingFvn == 1.0) && $grossVal > 0) {
                $grossVal = self::DELUXE_MEMBER_RATE;
            }

            if ($isExtraPerson) {
                // Extra person charge is 3,700
                $baseFee = self::EXTRA_PERSON_CHARGE;
                if ($hasPrivCard) {
                    // Extra person SC/PWD discount: 20% discount & 12% VAT exemption
                    // Net = (3,700 / 1.12) * 0.80 = 2,642.86
                    $vatExemptBase = round($baseFee / 1.12, 2);
                    $discountAmt   = round($vatExemptBase * 0.20, 2);
                    $finalVal      = round($vatExemptBase - $discountAmt, 2);
                } else {
                    $finalVal = $baseFee;
                }
            } else {
                // Base occupants (2 pax): Divide room rate by base billable occupants (excluding extra person)
                $baseDivisor = max(1, min(2, $totalBillable));
                $individualShare = round($grossVal / $baseDivisor, 2);

                if ($hasPrivCard) {
                    // SC/PWD discount: 20% discount & 12% VAT exemption on individual share
                    $vatExemptBase = round($individualShare / 1.12, 2);
                    $discountAmt   = round($vatExemptBase * 0.20, 2);
                    $finalVal      = round($vatExemptBase - $discountAmt, 2);
                } else {
                    $finalVal = $individualShare;
                }
            }

            $totalAcc += $finalVal;
            $accDates[$date] = [
                'val'       => $finalVal,
                'breakdown' => [
                    'gross_share'   => $grossVal,
                    'is_discounted' => $hasPrivCard,
                    'is_infant'     => false,
                    'divisor'       => $isExtraPerson ? 1 : max(1, min(2, $totalBillable)),
                    'base'          => $finalVal,
                    'discount'      => $hasPrivCard ? ($vatExemptBase * 0.20) : 0,
                    'sc'            => $hasPrivCard ? 1 : 0,
                    'vat'           => $hasPrivCard ? 0 : 12
                ]
            ];
        }

        return [
            'acc'         => $totalAcc,
            'acc_dates'   => $accDates,
            'air'         => 0,
            'han'         => 0,
            'avi'         => 0,
            'env'         => 0,
            'is_employee' => false,
            'base_pax'    => 2
        ];
    }
}
