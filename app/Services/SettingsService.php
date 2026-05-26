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
                'member' => 5600,
                'guest' => 12320,
                'employee' => 2800,
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
                    'amount' => 2000,
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
