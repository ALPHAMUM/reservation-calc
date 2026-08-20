<?php

namespace App\Http\Controllers;

use App\Services\Properties\BalesinPinesHandler;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReservationExport;

class BalesinPinesController extends Controller
{
    protected BalesinPinesHandler $handler;

    public function __construct(BalesinPinesHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Display Balesin Pines Dashboard
     */
    public function index(Request $request)
    {
        set_time_limit(300);
        return $this->handler->index($request);
    }

    /**
     * Dedicated Balesin Pines Printout Action
     */
    public function print(Request $request)
    {
        set_time_limit(300);

        $resNoList    = $request->get('resnolist');
        $fromDate     = $request->get('fromdate');
        $toDate       = $request->get('todate');
        $statusFilter = $request->get('status_filter');
        $search       = $request->get('search');

        $listApiUrl   = config('services.balesin_pines.list_url');
        $detailApiUrl = config('services.balesin_pines.detail_url');
        $apiKey       = config('services.balesin_pines.api_key');

        $reservations = [];
        $ids = [];

        try {
            if ($resNoList) {
                $ids = array_filter(explode(',', $resNoList));
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
                        $name  = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? '');
                        return str_contains($resNo, $s) || str_contains($name, $s);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
            }

            if (empty($ids)) {
                return back()->with('error', 'No Balesin Pines data to print.');
            }

            // Fetch Detail API for IDs
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

            // Apply Pines rate calculator logic
            $calculator = app(\App\Services\BalesinPinesRateCalculatorService::class);
            $reservations = [];
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

                $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                $unitType = $calculator->resolvePinesUnitType($roomType, $res['rateCode'] ?? '');

                $res['calculated_rates'] = $calculator->calculatePassengerRates(
                    $res,
                    $occupantIndex,
                    $roomType,
                    2,
                    $occupantIndex
                );
                $res['village_name'] = $unitType;
                $res['is_employee']  = false;

                if (isset($res['rate']) && is_array($res['rate'])) {
                    foreach ($res['rate'] as &$r) {
                        $d = $r['date'] ?? '';
                        if ($d && isset($res['calculated_rates']['acc_dates'][$d]['breakdown'])) {
                            $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                        }
                    }
                    unset($r);
                }

                $reservations[] = $res;
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Print Error (Pines): ' . $e->getMessage());
        }

        // Build date headers
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

        // Pre-calculate spans per reservation for Pines (base_pax = 2)
        $resGroups = [];
        foreach ($reservations as $r) {
            $rn = trim($r['resNo'] ?? $r['conf'] ?? 'N/A');
            $resGroups[$rn][] = $r;
        }

        $accSpans = [];
        foreach ($resGroups as $rn => $rows) {
            $count = count($rows);
            $basePax = 2;
            $curr = 0;
            while ($curr < $count) {
                $blockStart = $curr;
                $blockSize = min($count - $curr, $basePax);
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
                    $isExtraPerson = (($curr + $i) >= 2 && isset($rows[$curr + $i]));
                    if ($isExtraPerson) {
                        $extraTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = (float)($rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['val'] ?? 0);
                            if ($uv == 0) {
                                foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                    if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                                }
                            }
                            $extraTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $extraTotals, 'group_size' => 1, 'is_extra_person' => true];
                    } else {
                        $accSpans[] = ['span' => $span, 'totals' => $groupTotals, 'group_size' => $blockSize, 'is_extra_person' => false];
                    }
                }
                $curr += $blockSize;
            }
        }

        // Attach to each reservation
        foreach ($reservations as $idx => &$res) {
            $spanInfo = $accSpans[$idx] ?? ['span' => 1, 'totals' => [], 'group_size' => 1, 'is_extra_person' => false];
            $res['acc_span']          = $spanInfo['span'];
            $res['acc_group_size']    = $spanInfo['group_size'];
            $res['acc_group_totals']  = $spanInfo['totals'];
            $res['is_extra_person']   = $spanInfo['is_extra_person'] ?? false;
        }
        unset($res);

        $logoPath = public_path('images/balesin-logo.png');
        $balesinLogoUrl = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        return view('pines_print', compact('reservations', 'accSpans', 'dateCols', 'fromDate', 'toDate', 'balesinLogoUrl'));
    }

    /**
     * Balesin Pines Excel Export Action — Full HTML/XLS format
     */
    public function export(Request $request)
    {
        set_time_limit(300);

        $resNoList    = $request->get('resnolist');
        $fromDate     = $request->get('fromdate');
        $toDate       = $request->get('todate');
        $statusFilter = $request->get('status_filter');

        $listApiUrl   = config('services.balesin_pines.list_url');
        $detailApiUrl = config('services.balesin_pines.detail_url');
        $apiKey       = config('services.balesin_pines.api_key');

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
            return back()->with('error', 'No Balesin Pines data to export.');
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

        $calculator   = app(\App\Services\BalesinPinesRateCalculatorService::class);
        $reservations = [];
        $resOccupantIndex = [];

        foreach ($rawDetail as $res) {
            $resNo = trim((string)($res['resNo'] ?? $res['conf'] ?? ''));
            if (!isset($resOccupantIndex[$resNo])) {
                $resOccupantIndex[$resNo] = 0;
            }
            $occupantIndex = $resOccupantIndex[$resNo]++;

            $roomType                = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType                = $calculator->resolvePinesUnitType($roomType, $res['rateCode'] ?? '');
            $res['calculated_rates'] = $calculator->calculatePassengerRates($res, $occupantIndex, $roomType, 2, $occupantIndex);
            $res['village_name']     = $unitType;
            $res['is_employee']      = false;
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

        // ========== SPAN COMPUTATION (grouping per reservation, base_pax=2) ==========
        $resGroups = [];
        foreach ($reservations as $r) {
            $rn = trim($r['resNo'] ?? $r['conf'] ?? 'N/A');
            $resGroups[$rn][] = $r;
        }

        $accSpans = [];
        foreach ($resGroups as $rn => $rows) {
            $count = count($rows);
            $basePax = 2;
            $curr = 0;
            while ($curr < $count) {
                $blockStart = $curr;
                $blockSize = min($count - $curr, $basePax);
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
                    $isExtraPerson = (($curr + $i) >= 2 && isset($rows[$curr + $i]));
                    if ($isExtraPerson) {
                        $extraTotals = [];
                        foreach ($dateCols as $d) {
                            $uv = (float)($rows[$curr + $i]['calculated_rates']['acc_dates'][$d]['val'] ?? 0);
                            if ($uv == 0) {
                                foreach ($rows[$curr + $i]['rate'] ?? [] as $rt) {
                                    if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                                }
                            }
                            $extraTotals[$d] = $uv;
                        }
                        $accSpans[] = ['span' => 1, 'totals' => $extraTotals, 'group_size' => 1, 'is_extra_person' => true];
                    } else {
                        $accSpans[] = ['span' => $span, 'totals' => $groupTotals, 'group_size' => $blockSize, 'is_extra_person' => false];
                    }
                }
                $curr += $blockSize;
            }
        }

        // Attach to each reservation
        foreach ($reservations as $idx => &$res) {
            $spanInfo = $accSpans[$idx] ?? ['span' => 1, 'totals' => [], 'group_size' => 1, 'is_extra_person' => false];
            $res['acc_span']          = $spanInfo['span'];
            $res['acc_group_size']    = $spanInfo['group_size'];
            $res['acc_group_totals']  = $spanInfo['totals'];
            $res['is_extra_person']   = $spanInfo['is_extra_person'] ?? false;
        }
        unset($res);
        // ======================================================================

        // Pre-compute reservation group counts for rowspan on RSVN# and Room Type
        $resGroupCounts = [];
        foreach ($reservations as $res) {
            $rn = trim($res['resNo'] ?? $res['conf'] ?? '');
            $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
        }

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

        $pinesLogoUrl     = 'https://balesin.com/wp-content/uploads/2026/02/Balesin-Pines-Alternate-Logo_Primary-Color.png';
        $logoImageFormula = '=IMAGE("' . $pinesLogoUrl . '",,3,0,150)';
        $filename         = 'balesin_pines_reservations_' . date('Ymd_His') . '.xls';

        if (ob_get_level()) ob_end_clean();

        return response()->stream(function () use (
            $reservations, $dateCols, $dateCount, $logoImageFormula,
            $firstMemberName, $firstMemberNo, $firstContactNo, $formattedBookDate,
            $resGroupCounts
        ) {
            $emptyColsSpan = $dateCount + 1; // +1 for Total Rate column

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>';
            echo 'body { font-family: sans-serif; }';
            echo 'td, th { border: 0.5pt solid #9fd99c; vertical-align: middle; font-size: 8pt; padding: 5pt; }';
            echo '.hdr  { background-color: #caeac8; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.dh   { background-color: #caeac8; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.num  { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '.num-center { mso-number-format:"\#\,\#\#0\.00"; text-align: center; }';
            echo '</style></head><body>';

            echo '<table style="border-collapse: collapse; border: none;">';
            echo '<colgroup><col style="mso-width-source:userset; width:15pt;"></colgroup>';

            // ---- Header rows (Logo, Member info, Note) ----
            echo '<tr style="height:10pt;">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (8 + $emptyColsSpan) . '" style="border:none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td rowspan="5" colspan="2" style="text-align: center; vertical-align: middle; padding: 10px; border: none; border-left: 0.5pt solid #9fd99c; border-top: 0.5pt solid #9fd99c; border-bottom: 20px solid transparent;"';
            echo ' x:fmla="' . htmlspecialchars($logoImageFormula, ENT_QUOTES, 'UTF-8') . '"></td>';
            echo '<td style="font-weight: bold; background-color: #caeac8; padding: 8px; text-align: center; color: #000;">MEMBER\'S NAME:</td>';
            echo '<td colspan="5" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberName) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #caeac8; padding: 8px; text-align: center; color: #000;">MEMBERSHIP NUMBER:</td>';
            echo '<td colspan="5" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($firstMemberNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #caeac8; padding: 8px; text-align: center; color: #000;">CONTACT NUMBER:</td>';
            echo '<td colspan="5" style="padding: 8px; text-align: center; color: #000; font-weight: bold; mso-number-format:\'\\@\';">' . htmlspecialchars((string)$firstContactNo) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td style="font-weight: bold; background-color: #caeac8; padding: 8px; text-align: center; color: #000;">BOOKING DATE:</td>';
            echo '<td colspan="5" style="padding: 8px; text-align: center; color: #000; font-weight: bold;">' . htmlspecialchars($formattedBookDate) . '</td>';
            echo '<td colspan="' . $emptyColsSpan . '" style="border: none;"></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (6 + $emptyColsSpan) . '" style="border:none; font-size:5.5pt; color:#1e293b; font-style:italic; padding: 4px 0px; text-align: left; white-space: nowrap; vertical-align: middle;">';
            echo '<b style="color: red;">NOTE:</b> Deluxe Rooms and Junior Suites are designed to comfortably accommodate a maximum of two (2) adults. Only selected rooms can accommodate a third occupant for an additional charge of PHP 3,700 per night. To ensure a seamless check-in, please declare all guests at the time of booking.';
            echo '</td>';
            echo '</tr>';

            echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . (8 + $emptyColsSpan) . '" style="border: none; height: 10px;"></td></tr>';

            // ---- Table headers ----
            echo '<tr>';
            echo '<td rowspan="2" style="border:none; width:15pt;"></td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt;">RSVN#</td>';
            echo '<td rowspan="2" class="hdr" style="width: 100pt;">ROOM TYPE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 120pt;">OCCUPANTS</td>';
            echo '<td rowspan="2" class="hdr" style="width: 80pt;">RELATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 35pt;">AGE</td>';
            echo '<td rowspan="2" class="hdr" style="width: 70pt;">BIRTHDAY</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt;">CHECK-IN</td>';
            echo '<td rowspan="2" class="hdr" style="width: 75pt;">CHECK-OUT</td>';
            echo '<td colspan="' . $dateCount . '" class="dh">ACCOMMODATION</td>';
            echo '<td rowspan="2" class="hdr" style="width: 90pt; word-wrap:normal;">TOTAL RATE (Per Occupant)</td>';
            echo '</tr>';

            echo '<tr>';
            foreach ($dateCols as $d) {
                $label = $d;
                try {
                    $dt    = new \DateTime($d);
                    $label = strtoupper($dt->format('M-d, D'));
                } catch (\Exception $e) {}
                echo '<td class="dh" style="width: 75pt;">' . htmlspecialchars($label) . '</td>';
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

                $span        = $res['acc_span'] ?? 1;
                $groupTotals = $res['acc_group_totals'] ?? [];
                $groupSize   = max(1, (int) ($res['acc_group_size'] ?? 1));
                $isExtraPerson = $res['is_extra_person'] ?? false;

                $perOccupantAcc = $rates['acc'] ?? 0;

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
                echo '<td style=\'mso-number-format:"\\@"; text-align:center;\'>' . ($arrDt ? date('m/d/Y', strtotime($arrDt)) : '') . '</td>';
                echo '<td style=\'mso-number-format:"\\@"; text-align:center;\'>' . ($depDt ? date('m/d/Y', strtotime($depDt)) : '') . '</td>';

                // Accommodation cells – merged using rowspan
                if ($span > 0) {
                    foreach ($dateCols as $d) {
                        $val = $groupTotals[$d] ?? ($rates['acc_dates'][$d]['val'] ?? 0);
                        $rv = round($val, 2);
                        $frac = round($rv - floor($rv), 2);
                        $integerPart = floor($rv);
                        if ($frac == 0.01)      $formattedVal = '1 FVN';
                        elseif ($frac == 0.02)  $formattedVal = '1.5 FVN';
                        elseif ($frac == 0.03)  $formattedVal = ($integerPart > 0) ? number_format($integerPart, 2) . '<br>.5 FVN' : '.5 FVN';
                        else                    $formattedVal = ($val > 0 ? number_format($val, 2) : '');

                        echo '<td class="num" rowspan="' . $span . '" style="text-align: center;">' . $formattedVal . '</td>';

                        if (!in_array($frac, [0.01, 0.02, 0.03])) {
                            $dateTotals[$d] += (float) $val;
                        } else {
                            if ($frac == 0.03) {
                                $dateTotals[$d] += floor($val);
                            }
                        }
                    }
                }

                echo '<td class="num-center" style="font-weight:bold">' . number_format($perOccupantAcc, 2) . '</td>';
                echo '</tr>';
                flush();
            }

            // ---- Grand totals row ----
            $totalPax          = count($reservations);
            $overallGrandTotal = $accGrandTotal;
            $fullColspan       = 9 + $dateCount + 1; // 8 fixed columns + date cols + 1 total rate

            echo '<tr style="font-weight:bold;background:#eef7ee">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="2" style="text-align:right">TOTAL PAX:</td>';
            echo '<td style="text-align:center">' . $totalPax . '</td>';
            echo '<td colspan="5" style="text-align:right">GRAND TOTALS:</td>';
            foreach ($dateCols as $d) {
                echo '<td class="num" style="text-align:center;">' . number_format($dateTotals[$d], 2) . '</td>';
            }
            echo '<td class="num" style="font-weight:bold;text-align:center;">' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';

            // ---- Guidelines & Payments section ----
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

            // TOTAL AMOUNT DUE row (replace existing)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftOffsetCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="background-color:#caeac8; color:#1e293b; font-weight:bold; font-size:10pt; padding:8px 12px; border:0.5pt solid #9fd99c; text-align:left; vertical-align:middle;">TOTAL AMOUNT DUE:</td>';
            echo '<td colspan="' . $payAmtCols . '" class="num" style="background-color:#caeac8; color:#1e293b; font-weight:bold; font-size:10pt; padding:8px 12px; border:0.5pt solid #9fd99c; text-align:right; vertical-align:middle;" x:fmla="' . $overallGrandTotal . '">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';

            // VAT note – smaller font (7pt)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $leftOffsetCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="text-align:right; border:none; font-style:italic; color:#64748b; font-size:7pt; padding-top:4px; padding-bottom:12px;">Room Rates include service charge (10%) and VAT (12%)</td>';
            echo '</tr>';

            // Row 1: Booking Guidelines title + PAYMENT/S header
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: 1px solid #9fd99c; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:10pt; color:#2d6b2b; padding: 10px 15px 2px 15px; text-align: left; background-color:#ffffff;">Booking Guidelines:</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="border:none; font-weight:bold; font-size:9pt; color:#0f172a; text-align: left; padding-bottom:5px; padding-left:8px;">PAYMENT/S:</td>';
            echo '</tr>';

            // Row 2: Entry to Property subtitle + Payment row 1 (empty)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:8.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Entry to Property</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 3: Entry to Property content (smaller font) + Payment row 2
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-size:7.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">Entry to Alphaland Baguio Mountain Lodges &amp; Balesin Pines will be strictly limited to the guests declared on your confirmed reservation. All members and guests must present a valid government-issued ID at the Alphaland Baguio Mountain Lodges main gate for verification.</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 4: Cancellation Policy subtitle + Payment row 3
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:8.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Cancellation Policy</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 5: Cancellation Policy content (smaller font)
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: 1px solid #9fd99c; font-size:7.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 15px 15px; background-color:#ffffff;">Cancellations made within 7 days of the check-in date will result in the forfeiture of the first night\'s accommodation cost. For advance cancellations (entire booking, room, or occupant), any unused payments may be applied to future bookings and food &amp; beverage charges across three Balesin Key locations.</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            
            // BALANCE TO SETTLE with amount
            echo '<td colspan="' . $payDescCols . '" style="border: 1.5pt solid #2d6b2b; background-color: #eef7ee; color: #2d6b2b; font-weight: bold; font-size: 10pt; padding: 6px 10px; text-align: left; vertical-align: middle;">BALANCE TO SETTLE: </td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1.5pt solid #2d6b2b; background-color: #eef7ee; color: #2d6b2b; font-weight: bold; font-size: 10pt; padding: 6px 10px; text-align: right; vertical-align: middle;" x:fmla="=R[-6]C - SUM(R[-3]C:R[-1]C)">' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';
            echo '</table></body></html>';
        }, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
