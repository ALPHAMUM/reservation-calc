<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReservationExport;

class ReservationController extends Controller
{
    private $listApiUrl = 'https://intimusapi.balesinkey.com/api/upastraindata/intimusapiservice/getreslist';
    private $detailApiUrl = 'https://intimusapi.balesinkey.com/api/upastraindata/intimusapiservice/getresdetforcalc';
    private $apiKey = '12A3A9C3-3D8F-4204-9737-3C4ADE94650F'; //Training
    // private $apiKey = 'A450BD7D-DD86-4A54-A6A5-A971B64CC7AB'; //Prod

    public function index(Request $request)
    {
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        if ($perPage > 100)
            $perPage = 100;

        $reservations = [];
        $total = 0;
        $viewType = 'summary';
        $error = null;

        if (!$resNoList && !$fromDate) {
            $fromDate = date('Y-m-d', strtotime('-1 month'));
            $toDate = date('Y-m-d');
        }

        try {
            if ($resNoList) {
                $viewType = 'detail';
                $allConfIds = array_filter(explode(',', $resNoList));
                $chunks = array_chunk($allConfIds, 25);
                $calculator = app(\App\Services\RateCalculatorService::class);
                foreach ($chunks as $chunk) {
                    $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                        ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);

                    if (!$resp->successful()) {
                        $error = "API Error (Detail): " . ($resp->status() == 404 ? "Endpoint not found." : "Status " . $resp->status());
                        break;
                    }

                    $msgs = $resp->json()['msg'] ?? [];
                    $this->consolidateRates($msgs);
                    foreach ($msgs as &$res) {
                        $res['calculated_rates'] = $calculator->calculatePassengerRates($res);
                    }
                    $reservations = array_merge($reservations, $msgs);
                }
                $total = count($reservations);
                $pagedReservations = array_slice($reservations, ($page - 1) * $perPage, $perPage);
            } else {
                $viewType = 'list';
                $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);

                if (!$resp->successful()) {
                    $error = "API Error (List): " . ($resp->status() == 404 ? "Endpoint not found." : "Status " . $resp->status());
                } else {
                    $all = $resp->json()['msg'] ?? [];
                    $total = count($all);
                    $pagedReservations = array_slice($all, ($page - 1) * $perPage, $perPage);
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = "Connection Error: Could not reach the API server. Please check your internet or the endpoint URL.";
        } catch (\Exception $e) {
            $error = "System Error: " . $e->getMessage();
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($pagedReservations ?? [], $total, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('dashboard', ['reservations' => $paginator, 'resNoList' => $resNoList, 'fromDate' => $fromDate, 'toDate' => $toDate, 'viewType' => $viewType, 'error' => $error]);
    }

    public function export(Request $request)
    {
        set_time_limit(0);
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');

        $ids = [];
        if ($resNoList) {
            $ids = array_filter(explode(',', $resNoList));
        } elseif ($fromDate && $toDate) {
            try {
                $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);

                if (!$resp->successful()) {
                    return back()->with('error', 'API List Error: ' . ($resp->status() == 404 ? "Endpoint not found." : "Status " . $resp->status()));
                }

                $ids = collect($resp->json()['msg'] ?? [])->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
            } catch (\Exception $e) {
                return back()->with('error', 'Connection Error: ' . $e->getMessage());
            }
        }

        if (empty($ids))
            return back()->with('error', 'No data to export.');

        if (ob_get_level())
            ob_end_clean();
        $filename = 'reservations_' . date('Ymd_His') . '.xls';

        // PASS 1: Load all data, collect unique dates and calculated rates
        $allRows = [];
        $allDates = [];
        $calculator = app(\App\Services\RateCalculatorService::class);

        foreach (array_chunk($ids, 40) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                foreach ($resp->json()['msg'] ?? [] as $res) {
                    $depdt = $res['depdt'] ?? $res['depDt'] ?? '';
                    $rateDates = [];

                    // Filter out dates beyond depdt from the rates array so they don't affect sums or columns
                    $filteredRates = [];
                    foreach ($res['rate'] ?? [] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d && $depdt !== '' && strcmp($d, $depdt) >= 0) {
                            continue;
                        }
                        $filteredRates[] = $r;
                    }
                    $res['rate'] = $filteredRates;

                    foreach ($res['rate'] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d) {
                            $rateDates[$d] = (float) ($r['val'] ?? 0);
                            $allDates[$d] = true;
                        }
                    }
                    $allRows[] = [
                        'res' => $res,
                        'rates' => $calculator->calculatePassengerRates($res),
                        'rateDates' => $rateDates,
                    ];
                }
            }
        }

        ksort($allDates);
        $dateCols = array_keys($allDates);
        $dateCount = count($dateCols);

        // PASS 2: Stream HTML Excel output
        return response()->stream(function () use ($allRows, $dateCols, $dateCount) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>';
            echo 'body { font-family: sans-serif; }';
            echo 'td, th { border: 0.5pt solid #b4c6e7; vertical-align: middle; font-size: 8pt; padding: 5pt; }';
            echo '.hdr  { background-color: #bcd2ee; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.dh   { background-color: #bcd2ee; font-weight: bold; text-align: center; color: #1e293b; }';
            echo '.num  { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '</style></head><body><table border="1">';

            // Header Row 1: fixed cols (rowspan=2) + ACCOMMODATION (colspan = dateCount+1) + fee cols (rowspan=2)
            echo '<tr>';
            echo '<td rowspan="2" class="hdr">RSVN#</td>';
            echo '<td rowspan="2" class="hdr">VILLAGE</td>';
            echo '<td rowspan="2" class="hdr">OCCUPANTS</td>';
            echo '<td rowspan="2" class="hdr">RELATION</td>';
            echo '<td rowspan="2" class="hdr">AGE</td>';
            echo '<td rowspan="2" class="hdr">BIRTHDAY</td>';
            echo '<td rowspan="2" class="hdr">NATIONALITY</td>';
            echo '<td rowspan="2" class="hdr">CHECK-IN</td>';
            echo '<td rowspan="2" class="hdr">CHECK-OUT</td>';
            echo '<td colspan="' . $dateCount . '" class="dh">ACCOMMODATION</td>';
            echo '<td rowspan="2" class="hdr">AIRFARE</td>';
            echo '<td rowspan="2" class="hdr">HANGAR</td>';
            echo '<td rowspan="2" class="hdr">AVIATION</td>';
            echo '<td rowspan="2" class="hdr">ENVIRONMENTAL</td>';
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
                echo '<td class="dh">' . htmlspecialchars($label) . '</td>';
            }
            echo '</tr>';

            // Data rows
            $totals = ['air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
            $dateTotals = array_fill_keys($dateCols, 0);
            $accGrandTotal = 0;
            $last = null;

            foreach ($allRows as $row) {
                $res = $row['res'];
                $rates = $row['rates'];
                $rateDates = $row['rateDates'];

                $resNo = $res['resNo'] ?? $res['conf'] ?? '';
                $isFirst = ($resNo !== $last);
                if ($isFirst)
                    $last = $resNo;

                $totals['air'] += floor($rates['air']);
                $totals['han'] += floor($rates['han']);
                $totals['avi'] += floor($rates['avi']);
                $totals['env'] += floor($rates['env']);

                echo '<tr>';
                echo '<td>' . ($isFirst ? htmlspecialchars($resNo) : '') . '</td>';
                echo '<td>' . ($isFirst ? htmlspecialchars($res['roomtyp'] ?? $res['roomType'] ?? '') : '') . '</td>';
                echo '<td>' . htmlspecialchars($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                $privCardLower = strtolower($privCard);
                $isValidCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

                $relation = htmlspecialchars($res['gstType'] ?? '');
                if ($isValidCard) {
                    $relation .= ' (' . htmlspecialchars($privCard) . ')';
                }

                echo '<td>' . $relation . '</td>';
                echo '<td>' . htmlspecialchars($res['age'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['dateOfBirth'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['nationality'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['arrdt'] ?? $res['arrDt'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['depdt'] ?? $res['depDt'] ?? '') . '</td>';

                $passengerAccTotal = 0;
                foreach ($dateCols as $d) {
                    if (isset($rateDates[$d])) {
                        $val = $rateDates[$d];
                        echo '<td class="num">' . number_format($val, 2) . '</td>';
                        $valToSum = floor($val);
                        $dateTotals[$d] += $valToSum;
                        $passengerAccTotal += $valToSum;
                    } else {
                        echo '<td></td>';
                    }
                }
                $accGrandTotal += $passengerAccTotal;
                echo '<td class="num">' . number_format($rates['air'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['han'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['avi'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['env'], 2) . '</td>';
                echo '</tr>';
                flush();
            }

            $totalPax = count($allRows);

            // Grand totals row
            echo '<tr style="font-weight:bold;background:#f1f5f9">';
            echo '<td colspan="2" style="text-align:right">TOTAL PAX:</td>';
            echo '<td style="text-align:center">' . $totalPax . '</td>';
            echo '<td colspan="6" style="text-align:right">GRAND TOTALS:</td>';
            foreach ($dateCols as $d) {
                echo '<td class="num">' . number_format($dateTotals[$d], 2) . '</td>';
            }
            echo '<td class="num">' . number_format($totals['air'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['han'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['avi'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['env'], 2) . '</td>';
            echo '</tr>';

            $overallGrandTotal = $accGrandTotal + $totals['air'] + $totals['han'] + $totals['avi'] + $totals['env'];

            $fullColspan = 9 + $dateCount + 4;
            $labelColspan = 9 + $dateCount + 3;

            echo '<tr><td colspan="' . $fullColspan . '" style="border:none;">&nbsp;</td></tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; font-weight:bold; text-align:right;">TOTAL AMOUNT DUE:</td>';
            echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            echo '<td colspan="4" class="num" style="border:none; font-weight:bold; text-align:right;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; font-style:italic; text-align:right;">Room Rates include service charge (10%) and VAT (12%)</td>';
            echo '<td colspan="' . ($dateCount + 4) . '" style="border:none;"></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; font-weight:bold; text-align:right;">LESS PAYMENT/S:</td>';
            echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            echo '<td colspan="4" style="border:none;"></td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; text-align:right;">OVERPAYMENT/CREDIT FROM FOLIO</td>';
            echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            echo '<td colspan="4" class="num" style="border:none; text-align:right;">&#8369;0.00</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; text-align:right;">COLLECTION RECEIPT</td>';
            echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            echo '<td colspan="4" class="num" style="border:none; text-align:right;">&#8369;0.00</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="9" style="border:none; font-weight:bold; text-align:right;">BALANCE TO SETTLE</td>';
            echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            echo '<td colspan="4" class="num" style="border:none; font-weight:bold; text-align:right; border-top:0.5pt solid #000;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
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

        $ids = [];
        if ($resNoList) {
            $ids = array_filter(explode(',', $resNoList));
        } elseif ($fromDate && $toDate) {
            try {
                $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if (!$resp->successful()) {
                    return back()->with('error', 'API Print List Error: ' . ($resp->status() == 404 ? "Endpoint not found." : "Status " . $resp->status()));
                }
                $ids = collect($resp->json()['msg'] ?? [])->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
            } catch (\Exception $e) {
                return back()->with('error', 'Connection Error: ' . $e->getMessage());
            }
        }

        if (empty($ids))
            return back()->with('error', 'No data to print.');

        $reservations = [];
        $calculator = app(\App\Services\RateCalculatorService::class);
        foreach (array_chunk(array_slice($ids, 0, 1000), 50) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                $msgs = $resp->json()['msg'] ?? [];
                foreach ($msgs as &$res) {
                    $res['calculated_rates'] = $calculator->calculatePassengerRates($res);
                }
                $reservations = array_merge($reservations, $msgs);
            }
        }
        return view('print', compact('reservations'));
    }

    private function consolidateRates(array &$msgs)
    {
        foreach ($msgs as &$res) {
            if (isset($res['rate']) && is_array($res['rate']) && count($res['rate']) > 0) {
                // Sort by date just in case it is out of order
                usort($res['rate'], function ($a, $b) {
                    return strcmp($a['date'] ?? '', $b['date'] ?? '');
                });

                $firstDate = reset($res['rate'])['date'] ?? null;
                $originalDepDt = $res['depdt'] ?? $res['depDt'] ?? end($res['rate'])['date'] ?? '';

                // Consolidate rate values, grouping by 'desc'
                $grouped = [];
                foreach ($res['rate'] as $r) {
                    $desc = strtolower(trim($r['desc'] ?? ''));
                    if (!isset($grouped[$desc])) {
                        $grouped[$desc] = 0;
                    }
                    $grouped[$desc] += (float) ($r['val'] ?? 0);
                }

                $consolidated = [];
                foreach ($grouped as $desc => $val) {
                    $newItem = [
                        'date' => $firstDate . ' to ' . $originalDepDt,
                        'val' => $val
                    ];
                    if ($desc !== '') {
                        $newItem['desc'] = $desc;
                    }
                    $consolidated[] = $newItem;
                }

                $res['rate'] = $consolidated;
            }
        }
    }
}
