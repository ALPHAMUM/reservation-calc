<?php

namespace App\Services;

class RateCalculatorService
{
    protected $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->getSettings();
    }

    public function calculatePassengerRates(array $passenger)
    {
        // Default to extracting 'acc' from the API's rate array, ignore others
        $acc = 0;
        foreach ($passenger['rate'] ?? [] as $r) {
            $v = (float)($r['val'] ?? 0); 
            $d = strtolower($r['desc'] ?? '');
            if (!str_contains($d, 'airfare') && 
                !str_contains($d, 'hangar') && 
                !str_contains($d, 'aviation') && 
                !str_contains($d, 'environmental')) {
                $acc += $v;
            }
        }

        $gstTypeRaw = trim($passenger['gstType'] ?? $passenger['gst_type'] ?? 'Guest');
        $gstType = strtolower($gstTypeRaw);
        
        $isMember = false;
        $isEmployee = false;
        $isInfant = false;

        $privCardStr = strtolower(trim((string)($passenger['privCard'] ?? '')));
        $hasPrivCard = !empty($privCardStr) && !in_array($privCardStr, ['n', 'no', 'false', '0', 'none', 'null']);
        
        if (str_contains($gstType, 'senior') || str_contains($gstType, 'pwd')) {
            $hasPrivCard = true;
        }

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

        if (str_contains($gstType, 'infant')) {
            $isInfant = true;
        }

        $arrDate = $passenger['arrdt'] ?? $passenger['arrDt'] ?? null;
        
        // Base Airfare
        $airfare = 0;
        if ($isInfant) {
            $airfare = 0;
        } else {
            $isPeak = true; 
            
            if (!empty($this->settings['promo_rates']['active']) && $arrDate) {
                $promoStart = $this->settings['promo_rates']['start_date'] ?? '';
                $promoEnd = $this->settings['promo_rates']['end_date'] ?? '';
                
                if ($arrDate >= $promoStart && $arrDate <= $promoEnd) {
                    $isPeak = false;
                    foreach ($this->settings['promo_rates']['peak_periods'] ?? [] as $peak) {
                        if ($arrDate >= $peak['start'] && $arrDate <= $peak['end']) {
                            $isPeak = true;
                            break;
                        }
                    }
                }
            }

            $passengerClass = 'guest';
            if ($isEmployee) $passengerClass = 'employee';
            elseif ($isMember) $passengerClass = 'member';

            if ($isPeak) {
                $airfare = (float)($this->settings['base_rates'][$passengerClass] ?? 0);
            } else {
                $airfare = (float)($this->settings['promo_rates'][$passengerClass] ?? 0);
            }

            // Apply SC/PWD Discounts (Only if not employee)
            if ($hasPrivCard && !$isEmployee) {
                $vatRate = (float)($this->settings['discounts']['vat_rate'] ?? 12);
                // VAT removal
                $airfare = $airfare / (1 + ($vatRate / 100));
                
                // Additional 20% only for GUESTS during PEAK
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
        
        // Rules: Infants and Others are always EXEMPT. Members, Guests, and Employees follow settings.
        if (!$isInfant && !$isOthers) {
            if (in_array($passClass, $hangarApplyTo)) {
                $hangar = (float)($this->settings['fees']['hangar']['amount'] ?? 0);
            }
        }

        // AOF Fee
        // Exempt: Infants, Employees (all types), Others (ex-deals, priests, consultants, 
        //         consignors, inspectors, authorized personnel)
        // Applicable: Members and Guests only, during active periods
        $aof = 0;
        $aofApplyTo = $this->settings['fees']['aof']['apply_to'] ?? ['member', 'guest'];
        $activePeriods = $this->settings['fees']['aof']['active_periods'] ?? [];

        $arrTime  = $arrDate ? strtotime($arrDate) : 0;
        $arrMonth = $arrTime ? (int)date('n', $arrTime) : 0;
        $arrYear  = $arrTime ? (int)date('Y', $arrTime) : 0;

        // Hardcoded exemptions — these are NEVER charged AOF regardless of settings
        $aofExempt = $isInfant || $isEmployee || $isOthers;

        if (!$aofExempt) {
            // Check active periods: date range
            $periodApplies = false;
            $aofAmount = (float)($this->settings['fees']['aof']['amount'] ?? 2000);
            
            if (!empty($activePeriods) && $arrDate) {
                foreach ($activePeriods as $p) {
                    $pStart = $p['start'] ?? '';
                    $pEnd   = $p['end']   ?? '';
                    if ($arrDate >= $pStart && $arrDate <= $pEnd) {
                        $periodApplies = true;
                        if (isset($p['amount'])) {
                            $aofAmount = (float)$p['amount'];
                        }
                        break;
                    }
                }
            }
 
            if ($periodApplies && in_array($passClass, $aofApplyTo)) {
                $aof = $aofAmount;
            }
        }

        // Environmental Fee
        $env = 0;
        $envApplyTo = $this->settings['fees']['environmental']['apply_to'] ?? [];
        
        // Rules: Members and Others are always EXEMPT. Guests, Infants, and Employees follow settings.
        if (!$isMember && !$isOthers) {
            // Check if infants are included in settings, or if it's a regular guest/employee
            if (($isInfant && in_array('infant', $envApplyTo)) || 
                (!$isInfant && in_array($passClass, $envApplyTo))) {
                $env = (float)($this->settings['fees']['environmental']['amount'] ?? 0);
            }
        }

        return [
            'acc' => $acc,
            'air' => $airfare,
            'han' => $hangar,
            'avi' => $aof,
            'env' => $env,
            'is_employee' => $isEmployee
        ];
    }
}
