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
                'member' => 11400,
                'guest' => 15200,
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
                    'airfare_base_rate' => 9500,
                    'guest_base_rate' => 7600,
                    'member_base_rate' => 5700,
                ],
                [
                    'destination' => 'Balesin - Manila',
                    'airfare_base_rate' => 9500,
                    'guest_base_rate' => 7600,
                    'member_base_rate' => 5700,
                ],
                [
                    'destination' => 'Clark - Balesin',
                    'airfare_base_rate' => 8500,
                    'guest_base_rate' => 6800,
                    'member_base_rate' => 5100,
                ],
                [
                    'destination' => 'Balesin - Clark',
                    'airfare_base_rate' => 8500,
                    'guest_base_rate' => 6800,
                    'member_base_rate' => 5100,
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

                // Upgrade legacy base_rates (5600 / 12320) to new round-trip base rates (11400 / 15200)
                if (
                    isset($stored['base_rates']['member'], $stored['base_rates']['guest']) &&
                    (float)$stored['base_rates']['member'] == 5600 &&
                    (float)$stored['base_rates']['guest'] == 12320 &&
                    !isset($stored['base_rates']['guest_discount_percent'])
                ) {
                    $stored['base_rates']['member'] = 11400;
                    $stored['base_rates']['guest'] = 15200;
                }

                // Upgrade legacy sectoral_rates if airfare_base_rate is not yet present
                if (isset($stored['sectoral_rates']) && is_array($stored['sectoral_rates'])) {
                    $hasAirfareBase = false;
                    foreach ($stored['sectoral_rates'] as $sr) {
                        if (isset($sr['airfare_base_rate'])) {
                            $hasAirfareBase = true;
                            break;
                        }
                    }
                    if ($hasAirfareBase) {
                        unset($defaults['sectoral_rates']);
                    } else {
                        unset($stored['sectoral_rates']);
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
