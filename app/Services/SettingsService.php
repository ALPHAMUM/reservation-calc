<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SettingsService
{
    private $filename = 'settings.json';

    public function getDefaultSettings()
    {
        return [
            'base_rates' => [
                'member' => 5600, // Peak Regular
                'guest' => 12320, // Peak Regular
                'employee' => 2800,
            ],
            'promo_rates' => [
                'active' => true,
                'member' => 4200, // Non-Peak Regular
                'guest' => 6200, // Non-Peak Regular
                'employee' => 2800,
                'start_date' => '2026-02-23',
                'end_date' => '2026-11-30',
                'peak_periods' => [
                    ['start' => '2026-03-29', 'end' => '2026-04-05'], // Holy Week 2026 approx
                    ['start' => '2026-10-30', 'end' => '2026-11-02'], // Halloween
                    ['start' => '2026-12-20', 'end' => '2027-01-05'], // Christmas/New Year
                ]
            ],
            'fees' => [
                'hangar' => [
                    'amount' => 400,
                'apply_to' => ['member', 'guest'], // exclude infant, employee, others
                ],
                'aof' => [
                    'amount' => 2000,
                    'apply_to' => ['member', 'guest', 'employee'], // exclude infant
                ],
                'environmental' => [
                    'amount' => 200,
                    'apply_to' => ['guest', 'infant'], // applies to guest (including infants), exclude member/spouse/dependent, employee, others
                ]
            ],
            'discounts' => [
                'vat_rate' => 12,
                'sc_pwd' => [
                    'remove_vat' => true,
                    'additional_discount_percent' => 20, // Guest peak discount
                ]
            ],
            'infants' => [
                'max_age_months' => 23,
                'airfare_free' => true,
            ]
        ];
    }

    public function getSettings()
    {
        if (Storage::exists($this->filename)) {
            $json = Storage::get($this->filename);
            return array_replace_recursive($this->getDefaultSettings(), json_decode($json, true) ?? []);
        }
        return $this->getDefaultSettings();
    }

    public function saveSettings(array $settings)
    {
        Storage::put($this->filename, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
