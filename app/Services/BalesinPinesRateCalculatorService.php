<?php

namespace App\Services;

class BalesinPinesRateCalculatorService
{
    /** Extra person charge for 3rd occupant in Balesin Pines */
    public const EXTRA_PERSON_CHARGE = 3700;

    /** Balesin Pines Base Rates — GRATE (guest) / MRATE (member) */
    public const DELUXE_MEMBER_RATE = 10000;
    public const DELUXE_GUEST_RATE  = 14500;

    public const SUITE_MEMBER_RATE  = 15000;
    public const SUITE_GUEST_RATE   = 21500;

    protected $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->getSettings();
    }

    private function getScDiscountPercent(): float
    {
        $scSettings = $this->settings['pines']['discounts']['sc_pwd'] ?? $this->settings['discounts']['sc_pwd'] ?? [];
        $applyDisc  = $scSettings['apply_discount'] ?? true;
        return $applyDisc ? (float) ($scSettings['additional_discount_percent'] ?? 20) : 0.0;
    }

    private function getVatRate(): float
    {
        return (float) ($this->settings['pines']['discounts']['vat_rate'] ?? $this->settings['discounts']['vat_rate'] ?? 12);
    }

    private function getRemoveVat(): bool
    {
        $scSettings = $this->settings['pines']['discounts']['sc_pwd'] ?? $this->settings['discounts']['sc_pwd'] ?? [];
        return (bool) ($scSettings['remove_vat'] ?? true);
    }

    public function getExtraPersonCharge(): float
    {
        return (float) ($this->settings['pines']['extra_person']['charge'] ?? self::EXTRA_PERSON_CHARGE);
    }

    /**
     * Resolve whether unit is Deluxe Room or Junior Suite from roomType/rate desc
     */
    public function resolvePinesUnitType(string $roomType = '', string $rateCode = ''): string
    {
        $rt = strtolower($roomType . ' ' . $rateCode);
        if (str_contains($rt, 'suite') || str_contains($rt, 'js') || str_contains($rt, 'junior') || str_contains($rt, 'jrs')) {
            return 'JUNIOR SUITE';
        }
        return 'DELUXE ROOM';
    }

    /**
     * Detect GRATE / MRATE from list rate label or rateCode.
     * Returns 'GRATE', 'MRATE', or null when not applicable (e.g. FVN).
     */
    public function resolvePinesRateKind(array $passenger): ?string
    {
        $candidates = [
            is_string($passenger['rate'] ?? null) ? $passenger['rate'] : '',
            $passenger['list_rate'] ?? '',
            $passenger['rate_label'] ?? '',
            $passenger['rateCode'] ?? '',
            $passenger['ratecode'] ?? '',
            $passenger['rate_code'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            $u = strtoupper(trim((string) $candidate));
            if ($u === '') {
                continue;
            }
            if ($u === 'GRATE' || str_starts_with($u, 'GRATE')) {
                return 'GRATE';
            }
            if ($u === 'MRATE' || str_starts_with($u, 'MRATE')) {
                return 'MRATE';
            }
        }

        return null;
    }

    public function isExtraOccupant(int $occupantIndex, string $roomType = ''): bool
    {
        $basePax = (int) ($this->settings['pines']['extra_person']['base_pax'] ?? 2);
        return $occupantIndex >= $basePax;
    }

    /**
     * Get Base Nightly Rate for Pines Unit Type based on Member vs Guest status and member unit count limit (max 3)
     */
    public function getBasePinesRate(string $unitType, bool $isMember = true, int $memberUnitCount = 1, bool $fvnUtilized = true): float
    {
        $maxUnits = (int) ($this->settings['pines']['rules']['max_member_units'] ?? 3);
        $eligibleForMemberRate = $isMember && $fvnUtilized && ($memberUnitCount <= $maxUnits);

        $suiteMember  = (float) ($this->settings['pines']['rates']['suite_member'] ?? self::SUITE_MEMBER_RATE);
        $suiteGuest   = (float) ($this->settings['pines']['rates']['suite_guest'] ?? self::SUITE_GUEST_RATE);
        $deluxeMember = (float) ($this->settings['pines']['rates']['deluxe_member'] ?? self::DELUXE_MEMBER_RATE);
        $deluxeGuest  = (float) ($this->settings['pines']['rates']['deluxe_guest'] ?? self::DELUXE_GUEST_RATE);

        if ($unitType === 'JUNIOR SUITE') {
            return $eligibleForMemberRate ? $suiteMember : $suiteGuest;
        }

        return $eligibleForMemberRate ? $deluxeMember : $deluxeGuest;
    }

    /**
     * Known published room rates (without extra person).
     */
    public function publishedRoomBases(): array
    {
        $deluxeMember = (float) ($this->settings['pines']['rates']['deluxe_member'] ?? self::DELUXE_MEMBER_RATE);
        $deluxeGuest  = (float) ($this->settings['pines']['rates']['deluxe_guest'] ?? self::DELUXE_GUEST_RATE);
        $suiteMember  = (float) ($this->settings['pines']['rates']['suite_member'] ?? self::SUITE_MEMBER_RATE);
        $suiteGuest   = (float) ($this->settings['pines']['rates']['suite_guest'] ?? self::SUITE_GUEST_RATE);

        return [
            $deluxeMember,
            $deluxeGuest,
            $suiteMember,
            $suiteGuest,
        ];
    }

    /**
     * If amount is a published base + extra person (e.g. 21500+3700),
     * or a known composite rateCode (25500 = Junior Suite GRATE + extra),
     * return ['base' => roomRate, 'extra' => 3700]; otherwise null.
     */
    public function splitCompositeRoomRate(float $amount): ?array
    {
        $extraPersonCharge = $this->getExtraPersonCharge();
        if ($amount <= $extraPersonCharge) {
            return null;
        }

        $suiteGuest   = (float) ($this->settings['pines']['rates']['suite_guest'] ?? self::SUITE_GUEST_RATE);
        $deluxeGuest  = (float) ($this->settings['pines']['rates']['deluxe_guest'] ?? self::DELUXE_GUEST_RATE);
        $suiteMember  = (float) ($this->settings['pines']['rates']['suite_member'] ?? self::SUITE_MEMBER_RATE);
        $deluxeMember = (float) ($this->settings['pines']['rates']['deluxe_member'] ?? self::DELUXE_MEMBER_RATE);

        // Explicit API composite codes (business mapping; may not equal base+extra arithmetically)
        $knownComposites = [
            // Junior Suite GRATE + extra person
            25500.0 => $suiteGuest,
            // Exact base+extra variants
            (float) ($suiteGuest + $extraPersonCharge)   => $suiteGuest,
            (float) ($deluxeGuest + $extraPersonCharge)  => $deluxeGuest,
            (float) ($suiteMember + $extraPersonCharge)  => $suiteMember,
            (float) ($deluxeMember + $extraPersonCharge) => $deluxeMember,
        ];

        foreach ($knownComposites as $composite => $base) {
            if ($amount > 0 && abs($amount - $composite) < 0.05) {
                return [
                    'base'  => (float) $base,
                    'extra' => (float) $extraPersonCharge,
                ];
            }
        }

        foreach ($this->publishedRoomBases() as $base) {
            if ($amount > 0 && abs($amount - ($base + $extraPersonCharge)) < 0.05) {
                return [
                    'base'  => (float) $base,
                    'extra' => (float) $extraPersonCharge,
                ];
            }
        }

        return null;
    }

    /**
     * Resolve composite (base+extra) from daily val and/or numeric rateCode.
     */
    public function resolveCompositeFromPassenger(array $passenger, float $dailyVal = 0.0): ?array
    {
        $candidates = [$dailyVal];

        foreach (['rateCode', 'ratecode', 'rate_code'] as $key) {
            $raw = $passenger[$key] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }
            if (is_numeric($raw)) {
                $candidates[] = (float) $raw;
            }
        }

        foreach ($candidates as $amt) {
            $split = $this->splitCompositeRoomRate((float) $amt);
            if ($split !== null) {
                return $split;
            }
        }

        return null;
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

        $hasVillaSc = isset($passenger['sc_target_villa']) ? (bool) $passenger['sc_target_villa'] : $hasPrivCard;

        $unitType = $this->resolvePinesUnitType($roomType, $passenger['rateCode'] ?? '');
        $rateKind = $this->resolvePinesRateKind($passenger);
        $isExtraPerson = ($occupantIndex >= 2); // 3rd occupant is index 2

        // GRATE / MRATE force guest vs member published rates (independent of gstType)
        if ($rateKind === 'GRATE') {
            $isMember = false;
        } elseif ($rateKind === 'MRATE') {
            $isMember = true;
        }

        $accDates = [];
        $totalAcc = 0;

        foreach ($passenger['rate'] ?? [] as $r) {
            $val  = (float) ($r['val'] ?? 0);
            $date = $r['date'] ?? null;
            if (!$date) continue;

            // ── Detect FVN magic numbers ──────────────────────
            // NOTE: do NOT use float keys in PHP arrays (0.01/0.02/0.03 all cast to int 0)
            $integerPart = floor($val);
            $fraction = round($val - $integerPart, 2);
            $fvnMapping = [
                '0.01' => 1.0,   // 1 FVN
                '0.02' => 1.5,   // 1.5 FVN
                '0.03' => 0.5,   // 0.5 FVN
            ];
            $fracKey = number_format($fraction, 2, '.', '');

            if (isset($fvnMapping[$fracKey])) {
                $fvnRate = $fvnMapping[$fracKey];
                $paidAmount = ($fracKey === '0.03') ? $integerPart : 0;

                if ($isExtraPerson) {
                    // Extra person NEVER gets FVN — always charge ₱3,700
                    $baseFee = $this->getExtraPersonCharge();
                    if ($hasVillaSc) {
                        $scDisc        = $this->getScDiscountPercent();
                        $vatRate       = $this->getVatRate();
                        $removeVat     = $this->getRemoveVat();
                        $vatExemptBase = $removeVat ? ($baseFee / (1 + ($vatRate / 100))) : $baseFee;
                        $discountAmt   = $vatExemptBase * ($scDisc / 100);
                        $netBase       = $vatExemptBase - $discountAmt;
                        $scAmt         = $netBase * 0.10;
                        $finalVal      = round($netBase + $scAmt, 2);
                        $displaySc     = round($scAmt, 2);
                        $displayVat    = 0;
                        $displayDisc   = round($discountAmt, 2);
                    } else {
                        $finalVal    = $baseFee;
                        $displaySc   = round(($baseFee / 1.22) * 0.10, 2);
                        $displayVat  = round(($baseFee / 1.22) * 0.12, 2);
                        $displayDisc = 0;
                    }
                    $totalAcc += $finalVal;
                    $accDates[$date] = [
                        'val'       => $finalVal,
                        'breakdown' => [
                            'gross_share'   => $baseFee,
                            'is_discounted' => $hasVillaSc,
                            'is_infant'     => false,
                            'divisor'       => 1,
                            'base'          => $hasVillaSc ? round($netBase, 2) : $finalVal,
                            'discount'      => $displayDisc,
                            'sc'            => $displaySc,
                            'vat'           => $displayVat,
                            'is_fvn'        => false,
                        ]
                    ];
                    continue;
                }

                $baseDivisor = max(1, min(2, $totalBillable));
                if ($fracKey === '0.03') {
                    // For .03: divide the paid portion among base occupants
                    $finalVal = round($paidAmount / $baseDivisor, 2);
                } else {
                    // For .01/.02: these are magic FVN code markers — DO NOT divide or alter them
                    // They must remain 0.01 / 0.02 so the display logic can map to "1 FVN" / "1.5 FVN"
                    $finalVal = $fraction;
                }
                $divisor = $baseDivisor;

                $grossShare = ($fracKey === '0.03') ? $paidAmount : $val;

                $accDates[$date] = [
                    'val'       => $finalVal,
                    'breakdown' => [
                        'gross_share'   => $grossShare,
                        'is_discounted' => false,
                        'is_infant'     => false,
                        'divisor'       => $divisor,
                        'base'          => $finalVal,
                        'discount'      => 0,
                        'sc'            => 0,
                        'vat'           => 0,
                        'is_fvn'        => true,
                        'fvn_rate'      => $fvnRate,
                        'paid_amount'   => ($fracKey === '0.03') ? $paidAmount : 0,
                        'fraction'      => $fraction,
                    ]
                ];
                // Only add real money to totalAcc — FVN fraction markers (0.01/0.02) are ₱0.00
                // For 0.03: add the integer paid portion divided by base occupants
                if ($fracKey === '0.03') {
                    $totalAcc += round($paidAmount / $baseDivisor, 2);
                }
                continue; // Skip the rest of the loop for FVN
            }

            // ── Non‑FVN (normal processing) ──────────────────
            $grossVal = $val;

            // GRATE / MRATE: force published Pines rates by unit type
            if ($rateKind === 'GRATE' || $rateKind === 'MRATE') {
                $grossVal = $this->getBasePinesRate(
                    $unitType,
                    $rateKind === 'MRATE',
                    $memberUnitCount,
                    true
                );
            }

            // Composite rate (e.g. rateCode/val 25500 = 21500 Junior Suite + 3700 extra):
            // base occupants use room rate only; extra person uses 3700.
            $composite = $this->resolveCompositeFromPassenger($passenger, $val);
            if ($composite !== null && !$isExtraPerson) {
                $grossVal = $composite['base'];
            }

            // Suite fallback rule (only for non-FVN rates)
            if ($unitType === 'JUNIOR SUITE' && $isMember && ($remainingFvn == 0.5 || $remainingFvn == 1.0) && $grossVal > 0) {
                $suiteFallbackEnabled = $this->settings['pines']['rules']['suite_fvn_fallback'] ?? true;
                if ($suiteFallbackEnabled) {
                    $deluxeMember = (float) ($this->settings['pines']['rates']['deluxe_member'] ?? self::DELUXE_MEMBER_RATE);
                    $grossVal = $deluxeMember;
                }
            }

            $baseFee = $this->getExtraPersonCharge();
            $individualShare = 0.0;

            $scDisc    = $this->getScDiscountPercent();
            $vatRate   = $this->getVatRate();
            $removeVat = $this->getRemoveVat();

            if ($isExtraPerson) {
                // Extra person charge is 3,700 (same for GRATE and MRATE)
                if ($hasVillaSc) {
                    $vatExemptBase = $removeVat ? ($baseFee / (1 + ($vatRate / 100))) : $baseFee;
                    $discountAmt   = $vatExemptBase * ($scDisc / 100);
                    $netBase       = $vatExemptBase - $discountAmt;
                    $scAmt         = $netBase * 0.10;
                    $finalVal      = round($netBase + $scAmt, 2);
                    $displaySc     = round($scAmt, 2);
                    $displayDiscount = round($discountAmt, 2);
                } else {
                    $finalVal = $baseFee;
                }
            } else {
                // Base occupants (2 pax): divide room rate by base billable occupants
                $baseDivisor = max(1, min(2, $totalBillable));
                $individualShare = round($grossVal / $baseDivisor, 2);

                if ($hasVillaSc) {
                    $vatExemptBase = $removeVat ? ($individualShare / (1 + ($vatRate / 100))) : $individualShare;
                    $discountAmt   = $vatExemptBase * ($scDisc / 100);
                    $netBase       = $vatExemptBase - $discountAmt;
                    $scAmt         = $netBase * 0.10;
                    $finalVal      = round($netBase + $scAmt, 2);
                    $displaySc     = round($scAmt, 2);
                    $displayDiscount = round($discountAmt, 2);
                } else {
                    $finalVal = $individualShare;
                }
            }

            $totalAcc += $finalVal;

            // Compute SC / VAT / discount amounts for breakdown modal display
            if ($isExtraPerson) {
                $displayGross = $baseFee;
                if ($hasVillaSc) {
                    $displayVatExempt = $removeVat ? round($baseFee / (1 + ($vatRate / 100)), 2) : $baseFee;
                    $displayDiscount  = round($displayVatExempt * ($scDisc / 100), 2);
                    $displaySc        = round(($displayVatExempt - $displayDiscount) * 0.10, 2);
                    $displayVat       = 0;
                } else {
                    $displayDiscount  = 0;
                    $displaySc        = round(($baseFee / 1.22) * 0.10, 2);
                    $displayVat       = round(($baseFee / 1.22) * 0.12, 2);
                }
            } else {
                $displayGross = $grossVal;
                if ($hasVillaSc) {
                    // Breakdown modal shows full room rate (not per-occupant share)
                    $displayVatExempt = $removeVat ? round($grossVal / (1 + ($vatRate / 100)), 2) : $grossVal;
                    $displayDiscount  = round($displayVatExempt * ($scDisc / 100), 2);
                    $displaySc        = round(($displayVatExempt - $displayDiscount) * 0.10, 2);
                    $displayVat       = 0;
                    $displayBase      = round($displayVatExempt - $displayDiscount, 2);
                } else {
                    $displayDiscount  = 0;
                    $displayBase      = round($grossVal / (1 + ($vatRate / 100)), 2);
                    $displaySc        = round($displayBase * 0.10, 2);
                    $displayVat       = round($displayBase * ($vatRate / 100), 2);
                }
            }

            $accDates[$date] = [
                'val'       => $finalVal,
                'breakdown' => [
                    'gross_share'   => $displayGross,
                    'is_discounted' => $hasVillaSc,
                    'is_infant'     => false,
                    'divisor'       => $isExtraPerson ? 1 : max(1, min(2, $totalBillable)),
                    'base'          => $isExtraPerson ? ($hasVillaSc ? round($vatExemptBase - $discountAmt, 2) : $finalVal) : ($displayBase ?? $finalVal),
                    'discount'      => $hasVillaSc
                        ? ($isExtraPerson ? round(round($baseFee / 1.12, 2) * 0.20, 2) : $displayDiscount)
                        : 0,
                    'sc'            => $displaySc,
                    'vat'           => $displayVat,
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
