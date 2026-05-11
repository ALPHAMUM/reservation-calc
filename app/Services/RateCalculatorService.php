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
        $age = (int)($passenger['age'] ?? 99);
        $isInfant = str_contains($gstType, 'infant') || (isset($passenger['age']) && $age >= 0 && $age <= 1);
        
        $isMember = false;
        $isEmployee = false;
        $hasPrivCard = false;

        $custName = strtoupper($passenger['custName'] ?? $passenger['custname'] ?? $passenger['customer'] ?? '');
        
        $isOthers = false;
        if (str_contains($gstType, 'member') || str_contains($gstType, 'spouse') || str_contains($gstType, 'dependent')) {
            $isMember = true;
        } elseif (str_contains($gstType, 'employee') || 
                 (isset($passenger['rate_metadata']) && str_contains(strtoupper($passenger['rate_metadata']), 'EMPLOYEE')) ||
                 str_contains($custName, 'ALPHALAND EMPLOYEE')) {
            $isEmployee = true;
        } elseif (str_contains($gstType, 'others') || str_contains($gstType, 'authorized') || str_contains($gstType, 'priest') || str_contains($gstType, 'contractor')) {
            $isOthers = true;
        }

        $privCardStr = strtolower(trim((string)($passenger['privCard'] ?? '')));
        if ($privCardStr === 'senior citizen' || $privCardStr === 'pwd' || str_contains($gstType, 'senior') || str_contains($gstType, 'pwd')) {
            $hasPrivCard = true;
        }

        $acc = 0;
        $accDates = [];
        $isVilla = true; 
        $roomTypeUpper = strtoupper($roomType);
        if (str_contains($roomTypeUpper, 'RGNCY') || str_contains($roomTypeUpper, 'ROYL') || str_contains($roomTypeUpper, 'PV8') || str_contains($roomTypeUpper, 'SUITE')) {
            $isVilla = false;
        }
        
        $basePax = $isVilla ? 4 : 8;
        $isExtra = ($paxIndex >= $basePax);
        
        // Divisor is the total billable pax, but capped at the unit capacity for the "base" share
        $divisor = min($totalBillable, $basePax);
        if ($divisor < 1) $divisor = 1;

        foreach ($passenger['rate'] ?? [] as $r) {
            $v = (float)($r['val'] ?? 0); 
            $d = strtolower($r['desc'] ?? '');
            $date = $r['date'] ?? null;

            if ($date && !str_contains($d, 'airfare') && 
                !str_contains($d, 'hangar') && 
                !str_contains($d, 'aviation') && 
                !str_contains($d, 'environmental')) {
                
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

                if (!$isInfant) {
                    $grossAmount = 0;
                    if (!$isExtra) {
                        $unitRate = $this->getAccommodationUnitRate($date, $isVilla, $isMember);
                        $grossAmount = $unitRate / $divisor;
                    } else {
                        $grossAmount = $this->getExtraPersonRate($date, $isMember);
                    }

                    $breakdown['gross_share'] = $grossAmount;

                    if ($hasPrivCard) {
                        $breakdown['is_discounted'] = true;
                        $baseForDisc = $grossAmount / 1.22;
                        $discount = $baseForDisc * 0.2;
                        $netBase = $baseForDisc - $discount;
                        $sc = $netBase * 0.1;
                        
                        $breakdown['base'] = $baseForDisc;
                        $breakdown['discount'] = $discount;
                        $breakdown['sc'] = $sc;
                        $dateRate = $netBase + $sc;
                    } else {
                        $base = $grossAmount / 1.22;
                        $sc = $base * 0.1;
                        $vat = $base * 0.12;
                        
                        $breakdown['base'] = $base;
                        $breakdown['sc'] = $sc;
                        $breakdown['vat'] = $vat;
                        $dateRate = $grossAmount;
                    }
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
            if ($isOthers) $passengerClass = 'others';
            
            $isPeak = false; // Add peak logic if needed

            if ($isPeak) {
                $airfare = (float)($this->settings['peak_rates'][$passengerClass] ?? 0);
            } else {
                $airfare = (float)($this->settings['promo_rates'][$passengerClass] ?? 0);
            }

            if ($hasPrivCard && !$isEmployee) {
                $vatRate = (float)($this->settings['discounts']['vat_rate'] ?? 12);
                $airfare = $airfare / (1 + ($vatRate / 100));
                if ($passengerClass === 'guest' && $isPeak) {
                    $airfare = $airfare * 0.8; 
                }
            }
        }

        // Hangar Fee
        $hangar = 0;
        $hangarApplyTo = $this->settings['fees']['hangar']['apply_to'] ?? [];
        $passClass = $isEmployee ? 'employee' : ($isMember ? 'member' : 'guest');
        if ($isOthers) $passClass = 'others';
        
        if (!$isInfant && !$isOthers) {
            if (in_array($passClass, $hangarApplyTo)) {
                $hangar = (float)($this->settings['fees']['hangar']['amount'] ?? 0);
            }
        }

        // AOF Fee
        $aof = 0;
        $aofApplyTo = $this->settings['fees']['aof']['apply_to'] ?? ['member', 'guest'];
        if (!$isInfant && !$isEmployee && !$isOthers) {
            if (in_array($passClass, $aofApplyTo)) {
                $aof = (float)($this->settings['fees']['aof']['amount'] ?? 0);
            }
        }

        // Environmental Fee
        $env = 0;
        if (!$isInfant && !$isEmployee && !$isOthers) {
            $env = (float)($this->settings['fees']['environmental']['amount'] ?? 0);
        }

        return [
            'acc' => $acc,
            'acc_dates' => $accDates,
            'air' => $airfare,
            'han' => $hangar,
            'avi' => $aof,
            'env' => $env,
            'is_employee' => $isEmployee
        ];
    }

    private function getAccommodationUnitRate($date, $isVilla, $isMember)
    {
        $dayOfWeek = date('w', strtotime($date));
        $isWeekend = ($dayOfWeek == 5 || $dayOfWeek == 6);

        if ($isMember) {
            if ($isVilla) return $isWeekend ? 14000 : 10000;
            return $isWeekend ? 21000 : 15000;
        } else {
            if ($isVilla) return $isWeekend ? 29500 : 22500;
            return $isWeekend ? 45500 : 32500;
        }
    }

    private function getExtraPersonRate($date, $isMember)
    {
        return 3700;
    }
}
