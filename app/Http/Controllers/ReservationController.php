<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReservationExport;

class ReservationController extends Controller
{
    private $listApiUrl;
    private $detailApiUrl;
    private $apiKey;

    public function __construct()
    {
        $this->listApiUrl = config('services.balesin.list_url');
        $this->detailApiUrl = config('services.balesin.detail_url');
        $this->apiKey = config('services.balesin.api_key');
    }

    private $villageMap = [
        'BALI' => 'BALI',
        'BLSN' => 'BALESIN',
        'CDSL' => 'COSTA DEL SOL',
        'CLBH' => 'CLUBHOUSE',
        'MYKO' => 'MYKONOS',
        'PHKT' => 'PHUKET',
        'PV4' => 'PRIVATE 4',
        'PV6' => 'PRIVATE 6',
        'PV8' => 'PRIVATE 8',
        'RGNCY' => 'REGENCY A',
        'RGNYB' => 'REGENCY B',
        'RGNYC' => 'REGENCY C',
        'RGNYD' => 'REGENCY D',
        'ROYL' => 'ROYAL',
        'STRP' => 'ST. TROPEZ',
        'TANG' => 'APSARAS VILLA',
        'TCNA' => 'TOSCANA',
    ];

    private function getNationalityName($code)
    {
        if (!$code)
            return '';
        return trim($code);
    }

    private function getVillageName($code)
    {
        if (!$code)
            return 'N/A';
        $searchCode = strtoupper(trim($code));

        // If it exists in our map, return the "actual name"
        if (isset($this->villageMap[$searchCode])) {
            return $this->villageMap[$searchCode];
        }

        // Otherwise, return the original code/name as provided
        return $code;
    }

    private function appendFvnMetadataFlags(array &$res): void
    {
        $isComp = false;
        $isPkg = false;
        foreach ($res['rate'] ?? [] as $rt) {
            $v = (float) ($rt['val'] ?? 0);
            if (abs($v - 0.01) < 0.0001) {
                $isComp = true;
            }
            if (abs($v - 0.02) < 0.0001) {
                $isPkg = true;
            }
        }
        if ($isComp) {
            $res['rate_metadata'] = trim(($res['rate_metadata'] ?? '') . ' COMP');
        }
        if ($isPkg) {
            $res['rate_metadata'] = trim(($res['rate_metadata'] ?? '') . ' PKG');
        }
    }

    private function ingestListReservation(
        array $lr,
        array &$rateMap,
        array &$listRateMap,
        array &$memberMap,
        array &$memberMetaMap = null
    ): void {
        $c = trim($lr['conf'] ?? $lr['resNo'] ?? $lr['resno'] ?? '');
        if ($c === '') {
            return;
        }

        $listRate = strtoupper(trim((string) ($lr['rate'] ?? '')));
        $listRateMap[$c] = $listRate;

        $rateVal = $listRate;
        $rateCode = strtoupper(trim((string) ($lr['rateCode'] ?? $lr['rate_code'] ?? '')));
        if ($rateCode !== '') {
            $rateVal .= ($rateVal ? '|' : '') . $rateCode;
        }

        $cust = strtoupper($lr['customer'] ?? $lr['custName'] ?? '');
        if (str_contains($cust, 'ALPHALAND EMPLOYEE')) {
            $rateVal = 'EMPLOYEE';
        }

        $rateMap[$c] = $rateVal;
        $memberMap[$c] = $cust;

        if ($memberMetaMap !== null) {
            $memberMetaMap[$c] = [
                'memberNo' => trim($lr['memberNo'] ?? ''),
                'bookDate' => trim($lr['bookDate'] ?? ''),
                'conactNo' => trim($lr['conactNo'] ?? $lr['contactNo'] ?? ''),
            ];
        }
    }

