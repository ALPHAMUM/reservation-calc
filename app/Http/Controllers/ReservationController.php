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

    public function index(Request $request)
    {
        $resNoList = $request->get('resnolist');
        $fromDate = $request->get('fromdate');
        $toDate = $request->get('todate');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        if ($perPage > 100)
            $perPage = 100;

        $apiKey = '12A3A9C3-3D8F-4204-9737-3C4ADE94650F';
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
                    $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()
                        ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
                    if ($resp->successful()) {
                        $msgs = $resp->json()['msg'] ?? [];
                        foreach ($msgs as &$res) {
                            $res['calculated_rates'] = $calculator->calculatePassengerRates($res);
                        }
                        $reservations = array_merge($reservations, $msgs);
                    }
                }
                $total = count($reservations);
                $pagedReservations = array_slice($reservations, ($page - 1) * $perPage, $perPage);
            } else {
                $viewType = 'list';
                $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($resp->successful()) {
                    $all = $resp->json()['msg'] ?? [];
                    $total = count($all);
                    $pagedReservations = array_slice($all, ($page - 1) * $perPage, $perPage);
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
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
        $apiKey = '12A3A9C3-3D8F-4204-9737-3C4ADE94650F';

        $ids = [];
        if ($resNoList) {
            $ids = array_filter(explode(',', $resNoList));
        } elseif ($fromDate && $toDate) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()
                ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
            if ($resp->successful()) {
                $ids = collect($resp->json()['msg'] ?? [])->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
            }
        }

        if (empty($ids)) return back()->with('error', 'No data to export.');

        if (ob_get_level()) ob_end_clean();
        $filename = 'reservations_' . date('Ymd_His') . '.xls';

        // PASS 1: Load all data, collect unique dates and calculated rates
        $allRows  = [];
        $allDates = [];
        $calculator = app(\App\Services\RateCalculatorService::class);

        foreach (array_chunk($ids, 40) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()
                ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                foreach ($resp->json()['msg'] ?? [] as $res) {
                    $rateDates = [];
                    foreach ($res['rate'] ?? [] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d) {
                            $rateDates[$d] = (float)($r['val'] ?? 0);
                            $allDates[$d]  = true;
                        }
                    }
                    $allRows[] = [
                        'res'       => $res,
                        'rates'     => $calculator->calculatePassengerRates($res),
                        'rateDates' => $rateDates,
                    ];
                }
            }
        }

        ksort($allDates);
        $dateCols = array_keys($allDates);
        $dateCount = count($dateCols);

        // PASS 2: Stream HTML Excel output
        return response()->stream(function() use ($allRows, $dateCols, $dateCount) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>';
            echo 'td, th { border: 0.5pt solid #ccc; vertical-align: middle; font-size: 10pt; }';
            echo '.hdr  { background-color: #e2e8f0; font-weight: bold; text-align: center; }';
            echo '.dh   { background-color: #dbeafe; font-weight: bold; text-align: center; }';
            echo '.num  { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '</style></head><body><table border="1">';

            // Header Row 1: fixed cols (rowspan=2) + ACCOMMODATION (colspan = dateCount+1) + fee cols (rowspan=2)
            echo '<tr class="hdr">';
            echo '<td rowspan="2">RSVN#</td>';
            echo '<td rowspan="2">VILLAGE</td>';
            echo '<td rowspan="2">OCCUPANTS</td>';
            echo '<td rowspan="2">RELATION</td>';
            echo '<td rowspan="2">CHECK-IN</td>';
            echo '<td rowspan="2">CHECK-OUT</td>';
            echo '<td colspan="' . ($dateCount + 1) . '" class="dh">ACCOMMODATION</td>';
            echo '<td rowspan="2">AIRFARE</td>';
            echo '<td rowspan="2">HANGAR</td>';
            echo '<td rowspan="2">AVIATION</td>';
            echo '<td rowspan="2">ENVIRONMENTAL</td>';
            echo '</tr>';

            // Header Row 2: date sub-columns + TOTAL
            echo '<tr class="hdr">';
            foreach ($dateCols as $d) {
                $dt = new DateTime($d);
                echo '<td class="dh">' . $dt->format('M-d, D') . '</td>';
            }
            echo '<td class="dh">TOTAL</td>';
            echo '</tr>';

            // Data rows
            $totals     = ['air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
            $dateTotals = array_fill_keys($dateCols, 0);
            $accGrandTotal = 0;
            $last = null;

            foreach ($allRows as $row) {
                $res       = $row['res'];
                $rates     = $row['rates'];
                $rateDates = $row['rateDates'];

                $resNo   = $res['resNo'] ?? $res['conf'] ?? '';
                $isFirst = ($resNo !== $last);
                if ($isFirst) $last = $resNo;

                $totals['air'] += $rates['air'];
                $totals['han'] += $rates['han'];
                $totals['avi'] += $rates['avi'];
                $totals['env'] += $rates['env'];

                echo '<tr>';
                echo '<td>' . ($isFirst ? htmlspecialchars($resNo) : '') . '</td>';
                echo '<td>' . ($isFirst ? htmlspecialchars($res['roomtyp'] ?? $res['roomType'] ?? '') : '') . '</td>';
                echo '<td>' . htmlspecialchars($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['gstType'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['arrdt'] ?? $res['arrDt'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['depdt'] ?? $res['depDt'] ?? '') . '</td>';

                $passengerAccTotal = 0;
                foreach ($dateCols as $d) {
                    if (isset($rateDates[$d])) {
                        $val = $rateDates[$d];
                        echo '<td class="num">' . number_format($val, 2) . '</td>';
                        $dateTotals[$d]    += $val;
                        $passengerAccTotal += $val;
                    } else {
                        echo '<td></td>';
                    }
                }
                $accGrandTotal += $passengerAccTotal;
                echo '<td class="num">' . number_format($passengerAccTotal, 2) . '</td>';
                echo '<td class="num">' . number_format($rates['air'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['han'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['avi'], 2) . '</td>';
                echo '<td class="num">' . number_format($rates['env'], 2) . '</td>';
                echo '</tr>';
                flush();
            }

            // Grand totals row
            echo '<tr style="font-weight:bold;background:#f1f5f9">';
            echo '<td colspan="6" style="text-align:right">GRAND TOTALS:</td>';
            foreach ($dateCols as $d) {
                echo '<td class="num">' . number_format($dateTotals[$d], 2) . '</td>';
            }
            echo '<td class="num">' . number_format($accGrandTotal, 2) . '</td>';
            echo '<td class="num">' . number_format($totals['air'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['han'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['avi'], 2) . '</td>';
            echo '<td class="num">' . number_format($totals['env'], 2) . '</td>';
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
        $apiKey = '12A3A9C3-3D8F-4204-9737-3C4ADE94650F';

        $ids = [];
        if ($resNoList) {
            $ids = array_filter(explode(',', $resNoList));
        } elseif ($fromDate && $toDate) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
            if ($resp->successful())
                $ids = collect($resp->json()['msg'] ?? [])->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
        }

        if (empty($ids))
            return back()->with('error', 'No data to print.');

        $reservations = [];
        $calculator = app(\App\Services\RateCalculatorService::class);
        foreach (array_chunk(array_slice($ids, 0, 1000), 50) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
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
}
