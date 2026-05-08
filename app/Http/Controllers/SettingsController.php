<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index()
    {
        $settings = $this->settingsService->getSettings();
        return view('setup', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = $this->settingsService->getSettings();
        $input = $request->except('_token');

        // Update Base Rates
        $settings['base_rates']['member'] = (float)($input['base_rates']['member'] ?? $settings['base_rates']['member']);
        $settings['base_rates']['guest'] = (float)($input['base_rates']['guest'] ?? $settings['base_rates']['guest']);
        $settings['base_rates']['employee'] = (float)($input['base_rates']['employee'] ?? $settings['base_rates']['employee'] ?? 2800);

        // Update Promo Rates
        $settings['promo_rates']['active'] = isset($input['promo_rates']['active']);
        $settings['promo_rates']['description'] = $input['promo_rates']['description'] ?? '';
        $settings['promo_rates']['member'] = (float)($input['promo_rates']['member'] ?? $settings['promo_rates']['member']);
        $settings['promo_rates']['guest'] = (float)($input['promo_rates']['guest'] ?? $settings['promo_rates']['guest']);
        $settings['promo_rates']['employee'] = (float)($input['promo_rates']['employee'] ?? $settings['promo_rates']['employee'] ?? 2800);
        $settings['promo_rates']['start_date'] = $input['promo_rates']['start_date'] ?? $settings['promo_rates']['start_date'];
        $settings['promo_rates']['end_date'] = $input['promo_rates']['end_date'] ?? $settings['promo_rates']['end_date'];
        
        // Handle Peak Periods
        $peakPeriods = [];
        if (isset($input['peak_periods']['start']) && isset($input['peak_periods']['end'])) {
            foreach ($input['peak_periods']['start'] as $index => $start) {
                if (!empty($start) && !empty($input['peak_periods']['end'][$index])) {
                    $peakPeriods[] = [
                        'label' => $input['peak_periods']['label'][$index] ?? '',
                        'start' => $start,
                        'end' => $input['peak_periods']['end'][$index],
                    ];
                }
            }
        }
        $settings['promo_rates']['peak_periods'] = $peakPeriods;

        // Fees - Hangar
        $settings['fees']['hangar']['amount'] = (float)($input['fees']['hangar']['amount'] ?? $settings['fees']['hangar']['amount']);
        $settings['fees']['hangar']['apply_to'] = $input['fees']['hangar']['apply_to'] ?? [];

        // Fees - AOF
        $settings['fees']['aof']['amount'] = (float)($input['fees']['aof']['amount'] ?? $settings['fees']['aof']['amount']);
        $settings['fees']['aof']['apply_to'] = $input['fees']['aof']['apply_to'] ?? [];
        
        $aofPeriods = [];
        if (isset($input['fees']['aof']['active_periods']['start']) && isset($input['fees']['aof']['active_periods']['end'])) {
            foreach ($input['fees']['aof']['active_periods']['start'] as $idx => $start) {
                if (!empty($start) && !empty($input['fees']['aof']['active_periods']['end'][$idx])) {
                    $aofPeriods[] = [
                        'start' => $start,
                        'end' => $input['fees']['aof']['active_periods']['end'][$idx],
                        'amount' => (float)($input['fees']['aof']['active_periods']['amount'][$idx] ?? $settings['fees']['aof']['amount'])
                    ];
                }
            }
        }
        $settings['fees']['aof']['active_periods'] = $aofPeriods;
        // Clean up old active_months if it exists
        unset($settings['fees']['aof']['active_months']);

        // Fees - Environmental
        $settings['fees']['environmental']['amount'] = (float)($input['fees']['environmental']['amount'] ?? $settings['fees']['environmental']['amount']);
        $settings['fees']['environmental']['apply_to'] = $input['fees']['environmental']['apply_to'] ?? [];

        // Discounts
        $settings['discounts']['vat_rate'] = (float)($input['discounts']['vat_rate'] ?? $settings['discounts']['vat_rate']);
        $settings['discounts']['sc_pwd']['remove_vat'] = isset($input['discounts']['sc_pwd']['remove_vat']);
        $settings['discounts']['sc_pwd']['additional_discount_percent'] = (float)($input['discounts']['sc_pwd']['additional_discount_percent'] ?? 0);

        // Infants
        $settings['infants']['airfare_free'] = isset($input['infants']['airfare_free']);

        $this->settingsService->saveSettings($settings);

        return back()->with('success', 'Settings successfully updated.');
    }
}
