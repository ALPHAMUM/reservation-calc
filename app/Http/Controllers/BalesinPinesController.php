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
            foreach ($rawDetail as $res) {
                $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                $unitType = $calculator->resolvePinesUnitType($roomType, $res['rateCode'] ?? '');

                $res['calculated_rates'] = $calculator->calculatePassengerRates(
                    $res,
                    0,
                    $roomType,
                    2
                );
                $res['village_name'] = $unitType;
                $res['is_employee']  = false;
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

        // Group rows by reservation number
        $accSpans = [];
        foreach ($reservations as $r) {
            $accSpans[] = ['span' => 1, 'totals' => []];
        }

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
        foreach ($rawDetail as $res) {
            $roomType                = $res['roomtyp'] ?? $res['roomType'] ?? '';
            $unitType                = $calculator->resolvePinesUnitType($roomType, $res['rateCode'] ?? '');
            $res['calculated_rates'] = $calculator->calculatePassengerRates($res, 0, $roomType, 2);
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

        // Extract header metadata
        $firstMemberName = '';
        $firstMemberNo   = '';
        $firstContactNo  = '';
        $firstBookDate   = '';
        foreach ($reservations as $res) {
            if (!$firstMemberName && !empty($res['customer_name']))   $firstMemberName = $res['customer_name'];
            if (!$firstMemberNo   && !empty($res['memberNo']))         $firstMemberNo   = $res['memberNo'];
            if (!$firstContactNo  && !empty($res['conactNo']))         $firstContactNo  = (string)$res['conactNo'];
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

        // Pre-compute reservation group counts
        $resGroupCounts = [];
        foreach ($reservations as $res) {
            $rn                  = trim($res['resNo'] ?? $res['conf'] ?? '');
            $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
        }

        return response()->stream(function () use (
            $reservations, $dateCols, $dateCount, $logoImageFormula,
            $firstMemberName, $firstMemberNo, $firstContactNo, $formattedBookDate,
            $resGroupCounts
        ) {
            // Col count: 9 fixed + date cols + 1 (TOTAL RATE — no airfare cols for Pines)
            $emptyColsSpan = $dateCount + 1;

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

            // Row 1: narrow top margin
            echo '<tr style="height:10pt;">';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (8 + $emptyColsSpan) . '" style="border:none;"></td>';
            echo '</tr>';

            // Logo + MEMBER'S NAME row
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

            // NOTE row below booking date
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . (6 + $emptyColsSpan) . '" style="border:none; font-size:5.5pt; color:#1e293b; font-style:italic; padding: 4px 0px; text-align: left; white-space: nowrap; vertical-align: middle;">';
            echo '<b style="color: red;">NOTE:</b> Deluxe Rooms and Junior Suites are designed to comfortably accommodate a maximum of two (2) adults. Only selected rooms can accommodate a third occupant for an additional charge of PHP 3,700 per night. To ensure a seamless check-in, please declare all guests at the time of booking.';
            echo '</td>';
            echo '</tr>';

            // Spacer
            echo '<tr><td style="border:none; width:15pt;"></td><td colspan="' . (8 + $emptyColsSpan) . '" style="border: none; height: 10px;"></td></tr>';

            // Header Row 1
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

            // Header Row 2: date sub-columns
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
                $perOccupantAcc = $rates['acc'] ?? ($groupAccSum / $groupSize);
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

                if ($span > 0) {
                    foreach ($dateCols as $d) {
                        $val = $rates['acc_dates'][$d]['val'] ?? ($groupTotals[$d] ?? 0);
                        $rv  = round($val, 2);
                        if ($rv == 0.01)        $formattedVal = '1 FVN';
                        elseif ($rv == 0.02)    $formattedVal = '1.5 FVN';
                        elseif ($rv == 0.5)     $formattedVal = '.5 FVN';
                        elseif ($rv == 3700.01) $formattedVal = '3700.01';
                        else                    $formattedVal = ($val > 0 ? number_format($val, 2) : '');

                        echo '<td class="num" rowspan="' . $span . '" style="text-align: center;">' . $formattedVal . '</td>';

                        if ($rv !== 0.01 && $rv !== 0.02 && $rv !== 0.5) {
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
            echo '<td colspan="' . $payDescCols . '" style="background-color:#caeac8; color:#1e293b; font-weight:bold; font-size:11pt; padding:8px 12px; border:0.5pt solid #9fd99c; text-align:left; vertical-align:middle;">TOTAL AMOUNT DUE:</td>';
            echo '<td colspan="' . $payAmtCols . '" class="num" style="background-color:#caeac8; color:#1e293b; font-weight:bold; font-size:13pt; padding:8px 12px; border:0.5pt solid #9fd99c; text-align:right; vertical-align:middle;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
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
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: 1px solid #9fd99c; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:11pt; color:#2d6b2b; padding: 10px 15px 2px 15px; text-align: left; background-color:#ffffff;">Booking Guidelines:</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="border:none; font-weight:bold; font-size:10pt; color:#0f172a; text-align: left; padding-bottom:5px; padding-left:8px;">PAYMENT/S:</td>';
            echo '</tr>';

            // Row 2: Entry to Property subtitle + Payment row 1
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Entry to Property</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 3: Entry to Property content + Payment row 2
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 8px 15px; background-color:#ffffff;">Entry to Alphaland Baguio Mountain Lodges &amp; Balesin Pines will be strictly limited to the guests declared on your confirmed reservation. All members and guests must present a valid government-issued ID at the Alphaland Baguio Mountain Lodges main gate for verification.</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 4: Cancellation Policy subtitle + Payment row 3
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: none; font-weight:bold; font-size:9.5pt; color:#1e293b; padding: 5px 15px 2px 15px; text-align: left; background-color:#ffffff;"><u>Cancellation Policy</u></td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $payDescCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '<td colspan="' . $payAmtCols . '" style="border: 1px solid #cbd5e1; background-color: #ffffff; height: 18pt;"></td>';
            echo '</tr>';

            // Row 5: Cancellation Policy content + Balance to settle
            echo '<tr>';
            echo '<td style="border:none; width:15pt;"></td>';
            echo '<td colspan="' . $guidelinesCols . '" style="border-top: none; border-left: 1px solid #9fd99c; border-right: 1px solid #9fd99c; border-bottom: 1px solid #9fd99c; font-size:8.5pt; color:#334155; white-space:normal; vertical-align:top; text-align:left; padding: 0px 15px 15px 15px; background-color:#ffffff;">Cancellations made within 7 days of the check-in date will result in the forfeiture of the first night\'s accommodation cost. For advance cancellations (entire booking, room, or occupant), any unused payments may be applied to future bookings and food &amp; beverage charges across three Balesin Key locations.</td>';
            echo '<td colspan="' . $spacerCols . '" style="border:none;"></td>';
            echo '<td colspan="' . $paymentCols . '" style="border: 1.5pt solid #2d6b2b; background-color: #eef7ee; color: #2d6b2b; font-weight: bold; font-size: 10pt; padding: 6px 10px; text-align: center; vertical-align: middle;">BALANCE TO SETTLE</td>';
            echo '</tr>';

            echo '</table></body></html>';
        }, 200, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
