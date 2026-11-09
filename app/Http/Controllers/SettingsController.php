<?php

namespace App\Http\Controllers;

use App\Exports\RatesExport;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request)
    {
        $settings = $this->settingsService->getSettings();
        $activeTab = $request->get('property', $request->get('tab', 'island'));
        if (!in_array($activeTab, ['island', 'city', 'pines'], true)) {
            $activeTab = 'island';
        }
        return view('setup', compact('settings', 'activeTab'));
    }

    public function update(Request $request)
    {
        $settings = $this->settingsService->getSettings();
        $input = $request->except('_token');
        $property = $request->input('property', 'island');

        if ($property === 'city') {
            $cityInput = $input['city'] ?? [];

            // Rates
            $settings['city']['rates']['studio_member'] = (float)($cityInput['rates']['studio_member'] ?? $settings['city']['rates']['studio_member'] ?? 6400);
            $settings['city']['rates']['studio_guest'] = (float)($cityInput['rates']['studio_guest'] ?? $settings['city']['rates']['studio_guest'] ?? 8000);
            $settings['city']['rates']['twobed_member'] = (float)($cityInput['rates']['twobed_member'] ?? $settings['city']['rates']['twobed_member'] ?? 9250);
            $settings['city']['rates']['twobed_guest'] = (float)($cityInput['rates']['twobed_guest'] ?? $settings['city']['rates']['twobed_guest'] ?? 12500);

            // Extra Person
            $settings['city']['extra_person']['rate'] = (float)($cityInput['extra_person']['rate'] ?? $settings['city']['extra_person']['rate'] ?? 1300);
            $settings['city']['extra_person']['studio_base_pax'] = (int)($cityInput['extra_person']['studio_base_pax'] ?? $settings['city']['extra_person']['studio_base_pax'] ?? 4);
            $settings['city']['extra_person']['twobed_base_pax'] = (int)($cityInput['extra_person']['twobed_base_pax'] ?? $settings['city']['extra_person']['twobed_base_pax'] ?? 6);

            // Promo Rates
            $settings['city']['promo_rates']['active'] = isset($cityInput['promo_rates']['active']);
            $settings['city']['promo_rates']['description'] = $cityInput['promo_rates']['description'] ?? '';
            $settings['city']['promo_rates']['studio_member'] = (float)($cityInput['promo_rates']['studio_member'] ?? $settings['city']['promo_rates']['studio_member'] ?? 5500);
            $settings['city']['promo_rates']['studio_guest'] = (float)($cityInput['promo_rates']['studio_guest'] ?? $settings['city']['promo_rates']['studio_guest'] ?? 7000);
            $settings['city']['promo_rates']['twobed_member'] = (float)($cityInput['promo_rates']['twobed_member'] ?? $settings['city']['promo_rates']['twobed_member'] ?? 8000);
            $settings['city']['promo_rates']['twobed_guest'] = (float)($cityInput['promo_rates']['twobed_guest'] ?? $settings['city']['promo_rates']['twobed_guest'] ?? 11000);
            $settings['city']['promo_rates']['start_date'] = $cityInput['promo_rates']['start_date'] ?? '';
            $settings['city']['promo_rates']['end_date'] = $cityInput['promo_rates']['end_date'] ?? '';

            // City Peak Periods
            $cityPeakPeriods = [];
            if (isset($cityInput['peak_periods']['start']) && isset($cityInput['peak_periods']['end'])) {
                foreach ($cityInput['peak_periods']['start'] as $index => $start) {
                    if (!empty($start) && !empty($cityInput['peak_periods']['end'][$index])) {
                        $cityPeakPeriods[] = [
                            'label' => $cityInput['peak_periods']['label'][$index] ?? '',
                            'start' => $start,
                            'end' => $cityInput['peak_periods']['end'][$index],
                        ];
                    }
                }
            }
            $settings['city']['promo_rates']['peak_periods'] = $cityPeakPeriods;

            // Discounts & VAT
            $settings['city']['discounts']['vat_rate'] = (float)($cityInput['discounts']['vat_rate'] ?? 12);
            $settings['city']['discounts']['sc_pwd']['remove_vat'] = isset($cityInput['discounts']['sc_pwd']['remove_vat']);
            $settings['city']['discounts']['sc_pwd']['apply_discount'] = isset($cityInput['discounts']['sc_pwd']['apply_discount']);
            $settings['city']['discounts']['sc_pwd']['additional_discount_percent'] = (float)($cityInput['discounts']['sc_pwd']['additional_discount_percent'] ?? 20);

            $this->settingsService->saveSettings($settings);

            return redirect()->to(url('/setup?property=city'))->with('success', 'Balesin City settings successfully updated.');
        } elseif ($property === 'pines') {
            $pinesInput = $input['pines'] ?? [];

            // Rates
            $settings['pines']['rates']['deluxe_member'] = (float)($pinesInput['rates']['deluxe_member'] ?? $settings['pines']['rates']['deluxe_member'] ?? 10000);
            $settings['pines']['rates']['deluxe_guest'] = (float)($pinesInput['rates']['deluxe_guest'] ?? $settings['pines']['rates']['deluxe_guest'] ?? 14500);
            $settings['pines']['rates']['suite_member'] = (float)($pinesInput['rates']['suite_member'] ?? $settings['pines']['rates']['suite_member'] ?? 15000);
            $settings['pines']['rates']['suite_guest'] = (float)($pinesInput['rates']['suite_guest'] ?? $settings['pines']['rates']['suite_guest'] ?? 21500);

            // Extra Person
            $settings['pines']['extra_person']['charge'] = (float)($pinesInput['extra_person']['charge'] ?? $settings['pines']['extra_person']['charge'] ?? 3700);
            $settings['pines']['extra_person']['base_pax'] = (int)($pinesInput['extra_person']['base_pax'] ?? $settings['pines']['extra_person']['base_pax'] ?? 2);

            // Rules
            $settings['pines']['rules']['max_member_units'] = (int)($pinesInput['rules']['max_member_units'] ?? $settings['pines']['rules']['max_member_units'] ?? 3);
            $settings['pines']['rules']['suite_fvn_fallback'] = isset($pinesInput['rules']['suite_fvn_fallback']);
            $settings['pines']['rules']['extra_person_fvn_charge'] = isset($pinesInput['rules']['extra_person_fvn_charge']);

            // Promo Rates
            $settings['pines']['promo_rates']['active'] = isset($pinesInput['promo_rates']['active']);
            $settings['pines']['promo_rates']['description'] = $pinesInput['promo_rates']['description'] ?? '';
            $settings['pines']['promo_rates']['deluxe_member'] = (float)($pinesInput['promo_rates']['deluxe_member'] ?? $settings['pines']['promo_rates']['deluxe_member'] ?? 8500);
            $settings['pines']['promo_rates']['deluxe_guest'] = (float)($pinesInput['promo_rates']['deluxe_guest'] ?? $settings['pines']['promo_rates']['deluxe_guest'] ?? 12500);
            $settings['pines']['promo_rates']['suite_member'] = (float)($pinesInput['promo_rates']['suite_member'] ?? $settings['pines']['promo_rates']['suite_member'] ?? 13000);
            $settings['pines']['promo_rates']['suite_guest'] = (float)($pinesInput['promo_rates']['suite_guest'] ?? $settings['pines']['promo_rates']['suite_guest'] ?? 18500);
            $settings['pines']['promo_rates']['start_date'] = $pinesInput['promo_rates']['start_date'] ?? '';
            $settings['pines']['promo_rates']['end_date'] = $pinesInput['promo_rates']['end_date'] ?? '';

            // Pines Peak Periods
            $pinesPeakPeriods = [];
            if (isset($pinesInput['peak_periods']['start']) && isset($pinesInput['peak_periods']['end'])) {
                foreach ($pinesInput['peak_periods']['start'] as $index => $start) {
                    if (!empty($start) && !empty($pinesInput['peak_periods']['end'][$index])) {
                        $pinesPeakPeriods[] = [
                            'label' => $pinesInput['peak_periods']['label'][$index] ?? '',
                            'start' => $start,
                            'end' => $pinesInput['peak_periods']['end'][$index],
                        ];
                    }
                }
            }
            $settings['pines']['promo_rates']['peak_periods'] = $pinesPeakPeriods;

            // Discounts & VAT
            $settings['pines']['discounts']['vat_rate'] = (float)($pinesInput['discounts']['vat_rate'] ?? 12);
            $settings['pines']['discounts']['sc_pwd']['remove_vat'] = isset($pinesInput['discounts']['sc_pwd']['remove_vat']);
            $settings['pines']['discounts']['sc_pwd']['apply_discount'] = isset($pinesInput['discounts']['sc_pwd']['apply_discount']);
            $settings['pines']['discounts']['sc_pwd']['additional_discount_percent'] = (float)($pinesInput['discounts']['sc_pwd']['additional_discount_percent'] ?? 20);

            $this->settingsService->saveSettings($settings);

            return redirect()->to(url('/setup?property=pines'))->with('success', 'Balesin Pines settings successfully updated.');
        }

        // Default: Balesin Island
        // Update Base Rates
        $settings['base_rates']['member'] = (float)($input['base_rates']['member'] ?? $settings['base_rates']['member']);
        $settings['base_rates']['guest'] = (float)($input['base_rates']['guest'] ?? $settings['base_rates']['guest']);
        $settings['base_rates']['employee'] = (float)($input['base_rates']['employee'] ?? $settings['base_rates']['employee'] ?? 2800);

        // Update Sectoral Destinations (Dropdown Presets)
        $settings['sectoral_destinations'] = isset($input['sectoral_destinations']) && is_array($input['sectoral_destinations'])
            ? array_values(array_filter(array_map('trim', $input['sectoral_destinations'])))
            : [];

        // Update Sectoral Rates
        $sectoralRates = [];
        if (isset($input['sectoral_rates']['destination']) && is_array($input['sectoral_rates']['destination'])) {
            foreach ($input['sectoral_rates']['destination'] as $index => $destination) {
                if (!empty(trim($destination))) {
                    $sectoralRates[] = [
                        'destination' => trim($destination),
                        'member_base_rate' => (float)($input['sectoral_rates']['member_base_rate'][$index] ?? 0),
                        'member_weekday_discount' => (float)($input['sectoral_rates']['member_weekday_discount'][$index] ?? 0),
                        'member_weekend_discount' => (float)($input['sectoral_rates']['member_weekend_discount'][$index] ?? 0),
                        'guest_base_rate' => (float)($input['sectoral_rates']['guest_base_rate'][$index] ?? 0),
                        'guest_weekday_discount' => (float)($input['sectoral_rates']['guest_weekday_discount'][$index] ?? 0),
                        'guest_weekend_discount' => (float)($input['sectoral_rates']['guest_weekend_discount'][$index] ?? 0),
                    ];
                }
            }
        }
        $settings['sectoral_rates'] = $sectoralRates;

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
        $settings['fees']['aof']['apply_to'] = $input['fees']['aof']['apply_to'] ?? [];
        
        $aofPeriods = [];
        if (isset($input['fees']['aof']['active_periods']['start']) && isset($input['fees']['aof']['active_periods']['end'])) {
            foreach ($input['fees']['aof']['active_periods']['start'] as $idx => $start) {
                if (!empty($start) && !empty($input['fees']['aof']['active_periods']['end'][$idx])) {
                    $aofPeriods[] = [
                        'start' => $start,
                        'end' => $input['fees']['aof']['active_periods']['end'][$idx],
                        'amount' => (float)($input['fees']['aof']['active_periods']['amount'][$idx] ?? 2000)
                    ];
                }
            }
        }
        $settings['fees']['aof']['active_periods'] = $aofPeriods;
        unset($settings['fees']['aof']['amount']);
        unset($settings['fees']['aof']['start_date']);
        unset($settings['fees']['aof']['end_date']);
        unset($settings['fees']['aof']['active_months']);

        // Fees - Environmental
        $settings['fees']['environmental']['amount'] = (float)($input['fees']['environmental']['amount'] ?? $settings['fees']['environmental']['amount']);
        $settings['fees']['environmental']['apply_to'] = $input['fees']['environmental']['apply_to'] ?? [];

        // Discounts
        $settings['discounts']['vat_rate'] = (float)($input['discounts']['vat_rate'] ?? $settings['discounts']['vat_rate']);
        $settings['discounts']['sc_pwd']['remove_vat'] = isset($input['discounts']['sc_pwd']['remove_vat']);
        $settings['discounts']['sc_pwd']['apply_discount'] = isset($input['discounts']['sc_pwd']['apply_discount']);
        $settings['discounts']['sc_pwd']['additional_discount_percent'] = (float)($input['discounts']['sc_pwd']['additional_discount_percent'] ?? 20);

        // Infants
        $settings['infants']['airfare_free'] = isset($input['infants']['airfare_free']);
        
        // Extra Occupants Override
        $settings['extra_occupants']['villa']['override'] = isset($input['extra_occupants']['villa']['override']);
        $settings['extra_occupants']['villa']['amount'] = (float)($input['extra_occupants']['villa']['amount'] ?? $settings['extra_occupants']['villa']['amount'] ?? 3700);
        $settings['extra_occupants']['suite']['override'] = isset($input['extra_occupants']['suite']['override']);
        $settings['extra_occupants']['suite']['amount'] = (float)($input['extra_occupants']['suite']['amount'] ?? $settings['extra_occupants']['suite']['amount'] ?? 3700);

        $this->settingsService->saveSettings($settings);

        return redirect()->to(url('/setup?property=island'))->with('success', 'Balesin Island settings successfully updated.');
    }

    public function saveSectoralDestinations(Request $request)
    {
        $destinations = $request->input('destinations', []);
        if (is_array($destinations)) {
            $destinations = array_values(array_filter(array_map('trim', $destinations)));
            $settings = $this->settingsService->getSettings();
            $settings['sectoral_destinations'] = $destinations;
            $this->settingsService->saveSettings($settings);

            return response()->json([
                'success' => true,
                'destinations' => $destinations,
                'message' => 'Dropdown destination options saved permanently.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid data.'], 400);
    }

    public function exportRates()
    {
        $filename = 'tbl_rates_' . date('Ymd') . '.xlsx';

        return Excel::download(new RatesExport(), $filename);
    }
}
