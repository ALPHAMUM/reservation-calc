<?php

namespace App\Services\Properties;

use App\Services\RateCalculatorService;

/**
 * BalesinPinesHandler
 *
 * Handles all reservation data processing for Balesin Pines.
 * No Balesin Island business rules are applied here.
 * Add Pines-specific conditions in processListData() / processDetailData().
 */
class BalesinPinesHandler extends AbstractPropertyHandler
{
    public function __construct()
    {
        $this->slug         = 'pines';
        $this->listApiUrl   = config('services.balesin_pines.list_url');
        $this->detailApiUrl = config('services.balesin_pines.detail_url');
        $this->apiKey       = config('services.balesin_pines.api_key');
    }

    public function label(): string     { return 'Balesin Pines'; }
    public function configKey(): string { return 'balesin_pines'; }

    // ──────────────────────────────────────────────────────────────────
    // List processing — raw pass-through, no Island rules applied
    // ──────────────────────────────────────────────────────────────────

    protected function processListData(array $listData): array
    {
        $out = [];
        foreach ($listData as $res) {
            // TODO: add Balesin Pines list-level conditions here
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
            // TODO: add Balesin Pines detail-level conditions here

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
