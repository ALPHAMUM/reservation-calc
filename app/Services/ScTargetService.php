<?php

namespace App\Services;

use App\Models\ReservationScTarget;

class ScTargetService
{
    public const MODES = ['air', 'villa', 'both', 'none'];

    public function parseRaw(string $raw): array
    {
        $map = [];
        if ($raw === '') {
            return $map;
        }

        foreach (explode(',', $raw) as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) === 3) {
                $map[trim($parts[0])][(int) $parts[1]] = strtolower(trim($parts[2]));
            }
        }

        return $map;
    }

    public function serializeMap(array $map): string
    {
        $pairs = [];
        foreach ($map as $resNo => $occupants) {
            foreach ($occupants as $idx => $mode) {
                if (in_array($mode, self::MODES, true)) {
                    $pairs[] = $resNo . ':' . (int) $idx . ':' . $mode;
                }
            }
        }

        return implode(',', $pairs);
    }

    public function loadFromDb(string $property, array $resNos = []): array
    {
        $query = ReservationScTarget::where('property', $property);
        if ($resNos !== []) {
            $query->whereIn('res_no', $resNos);
        }

        $map = [];
        foreach ($query->get() as $row) {
            $map[$row->res_no][(int) $row->occupant_idx] = $row->mode;
        }

        return $map;
    }

    public function resolveRaw(string $property, string $requestRaw, array $resNos = []): string
    {
        $dbMap = $this->loadFromDb($property, $resNos);
        $requestMap = $this->parseRaw($requestRaw);

        foreach ($requestMap as $resNo => $occupants) {
            foreach ($occupants as $idx => $mode) {
                $dbMap[$resNo][$idx] = $mode;
            }
        }

        return $this->serializeMap($dbMap);
    }

    public function save(string $property, string $resNo, int $occupantIdx, string $mode): void
    {
        if (!in_array($mode, self::MODES, true)) {
            return;
        }

        ReservationScTarget::updateOrCreate(
            [
                'property'     => $property,
                'res_no'       => $resNo,
                'occupant_idx' => $occupantIdx,
            ],
            ['mode' => $mode]
        );
    }

    public function parseOverrideRaw(string $raw): array
    {
        $map = [];
        if ($raw === '') {
            return $map;
        }

        foreach (explode(',', $raw) as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) === 2) {
                $map[trim($parts[0])][] = (int) $parts[1];
            }
        }

        return $map;
    }

    public function parseRemoveRaw(string $raw): array
    {
        return $this->parseOverrideRaw($raw);
    }

    public static function passengerHasFvnAccommodation(array $passenger): bool
    {
        foreach ($passenger['rate'] ?? [] as $r) {
            $desc = strtolower($r['desc'] ?? '');
            if (str_contains($desc, 'airfare') || str_contains($desc, 'hangar')
                || str_contains($desc, 'aviation') || str_contains($desc, 'environmental')) {
                continue;
            }
            $val = (float) ($r['val'] ?? 0);
            $frac = round($val - floor($val), 2);
            if (in_array($frac, [0.01, 0.02, 0.03], true) && $val < 10) {
                return true;
            }
        }

        return false;
    }

    public function applyToPassenger(
        array $res,
        string $resNo,
        int $occupantIndex,
        array $scTargetMap,
        array $scOverrideMap,
        array $scRemoveMap,
        string $property = 'island',
        bool $isExtraPerson = false
    ): array {
        $resForCalc = $res;
        $targetMode = $scTargetMap[$resNo][$occupantIndex] ?? null;

        $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
        $privLower = strtolower($privCard);
        $alreadySc = $privCard !== '' && !in_array($privLower, ['n', 'no', 'false', '0', 'none', 'null'])
            && (str_contains($privLower, 'senior') || str_contains($privLower, 'pwd'));

        if ($targetMode !== null) {
            // Extra-person charge is a paid 3700 rate, even if the API still sends FVN 0.01/0.02 markers.
            $isFvn = ($property === 'island') && !$isExtraPerson && self::passengerHasFvnAccommodation($res);
            $targetMode = $this->normalizeMode($targetMode, $property, $isFvn, $alreadySc, $isExtraPerson);
            $resForCalc['sc_target_villa'] = in_array($targetMode, ['villa', 'both'], true);
            $resForCalc['sc_target_air']   = in_array($targetMode, ['air', 'both'], true);
            $resForCalc['sc_mode']         = $targetMode;
        } else {
            if (isset($scOverrideMap[$resNo]) && in_array($occupantIndex, $scOverrideMap[$resNo], true)) {
                $resForCalc['privCard']        = 'Senior Citizen';
                $resForCalc['sc_target_villa'] = true;
                $resForCalc['sc_target_air']   = true;
                $resForCalc['sc_mode']         = 'both';
            }
            if (isset($scRemoveMap[$resNo]) && in_array($occupantIndex, $scRemoveMap[$resNo], true)) {
                $resForCalc['privCard']        = 'N';
                $resForCalc['sc_target_villa'] = false;
                $resForCalc['sc_target_air']   = false;
                $resForCalc['sc_mode']         = 'none';
            }
        }

        return $resForCalc;
    }

    public function syncPassengerFlags(array &$res, array $resForCalc): void
    {
        $res['sc_target_villa'] = $resForCalc['sc_target_villa'] ?? null;
        $res['sc_target_air']   = $resForCalc['sc_target_air'] ?? null;
        $res['sc_mode']         = $resForCalc['sc_mode'] ?? null;
    }

    public static function isFvnAccommodation(array $totals): bool
    {
        foreach ($totals as $value) {
            $val = (float) $value;
            $frac = round($val - floor($val), 2);
            if (in_array($frac, [0.01, 0.02, 0.03], true) && $val < 10) {
                return true;
            }
        }

        return false;
    }

    public function normalizeMode(string $mode, string $property, bool $isFvnAccommodation, bool $alreadySc = false, bool $isExtraPerson = false): string
    {
        // Extra person pays a cash extra-occupant rate (not FVN villa stay) and can take villa/both SC.
        if ($property === 'island' && $isFvnAccommodation && !$isExtraPerson) {
            if ($mode === 'none') {
                return 'none';
            }
            if ($mode === 'air') {
                return 'air';
            }
            // Villa / both on FVN accommodation — villa discount does not apply; use airfare only
            return 'air';
        }

        if (in_array($property, ['pines', 'city'], true)) {
            if ($mode === 'both') {
                return 'villa';
            }
            if ($mode === 'air') {
                return $alreadySc ? 'villa' : 'none';
            }
        }

        return $mode;
    }
}
