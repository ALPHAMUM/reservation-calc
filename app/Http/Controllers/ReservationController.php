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
        if ($perPage > 100) $perPage = 100;

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
        } catch (\Exception $e) { $error = $e->getMessage(); }

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

        return response()->stream(function() use ($ids, $apiKey) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" />';
            echo '<style>td { vertical-align: top; border: 0.5pt solid #ccc; } .header { background-color: #e2e8f0; font-weight: bold; } .num { mso-number-format:"\#\,\#\#0\.00"; } .acc { white-space: pre-line; vertical-align: top; }</style></head><body><table border="1">';
            echo '<tr class="header"><td>RSVN#</td><td>VILLAGE</td><td>OCCUPANTS</td><td>RELATION</td><td>CHECK-IN</td><td>CHECK-OUT</td><td>ACCOMMODATION</td><td>AIRFARE</td><td>HANGAR</td><td>AVIATION</td><td>ENVIRONMENTAL</td></tr>';

            $totals = ['acc' => 0, 'air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
            $last = null;

            foreach (array_chunk($ids, 40) as $chunk) {
                $resp = Http::withHeaders(['Authorization' => $apiKey])->withoutVerifying()->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
                if ($resp->successful()) {
                    foreach ($resp->json()['msg'] ?? [] as $res) {
                        $resNo = $res['resNo'] ?? $res['conf'] ?? '';
                        $isFirst = ($resNo !== $last);
                        if ($isFirst) $last = $resNo;
                        $rates = app(\App\Services\RateCalculatorService::class)->calculatePassengerRates($res);
                        $totals['acc'] += $rates['acc'];
                        $totals['air'] += $rates['air'];
                        $totals['han'] += $rates['han'];
                        $totals['avi'] += $rates['avi'];
                        $totals['env'] += $rates['env'];
                        // Build per-date accommodation breakdown
                        $accLines = [];
                        foreach ($res['rate'] ?? [] as $r) {
                            $rDate = $r['date'] ?? '';
                            $rVal  = (float)($r['val'] ?? 0);
                            if ($rDate) {
                                $dt = new DateTime($rDate);
                                $accLines[] = $dt->format('Y-m-d') . ' (' . $dt->format('D') . '): ' . number_format($rVal, 2);
                            }
                        }
                        $accDisplay = implode('&#10;', $accLines); // Excel line break in cell

                        echo '<tr>';
                        echo '<td>' . ($isFirst ? $resNo : '') . '</td>';
                        echo '<td>' . ($isFirst ? ($res['roomtyp'] ?? $res['roomType'] ?? '') : '') . '</td>';
                        echo '<td>' . ($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                        echo '<td>' . ($res['gstType'] ?? '') . '</td>';
                        echo '<td>' . ($res['arrdt'] ?? $res['arrDt'] ?? '') . '</td>';
                        echo '<td>' . ($res['depdt'] ?? $res['depDt'] ?? '') . '</td>';
                        echo '<td class="acc" style="white-space:pre-wrap">' . $accDisplay . '</td>';
                        echo '<td class="num">' . number_format($rates['air'], 2) . '</td>';
                        echo '<td class="num">' . number_format($rates['han'], 2) . '</td>';
                        echo '<td class="num">' . number_format($rates['avi'], 2) . '</td>';
                        echo '<td class="num">' . number_format($rates['env'], 2) . '</td>';
                        echo '</tr>';
                    }
                    flush();
                }
            }
            echo '<tr style="font-weight:bold;background:#f8fafc"><td colspan="6" style="text-align:right">GRAND TOTALS:</td>';
            echo '<td class="num">'.number_format($totals['acc'],2).'</td><td class="num">'.number_format($totals['air'],2).'</td><td class="num">'.number_format($totals['han'],2).'</td><td class="num">'.number_format($totals['avi'],2).'</td><td class="num">'.number_format($totals['env'],2).'</td></tr>';
            echo '</table></body></html>';
        }, 200, ['Content-Type' => 'application/vnd.ms-excel', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
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
            if ($resp->successful()) $ids = collect($resp->json()['msg'] ?? [])->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? null)->filter()->unique()->toArray();
        }

        if (empty($ids)) return back()->with('error', 'No data to print.');

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
