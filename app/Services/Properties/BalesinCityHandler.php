<?php

namespace App\Services\Properties;

use App\Services\BalesinCityRateCalculatorService;

/**
 * BalesinCityHandler
 *
 * Handles all reservation data processing for Balesin City.
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

    protected function processListData(array $listData): array
    {
        $out = [];
        foreach ($listData as $res) {
            $out[] = $res;
        }
        return $out;
    }

    protected function processDetailData(array $msgs, array $listData = []): array
    {
        $calculator = app(BalesinCityRateCalculatorService::class);
        $out        = [];

        foreach ($msgs as &$res) {
            $roomType                = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType                = $calculator->resolveCityUnitType($roomType, $res['rateCode'] ?? '');
            $res['calculated_rates'] = $calculator->getPassThroughRates($res);
            $res['village_name']     = $unitType;
            $res['is_employee']      = false;

            $out[] = $res;
        }
        unset($res);

        return $out;
    }
}
