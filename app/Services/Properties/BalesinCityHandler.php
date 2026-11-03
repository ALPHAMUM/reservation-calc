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
        $listData = $this->hydrateMemberNumbersFromDetail($listData);

        $out = [];
        foreach ($listData as $res) {
            if (empty($res['memberNo'])) {
                $res['memberNo'] = trim((string)($res['memNo'] ?? $res['member_no'] ?? $res['memberno'] ?? $res['MemberNo'] ?? $res['mem_no'] ?? ''));
            }
            $out[] = $res;
        }
        return $out;
    }

    protected function processDetailData(array $msgs, array $listData = []): array
    {
        $calculator = app(BalesinCityRateCalculatorService::class);
        $out        = [];
        $resOccupantIndex = [];
        $resAdultIndex    = [];

        foreach ($msgs as &$res) {
            $resNo = trim((string)($res['resNo'] ?? $res['conf'] ?? ''));
            if (!isset($resOccupantIndex[$resNo])) {
                $resOccupantIndex[$resNo] = 0;
                $resAdultIndex[$resNo]    = 0;
            }
            $occupantIndex = $resOccupantIndex[$resNo]++;
            $res['occupant_idx'] = $occupantIndex;

            $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType = $calculator->resolveCityUnitType($roomType, $res['rateCode'] ?? '');

            $isAdult    = $calculator->isAdultOccupant($res);
            $adultIndex = $isAdult ? ($resAdultIndex[$resNo]++) : $resAdultIndex[$resNo];
            $isExtraPerson = $calculator->isExtraOccupant($occupantIndex, $roomType, $adultIndex, $isAdult);
            $res['is_extra_person'] = $isExtraPerson;

            $scTargetService = app(\App\Services\ScTargetService::class);
            $resForCalc = $scTargetService->applyToPassenger(
                $res,
                $resNo,
                $occupantIndex,
                $this->scTargetMap,
                $this->scOverrideMap,
                $this->scRemoveMap,
                $this->slug,
                $isExtraPerson
            );
            $scTargetService->syncPassengerFlags($res, $resForCalc);

            $res['calculated_rates'] = $calculator->calculatePassengerRates($resForCalc, $isExtraPerson);
            $res['village_name']     = $unitType;
            $res['is_employee']      = false;

            if (isset($res['rate']) && is_array($res['rate'])) {
                foreach ($res['rate'] as &$r) {
                    $d = $r['date'] ?? '';
                    if ($d && isset($res['calculated_rates']['acc_dates'][$d]['breakdown'])) {
                        $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                        $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                    }
                }
                unset($r);
            }

            $out[] = $res;
        }
        unset($res);

        return $out;
    }
}
