<?php

namespace App\Services\Properties;

use App\Services\BalesinPinesRateCalculatorService;

/**
 * BalesinPinesHandler
 *
 * Handles all reservation data processing and rate calculations specifically for Balesin Pines.
 * No Balesin Island rules are applied here.
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
            $res['village_name'] = $res['roomtyp'] ?? $res['roomType'] ?? 'Balesin Pines';
            $out[] = $res;
        }
        return $out;
    }

    // ──────────────────────────────────────────────────────────────────
    // Detail processing — Dedicated Balesin Pines Rules & Calculations
    // ──────────────────────────────────────────────────────────────────

    protected function processDetailData(array $msgs, array $listData = []): array
    {
        $calculator = app(BalesinPinesRateCalculatorService::class);
        $out        = [];

        // Track member unit count per member for 3-unit Member Rate limit rule
        $memberUnitCounts = [];

        foreach ($msgs as &$res) {
            $memNo = trim((string)($res['memberNo'] ?? $res['memNo'] ?? ''));
            if ($memNo !== '') {
                $memberUnitCounts[$memNo] = ($memberUnitCounts[$memNo] ?? 0) + 1;
            }
        }
        unset($res);

        foreach ($msgs as &$res) {
            $memNo = trim((string)($res['memberNo'] ?? $res['memNo'] ?? ''));
            $unitCount = $memberUnitCounts[$memNo] ?? 1;

            $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType = $calculator->resolvePinesUnitType($roomType, $res['rateCode'] ?? '');

            $res['calculated_rates'] = $calculator->calculatePassengerRates(
                $res,
                0,
                $roomType,
                2,
                null,
                0.0,
                $unitCount
            );

            $res['village_name'] = $unitType;
            $res['is_employee']  = false;

            $out[] = $res;
        }
        unset($res);

        return $out;
    }
}
