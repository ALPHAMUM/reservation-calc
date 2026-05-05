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
                'member' => 4200,
                'guest' => 6200,
            ],
            'promo_rates' => [
                'active' => true,
                'member' => 4200,
                'guest' => 6200,
                'start_date' => '2026-02-23',
                'end_date' => '2026-11-30',
                'peak_periods' => [
                    // Array of ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
                ]
            ],
            'fees' => [
                'hangar' => [
                    'amount' => 400,
                    'apply_to' => ['member', 'guest'], // exclude infant
                ],
                'aof' => [
                    'amount' => 2000,
                    'apply_to' => ['member', 'guest'], // exclude infant
                    'active_months' => [4], // April
                ],
                'environmental' => [
                    'amount' => 200,
                    'apply_to' => ['guest'], // applies to guest (including infants), exclude member/spouse
                ]
            ],
            'discounts' => [
                'vat_rate' => 12,
                'sc_pwd' => [
                    'remove_vat' => true,
                    'additional_discount_percent' => 0,
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