    /**
     * When detail reservations are outside the dashboard list date range, fetch List API
     * using each reservation's arrival/departure so KEY-SUITE / KEY-VILLA are available.
     */
    private function hydrateListMetadataFromDetail(
        array $msgs,
        array &$rateMap,
        array &$listRateMap,
        array &$memberMap,
        array &$memberMetaMap = null
    ): void {
        $arrDates = [];
        foreach ($msgs as $m) {
            $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
            if ($rn === '' || $this->lookupReservationMap($listRateMap, $rn) !== '') {
                continue;
            }
            $arr = $m['arrdt'] ?? $m['arrDt'] ?? $m['arr_dt'] ?? null;
            $dep = $m['depdt'] ?? $m['depDt'] ?? $m['dep_dt'] ?? null;
            if ($arr) {
                $arrDates[$arr] = $dep ?? $arr;
            }
        }
        if ($arrDates === []) {
            return;
        }
        foreach ($arrDates as $arrDate => $depDate) {
            try {
                $lr = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $arrDate, 'todate' => $depDate]);
                if ($lr->successful()) {
                    foreach ($lr->json()['msg'] ?? [] as $item) {
                        $this->ingestListReservation($item, $rateMap, $listRateMap, $memberMap, $memberMetaMap);
                    }
                }
            } catch (\Exception $e) {
                // continue with next date range
            }
        }
    }

    private function lookupReservationMap(array $map, string $resNo): string
    {
        $key = trim($resNo);
        if ($key === '') {
            return '';
        }
        if (array_key_exists($key, $map)) {
            return (string) $map[$key];
        }
        $alt = ltrim($key, '#');
        if ($alt !== $key && array_key_exists($alt, $map)) {
            return (string) $map[$alt];
        }

        return '';
    }

    private function buildReservationMetadata(string $resNo, array $rateMap, array $localMetaMap): string
    {
        $metadata = $this->lookupReservationMap($rateMap, $resNo);
        if (isset($localMetaMap[$resNo]) && $localMetaMap[$resNo] !== '') {
            $metadata .= ($metadata ? '|' : '') . $localMetaMap[$resNo];
        }

        return $metadata;
    }

    /**
     * List API "rate" (e.g. KEY-SUITE) is authoritative; do not infer from per-passenger FVN (0.01/COMP).
     */
    private function resolveUnitTypeLabel(string $listRate, string $metadata, string $roomType): string
    {
        $listRate = strtoupper(trim($listRate));
        $meta = strtoupper($metadata);

        if ($listRate === '' && $meta !== '') {
            if (str_contains($meta, 'KEY-SUITE')) {
                $listRate = 'KEY-SUITE';
            } elseif (str_contains($meta, 'KEY-VILLA')) {
                $listRate = 'KEY-VILLA';
            }
        }

        if ($listRate !== '') {
            if (str_contains($listRate, 'KEY-SUITE') || preg_match('/(?:^|\|)(G|M)?SUITE(?:\||$|-)/', $listRate)) {
                return 'SUITE';
            }
            if (str_contains($listRate, 'KEY-VILLA') || preg_match('/(?:^|\|)(G|M)?VILLA(?:\||$|-)/', $listRate)) {
                return 'VILLA';
            }
        }

        if (str_contains($meta, 'KEY-SUITE')) {
            return 'SUITE';
        }
        if (str_contains($meta, 'KEY-VILLA')) {
            return 'VILLA';
        }

        return app(\App\Services\RateCalculatorService::class)->unitTypeLabel($roomType, $meta, '', []);
    }

    /**
     * @return array<string, string>
     */
    private function buildReservationUnitTypes(
        array $rateMap,
        array $listRateMap,
        array $localMetaMap,
        array $resRoomTypeMap,
        array $msgs
    ): array {
        $unitTypes = [];
        $resNos = [];
        foreach ($msgs as $m) {
            $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
            if ($rn !== '') {
                $resNos[$rn] = true;
            }
        }
        foreach (array_keys($resNos) as $rn) {
            $roomType = $resRoomTypeMap[$rn] ?? '';
            $metadata = $this->buildReservationMetadata($rn, $rateMap, $localMetaMap);
            $listRate = $this->lookupReservationMap($listRateMap, $rn);
            $unitTypes[$rn] = $this->resolveUnitTypeLabel($listRate, $metadata, $roomType);
        }

        return $unitTypes;
    }

    private function applyUnitTypeLabel(array &$res, string $unitTypeLabel): void
    {
        $res['unit_type_label'] = $unitTypeLabel;
    }

    private function formatVillageWithUnitType(array $res): string
    {
        $village = htmlspecialchars(strtoupper($res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? ''), ENT_QUOTES, 'UTF-8');
        $unit = htmlspecialchars(strtoupper($res['unit_type_label'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($unit === '') {
            return $village;
        }

        return $village . '&#10;' . $unit;
    }

    public function index(Request $request)
    {
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search = $request->get('search');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        if ($perPage > 100)
            $perPage = 100;

        $reservations = [];
        $pagedReservations = [];
        $total = 0;
        $viewType = 'summary';
        $error = null;

        if (!$resNoList && !$fromDate) {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
            // Default status filter for fresh dashboard
            if (!$statusFilter) {
                $statusFilter = ['CONFIRMED'];
            }
        }

        // Ensure statusFilter is an array for consistent handling
        if ($statusFilter && !is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        // When explicitly requesting reservation IDs, don't filter by status.
        if (is_string($resNoList) && trim($resNoList) !== '') {
            $statusFilter = [];
        }

        try {
            // ALWAYS fetch List API first to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            $listRateMap = [];
            $memberMap = [];
            $listData = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                    foreach ($listData as $lr) {
                        $this->ingestListReservation($lr, $rateMap, $listRateMap, $memberMap);
                    }
                }
            }

            if ($resNoList) {
                $viewType = 'detail';
                $allConfIds = array_unique(array_filter(explode(',', $resNoList)));
                $chunks = array_chunk($allConfIds, 25);
                $calculator = app(\App\Services\RateCalculatorService::class);
                $paxIndices = [];
                $occupantIndices = [];
                $reservationPaxCounts = [];
                foreach ($chunks as $chunk) {
                    $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                        ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);

                    if (!$resp->successful()) {
                        $error = "API Error (Detail): " . ($resp->status() == 404 ? "Endpoint not found." : "Status " . $resp->status());
                        break;
                    }

                    $msgs = $resp->json()['msg'] ?? [];
                    $this->consolidateRates($msgs);

                    foreach ($msgs as $res) {
                        $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                        if (!isset($reservationPaxCounts[$resNo]))
                            $reservationPaxCounts[$resNo] = 0;
                        $age = (int) ($res['age'] ?? 99);
                        $gstType = strtolower($res['gstType'] ?? '');
                        $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);
                        if (!$isInfant)
                            $reservationPaxCounts[$resNo]++;
                    }

                    $this->hydrateListMetadataFromDetail($msgs, $rateMap, $listRateMap, $memberMap);

                    // Build localMetaMap from rateCode in Detail records (rateCode is a string field)
                    $localMetaMap = [];
                    foreach ($msgs as $m) {
                        $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                        if ($rn === '')
                            continue;
                        if (!isset($localMetaMap[$rn]))
                            $localMetaMap[$rn] = '';
                        $mCode = $m['rateCode'] ?? $m['rate_code'] ?? '';
                        $mType = $m['rate'] ?? '';
                        if (is_string($mType) && trim($mType) !== '') {
                            $upper = strtoupper(trim($mType));
                            if (!str_contains($localMetaMap[$rn], $upper)) {
                                $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . $upper;
                            }
                        }
                        if (is_string($mCode) && trim($mCode) !== '') {
                            $upper = strtoupper(trim($mCode));
                            if (!str_contains($localMetaMap[$rn], $upper)) {
                                $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . $upper;
                            }
                        }
                    }

                    // Pre-scan msgs to map reservation number to correct, non-empty room type
                    $resRoomTypeMap = [];
                    foreach ($msgs as $m) {
                        $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                        if ($rn === '') continue;
                        $rt = $m['roomtyp'] ?? $m['roomType'] ?? '';
                        if (is_string($rt) && trim($rt) !== '' && strtoupper(trim($rt)) !== 'N/A') {
                            $resRoomTypeMap[$rn] = trim($rt);
                        }
                    }

                    $resUnitTypes = $this->buildReservationUnitTypes(
                        $rateMap,
                        $listRateMap,
                        $localMetaMap,
                        $resRoomTypeMap,
                        $msgs
                    );

                    foreach ($msgs as &$res) {
                        $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');

                        $res['customer_name'] = $memberMap[$resNo] ?? '';

                        $res['rate_metadata'] = $this->buildReservationMetadata($resNo, $rateMap, $localMetaMap);
                        $this->appendFvnMetadataFlags($res);

                        $age = (int) ($res['age'] ?? 99);
                        $gstType = strtolower($res['gstType'] ?? '');
                        $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);

                        if (!isset($paxIndices[$resNo]))
                            $paxIndices[$resNo] = 0;
                        if (!isset($occupantIndices[$resNo]))
                            $occupantIndices[$resNo] = 0;
                        $paxIndex = $paxIndices[$resNo];
                        $occupantIndex = $occupantIndices[$resNo];
                        $roomType = $resRoomTypeMap[$resNo] ?? $res['roomtyp'] ?? $res['roomType'] ?? '';
                        $res['roomtyp'] = $roomType;
                        $res['roomType'] = $roomType;
                        $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                        $res['calculated_rates'] = $calculator->calculatePassengerRates(
                            $res,
                            $paxIndex,
                            $roomType,
                            $totalBillable,
                            $occupantIndex
                        );

                        // Sync rates back to the rate array for display (replaces original accommodation values)
                        foreach ($res['rate'] as &$r) {
                            $d = $r['date'] ?? '';
                            if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                                $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                                $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                            }
                        }
                        unset($r);

                        $occupantIndices[$resNo]++;
                        if (!$isInfant) {
                            $paxIndices[$resNo]++;
                        }

                        $res['is_employee'] = $res['calculated_rates']['is_employee'] ?? false;
                        $res['village_name'] = $this->getVillageName($roomType);
                        $this->applyUnitTypeLabel($res, $resUnitTypes[$resNo] ?? 'VILLA');
                        $res['nationality_name'] = $this->getNationalityName($res['nationality'] ?? '');
                    }
                    unset($res); // Fix reference leak
                    $reservations = array_merge($reservations, $msgs);
                }

                // Apply Status Filter (Array Support)
                if ($statusFilter && is_array($statusFilter)) {
                    $reservations = array_filter($reservations, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                // Apply Global Search
                if ($search) {
                    $search = strtolower(trim($search));
                    $reservations = array_filter($reservations, function ($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $total = count($reservations);
                $pagedReservations = $reservations;
            } else {
                $viewType = 'list';
                $all = $listData ?? [];

                if ($statusFilter && is_array($statusFilter)) {
                    $all = array_filter($all, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                if ($search) {
                    $search = strtolower(trim($search));
                    $all = array_filter($all, function ($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $total = count($all);
                $pagedReservations = array_values($all);

                foreach ($pagedReservations as &$res) {
                    $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $res['village_name'] = $this->getVillageName($roomType);
                    $listRate = is_string($res['rate'] ?? null)
                        ? strtoupper(trim($res['rate']))
                        : $this->lookupReservationMap($listRateMap, trim($res['resNo'] ?? $res['conf'] ?? ''));
                    $this->applyUnitTypeLabel(
                        $res,
                        $this->resolveUnitTypeLabel($listRate, '', $roomType)
                    );
                }
                unset($res); // Fix reference leak
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = "Connection Error: Could not reach the API server. Please check your internet or the endpoint URL.";
        } catch (\Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }

        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getSettings();

        $dateCols = [];
        if ($viewType === 'detail' && is_array($pagedReservations) && !empty($pagedReservations)) {
            // Sort by Reservation Number first
            usort($pagedReservations, function ($a, $b) {
                $resA = $a['resNo'] ?? $a['conf'] ?? '';
                $resB = $b['resNo'] ?? $b['conf'] ?? '';
                return strcmp($resA, $resB);
            });

            $allDates = [];
            foreach ($pagedReservations as $res) {
                foreach ($res['rate'] ?? [] as $r) {
                    if ($d = ($r['date'] ?? null)) {
                        $allDates[$d] = true;
                    }
                }
            }
            ksort($allDates);
            $dateCols = array_keys($allDates);
        }

        return view('dashboard', [
            'reservations' => $pagedReservations ?? [],
            'resNoList' => $resNoList,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'statusFilter' => $statusFilter,
            'dateCols' => $dateCols,
            'viewType' => $viewType,
            'error' => $error,
            'settings' => $settings
        ]);
    }

    public function export(Request $request)
    {
        set_time_limit(0);
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search = $request->get('search');

        try {
            if ($resNoList) {
                $ids = array_filter(explode(',', $resNoList));
            } elseif ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                }
                $all = $listData ?? [];

                // Apply Status Filter (Array Support)
                if ($statusFilter) {
                    if (!is_array($statusFilter))
                        $statusFilter = [$statusFilter];
                    $all = array_filter($all, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                // Apply Global Search
                if ($search) {
                    $search = strtolower(trim($search));
                    $all = array_filter($all, function ($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? $res['resno'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? $res['custname'] ?? $res['guestname'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? $i['resno'] ?? null)->filter()->unique()->toArray();
            }

            if (empty($ids))
                return back()->with('error', 'No data to export.');

            // Self-healing: if dates are empty, fetch check-in/out dates from Detail API of first chunk
            if ((!$fromDate || !$toDate) && !empty($ids)) {
                $firstChunkResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->detailApiUrl, ['resnolist' => implode(',', array_slice($ids, 0, 40))]);
                if ($firstChunkResp->successful()) {
                    $msgs = $firstChunkResp->json()['msg'] ?? [];
                    $minDate = null;
                    $maxDate = null;
                    foreach ($msgs as $m) {
                        $arr = $m['arrdt'] ?? $m['arrDt'] ?? '';
                        $dep = $m['depdt'] ?? $m['depDt'] ?? '';
                        if ($arr) {
                            if (!$minDate || $arr < $minDate) $minDate = $arr;
                        }
                        if ($dep) {
                            if (!$maxDate || $dep > $maxDate) $maxDate = $dep;
                        }
                    }
                    if ($minDate && $maxDate) {
                        $fromDate = $minDate;
                        $toDate = $maxDate;
                    }
                }
            }

            // ALWAYS fetch List API to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            $listRateMap = [];
            $memberMap = [];
            $memberMetaMap = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    foreach ($listResp->json()['msg'] ?? [] as $lr) {
                        $this->ingestListReservation($lr, $rateMap, $listRateMap, $memberMap, $memberMetaMap);
                    }
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
        }

        if (empty($ids))
            return back()->with('error', 'No data to export.');

        if (ob_get_level())
            ob_end_clean();
        $filename = 'reservations_' . date('Ymd_His') . '.xls';
        $logoImageFormula = $this->balesinLogoImageFormula();

        // PASS 1: Load all data, collect unique dates and calculated rates
        $allRows = [];
        $allDates = [];
        $calculator = app(\App\Services\RateCalculatorService::class);

        $paxIndices = [];
        $occupantIndices = [];
        $reservationPaxCounts = [];
        $memberMap = $memberMap ?? [];
        $listRateMap = $listRateMap ?? [];
        foreach (array_chunk($ids, 40) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                $msgs = $resp->json()['msg'] ?? [];
                $this->consolidateRates($msgs);

                foreach ($msgs as $res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    if (!isset($reservationPaxCounts[$resNo]))
                        $reservationPaxCounts[$resNo] = 0;
                    $age = (int) ($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);
                    if (!$isInfant)
                        $reservationPaxCounts[$resNo]++;
                }

                $localMetaMap = [];
                foreach ($msgs as $m) {
                    $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                    if ($rn === '')
                        continue;

                    $mCode = $m['rateCode'] ?? $m['rate_code'] ?? '';
                    $mType = $m['rate'] ?? '';

                    if (!isset($localMetaMap[$rn]))
                        $localMetaMap[$rn] = '';

                    if (is_string($mType) && trim($mType) !== '' && !str_contains($localMetaMap[$rn], strtoupper(trim($mType)))) {
                        $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . strtoupper(trim($mType));
                    }
                    if (is_string($mCode) && trim($mCode) !== '' && !str_contains($localMetaMap[$rn], strtoupper(trim($mCode)))) {
                        $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . strtoupper(trim($mCode));
                    }
                }

                // Pre-scan msgs to map reservation number to correct, non-empty room type
                $resRoomTypeMap = [];
                foreach ($msgs as $m) {
                    $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                    if ($rn === '') continue;
                    $rt = $m['roomtyp'] ?? $m['roomType'] ?? '';
                    if (is_string($rt) && trim($rt) !== '' && strtoupper(trim($rt)) !== 'N/A') {
                        $resRoomTypeMap[$rn] = trim($rt);
                    }
                }

                $this->hydrateListMetadataFromDetail($msgs, $rateMap, $listRateMap, $memberMap, $memberMetaMap);

                $resUnitTypes = $this->buildReservationUnitTypes(
                    $rateMap,
                    $listRateMap,
                    $localMetaMap,
                    $resRoomTypeMap,
                    $msgs
                );

                foreach ($msgs as &$res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    $res['customer_name'] = $memberMap[$resNo] ?? '';
                    $meta = $memberMetaMap[$resNo] ?? [];
                    $res['memberNo'] = $meta['memberNo'] ?? $res['memberNo'] ?? '';
                    $res['bookDate'] = $meta['bookDate'] ?? $res['bookDate'] ?? '';
                    $res['conactNo'] = $meta['conactNo'] ?? $res['conactNo'] ?? $res['contactNo'] ?? '';
                    $res['rate_metadata'] = $this->buildReservationMetadata($resNo, $rateMap, $localMetaMap);
                    $this->appendFvnMetadataFlags($res);

                    $roomType = $resRoomTypeMap[$resNo] ?? $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $res['roomtyp'] = $roomType; // Ensure it's synchronized
                    $res['roomType'] = $roomType;
                    $res['village_name'] = $this->getVillageName($roomType);
                    $res['nationality_name'] = $this->getNationalityName($res['nationality'] ?? '');
                    $this->applyUnitTypeLabel($res, $resUnitTypes[$resNo] ?? 'VILLA');

                    if (!isset($paxIndices[$resNo]))
                        $paxIndices[$resNo] = 0;
                    if (!isset($occupantIndices[$resNo]))
                        $occupantIndices[$resNo] = 0;
                    $paxIndex = $paxIndices[$resNo];
                    $occupantIndex = $occupantIndices[$resNo];
                    $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                    $calcResult = $calculator->calculatePassengerRates(
                        $res,
                        $paxIndex,
                        $roomType,
                        $totalBillable,
                        $occupantIndex
                    );
                    $res['calculated_rates'] = $calcResult;

                    $rd = [];
                    foreach ($res['rate'] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d && !str_contains($d, ' to ')) {
                            $rd[$d] = (float) ($r['val'] ?? 0);
                            $allDates[$d] = true;
                        }
                    }

                    $age = (int) ($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);

                    // Sync rates back to the rate array for display
                    foreach ($res['rate'] as &$r) {
                        $d = $r['date'] ?? '';
                        if ($d && isset($calcResult['acc_dates'][$d])) {
                            $r['val'] = $calcResult['acc_dates'][$d]['val'];
                            $r['breakdown'] = $calcResult['acc_dates'][$d]['breakdown'];
                            $rd[$d] = $r['val'];
                        }
                    }
                    unset($r);

                    $occupantIndices[$resNo]++;
                    if (!$isInfant) {
                        $paxIndices[$resNo]++;
                    }

                    $res['is_employee'] = $calcResult['is_employee'] ?? false;

                    $allRows[] = [
                        'res' => $res,
                        'rates' => $calcResult,
                        'paxIndex' => $paxIndex,
                        'rateDates' => $rd,
                    ];
                }
                unset($res); // Fix reference leak
            }
        }

        ksort($allDates);
        $dateCols = array_keys($allDates);
        $dateCount = count($dateCols);

        // Pass 2: Stream HTML Excel output
        return response()->stream(function () use ($allRows, $dateCols, $dateCount, $logoImageFormula) {
            
            $firstMemberName = '';
            $firstMemberNo = '';
            $firstContactNo = '';
            $firstBookDate = '';
            foreach ($allRows as $row) {
                if (!$firstMemberName && !empty($row['res']['customer_name'])) {
                    $firstMemberName = $row['res']['customer_name'];
                }
                if (!$firstMemberNo && !empty($row['res']['memberNo'])) {
                    $firstMemberNo = $row['res']['memberNo'];
                }
                if (!$firstContactNo && !empty($row['res']['conactNo'])) {
                    $firstContactNo = $row['res']['conactNo'];
                } elseif (!$firstContactNo && !empty($row['res']['contactNo'])) {
                    $firstContactNo = $row['res']['contactNo'];
                }
                if (!$firstBookDate && !empty($row['res']['bookDate'])) {
                    $firstBookDate = $row['res']['bookDate'];
                }
            }

            $formattedBookDate = '';
            if ($firstBookDate) {
                try {
                    $dt = new \DateTime($firstBookDate);
                    $formattedBookDate = $dt->format('l, d F Y');
                } catch (\Exception $e) {
                    $formattedBookDate = $firstBookDate;
                }
            }

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>';
            echo 'body { font-family: sans-serif; }';
            echo 'td, th { border: 0.5pt solid #b4c6e7; vertical-align: middle; font-size: 8pt; padding: 5pt; }';
            echo '.hdr  { background-color: #bcd2ee; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.dh   { background-color: #bcd2ee; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.num  { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '.num-center { mso-number-format:"\#\,\#\#0\.00"; text-align: center; }';
            echo '</style></head><body>';
            
            // Main Table start with header integrated
            echo '<table style="border-collapse: collapse; border: none;">';
            $emptyColsSpan = $dateCount + 5; // Accommodation dates + 5 fee columns

            // Narrow margin column (col A) + narrow margin row (row 1)
            echo '<colgroup>';
            echo '<col style="mso-width-source:userset; width:15pt;">';
            echo '</colgroup>';

            // Row 1: narrow top margin row
            echo '<tr style="height:10pt;">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (9 + $emptyColsSpan) . '" style="border:none;"></td>';
            echo '</tr>';

            // Logo + MEMBER'S NAME row
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td rowspan="4" colspan="2" style="text-align: center; vertical-align: middle; padding: 10px; border: none; border-left: 0.5pt solid #b4c6e7; border-top: 0.5pt solid #b4c6e7; border-bottom: 20px solid transparent;"';
            echo ' x:fmla="' . htmlspecialchars($logoImageFormula, ENT_QUOTES, 'UTF-8') . '">';
            echo '</td>';
            echo '<td style="font-weight: bold; background-color: #dbeafe; padding: 8px; text-align: center; color: #000;">MEMBER\'S NAME:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberName) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #dbeafe; padding: 8px; text-align: center; color: #000;">MEMBERSHIP NUMBER:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #dbeafe; padding: 8px; text-align: center; color: #000;">CONTACT NUMBER:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold; mso-number-format:\'\@\';">' . htmlspecialchars((string)$firstContactNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #dbeafe; padding: 8px; text-align: center; color: #000;">BOOKING DATE:</td>';
            echo '<td colspan="6" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($formattedBookDate) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';
            
            // Empty spacer row before main headers
            echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . (9 + $emptyColsSpan) . '" style="border: none; height: 10px;"></td></tr>';

            // Header Row 1: fixed cols (rowspan=2) + ACCOMMODATION (colspan = dateCount+1) + fee cols (rowspan=2)
            echo '<tr>';
            echo '<td rowspan="2" style="border:none; width:15pt;"></td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt;">RSVN#</td>';
            echo '<td rowspan="2" class="hdr" style="width: 100pt;">VILLAGE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 120pt;">OCCUPANTS</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt;">RELATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 35pt;">AGE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt;">BIRTHDAY</td>';
            echo '<td rowspan="2" class="hdr" style="width: 90pt;">NATIONALITY</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt;">CHECK-IN</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt;">CHECK-OUT</td>';
            echo '<td colspan="' . $dateCount . '" class="dh">ACCOMMODATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt; word-wrap:normal;">AIRFARE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt; word-wrap:normal;">HANGAR FEE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt; word-wrap:normal;">AVIATION OPERATIONAL FEE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt; word-wrap:normal;">ENVIRONMENTAL FEE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 90pt; word-wrap:normal;">TOTAL RATE (Per Occupant)</td>';
            echo '</tr>';

            // Header Row 2: date sub-columns + TOTAL
            echo '<tr>';
            foreach ($dateCols as $d) {
                $label = $d;
                try {
                    if (!str_contains($d, ' to ')) {
                        $dt = new \DateTime($d);
                        $label = strtoupper($dt->format('M-d, D'));
                    }
                } catch (\Exception $e) {
                }
                echo '<td class="dh" style="width: 75pt;">' . htmlspecialchars($label) . '</td>';
            }
            echo '</tr>';

            // Group rows by reservation for robust span calculation
            $groupedRows = [];
            foreach ($allRows as $row) {
                $rn = trim($row['res']['resNo'] ?? $row['res']['conf'] ?? 'N/A');
                $groupedRows[$rn][] = $row;
            }

            $processedRows = [];
             foreach ($groupedRows as $resNo => $rows) {
                 $count = count($rows);
                 if ($count === 0)
                     continue;

                 // 1. Scan to find basePax
                 $basePax = 4;
                 foreach ($rows as $r) {
                     $bp = $r['rates']['base_pax'] ?? 4;
                     if ($bp === 8) {
                         $basePax = 8;
                         break;
                     }
                 }

                 // 2. No padding - only show actual occupant rows

                 $i = 0;
                 while ($i < $count) {
                     $startIdx = $i;
                     $mergeLimit = min($count, $startIdx + $basePax);
                     $span = $mergeLimit - $startIdx;
                     $i = $mergeLimit;

                     for ($k = $startIdx; $k < $mergeLimit; $k++) {
                         $rows[$k]['accRowSpan'] = ($k === $startIdx) ? $span : 0;
                         $rows[$k]['accGroupSize'] = $span;
                         $rows[$k]['accGroupTotals'] = [];
                         foreach ($dateCols as $d) {
                             $rows[$k]['accGroupTotals'][$d] = (float) ($rows[$startIdx]['rateDates'][$d] ?? 0);
                         }

                         $rows[$k]['feeGroupTotals'] = [
                             'air' => 0,
                             'han' => 0,
                             'avi' => 0,
                             'env' => 0
                         ];
                         for ($m = $startIdx; $m < $mergeLimit; $m++) {
                             $rows[$k]['feeGroupTotals']['air'] += (float) ($rows[$m]['res']['calculated_rates']['air'] ?? 0);
                             $rows[$k]['feeGroupTotals']['han'] += (float) ($rows[$m]['res']['calculated_rates']['han'] ?? 0);
                             $rows[$k]['feeGroupTotals']['avi'] += (float) ($rows[$m]['res']['calculated_rates']['avi'] ?? 0);
                             $rows[$k]['feeGroupTotals']['env'] += (float) ($rows[$m]['res']['calculated_rates']['env'] ?? 0);
                         }

                         $processedRows[] = $rows[$k];
                     }
                 }
             }
            $allRows = $processedRows;

            $totals = ['air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
            $dateTotals = array_fill_keys($dateCols, 0);
            $accGrandTotal = 0;
            $last = null;

            // Pre-calculate rowspan counts per reservation number for RSVN# and Village merging
            $resGroupCounts = [];
            foreach ($allRows as $r) {
                $rn = trim($r['res']['resNo'] ?? $r['res']['conf'] ?? '');
                $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
            }

            foreach ($allRows as $idx => $row) {
                $res = $row['res'];
                $rates = $row['rates'];
                $rateDates = $row['rateDates'];
                $accRowSpan = $row['accRowSpan'] ?? 1;
                $accGroupTotals = $row['accGroupTotals'] ?? [];

                $resNo = $res['resNo'] ?? $res['conf'] ?? '';
                $isFirst = ($resNo !== $last);
                if ($isFirst)
                    $last = $resNo;

                $totals['air'] += (float) ($rates['air'] ?? 0);
                $totals['han'] += (float) ($rates['han'] ?? 0);
                $totals['avi'] += (float) ($rates['avi'] ?? 0);
                $totals['env'] += (float) ($rates['env'] ?? 0);

                echo '<tr>';
                echo '<td style="border:none; width:15pt;"></td>';
                if ($isFirst) {
                    $resSpan = $resGroupCounts[$resNo] ?? 1;
                    echo '<td rowspan="' . $resSpan . '" style="vertical-align:middle; text-align:center; font-weight:600">' . htmlspecialchars($resNo) . '</td>';
                    echo '<td rowspan="' . $resSpan . '" style="vertical-align:middle; text-align:center; white-space:normal; mso-data-placement:same-cell;">' . $this->formatVillageWithUnitType($res) . '</td>';
                }
                echo '<td style="text-align:center">' . htmlspecialchars($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                $privCardLower = strtolower($privCard);
                $isValidCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

                $relation = strtoupper(htmlspecialchars($res['gstType'] ?? ''));
                if (!empty($res['is_employee'])) {
                    $relation .= ' (EMPLOYEE)';
                }
                if ($isValidCard) {
                    $relation .= ' (' . strtoupper(htmlspecialchars($privCard)) . ')';
                }

                echo '<td style="text-align:center">' . $relation . '</td>';
                echo '<td style="text-align:center">' . htmlspecialchars($res['age'] ?? '') . '</td>';
                echo '<td style="text-align:center">' . htmlspecialchars($res['dateOfBirth'] ?? '') . '</td>';
                echo '<td style="text-align:center">' . strtoupper(htmlspecialchars($res['nationality_name'] ?? $res['nationality'] ?? '')) . '</td>';
                $arrDt = $res['arrdt'] ?? $res['arrDt'] ?? '';
                $depDt = $res['depdt'] ?? $res['depDt'] ?? '';
                echo '<td style=\'mso-number-format:"\@"; text-align:center;\'>' . ($arrDt ? date('m/d/Y', strtotime($arrDt)) : '') . '</td>';
                echo '<td style=\'mso-number-format:"\@"; text-align:center;\'>' . ($depDt ? date('m/d/Y', strtotime($depDt)) : '') . '</td>';

                if ($accRowSpan > 0) {
                    foreach ($dateCols as $d) {
                        $val = $accGroupTotals[$d] ?? 0;
                        $isWhole = (round($val, 2) == floor(round($val, 2)));
                        $rv = round($val, 2);
                        if ($rv == 0.01)
                            $formattedVal = '1 FVN';
                        elseif ($rv == 0.02)
                            $formattedVal = '1.5 FVN';
                        elseif ($rv == 0.5)
                            $formattedVal = '.5 FVN';
                        elseif ($rv == 3700.01)
                            $formattedVal = '3700.01';
                        else
                            $formattedVal = ($val > 0 ? number_format($val, 2) : '');

                        $style = ' style="text-align: center;"';
                        echo '<td class="num" rowspan="' . $accRowSpan . '"' . $style . '>' . $formattedVal . '</td>';
                        
                        // FVN rates (0.01, 0.02, 0.5) do not contribute to the grand total sum
                        if ($rv !== 0.01 && $rv !== 0.02 && $rv !== 0.5) {
                            $dateTotals[$d] += (float) $val;
                        }
                    }
                }

                // Compute the total accommodation for this group (non-FVN only)
                $groupAccSum = 0;
                foreach ($accGroupTotals as $gv) {
                    $grv = round((float)$gv, 2);
                    if ($grv !== 0.01 && $grv !== 0.02 && $grv !== 0.5) {
                        $groupAccSum += (float)$gv;
                    }
                }
                // Each occupant's fair share of the shared accommodation
                $accGroupSize = max(1, (int) ($row['accGroupSize'] ?? 1));
                $passengerAccTotal = $groupAccSum / $accGroupSize;
                $accGrandTotal += $passengerAccTotal;

                $air = (float) ($res['calculated_rates']['air'] ?? 0);
                $han = (float) ($res['calculated_rates']['han'] ?? 0);
                $avi = (float) ($res['calculated_rates']['avi'] ?? 0);
                $env = (float) ($res['calculated_rates']['env'] ?? 0);

                $passengerTotalRate = $passengerAccTotal + $air + $han + $avi + $env;

                echo '<td class="num-center">' . ($air > 0 ? number_format($air, 2) : '') . '</td>';
                echo '<td class="num-center">' . ($han > 0 ? number_format($han, 2) : '') . '</td>';
                echo '<td class="num-center">' . ($avi > 0 ? number_format($avi, 2) : '') . '</td>';
                echo '<td class="num-center">' . ($env > 0 ? number_format($env, 2) : '') . '</td>';
                echo '<td class="num-center" style="font-weight:bold">' . ($passengerTotalRate > 0 ? number_format($passengerTotalRate, 2) : '') . '</td>';
                echo '</tr>';
                flush();
            }

            $totalPax = count($allRows);

            // Grand totals row
            echo '<tr style="font-weight:bold;background:#f1f5f9">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="2" style="text-align:right">TOTAL PAX:</td>';
            echo '<td style="text-align:center">' . $totalPax . '</td>';
            echo '<td colspan="6" style="text-align:right">GRAND TOTALS:</td>';
            foreach ($dateCols as $d) {
                echo '<td class="num" style="text-align:center;">' . number_format($dateTotals[$d], 2) . '</td>';
            }
            echo '<td class="num" style="text-align:center;">' . number_format($totals['air'], 2) . '</td>';
            echo '<td class="num" style="text-align:center;">' . number_format($totals['han'], 2) . '</td>';
            echo '<td class="num" style="text-align:center;">' . number_format($totals['avi'], 2) . '</td>';
            echo '<td class="num" style="text-align:center;">' . number_format($totals['env'], 2) . '</td>';

            $overallGrandTotal = $accGrandTotal + $totals['air'] + $totals['han'] + $totals['avi'] + $totals['env'];
            echo '<td class="num" style="font-weight:bold;text-align:center;">' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';

            $fullColspan = 9 + $dateCount + 5;
            $labelColspan = 9 + $dateCount + 4;

            $spacerCount = 2; // Always 2 blank rows between main table and summary block

            for ($s = 0; $s < $spacerCount; $s++) {
                echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . $fullColspan . '" style="border:none;">&nbsp;</td></tr>';
            }
            
            $grandTotalsRowIndex = 9 + count($allRows);
            $dueRowIndex = $grandTotalsRowIndex + $spacerCount + 1;

            $paymentRow1 = $dueRowIndex + 3;
            $paymentRow2 = $dueRowIndex + 4;
            $paymentRow3 = $dueRowIndex + 5;
            $paymentRow4 = $dueRowIndex + 6;
            $paymentRow5 = $dueRowIndex + 7;
            
            $colPay = 'A';
            for($i=1; $i < 14 + count($dateCols); $i++) $colPay++;

            echo '<tr>
            <td style="border:none; width:15pt;"></td>
            <td colspan="' . (9 + $dateCount) . '" style="border:none;"></td>
            <td colspan="3" style="background-color:#2b5a97; color:white; font-weight:bold; font-size:11pt; padding:8px 12px; border:none; text-align:left; vertical-align:middle;">TOTAL AMOUNT DUE:</td>
            <td colspan="2" class="num" style="background-color:#2b5a97; color:white; font-weight:bold; font-size:11pt; padding:8px 12px; border:none; text-align:right; vertical-align:middle;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>
            </tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (9 + $dateCount) . '" style="border:none;"></td>';
            echo '<td colspan="5" style="text-align:right; border:none; font-style:italic; color:#64748b; font-size:9pt; padding-top:4px; padding-bottom:12px;">Room Rates include service charge (10%) and VAT (12%)</td>';
            echo '</tr>';

            $leftColspan = $fullColspan - 6;

            // Row 1
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: 1px solid #cbd5e1; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:11pt; color:#dc2626; padding: 10px 15px 2px 15px; text-align: left; background-color:#ffffff;">Booking Guidelines:</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none; font-weight:bold; font-size:10pt; color:#0f172a; text-align: left; padding-bottom:5px;">PAYMENT/S:</td>';
            echo '</tr>';

            // Row 2
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Booking Confirmation</u></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="2" class="num" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 3
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">Full payment is required within seven (7) days of receiving the computation of charges; Unpaid reservations after this period will be released to other Members to ensure maximum availability.</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="2" class="num" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 4
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Flight Schedule</u></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="2" class="num" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 5
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td rowspan="2" colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">With the increasing number of international and domestic flights at NAIA, the Civil Aviation Authority of the Philippines (CAAP) has issued a Memorandum that limits General Aviation, which we are part of, from flying via the NAIA runway from 9 AM to 7 PM daily. Given this restriction, kindly expect that our flights to/from the island may depart from/arrive at Clark Airport (Pampanga).</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="2" class="num" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 5B (Extra Payment Row)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="2" class="num" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 6
            $formula = '=' . number_format($overallGrandTotal, 2, '.', '') . '-SUM(' . $colPay . $paymentRow1 . ':' . $colPay . $paymentRow5 . ')';
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Expectant Mother</u></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="3" style="border-top: 2px solid #dc2626; border-bottom: 2px solid #dc2626; border-left: 2px solid #dc2626; border-right: none; background-color:#fff5f5; color:#dc2626; font-weight:bold; font-size:10pt; padding: 6px 10px; text-align: left; vertical-align: middle;">BALANCE TO SETTLE</td>';
            echo '<td style="border-top: 2px solid #dc2626; border-bottom: 2px solid #dc2626; border-left: none; border-right: none; background-color:#fff5f5; color:#dc2626; font-weight:bold; font-size:11pt; padding: 6px 0px 6px 10px; text-align: right; vertical-align: middle;">&#8369;</td>';
            echo '<td class="num" style="border-top: 2px solid #dc2626; border-bottom: 2px solid #dc2626; border-left: none; border-right: 2px solid #dc2626; background-color:#fff5f5; color:#dc2626; font-weight:bold; font-size:11pt; padding: 6px 10px 6px 0px; text-align: right; vertical-align: middle;">' . $formula . '</td>';
            echo '</tr>';

            // Row 7
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">Guests 20 weeks pregnant or less must present a medical certificate confirming fitness for air travel (issued within 7 days before the flight).</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none;"></td>';
            echo '</tr>';

            // Row 8
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Cancellation Policy</u></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none;"></td>';
            echo '</tr>';

            // Row 9
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">The deadline for cancellation is one week (7 days) prior to the scheduled check-in date. <font color="red"><span style="color: red;">If a cancellation is made within this 7-day period, a fee equivalent to the one-way airfare per passenger and cost of one night per villa will be applied.</span></font> However, for cancellations made with more than 7 days\' notice, any member overpayments may be applied to travel expenses across three key locations and will expire on the member\'s anniversary date.</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none;"></td>';
            echo '</tr>';

            // Row 10
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Rebooking/ Name Change/ Pax replacement</u></td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none;"></td>';
            echo '</tr>';

            // Row 11
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftColspan . '" style="border-top: none; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 15px 15px; background-color:#ffffff;">A fee equivalent to one-way airfare will apply to any changes made within 7 days of the scheduled departure date and time.</td>';
            echo '<td style="border:none;"></td>';
            echo '<td colspan="5" style="border:none;"></td>';
            echo '</tr>';

            echo '</table></body></html>';
        }, 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="' . $filename . '"']);
    }

    public function print(Request $request)
    {
        set_time_limit(0);
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search = $request->get('search');

        try {
            if ($resNoList) {
                $ids = array_filter(explode(',', $resNoList));
            } elseif ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                }
                $all = $listData ?? [];

                // Apply Status Filter (Array Support)
                if ($statusFilter) {
                    if (!is_array($statusFilter))
                        $statusFilter = [$statusFilter];
                    $all = array_filter($all, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                // Apply Global Search
                if ($search) {
                    $search = strtolower(trim($search));
                    $all = array_filter($all, function ($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? $res['resno'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? $res['custname'] ?? $res['guestname'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? $i['resno'] ?? null)->filter()->unique()->toArray();
            }

            if (empty($ids))
                return back()->with('error', 'No data to print.');

            // Self-healing: if dates are empty, fetch check-in/out dates from Detail API of first chunk
            if ((!$fromDate || !$toDate) && !empty($ids)) {
                $firstChunkResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->detailApiUrl, ['resnolist' => implode(',', array_slice($ids, 0, 40))]);
                if (!$firstChunkResp->successful()) {
                    $status = $firstChunkResp->status();
                    $err = $status == 503 ? 'Balesin API Service is temporarily unavailable (503 Service Unavailable).' : "Balesin API Error (Status {$status}).";
                    return back()->with('error', $err);
                }
                $msgs = $firstChunkResp->json()['msg'] ?? [];
                    $minDate = null;
                    $maxDate = null;
                    foreach ($msgs as $m) {
                        $arr = $m['arrdt'] ?? $m['arrDt'] ?? '';
                        $dep = $m['depdt'] ?? $m['depDt'] ?? '';
                        if ($arr) {
                            if (!$minDate || $arr < $minDate) $minDate = $arr;
                        }
                        if ($dep) {
                            if (!$maxDate || $dep > $maxDate) $maxDate = $dep;
                        }
                    }
                    if ($minDate && $maxDate) {
                        $fromDate = $minDate;
                        $toDate = $maxDate;
                    }
                }

            // ALWAYS fetch List API to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            $listRateMap = [];
            $memberMap = [];
            $memberMetaMap = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    foreach ($listResp->json()['msg'] ?? [] as $lr) {
                        $this->ingestListReservation($lr, $rateMap, $listRateMap, $memberMap, $memberMetaMap);
                    }
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
        }

        $reservations = [];
        $calculator = app(\App\Services\RateCalculatorService::class);
        $paxIndices = [];
        $occupantIndices = [];
        $reservationPaxCounts = [];
        $memberMap = $memberMap ?? [];
        $listRateMap = $listRateMap ?? [];
        foreach (array_chunk(array_slice($ids, 0, 1000), 50) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if (!$resp->successful()) {
                $status = $resp->status();
                $err = $status == 503 ? 'Balesin API Service is temporarily unavailable (503 Service Unavailable).' : "Balesin API Error (Status {$status}).";
                return back()->with('error', $err);
            }
            $msgs = $resp->json()['msg'] ?? [];
                $this->consolidateRates($msgs);

                foreach ($msgs as $res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    if (!isset($reservationPaxCounts[$resNo]))
                        $reservationPaxCounts[$resNo] = 0;
                    $age = (int) ($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);
                    if (!$isInfant)
                        $reservationPaxCounts[$resNo]++;
                }

                $localMetaMap = [];
                foreach ($msgs as $m) {
                    $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                    if ($rn === '')
                        continue;

                    $mCode = $m['rateCode'] ?? $m['rate_code'] ?? '';
                    $mType = $m['rate'] ?? '';

                    if (!isset($localMetaMap[$rn]))
                        $localMetaMap[$rn] = '';

                    if (is_string($mType) && trim($mType) !== '' && !str_contains($localMetaMap[$rn], strtoupper(trim($mType)))) {
                        $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . strtoupper(trim($mType));
                    }
                    if (is_string($mCode) && trim($mCode) !== '' && !str_contains($localMetaMap[$rn], strtoupper(trim($mCode)))) {
                        $localMetaMap[$rn] .= ($localMetaMap[$rn] ? '|' : '') . strtoupper(trim($mCode));
                    }
                }

                // Pre-scan msgs to map reservation number to correct, non-empty room type
                $resRoomTypeMap = [];
                foreach ($msgs as $m) {
                    $rn = trim($m['resNo'] ?? $m['conf'] ?? '');
                    if ($rn === '') continue;
                    $rt = $m['roomtyp'] ?? $m['roomType'] ?? '';
                    if (is_string($rt) && trim($rt) !== '' && strtoupper(trim($rt)) !== 'N/A') {
                        $resRoomTypeMap[$rn] = trim($rt);
                    }
                }

                $this->hydrateListMetadataFromDetail($msgs, $rateMap, $listRateMap, $memberMap, $memberMetaMap);

                $resUnitTypes = $this->buildReservationUnitTypes(
                    $rateMap,
                    $listRateMap,
                    $localMetaMap,
                    $resRoomTypeMap,
                    $msgs
                );

                foreach ($msgs as &$res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    $res['customer_name'] = $memberMap[$resNo] ?? '';
                    $meta = $memberMetaMap[$resNo] ?? [];
                    $res['memberNo'] = $meta['memberNo'] ?? $res['memberNo'] ?? '';
                    $res['bookDate'] = $meta['bookDate'] ?? $res['bookDate'] ?? '';
                    $res['conactNo'] = $meta['conactNo'] ?? $res['conactNo'] ?? $res['contactNo'] ?? '';
                    $res['rate_metadata'] = $this->buildReservationMetadata($resNo, $rateMap, $localMetaMap);
                    $this->appendFvnMetadataFlags($res);

                    $age = (int) ($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $res['age'] !== '' && $age >= 0 && $age <= 1);

                    if (!isset($paxIndices[$resNo]))
                        $paxIndices[$resNo] = 0;
                    if (!isset($occupantIndices[$resNo]))
                        $occupantIndices[$resNo] = 0;
                    $paxIndex = $paxIndices[$resNo];
                    $occupantIndex = $occupantIndices[$resNo];
                    $roomType = $resRoomTypeMap[$resNo] ?? $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $res['roomtyp'] = $roomType;
                    $res['roomType'] = $roomType;
                    $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                    $res['calculated_rates'] = $calculator->calculatePassengerRates(
                        $res,
                        $paxIndex,
                        $roomType,
                        $totalBillable,
                        $occupantIndex
                    );

                    // Sync rates back to the rate array for display
                    foreach ($res['rate'] as &$r) {
                        $d = $r['date'] ?? '';
                        if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                            $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                            $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                        }
                    }
                    unset($r);

                    $occupantIndices[$resNo]++;
                    if (!$isInfant) {
                        $paxIndices[$resNo]++;
                    }

                    $res['is_employee'] = $res['calculated_rates']['is_employee'] ?? false;
                    $res['village_name'] = $this->getVillageName($roomType);
                    $this->applyUnitTypeLabel($res, $resUnitTypes[$resNo] ?? 'VILLA');
                    $res['nationality_name'] = $this->getNationalityName($res['nationality'] ?? '');
                }
                unset($res); // Fix reference leak
                $reservations = array_merge($reservations, $msgs);
        }

        // Collect all unique dates across all reservations to define columns
        $allDates = [];
        foreach ($reservations as $res) {

            foreach ($res['rate'] ?? [] as $r) {
                $d = $r['date'] ?? '';
                if ($d && !str_contains($d, ' to ')) {
                    $allDates[$d] = true;
                }
            }
        }
        ksort($allDates);
        $dateCols = array_keys($allDates);

        // Group rows by reservation for robust span calculation in print view
        $groupedReservations = [];
        foreach ($reservations as $res) {
            $rn = trim($res['resNo'] ?? $res['conf'] ?? 'N/A');
            $groupedReservations[$rn][] = $res;
        }

        $processedReservations = [];
        $accSpans = []; // We will store span info per index in the final list
        foreach ($groupedReservations as $resNo => $rows) {
            $count = count($rows);
            if ($count === 0)
                continue;

            // 1. Scan to find basePax
            $basePax = 4;
            foreach ($rows as $r) {
                $bp = $r['calculated_rates']['base_pax'] ?? 4;
                if ($bp === 8) {
                    $basePax = 8;
                    break;
                }
            }

            // 2. No padding - only show actual occupant rows

            $i = 0;
            while ($i < $count) {
                $startIdx = $i;
                $mergeLimit = min($count, $startIdx + $basePax);
                $span = $mergeLimit - $startIdx;
                $i = $mergeLimit;

                for ($k = $startIdx; $k < $mergeLimit; $k++) {
                    $rows[$k]['acc_group_size'] = $span;
                    $rows[$k]['acc_group_totals'] = [];
                    foreach ($dateCols as $d) {
                        $uv = (float) ($rows[$startIdx]['calculated_rates']['acc_dates'][$d]['val'] ?? 0);
                        if ($uv == 0 && isset($rows[$startIdx]['rate'])) {
                            foreach ($rows[$startIdx]['rate'] as $rt) {
                                if (($rt['date'] ?? '') === $d) {
                                    $uv = (float) ($rt['val'] ?? 0);
                                    break;
                                }
                            }
                        }
                        $rows[$k]['acc_group_totals'][$d] = $uv;
                    }
                    if ($k === $startIdx) {
                        $rows[$k]['acc_span'] = $span;
                    } else {
                        $rows[$k]['acc_span'] = 0;
                    }
                    $processedReservations[] = $rows[$k];
                }
            }
        }
        $reservations = $processedReservations;

        $balesinLogoUrl = $this->balesinLogoUrl();

        return view('print', compact('reservations', 'accSpans', 'dateCols', 'fromDate', 'toDate', 'balesinLogoUrl'));
    }

    private function consolidateRates(array &$msgs)
    {
        $grouped = [];
        foreach ($msgs as $res) {
            $resNo = trim($res['resNo'] ?? $res['conf'] ?? 'N/A');
            $gstName = trim($res['gstName'] ?? $res['guestName'] ?? $res['gstname'] ?? $res['guestname'] ?? 'Unknown');
            $key = $resNo . '|' . $gstName;

            if (!isset($grouped[$key])) {
                $grouped[$key] = $res;
                // Initialize rate map for accumulation
                $grouped[$key]['_rateMap'] = [];
            } else {
                // Thoroughly merge all fields (except rates which are handled below)
                // This ensures components like airfare, hangar, etc. from secondary records are kept
                foreach ($res as $k => $v) {
                    if ($k !== 'rate' && (empty($grouped[$key][$k])) && !empty($v)) {
                        $grouped[$key][$k] = $v;
                    }
                }
            }

            // Consolidate rates from current record into the map
            foreach ($res['rate'] ?? [] as $r) {
                $date = $r['date'] ?? '';
                if ($date) {
                    $grouped[$key]['_rateMap'][$date] = ($grouped[$key]['_rateMap'][$date] ?? 0) + (float) ($r['val'] ?? 0);
                }
            }
        }

        $final = [];
        foreach ($grouped as $res) {
            $newRates = [];
            ksort($res['_rateMap']);
            foreach ($res['_rateMap'] as $date => $val) {
                $newRates[] = ['date' => $date, 'val' => $val];
            }
            $res['rate'] = $newRates;
            unset($res['_rateMap']);
            $final[] = $res;
        }
        $msgs = $final;
    }

    private function balesinLogoUrl(): string
    {
        return 'https://balesin.com/wp-content/uploads/2025/09/balesin-island-logo-dark-blue-min.png';
    }

    private function balesinLogoImageFormula(): string
    {
        return '=IMAGE("' . $this->balesinLogoUrl() . '")';
    }
}
