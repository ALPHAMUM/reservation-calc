<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    private const KEY = 'app_settings';

    public function getDefaultSettings(): array
    {
        return [
            'base_rates' => [
                'member' => 10179,
                'guest' => 13571,
                'employee' => 2800,
                'guest_discount_percent' => 20,
                'member_discount_percent' => 40,
                'weekday_extra_discount_enabled' => true,
                'weekday_extra_discount_percent' => 10,
            ],
            'sectoral_destinations' => [
                'Manila - Balesin',
                'Balesin - Manila',
                'Clark - Balesin',
                'Balesin - Clark',
            ],
            'sectoral_rates' => [
                [
                    'destination' => 'Manila - Balesin',
                    'airfare_base_rate' => 8482,
                    'guest_base_rate' => 6786,
                    'member_base_rate' => 5089,
                ],
                [
                    'destination' => 'Balesin - Manila',
                    'airfare_base_rate' => 8482,
                    'guest_base_rate' => 6786,
                    'member_base_rate' => 5089,
                ],
                [
                    'destination' => 'Clark - Balesin',
                    'airfare_base_rate' => 7589,
                    'guest_base_rate' => 6071,
                    'member_base_rate' => 4554,
                ],
                [
                    'destination' => 'Balesin - Clark',
                    'airfare_base_rate' => 7589,
                    'guest_base_rate' => 6071,
                    'member_base_rate' => 4554,
                ],
            ],
            'promo_rates' => [
                'active' => true,
                'member' => 4200,
                'guest' => 6200,
                'employee' => 2800,
                'start_date' => '2026-02-23',
                'end_date' => '2026-11-30',
                'description' => '',
                'peak_periods' => [
                    ['label' => 'Holy Week', 'start' => '2026-03-29', 'end' => '2026-04-05'],
                    ['label' => 'Halloween', 'start' => '2026-10-30', 'end' => '2026-11-02'],
                    ['label' => 'Christmas/New Year', 'start' => '2026-12-20', 'end' => '2027-01-05'],
                ],
            ],
            'fees' => [
                'hangar' => [
                    'amount' => 400,
                    'apply_to' => ['member', 'guest'],
                ],
                'aof' => [
                    'apply_to' => ['member', 'guest'],
                    'active_periods' => [
                        ['start' => '2026-04-01', 'end' => '2026-05-31', 'amount' => 2000],
                        ['start' => '2026-06-01', 'end' => '2026-12-31', 'guest_amount' => 2500, 'member_amount' => 2200, 'amount' => 2500],
                    ],
                ],
                'environmental' => [
                    'amount' => 200,
                    'apply_to' => ['guest', 'infant'],
                ],
            ],
            'discounts' => [
                'vat_rate' => 12,
                'sc_pwd' => [
                    'remove_vat' => true,
                    'apply_discount' => true,
                    'additional_discount_percent' => 20,
                ],
            ],
            'infants' => [
                'max_age_months' => 23,
                'airfare_free' => true,
            ],
            'extra_occupants' => [
                'villa' => [
                    'override' => false,
                    'amount' => 3700,
                ],
                'suite' => [
                    'override' => false,
                    'amount' => 3700,
                ]
            ],

            // Balesin City Defaults
            'city' => [
                'rates' => [
                    'studio_member' => 6400,
                    'studio_guest' => 8000,
                    'twobed_member' => 9250,
                    'twobed_guest' => 12500,
                ],
                'extra_person' => [
                    'rate' => 1300,
                    'studio_base_pax' => 4,
                    'twobed_base_pax' => 6,
                ],
                'promo_rates' => [
                    'active' => false,
                    'description' => '',
                    'studio_member' => 5500,
                    'studio_guest' => 7000,
                    'twobed_member' => 8000,
                    'twobed_guest' => 11000,
                    'start_date' => '',
                    'end_date' => '',
                    'peak_periods' => [],
                ],
                'discounts' => [
                    'vat_rate' => 12,
                    'sc_pwd' => [
                        'remove_vat' => true,
                        'apply_discount' => true,
                        'additional_discount_percent' => 20,
                    ],
                ],
            ],

            // Balesin Pines Defaults
            'pines' => [
                'rates' => [
                    'deluxe_member' => 10000,
                    'deluxe_guest' => 14500,
                    'suite_member' => 15000,
                    'suite_guest' => 21500,
                ],
                'extra_person' => [
                    'charge' => 3700,
                    'base_pax' => 2,
                ],
                'rules' => [
                    'max_member_units' => 3,
                    'suite_fvn_fallback' => true,
                    'extra_person_fvn_charge' => true,
                ],
                'promo_rates' => [
                    'active' => false,
                    'description' => '',
                    'deluxe_member' => 8500,
                    'deluxe_guest' => 12500,
                    'suite_member' => 13000,
                    'suite_guest' => 18500,
                    'start_date' => '',
                    'end_date' => '',
                    'peak_periods' => [],
                ],
                'discounts' => [
                    'vat_rate' => 12,
                    'sc_pwd' => [
                        'remove_vat' => true,
                        'apply_discount' => true,
                        'additional_discount_percent' => 20,
                    ],
                ],
            ],
        ];
    }

    public function getSettings(): array
    {
        try {
            $stored = Setting::getValue(self::KEY);
            if ($stored && is_array($stored)) {
                $defaults = $this->getDefaultSettings();

                // For apply_to arrays, we want to OVERWRITE defaults, not merge
                // array_replace_recursive can sometimes cause issues with sequential arrays
                foreach (['hangar', 'aof', 'environmental'] as $fee) {
                    if (isset($stored['fees'][$fee]['apply_to']) && isset($defaults['fees'][$fee]['apply_to'])) {
                        // Clear the default so the stored one wins completely
                        unset($defaults['fees'][$fee]['apply_to']);
                    }
                }

                if (isset($stored['sectoral_destinations'])) {
                    unset($defaults['sectoral_destinations']);
                }

                // Upgrade legacy or decimal base_rates to rounded non-VAT sectoral round-trip base rates
                if (isset($stored['base_rates']['member'], $stored['base_rates']['guest'])) {
                    $m = (float)$stored['base_rates']['member'];
                    $g = (float)$stored['base_rates']['guest'];
                    if (($m == 5600 && $g == 12320) || ($m == 11400 && $g == 15200) || abs($m - 10178.57) < 1 || abs($g - 13571.42) < 1) {
                        $stored['base_rates']['member'] = 10179;
                        $stored['base_rates']['guest'] = 13571;
                    } else {
                        $stored['base_rates']['member'] = (int) round($m);
                        $stored['base_rates']['guest'] = (int) round($g);
                    }
                }
                if (isset($stored['base_rates']['employee'])) {
                    $stored['base_rates']['employee'] = (int) round((float)$stored['base_rates']['employee']);
                }

                // Upgrade legacy sectoral_rates if airfare_base_rate is missing or still using 9500/8500, and round to whole numbers
                if (isset($stored['sectoral_rates']) && is_array($stored['sectoral_rates'])) {
                    $hasNonVatBase = false;
                    foreach ($stored['sectoral_rates'] as $sr) {
                        $ab = (float)($sr['airfare_base_rate'] ?? 0);
                        if ($ab > 0 && $ab != 9500 && $ab != 8500) {
                            $hasNonVatBase = true;
                            break;
                        }
                    }
                    if ($hasNonVatBase) {
                        unset($defaults['sectoral_rates']);
                        foreach ($stored['sectoral_rates'] as &$sr) {
                            if (isset($sr['airfare_base_rate'])) {
                                $sr['airfare_base_rate'] = (int) round((float)$sr['airfare_base_rate']);
                            }
                            if (isset($sr['guest_base_rate'])) {
                                $sr['guest_base_rate'] = (int) round((float)$sr['guest_base_rate']);
                            }
                            if (isset($sr['member_base_rate'])) {
                                $sr['member_base_rate'] = (int) round((float)$sr['member_base_rate']);
                            }
                        }
                        unset($sr);
                    } else {
                        unset($stored['sectoral_rates']);
                    }
                }

                // Ensure moving-forward AOF periods have rounded guest_amount (2500) and member_amount (2200)
                if (isset($stored['fees']['aof']['active_periods']) && is_array($stored['fees']['aof']['active_periods'])) {
                    unset($defaults['fees']['aof']['active_periods']);
                    $todayStr = date('Y-m-d');
                    $hasMovingForward = false;
                    foreach ($stored['fees']['aof']['active_periods'] as &$period) {
                        $end = $period['end'] ?? '';
                        $isPast = ($end !== '' && $todayStr > $end);
                        if (!$isPast) {
                            $hasMovingForward = true;
                            $gAmt = isset($period['guest_amount']) ? (int) round((float)$period['guest_amount']) : 2500;
                            $mAmt = isset($period['member_amount']) ? (int) round((float)$period['member_amount']) : 2200;
                            if (($gAmt === 2232 && $mAmt === 1964) || ($gAmt === 2200 && $mAmt === 2500)) {
                                $gAmt = 2500;
                                $mAmt = 2200;
                            }
                            $period['guest_amount'] = $gAmt;
                            $period['member_amount'] = $mAmt;
                            $period['amount'] = $period['guest_amount'];
                        } else {
                            $period['amount'] = (int) round((float)($period['amount'] ?? 0));
                        }
                    }
                    unset($period);

                    if (!$hasMovingForward) {
                        $stored['fees']['aof']['active_periods'][] = [
                            'start' => '2026-06-01',
                            'end' => '2026-12-31',
                            'guest_amount' => 2500,
                            'member_amount' => 2200,
                            'amount' => 2500,
                        ];
                    }
                }

                return array_replace_recursive($defaults, $stored);
            }
        } catch (\Exception $e) {
            // DB not yet available (e.g. first migration) — fall back to defaults
        }

        return $this->getDefaultSettings();
    }

    public function saveSettings(array $settings): void
    {
        Setting::setValue(self::KEY, $settings);
    }
}
