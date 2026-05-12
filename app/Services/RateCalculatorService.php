<?php

namespace App\Services;

class RateCalculatorService
{
    protected $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->getSettings();
    }

    public function calculatePassengerRates(array $passenger, $paxIndex = 0, $roomType = '', $totalBillable = 1)
    {
        $gstType = strtolower($passenger['gstType'] ?? $passenger['gsttype'] ?? '');
        $rateMetadata = strtoupper($passenger['rate_metadata'] ?? '');
        $age = (int) ($passenger['age'] ?? 99);
        $isInfant = str_contains($gstType, 'infant') || (isset($passenger['age']) && $age >= 0 && $age <= 1);

        $isMember = false;
        $isEmployee = false;
        $hasPrivCard = false;

        $custName = strtoupper($passenger['custName'] ?? $passenger['custname'] ?? $passenger['customer'] ?? '');

        $isOthers = false;
        // Always treat Spouse and Dependent as Members for airfare and fees
        if ($gstType === 'member' || str_starts_with($gstType, 'member')) {
            $isMember = true;
        } elseif ($gstType === 'spouse' || $gstType === 'dependent') {
            $isMember = true;
        } elseif (
            $gstType === 'employee' || 
            str_contains($gstType, 'employee') ||
            (isset($passenger['rate_metadata']) && str_contains(strtoupper($passenger['rate_metadata']), 'EMPLOYEE')) ||
            str_contains($custName, 'ALPHALAND EMPLOYEE')
        ) {
            $isEmployee = true;
        } elseif (str_contains($gstType, 'others') || str_contains($gstType, 'authorized') || str_contains($gstType, 'priest') || str_contains($gstType, 'contractor')) {
            $isOthers = true;
            $isMember = true; // Treat authorized personnel as members for fee purposes
        }

        $privCardStr = strtolower(trim((string) ($passenger['privCard'] ?? '')));
        if ($privCardStr === 'senior citizen' || $privCardStr === 'pwd' || str_contains($gstType, 'senior') || str_contains($gstType, 'pwd')) {
            $hasPrivCard = true;
        }

        $acc = 0;
        $accDates = [];
        $isVilla = true;
        $roomTypeUpper = strtoupper($roomType);
        $metaUpper = strtoupper($rateMetadata);
        if (str_contains($roomTypeUpper, 'RGNCY') || str_contains($roomTypeUpper, 'ROYL') || str_contains($roomTypeUpper, 'PV8') || str_contains($roomTypeUpper, 'SUITE') ||
            str_contains($metaUpper, 'SUITE') || str_contains($metaUpper, 'ROYL') || str_contains($metaUpper, 'RGNCY')) {
            $isVilla = false;
        }

        $basePax = $isVilla ? 4 : 8;
        $isExtra = ($paxIndex >= $basePax);

        // Divisor is the total billable pax, but capped at the unit capacity for the "base" share
        $divisor = min($totalBillable, $basePax);
        if ($divisor < 1)
            $divisor = 1;

        $fvnApplied = false;
        foreach ($passenger['rate'] ?? [] as $idx => $r) {
            $v = (float) ($r['val'] ?? 0);
            $d = strtolower($r['desc'] ?? '');
            $date = $r['date'] ?? null;

            if (
                $date && !str_contains($d, 'airfare') &&
                !str_contains($d, 'hangar') &&
                !str_contains($d, 'aviation') &&
                !str_contains($d, 'environmental')
            ) {

                $dateRate = 0;
                $breakdown = [
                    'gross_share' => 0,
                    'is_discounted' => false,
                    'is_infant' => $isInfant,
                    'divisor' => $divisor,
                    'base' => 0,
                    'discount' => 0,
                    'sc' => 0,
                    'vat' => 0
                ];

                $isFVN = false;
                $unitRate = 0;
                $originalVal = (float) ($r['val'] ?? 0);
                $isVillaCode = (round($originalVal, 2) == 0.01);
                $isSuiteCode = (round($originalVal, 2) == 0.02);

                if (!$isInfant) {
                    $grossAmount = 0;
                    if (!$isExtra) {

                        // 1. Magic numbers: always honor if sent by API
                        if ($originalVal == 0.01 || $originalVal == 0.02) {
                            $unitRate = 0.01; // Force to .01 as requested
                            $isFVN = true;
                            $fvnApplied = true;
                        }
                        // 2. Automatic detection from metadata only on Day 1 IF the rate is a magic number (not 0)
                        elseif ($idx === 0 && !$fvnApplied && ($originalVal == 0.01 || $originalVal == 0.02)) {
                            $rCodeRaw = $passenger['rateCode'] ?? $passenger['rate_code'] ?? '';
                            $rCode = is_string($rCodeRaw) ? strtoupper($rCodeRaw) : '';

                            $rNameRaw = $passenger['rate'] ?? '';
                            $rName = is_string($rNameRaw) ? strtoupper($rNameRaw) : '';

                            if (
                                str_contains($rateMetadata, 'KEY-VILLA') || str_contains($rName, 'KEY-VILLA') ||
                                str_contains($rateMetadata, '.01') || $rCode === '.01' ||
                                str_contains($rateMetadata, 'KEY-SUITE') || str_contains($rName, 'KEY-SUITE') ||
                                str_contains($rateMetadata, '.02') || $rCode === '.02'
                            ) {
                                $unitRate = 0.01; // Force to .01
                                $isFVN = true;
                                $fvnApplied = true;
                            }
                        }
                        if (!$isFVN) {
                            // Always trust the API value, even if it's 0.00
                            $grossAmount = $originalVal;
                        } else {
                            $grossAmount = $unitRate;
                        }
                    } else {
                        // Always trust the API value for extra persons as well
                        $grossAmount = $originalVal;
                    }

                    if (!$isFVN) {
                        $breakdown['gross_share'] = $grossAmount;

                        if ($hasPrivCard) {
                            $breakdown['is_discounted'] = true;
                            $baseForDisc = $grossAmount / 1.12;
                            $discount = $baseForDisc * 0.2;
                            $netBase = $baseForDisc - $discount;
                            $sc = $netBase * 0.1;

                            $breakdown['base'] = $baseForDisc;
                            $breakdown['discount'] = $discount;
                            $breakdown['sc'] = $sc;
                            $dateRate = $netBase + $sc;
                        } else {
                            $base = $grossAmount / 1.12;
                            $sc = $base * 0.1;
                            $vat = $base * 0.12;

                            $breakdown['base'] = $base;
                            $breakdown['sc'] = $sc;
                            $breakdown['vat'] = $vat;
                            $dateRate = $grossAmount;
                        }
                    } else {
                        $dateRate = $unitRate;
                        $breakdown['gross_share'] = $unitRate;
                        // Populate minimal breakdown to prevent "empty" modal
                        $breakdown['base'] = $unitRate;
                    }
                }

                if ($isFVN) {
                    $breakdown['is_fvn'] = true;
                    $breakdown['fvn_rate'] = $unitRate;
                }

                $acc += $dateRate;
                $accDates[$date] = [
                    'val' => $dateRate,
                    'breakdown' => $breakdown
                ];
            }
        }

        // Airfare Calculation
        $airfare = 0;
        if (isset($passenger['rate_metadata']) && str_contains(strtoupper($passenger['rate_metadata']), 'AIRFARE:EXEMPT')) {
            $airfare = 0;
        } else {
            $passengerClass = $isEmployee ? 'employee' : ($isMember ? 'member' : 'guest');
            if ($isOthers)
                $passengerClass = 'others';

            $isPeak = false; // Add peak logic if needed

            if ($isPeak) {
                $airfare = (float) ($this->settings['peak_rates'][$passengerClass] ?? 0);
            } else {
                $airfare = (float) ($this->settings['promo_rates'][$passengerClass] ?? 0);
            }

            if ($hasPrivCard && !$isEmployee) {
                $vatRate = (float) ($this->settings['discounts']['vat_rate'] ?? 12);
                $airfare = $airfare / (1 + ($vatRate / 100));
                if ($passengerClass === 'guest' && $isPeak) {
                    $airfare = $airfare * 0.8;
                }
            }
        }

        // Determine classification for fee application
        if ($isEmployee) {
            $paxClass = 'employee';
        } elseif ($isMember) {
            $paxClass = 'member';
        } elseif ($isInfant) {
            $paxClass = 'infant';
        } else {
            $paxClass = 'guest';
        }

        // Hangar Fee
        $hangar = 0;
        $hangarApplies = $this->settings['fees']['hangar']['apply_to'] ?? ['member', 'guest'];
        if (in_array($paxClass, $hangarApplies)) {
            $hangar = (float) ($this->settings['fees']['hangar']['amount'] ?? 400);
        }

        // AOF Fee
        $aof = 0;
        $aofApplies = $this->settings['fees']['aof']['apply_to'] ?? ['member', 'guest'];
        if (in_array($paxClass, $aofApplies)) {
            $checkInDate = $passenger['arrdt'] ?? $passenger['arrDt'] ?? null;
            $aof = $this->calculateAof($checkInDate);
        }

        // Environmental Fee
        $env = 0;
        $envApplies = $this->settings['fees']['environmental']['apply_to'] ?? ['guest'];
        // Note: isInfant check is removed here because 'infant' is now a valid paxClass
        if (in_array($paxClass, $envApplies)) {
            $env = (float) ($this->settings['fees']['environmental']['amount'] ?? 200);
        }

        return [
            'acc' => $acc,
            'acc_dates' => $accDates,
            'air' => $airfare,
            'han' => $hangar,
            'avi' => $aof,
            'env' => $env,
            'is_employee' => $isEmployee,
            'base_pax' => $basePax
        ];
    }

    private function getAccommodationUnitRate($date, $isVilla, $isMember)
    {
        $dayOfWeek = date('w', strtotime($date));
        $isWeekend = ($dayOfWeek == 5 || $dayOfWeek == 6);

        // Try to fetch from Database first
        try {
            $prefix = $isMember ? 'M' : 'G';
            $type = $isVilla ? 'VILLA' : 'SUITE';
            $suffix = $isWeekend ? 'WKENDS' : 'WKDAYS';
            $code = "{$prefix}{$type}-{$suffix}";

            $rate = \App\Models\Rate::where('rate_code', $code)->first();
            if ($rate) {
                return (float) $rate->rate_value;
            }
        } catch (\Exception $e) {
            // Fallback to hardcoded if table doesn't exist yet
        }

        if ($isMember) {
            if ($isVilla)
                return $isWeekend ? 14000 : 10000;
            return $isWeekend ? 21000 : 15000;
        } else {
            if ($isVilla)
                return $isWeekend ? 29500 : 22500;
            return $isWeekend ? 45500 : 32500;
        }
    }

    private function getExtraPersonRate($date, $isMember)
    {
        try {
            $rate = \App\Models\Rate::where('rate_code', $isMember ? 'MVILLA-WKDAYS' : 'GVILLA-WKDAYS')->first();
            if ($rate) {
                return (float) ($rate->rate_extra - $rate->rate_value);
            }
        } catch (\Exception $e) {
        }

        return 3700;
    }
    private function calculateAof($date)
    {
        $baseAmount = (float) ($this->settings['fees']['aof']['amount'] ?? 0);
        $activePeriods = $this->settings['fees']['aof']['active_periods'] ?? [];

        $currentDate = strtotime($date);
        foreach ($activePeriods as $period) {
            $start = strtotime($period['start'] ?? '');
            $end = strtotime($period['end'] ?? '');
            if ($start && $end && $currentDate >= $start && $currentDate <= $end) {
                return (float) ($period['amount'] ?? $baseAmount);
            }
        }

        return $baseAmount;
    }
}
