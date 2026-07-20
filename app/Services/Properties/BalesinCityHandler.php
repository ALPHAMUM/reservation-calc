<?php

namespace App\Services\Properties;

use App\Services\RateCalculatorService;

/**
 * BalesinCityHandler
 *
 * Handles all reservation data processing for Balesin City.
 * No Balesin Island business rules are applied here.
 * Add City-specific conditions in processListData() / processDetailData().
 */
class BalesinCityHandler extends AbstractPropertyHandler
{
    public function __construct()
    {
        $this->slug         = 'city';
        $this->listApiUrl   = config('services.balesin_city.list_url');
        $this->detailApiUrl = config('services.balesin_city.detail_url');
        $this->apiKey       = config('services.balesin_city.api_key');
    }

    public function label(): string     { return 'Balesin City'; }
    public function configKey(): string { return 'balesin_city'; }

    // ──────────────────────────────────────────────────────────────────
    // List processing — raw pass-through, no Island rules applied
    // ──────────────────────────────────────────────────────────────────

    protected function processListData(array $listData): array
    {
        $out = [];
        foreach ($listData as $res) {
            // TODO: add Balesin City list-level conditions here
            $out[] = $res;
        }
        return $out;
    }

    // ──────────────────────────────────────────────────────────────────
    // Detail processing — raw pass-through rates, no Island calculation
    // ──────────────────────────────────────────────────────────────────

    protected function processDetailData(array $msgs, array $listData = []): array
    {
        $calculator = app(RateCalculatorService::class);
        $out        = [];

        foreach ($msgs as &$res) {
            // TODO: add Balesin City detail-level conditions here

            // Pass-through: sum rates as-is from the API without applying Island rules
            $res['calculated_rates'] = $calculator->getPassThroughRates($res);
            $res['village_name']     = $res['roomtyp'] ?? $res['roomType'] ?? 'N/A';
            $res['is_employee']      = false;

            $out[] = $res;
        }
        unset($res);

        return $out;
    }
}
