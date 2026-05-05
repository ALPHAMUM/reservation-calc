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

        $gstType = strtolower($passenger['gstType'] ?? 'guest');
        $isMember = in_array($gstType, ['member', 'spouse', 'dependent']); // Generic check
        if ($gstType === 'member') $isMember = true; // explicitly

        $isInfant = !empty($passenger['pup']);
        $hasPrivCard = !empty($passenger['privCard']);
        $arrDate = $passenger['arrdt'] ?? $passenger['arrDt'] ?? null;

        // Base Airfare
        $airfare = 0;
        if ($isInfant && !empty($this->settings['infants']['airfare_free'])) {
            $airfare = 0;
        } else {
            $passengerClass = $isMember ? 'member' : 'guest';
            
            // Determine if promo applies
            $usePromo = false;
            if (!empty($this->settings['promo_rates']['active']) && $arrDate) {
                $promoStart = $this->settings['promo_rates']['start_date'] ?? '';
                $promoEnd = $this->settings['promo_rates']['end_date'] ?? '';
                
                if ($arrDate >= $promoStart && $arrDate <= $promoEnd) {
                    $usePromo = true;
                    // Check if in peak period
                    foreach ($this->settings['promo_rates']['peak_periods'] ?? [] as $peak) {
                        if ($arrDate >= $peak['start'] && $arrDate <= $peak['end']) {
                            $usePromo = false;
                            break;
                        }
                    }
                }
            }

            if ($usePromo) {
                $airfare = (float)($this->settings['promo_rates'][$passengerClass] ?? 0);
            } else {
                $airfare = (float)($this->settings['base_rates'][$passengerClass] ?? 0);
            }

            // Apply SC/PWD Discounts
            if ($hasPrivCard) {
                $vatRate = (float)($this->settings['discounts']['vat_rate'] ?? 12);
                if (!empty($this->settings['discounts']['sc_pwd']['remove_vat'])) {
                    $airfare = $airfare / (1 + ($vatRate / 100));
                }
                
                $additionalDiscount = (float)($this->settings['discounts']['sc_pwd']['additional_discount_percent'] ?? 0);
                if ($additionalDiscount > 0) {
                    $airfare = $airfare * (1 - ($additionalDiscount / 100));
                }
            }
        }

        // Hangar Fee
        $hangar = 0;
        $hangarApplyTo = $this->settings['fees']['hangar']['apply_to'] ?? [];
        if (($isInfant && in_array('infant', $hangarApplyTo)) || 
            (!$isInfant && $isMember && in_array('member', $hangarApplyTo)) || 
            (!$isInfant && !$isMember && in_array('guest', $hangarApplyTo))) {
            $hangar = (float)($this->settings['fees']['hangar']['amount'] ?? 0);
        }

        // AOF Fee
        $aof = 0;
        $aofApplyTo = $this->settings['fees']['aof']['apply_to'] ?? [];
        $activeMonths = $this->settings['fees']['aof']['active_months'] ?? [];
        
        $arrMonth = $arrDate ? (int)date('n', strtotime($arrDate)) : 0;
        
        if (in_array($arrMonth, $activeMonths)) {
            if (($isInfant && in_array('infant', $aofApplyTo)) || 
                (!$isInfant && $isMember && in_array('member', $aofApplyTo)) || 
                (!$isInfant && !$isMember && in_array('guest', $aofApplyTo))) {
                $aof = (float)($this->settings['fees']['aof']['amount'] ?? 0);
            }
        }

        // Environmental Fee
        $env = 0;
        $envApplyTo = $this->settings['fees']['environmental']['apply_to'] ?? [];
        if (($isInfant && in_array('infant', $envApplyTo)) || 
            (!$isInfant && $isMember && in_array('member', $envApplyTo)) || 
            (!$isInfant && !$isMember && in_array('guest', $envApplyTo))) {
            $env = (float)($this->settings['fees']['environmental']['amount'] ?? 0);
        }

        return [
            'acc' => $acc,
            'air' => $airfare,
            'han' => $hangar,
            'avi' => $aof,
            'env' => $env,
        ];
    }
}
