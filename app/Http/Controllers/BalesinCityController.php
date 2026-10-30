<?php

namespace App\Http\Controllers;

use App\Services\Properties\BalesinCityHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BalesinCityController extends Controller
{
    protected BalesinCityHandler $handler;

    public function __construct(BalesinCityHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Display Balesin City Dashboard
     */
    public function index(Request $request)
    {
        set_time_limit(300);
        return $this->handler->index($request);
    }

    /**
     * Dedicated Balesin City Printout Action
     */
    public function print(Request $request)
    {
        set_time_limit(300);

        $resNoList    = $request->get('resnolist');
        $fromDate     = $request->get('fromdate');
        $toDate       = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search       = $request->get('search');

        $listApiUrl   = config('services.balesin_city.list_url');
        $detailApiUrl = config('services.balesin_city.detail_url');
        $apiKey       = config('services.balesin_city.api_key');

        $reservations = [];
        $ids = [];
        $listData = [];

        try {
            if ($resNoList) {
                $ids = array_filter(array_map('trim', explode(',', $resNoList)));
                if ($fromDate && $toDate) {
                    $resp = Http::withHeaders(['Authorization' => $apiKey])
                        ->withoutVerifying()
                        ->timeout(30)
                        ->get($listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                    $listData = $resp->successful() ? ($resp->json()['msg'] ?? []) : [];
                }
            } elseif ($fromDate && $toDate) {
                $resp = Http::withHeaders(['Authorization' => $apiKey])
                    ->withoutVerifying()
                    ->timeout(30)
                    ->get($listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);

                $listData = $resp->successful() ? ($resp->json()['msg'] ?? []) : [];
                $all = $listData;

                if ($statusFilter) {
                    if (!is_array($statusFilter)) $statusFilter = [$statusFilter];
                    $all = array_filter($all, fn($res) => in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter)));
                }

                if ($search) {
                    $s = strtolower(trim($search));
                    $all = array_filter($all, function ($res) use ($s) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? '');
                        $name  = strtolower($res['customer_name'] ?? '');
                        return str_contains($resNo, $s) || str_contains($name, $s);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
            }

            if (empty($ids)) {
                return back()->with('error', 'No Balesin City data to print.');
            }

            if (!empty($ids)) {
                $chunks = array_chunk($ids, 25);
                $rawDetail = [];
                foreach ($chunks as $chunk) {
                    $resp = Http::withHeaders(['Authorization' => $apiKey])
                        ->withoutVerifying()
                        ->timeout(30)
                        ->get($detailApiUrl, ['resnolist' => implode(',', $chunk)]);

                    if ($resp->successful()) {
                        $msgs = $resp->json()['msg'] ?? [];
                        $rawDetail = array_merge($rawDetail, $msgs);
                    }
                }

                $scTargetService = app(\App\Services\ScTargetService::class);
                $scOverrideRaw = (string) $request->get('sc_override', '');
                $scRemoveRaw   = (string) $request->get('sc_remove', '');
                $requestScTargetRaw = (string) $request->get('sc_target', '');
                $scTargetRaw = $scTargetService->resolveRaw('city', $requestScTargetRaw, array_values($ids));
                $scOverrideMap = $scTargetService->parseOverrideRaw($scOverrideRaw);
                $scRemoveMap   = $scTargetService->parseRemoveRaw($scRemoveRaw);
                $scTargetMap   = $scTargetService->parseRaw($scTargetRaw);

                $calculator = app(\App\Services\BalesinCityRateCalculatorService::class);
                $resOccupantIndex = [];
                
                // Build list lookup map if listData is available
                $listMap = [];
                if (!empty($listData)) {
                    foreach ($listData as $lr) {
                        $k = trim($lr['resNo'] ?? $lr['conf'] ?? '');
                        if ($k !== '') $listMap[$k] = $lr;
                    }
                }

                foreach ($rawDetail as $res) {
                    $resNo = trim((string)($res['resNo'] ?? $res['conf'] ?? ''));
                    $lr = $listMap[$resNo] ?? [];

                    $mNo = trim((string)($res['memNo'] ?? $res['memberNo'] ?? $res['member_no'] ?? $lr['memberNo'] ?? $lr['memNo'] ?? $lr['member_no'] ?? $lr['memberno'] ?? $lr['MemberNo'] ?? $lr['mem_no'] ?? ''));
                    if ($mNo !== '') {
                        $res['memberNo'] = $mNo;
                        $res['memNo']    = $mNo;
                    }

                    $cName = trim((string)($res['custName'] ?? $res['customer_name'] ?? $res['customer'] ?? $lr['customer'] ?? $lr['custName'] ?? $lr['guestName'] ?? $lr['gstName'] ?? ''));
                    if ($cName !== '') {
                        $res['customer_name'] = $cName;
                        $res['customer']      = $cName;
                        $res['custName']      = $cName;
                    }

                    if (empty($res['bookDate'])) $res['bookDate'] = $lr['bookDate'] ?? '';
                    if (empty($res['conactNo'])) $res['conactNo'] = $lr['conactNo'] ?? $lr['contactNo'] ?? '';

                    if (!isset($resOccupantIndex[$resNo])) {
                        $resOccupantIndex[$resNo] = 0;
                    }
                    $occupantIndex = $resOccupantIndex[$resNo]++;
                    $res['occupant_idx'] = $occupantIndex;

                    $roomType                = $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $unitType                = $calculator->resolveCityUnitType($roomType, $res['rateCode'] ?? '');
                    $isExtraPerson           = $calculator->isExtraOccupant($occupantIndex, $roomType);

                    $resForCalc = $scTargetService->applyToPassenger(
                        $res,
                        $resNo,
                        $occupantIndex,
                        $scTargetMap,
                        $scOverrideMap,
                        $scRemoveMap,
                        'city',
                        $isExtraPerson
                    );
                    $scTargetService->syncPassengerFlags($res, $resForCalc);

                    $res['calculated_rates'] = $calculator->calculatePassengerRates($resForCalc);
                    $res['village_name']     = $unitType;
                    $res['is_employee']      = false;

                    if (isset($res['rate']) && is_array($res['rate'])) {
                        foreach ($res['rate'] as &$r) {
                            $d = $r['date'] ?? '';
                            if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                                $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                                $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'] ?? null;
                            }
                        }
                        unset($r);
                    }

                    $reservations[]          = $res;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('City print fetching failed: ' . $e->getMessage());
        }

        // Collect all distinct dates
        $allDates = [];
        foreach ($reservations as $r) {
            foreach ($r['rate'] ?? [] as $rt) {
                if ($d = ($rt['date'] ?? null)) {
                    $allDates[$d] = true;
                }
            }
        }
        ksort($allDates);
        $dateCols = array_keys($allDates);
        // Pre-calculate spans per reservation for Balesin City (Studio base_pax = 4, 2-Bedroom base_pax = 6)
        $resGroups = [];
        foreach ($reservations as $r) {
            $rn = trim($r['resNo'] ?? $r['conf'] ?? 'N/A');
            $resGroups[$rn][] = $r;
        }

        $accSpans = [];
        foreach ($resGroups as $rn => $rows) {
            $count = count($rows);
            $basePax = 4;
            foreach ($rows as $r) {
                $unitType = strtoupper($r['village_name'] ?? $r['unit_type_label'] ?? $r['roomtyp'] ?? $r['roomType'] ?? '');
                if (str_contains($unitType, '2-BEDROOM') || str_contains($unitType, '2-BR') || str_contains($unitType, 'TWOBED') || str_contains($unitType, 'TWO BEDROOM')) {
                    $basePax = 6;
                    break;
                }
            }
            $curr = 0;
            while ($curr < $count) {
                $blockStart = $curr;
                $isBlockExtra = ($curr >= $basePax);
                $blockSize = $isBlockExtra ? 1 : min($count - $curr, $basePax);

                // Detect whether ANY base occupant in this block has a Villa SC/PWD discount
                $hasAnyVillaScInBlock = false;
                for ($k = $blockStart; $k < $blockStart + $blockSize; $k++) {
                    $occIdx = (int) ($rows[$k]['occupant_idx'] ?? $k);
                    $isExtra = ($rows[$k]['is_extra_person'] ?? false) || ($occIdx >= $basePax);
                    if (!$isExtra) {
                        $hasVillaSc = (bool) ($rows[$k]['sc_target_villa'] ?? false);
                        if (!$hasVillaSc && isset($rows[$k]['sc_mode'])) {
                            $hasVillaSc = in_array($rows[$k]['sc_mode'], ['villa', 'both'], true);
                        }
                        if ($hasVillaSc) {
                            $hasAnyVillaScInBlock = true;
                            break;
                        }
                    }
                }

                $groupTotals = [];
                foreach ($dateCols as $d) {
                    $uv = 0;
                    foreach ($rows[$blockStart]['rate'] ?? [] as $rt) {
                        if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                    }
                    $groupTotals[$d] = $uv;
                }
                for ($i = 0; $i < $blockSize; $i++) {
                    $span = ($i === 0) ? $blockSize : 0;
                    $isExtraPerson = ($curr + $i) >= $basePax;
                    if ($isExtraPerson) {
                        $extraTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = (float)($rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['val'] ?? 0);
                            if ($uv == 0) {
                                foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                    if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                                }
                            }
                            $bd = $rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['breakdown'] ?? null;
                            if (!$bd || empty($bd['is_discounted'])) {
                                if ($uv > 1300 || $uv <= 1.0) $uv = 1300;
                            }
                            $extraTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $extraTotals, 'group_size' => 1, 'is_extra_person' => true, 'has_discount' => false];
                    } elseif ($hasAnyVillaScInBlock) {
                        // UNSPAN: Give each base occupant their own row with individual rate
                        $occTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = 0;
                            foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                if (($rt['date'] ?? '') === $d) {
                                    $uv = (float)($rt['val'] ?? 0);
                                    break;
                                }
                            }
                            if ($uv == 0) {
                                $uv = $groupTotals[$d] ?? 0;
                            }
                            $uvFrac = round($uv - floor($uv), 2);
                            $isUvFvn = in_array($uvFrac, [0.01, 0.02, 0.03, 0.5]);
                            if (!$isUvFvn) {
                                if ($uv > 100 && $blockSize > 1) {
                                    if (abs($uv - ($groupTotals[$d] ?? 0)) < 1.0 || $uv > 3000) {
                                        $uv = round($uv / $blockSize, 2);
                                    }
                                }
                            }
                            $occTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $occTotals, 'group_size' => 1, 'is_extra_person' => false, 'has_discount' => true];
                    } else {
                        $accSpans[] = ['span' => $span, 'totals' => $groupTotals, 'group_size' => $blockSize, 'is_extra_person' => false, 'has_discount' => false];
                    }
                }
                $curr += $blockSize;
            }
        }

        // Attach to each reservation
        foreach ($reservations as $idx => &$res) {
            $spanInfo = $accSpans[$idx] ?? ['span' => 1, 'totals' => [], 'group_size' => 1, 'is_extra_person' => false, 'has_discount' => false];
            $res['acc_span']          = $spanInfo['span'];
            $res['acc_group_size']    = $spanInfo['group_size'];
            $res['acc_group_totals']  = $spanInfo['totals'];
            $res['is_extra_person']   = $spanInfo['is_extra_person'] ?? false;
            $res['acc_has_discount']  = $spanInfo['has_discount'] ?? false;
        }
        unset($res);

        return view('city_print', compact('reservations', 'accSpans', 'dateCols', 'fromDate', 'toDate'));
    }

    /**
     * Dedicated Balesin City Excel Export Action
     */
    public function export(Request $request)
    {
        set_time_limit(300);

        $resNoList    = $request->get('resnolist');
        $fromDate     = $request->get('fromdate');
        $toDate       = $request->get('todate');
        $statusFilter = $request->get('status_filter');

        $listApiUrl   = config('services.balesin_city.list_url');
        $detailApiUrl = config('services.balesin_city.detail_url');
        $apiKey       = config('services.balesin_city.api_key');

        $ids = [];
        if ($resNoList) {
            $ids = array_filter(explode(',', $resNoList));
        } elseif ($fromDate && $toDate) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])
                ->withoutVerifying()
                ->timeout(30)
                ->get($listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);

            $listData = $resp->successful() ? ($resp->json()['msg'] ?? []) : [];
            if ($statusFilter) {
                if (!is_array($statusFilter)) $statusFilter = [$statusFilter];
                $listData = array_filter($listData, fn($res) => in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter)));
            }
            $ids = collect($listData)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
        }

        if (empty($ids)) {
            return back()->with('error', 'No Balesin City data to export.');
        }

        $chunks    = array_chunk($ids, 25);
        $rawDetail = [];
        foreach ($chunks as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])
                ->withoutVerifying()
                ->timeout(30)
                ->get($detailApiUrl, ['resnolist' => implode(',', $chunk)]);

            if ($resp->successful()) {
                $msgs      = $resp->json()['msg'] ?? [];
                $rawDetail = array_merge($rawDetail, $msgs);
            }
        }

        $scTargetService = app(\App\Services\ScTargetService::class);
        $scOverrideRaw = (string) $request->get('sc_override', '');
        $scRemoveRaw   = (string) $request->get('sc_remove', '');
        $requestScTargetRaw = (string) $request->get('sc_target', '');
        $scTargetRaw = $scTargetService->resolveRaw('city', $requestScTargetRaw, array_values($ids));
        $scOverrideMap = $scTargetService->parseOverrideRaw($scOverrideRaw);
        $scRemoveMap   = $scTargetService->parseRemoveRaw($scRemoveRaw);
        $scTargetMap   = $scTargetService->parseRaw($scTargetRaw);

        $calculator   = app(\App\Services\BalesinCityRateCalculatorService::class);
        $reservations = [];
        $resOccupantIndex = [];
        foreach ($rawDetail as $res) {
            $resNo = trim((string)($res['resNo'] ?? $res['conf'] ?? ''));
            if (!isset($resOccupantIndex[$resNo])) {
                $resOccupantIndex[$resNo] = 0;
            }
            $occupantIndex = $resOccupantIndex[$resNo]++;
            $res['occupant_idx'] = $occupantIndex;

            $roomType                = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType                = $calculator->resolveCityUnitType($roomType, $res['rateCode'] ?? '');
            $isExtraPerson           = $calculator->isExtraOccupant($occupantIndex, $roomType);

            $resForCalc = $scTargetService->applyToPassenger(
                $res,
                $resNo,
                $occupantIndex,
                $scTargetMap,
                $scOverrideMap,
                $scRemoveMap,
                'city',
                $isExtraPerson
            );
            $scTargetService->syncPassengerFlags($res, $resForCalc);

            $res['calculated_rates'] = $calculator->calculatePassengerRates($resForCalc);
            $res['village_name']     = $unitType;
            $res['is_employee']      = false;

            if (isset($res['rate']) && is_array($res['rate'])) {
                foreach ($res['rate'] as &$r) {
                    $d = $r['date'] ?? '';
                    if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                        $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                        $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'] ?? null;
                    }
                }
                unset($r);
            }

            $reservations[]          = $res;
        }

        // Build date columns
        $allDates = [];
        foreach ($reservations as $r) {
            foreach ($r['rate'] ?? [] as $rt) {
                if ($d = ($rt['date'] ?? null)) {
                    $allDates[$d] = true;
                }
            }
        }
        ksort($allDates);
        $dateCols  = array_keys($allDates);
        $dateCount = count($dateCols);

        // Extract header metadata
        $firstMemberName = '';
        $firstMemberNo   = '';
        $firstContactNo  = '';
        $firstBookDate   = '';
        foreach ($reservations as $res) {
            if (!$firstMemberName) {
                $cCandidate = trim((string)($res['customer_name'] ?? $res['custName'] ?? $res['customer'] ?? $res['cust_name'] ?? $res['guestName'] ?? $res['gstName'] ?? ''));
                if ($cCandidate !== '') $firstMemberName = $cCandidate;
            }
            if (!$firstMemberNo) {
                $mCandidate = trim((string)($res['memNo'] ?? $res['memberNo'] ?? $res['member_no'] ?? $res['MemberNo'] ?? $res['memberno'] ?? $res['mem_no'] ?? ''));
                if ($mCandidate !== '') $firstMemberNo = $mCandidate;
            }
            if (!$firstContactNo && !empty($res['conactNo']))         $firstContactNo  = (string)$res['conactNo'];
            elseif (!$firstContactNo && !empty($res['contactNo']))     $firstContactNo  = (string)$res['contactNo'];
            if (!$firstBookDate   && !empty($res['bookDate']))         $firstBookDate   = $res['bookDate'];
        }

        $formattedBookDate = '';
        if ($firstBookDate) {
            try {
                $dt                = new \DateTime($firstBookDate);
                $formattedBookDate = $dt->format('l, d F Y');
            } catch (\Exception $e) {
                $formattedBookDate = $firstBookDate;
            }
        }

        $cityLogoUrl      = 'https://balesin.com/wp-content/uploads/2025/09/balesin-island-logo-dark-line.png';
        $logoImageFormula = '=IMAGE("' . $cityLogoUrl . '",,3,0,120)';
        $filename         = 'balesin_city_reservations_' . date('Ymd_His') . '.xls';

        if (ob_get_level()) ob_end_clean();

        // Pre-compute reservation group counts
        $resGroupCounts = [];
        foreach ($reservations as $res) {
            $rn                  = trim($res['resNo'] ?? $res['conf'] ?? '');
            $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
        }

        // Pre-calculate spans per reservation for Balesin City Export
        $resGroups = [];
        foreach ($reservations as $r) {
            $rn = trim($r['resNo'] ?? $r['conf'] ?? 'N/A');
            $resGroups[$rn][] = $r;
        }

        $accSpans = [];
        foreach ($resGroups as $rn => $rows) {
            $count = count($rows);
            $basePax = 4;
            foreach ($rows as $r) {
                $unitType = strtoupper($r['village_name'] ?? $r['unit_type_label'] ?? $r['roomtyp'] ?? $r['roomType'] ?? '');
                if (str_contains($unitType, '2-BEDROOM') || str_contains($unitType, '2-BR') || str_contains($unitType, 'TWOBED') || str_contains($unitType, 'TWO BEDROOM')) {
                    $basePax = 6;
                    break;
                }
            }
            $curr = 0;
            while ($curr < $count) {
                $blockStart = $curr;
                $isBlockExtra = ($curr >= $basePax);
                $blockSize = $isBlockExtra ? 1 : min($count - $curr, $basePax);

                // Detect whether ANY base occupant in this block has a Villa SC/PWD discount
                $hasAnyVillaScInBlock = false;
                for ($k = $blockStart; $k < $blockStart + $blockSize; $k++) {
                    $occIdx = (int) ($rows[$k]['occupant_idx'] ?? $k);
                    $isExtra = ($rows[$k]['is_extra_person'] ?? false) || ($occIdx >= $basePax);
                    if (!$isExtra) {
                        $hasVillaSc = (bool) ($rows[$k]['sc_target_villa'] ?? false);
                        if (!$hasVillaSc && isset($rows[$k]['sc_mode'])) {
                            $hasVillaSc = in_array($rows[$k]['sc_mode'], ['villa', 'both'], true);
                        }
                        if ($hasVillaSc) {
                            $hasAnyVillaScInBlock = true;
                            break;
                        }
                    }
                }

                $groupTotals = [];
                foreach ($dateCols as $d) {
                    $uv = 0;
                    foreach ($rows[$blockStart]['rate'] ?? [] as $rt) {
                        if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                    }
                    $groupTotals[$d] = $uv;
                }
                for ($i = 0; $i < $blockSize; $i++) {
                    $span = ($i === 0) ? $blockSize : 0;
                    $isExtraPerson = ($curr + $i) >= $basePax;
                    if ($isExtraPerson) {
                        $extraTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = (float)($rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['val'] ?? 0);
                            if ($uv == 0) {
                                foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                    if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                                }
                            }
                            $bd = $rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['breakdown'] ?? null;
                            if (!$bd || empty($bd['is_discounted'])) {
                                if ($uv > 1300 || $uv <= 1.0) $uv = 1300;
                            }
                            $extraTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $extraTotals, 'group_size' => 1, 'is_extra_person' => true, 'has_discount' => false];
                    } elseif ($hasAnyVillaScInBlock) {
                        // UNSPAN: Give each base occupant their own row with individual rate
                        $occTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = 0;
                            foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                if (($rt['date'] ?? '') === $d) {
                                    $uv = (float)($rt['val'] ?? 0);
                                    break;
                                }
                            }
                            if ($uv == 0) {
                                $uv = $groupTotals[$d] ?? 0;
                            }
                            $uvFrac = round($uv - floor($uv), 2);
                            $isUvFvn = in_array($uvFrac, [0.01, 0.02, 0.03, 0.5]);
                            if (!$isUvFvn) {
                                if ($uv > 100 && $blockSize > 1) {
                                    if (abs($uv - ($groupTotals[$d] ?? 0)) < 1.0 || $uv > 3000) {
                                        $uv = round($uv / $blockSize, 2);
                                    }
                                }
                            }
                            $occTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $occTotals, 'group_size' => 1, 'is_extra_person' => false, 'has_discount' => true];
                    } else {
                        $accSpans[] = ['span' => $span, 'totals' => $groupTotals, 'group_size' => $blockSize, 'is_extra_person' => false, 'has_discount' => false];
                    }
                }
                $curr += $blockSize;
            }
        }

        // Attach to each reservation
        foreach ($reservations as $idx => &$res) {
            $spanInfo = $accSpans[$idx] ?? ['span' => 1, 'totals' => [], 'group_size' => 1, 'is_extra_person' => false, 'has_discount' => false];
            $res['acc_span']          = $spanInfo['span'];
            $res['acc_group_size']    = $spanInfo['group_size'];
            $res['acc_group_totals']  = $spanInfo['totals'];
            $res['is_extra_person']   = $spanInfo['is_extra_person'] ?? false;
            $res['acc_has_discount']  = $spanInfo['has_discount'] ?? false;
        }
        unset($res);

        return response()->stream(function () use (
            $reservations, $dateCols, $dateCount, $logoImageFormula,
            $firstMemberName, $firstMemberNo, $firstContactNo, $formattedBookDate,
            $resGroupCounts
        ) {
            $emptyColsSpan = $dateCount + 1;

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>';
            echo 'body { font-family: sans-serif; }';
            echo 'td, th { border: 0.5pt solid #3a4fa3; vertical-align: middle; font-size: 8pt; padding: 5pt; }';
            echo '.hdr  { background-color: #10206d; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.dh   { background-color: #10206d; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.num  { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '.num-center { mso-number-format:"\#\,\#\#0\.00"; text-align: center; }';
            echo '</style></head><body>';

            echo '<table style="border-collapse: collapse; border: none;">';
            echo '<colgroup><col style="mso-width-source:userset; width:15pt;"></colgroup>';

            // Row 1: narrow top margin
            echo '<tr style="height:10pt;">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (9 + $emptyColsSpan) . '" style="border:none;"></td>';
            echo '</tr>';

            // Logo + MEMBER'S NAME row
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td rowspan="4" colspan="2" style="text-align: center; vertical-align: middle; padding: 10px; border: none; border-left: 0.5pt solid #3a4fa3; border-top: 0.5pt solid #3a4fa3; border-bottom: 20px solid transparent;"';
            echo ' x:fmla="' . htmlspecialchars($logoImageFormula, ENT_QUOTES, 'UTF-8') . '"></td>';
            echo '<td style="font-weight: bold; background-color: #10206d; padding: 8px; text-align: center; color: #ffffff;">MEMBER\'S NAME:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberName) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #10206d; padding: 8px; text-align: center; color: #ffffff;">MEMBERSHIP NUMBER:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #10206d; padding: 8px; text-align: center; color: #ffffff;">CONTACT NUMBER:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold; mso-number-format:\'\\@\';">' . htmlspecialchars((string)$firstContactNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #10206d; padding: 8px; text-align: center; color: #ffffff;">BOOKING DATE:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($formattedBookDate) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            // Spacer
            echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . (9 + $emptyColsSpan) . '" style="border: none; height: 10px;"></td></tr>';

            // Header Row 1
            echo '<tr>';
            echo '<td rowspan="2" style="border:none; width:15pt;"></td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt; color: #ffffff;">RSVN#</td>';
            echo '<td rowspan="2" class="hdr" style="width: 100pt; color: #ffffff;">ROOM TYPE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 120pt; color: #ffffff;">OCCUPANTS</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt; color: #ffffff;">RELATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 35pt; color: #ffffff;">AGE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt; color: #ffffff;">BIRTHDAY</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt; color: #ffffff;">CHECK-IN</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt; color: #ffffff;">CHECK-OUT</td>';
            echo '<td colspan="' . $dateCount . '" class="dh" style="color: #ffffff;">ACCOMMODATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 90pt; word-wrap:normal; color: #ffffff;">TOTAL RATE (Per Occupant)</td>';
            echo '</tr>';

            // Header Row 2: date sub-columns
            echo '<tr>';
            foreach ($dateCols as $d) {
                $label = $d;
                try {
                    $dt    = new \DateTime($d);
                    $label = strtoupper($dt->format('M-d, D'));
                } catch (\Exception $e) {}
                echo '<td class="dh" style="width: 75pt; color: #ffffff;">' . htmlspecialchars($label) . '</td>';
            }
            echo '</tr>';

            // ---- Data rows ----
            $dateTotals    = array_fill_keys($dateCols, 0);
            $accGrandTotal = 0;
            $lastResNo     = null;

            foreach ($reservations as $res) {
                $resNo   = trim($res['resNo'] ?? $res['conf'] ?? '');
                $isFirst = ($resNo !== $lastResNo);
                if ($isFirst) $lastResNo = $resNo;

                $rates = $res['calculated_rates'] ?? ['acc' => 0];

                $privCard      = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                $privCardLower = strtolower($privCard);
                $isValidCard   = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

                $groupTotals = $res['acc_group_totals'] ?? [];
                $groupAccSum = 0;
                foreach ($groupTotals as $gv) {
                    $grv = round((float)$gv, 2);
                    if ($grv !== 0.01 && $grv !== 0.02 && $grv !== 0.5) {
                        $groupAccSum += (float)$gv;
                    }
                }
                $span           = $res['acc_span'] ?? 1;
                $groupSize      = max(1, (int) ($res['acc_group_size'] ?? $span ?? 1));
                $isExtraOcc     = $res['is_extra_person'] ?? false;
                $hasDiscount    = $res['acc_has_discount'] ?? false;
                if ($isExtraOcc || ($span === 1 && $hasDiscount)) {
                    $perOccupantAcc = $groupAccSum;
                } else {
                    $perOccupantAcc = $groupAccSum / $groupSize;
                }
                $accGrandTotal += $perOccupantAcc;

                $relation = strtoupper(htmlspecialchars($res['gstType'] ?? ''));
                if (!empty($res['is_employee'])) $relation .= ' (EMPLOYEE)';
                if ($isValidCard)                $relation .= ' (' . strtoupper(htmlspecialchars($privCard)) . ')';

                echo '<tr>';
                echo '<td style="border:none; width:15pt;"></td>';

                if ($isFirst) {
                    $resSpan       = $resGroupCounts[$resNo] ?? 1;
                    $roomTypeLabel = strtoupper($res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? '');
                    echo '<td rowspan="' . $resSpan . '" style="vertical-align:middle; text-align:center; font-weight:600">' . htmlspecialchars($resNo) . '</td>';
                    echo '<td rowspan="' . $resSpan . '" style="vertical-align:middle; text-align:center;">' . htmlspecialchars($roomTypeLabel) . '</td>';
                }

                echo '<td style="text-align:center">' . htmlspecialchars($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                echo '<td style="text-align:center">' . $relation . '</td>';
                echo '<td style="text-align:center">' . htmlspecialchars($res['age'] ?? '') . '</td>';
                echo '<td style="text-align:center">' . htmlspecialchars($res['dateOfBirth'] ?? '') . '</td>';


                $arrDt = $res['arrdt'] ?? $res['arrDt'] ?? '';
                $depDt = $res['depdt'] ?? $res['depDt'] ?? '';
                echo '<td style=\'mso-number-format:"\\@"; text-align:center;\'>' . ($arrDt ? (strtotime($arrDt) ? strtoupper(date('d-M-Y', strtotime($arrDt))) : $arrDt) : '') . '</td>';
                echo '<td style=\'mso-number-format:"\\@"; text-align:center;\'>' . ($depDt ? (strtotime($depDt) ? strtoupper(date('d-M-Y', strtotime($depDt))) : $depDt) : '') . '</td>';

                if ($span > 0) {
                    foreach ($dateCols as $d) {
                        $val = ($span > 1) ? ($groupTotals[$d] ?? 0) : ($groupTotals[$d] ?? ($rates['acc_dates'][$d]['val'] ?? 0));
                        $rv  = round($val, 2);
                        $rawFrac = round($val - floor($val), 2);
                        $isFvn = in_array($rawFrac, [0.01, 0.02, 0.03]);
                        if ($isFvn) {
                            if ($rawFrac == 0.01)      $formattedVal = '1 FVN';
                            elseif ($rawFrac == 0.02)  $formattedVal = '1.5 FVN';
                            else                       $formattedVal = '.5 FVN';
                        } else {
                            $formattedVal = ($val > 0 ? number_format($val, 2) : '');
                        }

                        echo '<td class="num" rowspan="' . $span . '" style="text-align: center;">' . $formattedVal . '</td>';

                        if (!$isFvn && $rv !== 0.5) {
                            $dateTotals[$d] += (float) $val;
                        }
                    }
                }

                echo '<td class="num-center" style="font-weight:bold">' . ($perOccupantAcc > 0 ? number_format($perOccupantAcc, 2) : '') . '</td>';
                echo '</tr>';
                flush();
            }

            $totalPax          = count($reservations);
            $overallGrandTotal = $accGrandTotal;
            $fullColspan       = 9 + $dateCount + 1;

            // Grand totals row
            echo '<tr style="font-weight:bold;background:#e8eaf6">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="2" style="text-align:right">TOTAL PAX:</td>';
            echo '<td style="text-align:center">' . $totalPax . '</td>';
            echo '<td colspan="5" style="text-align:right">GRAND TOTALS:</td>';
            foreach ($dateCols as $d) {
                echo '<td class="num" style="text-align:center;">' . number_format($dateTotals[$d], 2) . '</td>';
            }
            echo '<td class="num" style="font-weight:bold;text-align:center;">' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';

            // Guidelines & Payments section params
            $totalTableCols = 8 + $dateCount + 1;
            $guidelinesCols = (int) floor(($totalTableCols - 1) * 0.50);
            $spacerCols     = 1;
            $paymentCols    = $totalTableCols - $guidelinesCols - $spacerCols;
            $payDescCols    = (int) ceil($paymentCols * 0.65);
            $payAmtCols     = $paymentCols - $payDescCols;
            $leftOffsetCols = $guidelinesCols + $spacerCols;

            // Spacers
            for ($s = 0; $s < 2; $s++) {
                echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . $fullColspan . '" style="border:none;">&nbsp;</td></tr>';
            }

            // TOTAL AMOUNT DUE on single row (aligned with PAYMENT/S columns)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftOffsetCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="background-color:#10206d; color:#ffffff; font-weight:bold; font-size:11pt; padding:8px 12px; border:0.5pt solid #3a4fa3; text-align:left; vertical-align:middle;">TOTAL AMOUNT DUE:</td>';
            echo '<td colspan="' . $payAmtCols . '" class="num" style="background-color:#10206d; color:#ffffff; font-weight:bold; font-size:13pt; padding:8px 12px; border:0.5pt solid #3a4fa3; text-align:right; vertical-align:middle;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';

            // VAT note
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftOffsetCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="text-align:right; border:none; font-style:italic; color:#64748b; font-size:9pt; padding-top:4px; padding-bottom:12px;">Room Rates include service charge (10%) and VAT (12%)</td>';
            echo '</tr>';

            // Row 1: Booking Guidelines title + PAYMENT/S header
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: 1px solid #3a4fa3; border-left: 1px solid #3a4fa3; border-right: 1px solid #3a4fa3; border-bottom: none; font-weight:bold; font-size:11pt; color:#10206d; padding: 10px 15px 2px 15px; text-align: left; background-color:#ffffff;">Booking Guidelines:</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="border:none; font-weight:bold; font-size:10pt; color:#0f172a; text-align: left; padding-bottom:5px; padding-left:8px;">PAYMENT/S:</td>';
            echo '</tr>';

            // Row 2: Room Capacity subtitle + Payment row 1
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #3a4fa3; border-right: 1px solid #3a4fa3; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Room Capacity</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 3: Room Capacity content + Payment row 2
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #3a4fa3; border-right: 1px solid #3a4fa3; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">• 1 Bedroom Suite: 4 persons (2 adults and 2 kids); - 2-Bedroom Suite: 6 persons (4 adults and 2 kids)<br/>• 1 additional occupant may be allowed per suite at Php1,300/night<br/>• Kids are defined as 12 years old and younger; infants younger than 24 months are not counted</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 4: Cancellation subtitle + Payment row 3
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #3a4fa3; border-right: 1px solid #3a4fa3; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Cancellation</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 5: Cancellation content + Balance to settle
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #3a4fa3; border-right: 1px solid #3a4fa3; border-bottom: 1px solid #3a4fa3; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 15px 15px; background-color:#ffffff;">The deadline for cancellations is one week (7 days) prior to the scheduled check-in date. If a cancellation is made within this 7-day period, a fee equivalent to the cost of one night per room will be forfeited. However, for cancellations made with more than 7 days notice, any member overpayments may be applied to future bookings.</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="border: 1.5pt solid #10206d; background-color: #e8eaf6; color: #10206d; font-weight: bold; font-size: 10pt; padding: 6px 10px; text-align: center; vertical-align: middle;">BALANCE TO SETTLE</td>';
            echo '</tr>';

            echo '</table></body></html>';
        }, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
