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

    private $nationalityMap = [
        '2' => 'Afghanistani',
        '3' => 'Albaniaian',
        '4' => 'Algeriaian',
        '5' => 'Andorreaian',
        '8' => 'Angolese',
        '10' => 'Argentinaian',
        '11' => 'Australiaian',
        '13' => 'Austriaian',
        '14' => 'Bahamaian',
        '15' => 'Bahraini',
        '16' => 'Bangladeshi',
        '17' => 'Barbados',
        '18' => 'Belgian',
        '19' => 'Bhutanese',
        '20' => 'Bosniaian',
        '21' => 'Brazilian',
        '22' => 'Brunei',
        '23' => 'Bulgarian',
        '24' => 'Burmese',
        '25' => 'Camarooni',
        '26' => 'Canadian',
        '27' => 'Indian',
        '28' => 'Indonesian',
        '29' => 'Iranian',
        '30' => 'Iraqi',
        '31' => 'Irish',
        '32' => 'Italian',
        '33' => 'Jamaican',
        '34' => 'Japanese',
        '35' => 'Jordanian',
        '36' => 'Kenyan',
        '37' => 'Korean',
        '38' => 'Kuwaiti',
        '39' => 'Lebanese',
        '40' => 'Libyan',
        '41' => 'Luxemborg',
        '43' => 'Maldives',
        '44' => 'Maltese',
        '45' => 'Mauritian',
        '46' => 'Mexican',
        '47' => 'Moroccon',
        '48' => 'Mozambique',
        '49' => 'Nepali',
        '50' => 'Dutch',
        '51' => 'New Zealand',
        '52' => 'Chilean',
        '53' => 'Chinese',
        '54' => 'Colombian',
        '55' => 'Congolese',
        '56' => 'Costa Rican',
        '57' => 'Croatian',
        '58' => 'Cuban',
        '59' => 'Cypriot',
        '60' => 'Czechoslovakian',
        '62' => 'Danish',
        '63' => 'Ecuadorian',
        '64' => 'Egyptian',
        '65' => 'El Salvador',
        '66' => 'Ethiopian',
        '67' => 'Fijian',
        '68' => 'Finnish',
        '69' => 'French',
        '70' => 'German',
        '72' => 'Ghanaian',
        '74' => 'Hong Kong',
        '75' => 'Hungarian',
        '76' => 'Nigerian',
        '77' => 'Norwegian',
        '78' => 'Omani',
        '79' => 'Pakistani',
        '80' => 'Panama',
        '81' => 'Peruvian',
        '82' => 'Filipino',
        '83' => 'Polish',
        '84' => 'Portugese',
        '85' => 'Qatari',
        '86' => 'Romanian',
        '87' => 'Russian',
        '88' => 'Saudi Arabian',
        '89' => 'Scottish',
        '90' => 'English',
        '91' => 'Singaporean',
        '92' => 'Slovakian',
        '93' => 'Somalian',
        '94' => 'South African',
        '95' => 'Spainish',
        '96' => 'Sri Lankan',
        '97' => 'Sudani',
        '98' => 'Swedish',
        '99' => 'Swiss',
        '100' => 'Syrian',
        '101' => 'Taiwanese',
        '102' => 'Tanzanian',
        '103' => 'Thai',
        '104' => 'Tunisian',
        '105' => 'Turkish',
        '106' => 'USA',
        '107' => 'Ugandan',
        '108' => 'Ukrainian',
        '109' => 'UAE',
        '110' => 'Uruguay',
        '111' => 'Venenzuela',
        '112' => 'Vietnamese',
        '113' => 'Yemeni',
        '115' => 'Zambian',
        '116' => 'Zimbabwe',
        '117' => 'Others',
        '121' => 'Madagascar',
        '122' => 'Yugoslavian',
        '123' => 'Palestine',
        '137' => 'NONE',
        '138' => 'Greek',
        '140' => 'Icelandic',
        '142' => 'Malaysian',
        '143' => 'Myanmar',
        '144' => 'Greenlandian',
        '146' => 'Guamese',
        '147' => 'Hawaiian',
        '150' => 'Timor Leste',
        '151' => 'Laos',
        '152' => 'United Kingdom',
    ];

    private function getNationalityName($code)
    {
        if (!$code)
            return '';
        $code = trim($code);
        return $this->nationalityMap[$code] ?? $code;
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
        $total = 0;
        $viewType = 'summary';
        $error = null;

        if (!$resNoList && !$fromDate) {
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
            // Default status filter for fresh dashboard
            if (!$statusFilter) {
                $statusFilter = ['CONFIRMED', 'PARTLY ARRIVED'];
            }
        }

        // Ensure statusFilter is an array for consistent handling
        if ($statusFilter && !is_array($statusFilter)) {
            $statusFilter = [$statusFilter];
        }

        try {
            // ALWAYS fetch List API first to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                    foreach($listData as $lr) {
                        $c = trim($lr['conf'] ?? $lr['resNo'] ?? $lr['resno'] ?? '');
                        $rateVal = strtoupper(trim($lr['rate'] ?? ''));
                        $cust = strtoupper($lr['customer'] ?? $lr['custName'] ?? '');
                        if (str_contains($cust, 'ALPHALAND EMPLOYEE')) $rateVal = 'EMPLOYEE';
                        if ($c !== '') $rateMap[$c] = $rateVal;
                    }
                }
            }

            if ($resNoList) {
                $viewType = 'detail';
                $allConfIds = array_unique(array_filter(explode(',', $resNoList)));
                $chunks = array_chunk($allConfIds, 25);
                $calculator = app(\App\Services\RateCalculatorService::class);
                $paxIndices = [];
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
                        if (!isset($reservationPaxCounts[$resNo])) $reservationPaxCounts[$resNo] = 0;
                        $age = (int)($res['age'] ?? 99);
                        $gstType = strtolower($res['gstType'] ?? '');
                        $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                        if (!$isInfant) $reservationPaxCounts[$resNo]++;
                    }

                    foreach ($msgs as &$res) {
                        $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                        $res['rate_metadata'] = $rateMap[$resNo] ?? '';
                        
                        $age = (int)($res['age'] ?? 99);
                        $gstType = strtolower($res['gstType'] ?? '');
                        $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                        
                        if (!isset($paxIndices[$resNo])) $paxIndices[$resNo] = 0;
                        $paxIndex = $paxIndices[$resNo];
                        $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                        $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                        $res['calculated_rates'] = $calculator->calculatePassengerRates($res, $paxIndex, $roomType, $totalBillable);
                        
                        // Sync rates back to the rate array for display (replaces original accommodation values)
                        foreach($res['rate'] as &$r) {
                            $d = $r['date'] ?? '';
                            if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                                $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                                $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                            }
                        }
                        unset($r);

                        if (!$isInfant) {
                            $paxIndices[$resNo]++;
                        }

                        $res['is_employee'] = $res['calculated_rates']['is_employee'] ?? false;
                        $res['village_name'] = $this->getVillageName($roomType);
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
                    $reservations = array_filter($reservations, function($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }
                
                $total = count($reservations);
                $pagedReservations = $reservations; 
            } else {
                $viewType = 'list';
                // If we didn't fetch listResp above or it failed, we might need to handle it, 
                // but usually $fromDate/$toDate are there.
                $all = $listData ?? [];
                $total = count($all);
                $pagedReservations = $all;
                
                foreach($pagedReservations as &$res) {
                    $res['village_name'] = $this->getVillageName($res['roomtyp'] ?? $res['roomType'] ?? '');
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
        if ($viewType === 'detail') {
            // Sort by Reservation Number first
            usort($pagedReservations, function($a, $b) {
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
            // ALWAYS fetch List API first to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                    foreach($listData as $lr) {
                        $c = trim($lr['conf'] ?? $lr['resNo'] ?? $lr['resno'] ?? '');
                        $rateVal = strtoupper(trim($lr['rate'] ?? ''));
                        $cust = strtoupper($lr['customer'] ?? $lr['custName'] ?? '');
                        if (str_contains($cust, 'ALPHALAND EMPLOYEE')) $rateVal = 'EMPLOYEE';
                        if ($c !== '') $rateMap[$c] = $rateVal;
                    }
                }
            }

            if ($resNoList) {
                $ids = array_filter(explode(',', $resNoList));
            } elseif ($fromDate && $toDate) {
                $all = $listData ?? [];
                
                // Apply Status Filter (Array Support)
                if ($statusFilter) {
                    if (!is_array($statusFilter)) $statusFilter = [$statusFilter];
                    $all = array_filter($all, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                // Apply Global Search
                if ($search) {
                    $search = strtolower(trim($search));
                    $all = array_filter($all, function($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? $res['resno'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? $res['custname'] ?? $res['guestname'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? $i['resno'] ?? null)->filter()->unique()->toArray();
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
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

        $paxIndices = [];
        $reservationPaxCounts = [];
        foreach (array_chunk($ids, 40) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                ->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                $msgs = $resp->json()['msg'] ?? [];
                $this->consolidateRates($msgs);

                foreach ($msgs as $res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    if (!isset($reservationPaxCounts[$resNo])) $reservationPaxCounts[$resNo] = 0;
                    $age = (int)($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                    if (!$isInfant) $reservationPaxCounts[$resNo]++;
                }

                foreach ($msgs as &$res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    $res['rate_metadata'] = $rateMap[$resNo] ?? '';
                    $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $res['village_name'] = $this->getVillageName($roomType);
                    $res['nationality_name'] = $this->getNationalityName($res['nationality'] ?? '');
                    
                    $rd = [];
                    foreach ($res['rate'] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d) {
                            $rd[$d] = (float)($r['val'] ?? 0);
                            $allDates[$d] = true;
                        }
                    }

                    $age = (int)($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                    
                    if (!isset($paxIndices[$resNo])) $paxIndices[$resNo] = 0;
                    $paxIndex = $paxIndices[$resNo];
                    $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                    $calcResult = $calculator->calculatePassengerRates($res, $paxIndex, $roomType, $totalBillable);
                    
                    // Sync rates back to the rate array for display
                    foreach($res['rate'] as &$r) {
                        $d = $r['date'] ?? '';
                        if ($d && isset($calcResult['acc_dates'][$d])) {
                            $r['val'] = $calcResult['acc_dates'][$d]['val'];
                            $r['breakdown'] = $calcResult['acc_dates'][$d]['breakdown'];
                            // Update $rd for export
                            $rd[$d] = $r['val'];
                        }
                    }
                    unset($r);

                    if (!$isInfant) {
                        $paxIndices[$resNo]++;
                    }

                    $res['is_employee'] = $calcResult['is_employee'] ?? false;
                    
                    $allRows[] = [
                        'res' => $res,
                        'rates' => $calcResult,
                        'rateDates' => $rd,
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
            echo '<td rowspan="2" class="hdr" style="width: 150px;">OCCUPANTS</td>';
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
            echo '<td rowspan="2" class="hdr" style="width: 100px;">ENVIRONMENTAL</td>';
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
                echo '<td>' . ($isFirst ? htmlspecialchars($res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? '') : '') . '</td>';
                echo '<td>' . htmlspecialchars($res['gstName'] ?? $res['guestName'] ?? '') . '</td>';
                $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                $privCardLower = strtolower($privCard);
                $isValidCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

                $relation = htmlspecialchars($res['gstType'] ?? '');
                if (!empty($res['is_employee'])) {
                    $relation .= ' (Employee)';
                }
                if ($isValidCard) {
                    $relation .= ' (' . htmlspecialchars($privCard) . ')';
                }

                echo '<td>' . $relation . '</td>';
                echo '<td>' . htmlspecialchars($res['age'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['dateOfBirth'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($res['nationality_name'] ?? $res['nationality'] ?? '') . '</td>';
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
            // echo '<tr>';
            // echo '<td colspan="9" style="border:none; font-weight:bold; text-align:right;">LESS PAYMENT/S:</td>';
            // echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            // echo '<td colspan="4" style="border:none;"></td>';
            // echo '</tr>';
            // echo '<tr>';
            // echo '<td colspan="9" style="border:none; text-align:right;">OVERPAYMENT/CREDIT FROM FOLIO</td>';
            // echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            // echo '<td colspan="4" class="num" style="border:none; text-align:right;">&#8369;0.00</td>';
            // echo '</tr>';
            // echo '<tr>';
            // echo '<td colspan="9" style="border:none; text-align:right;">COLLECTION RECEIPT</td>';
            // echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            // echo '<td colspan="4" class="num" style="border:none; text-align:right;">&#8369;0.00</td>';
            // echo '</tr>';
            // echo '<tr>';
            // echo '<td colspan="9" style="border:none; font-weight:bold; text-align:right;">BALANCE TO SETTLE</td>';
            // echo '<td colspan="' . $dateCount . '" style="border:none;"></td>';
            // echo '<td colspan="4" class="num" style="border:none; font-weight:bold; text-align:right; border-top:0.5pt solid #000;">&#8369;' . number_format($overallGrandTotal, 2) . '</td>';
            // echo '</tr>';

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
            // ALWAYS fetch List API first to get metadata like 'rate' (Employee Discount ref)
            $rateMap = [];
            if ($fromDate && $toDate) {
                $listResp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()
                    ->get($this->listApiUrl, ['fromdate' => $fromDate, 'todate' => $toDate]);
                if ($listResp->successful()) {
                    $listData = $listResp->json()['msg'] ?? [];
                    foreach($listData as $lr) {
                        $c = trim($lr['conf'] ?? $lr['resNo'] ?? $lr['resno'] ?? '');
                        $rateVal = strtoupper(trim($lr['rate'] ?? ''));
                        $cust = strtoupper($lr['customer'] ?? $lr['custName'] ?? '');
                        if (str_contains($cust, 'ALPHALAND EMPLOYEE')) $rateVal = 'EMPLOYEE';
                        if ($c !== '') $rateMap[$c] = $rateVal;
                    }
                }
            }

            if ($resNoList) {
                $ids = array_filter(explode(',', $resNoList));
            } elseif ($fromDate && $toDate) {
                $all = $listData ?? [];

                // Apply Status Filter (Array Support)
                if ($statusFilter) {
                    if (!is_array($statusFilter)) $statusFilter = [$statusFilter];
                    $all = array_filter($all, function ($res) use ($statusFilter) {
                        return in_array(strtoupper(trim($res['status'] ?? '')), array_map('strtoupper', $statusFilter));
                    });
                }

                // Apply Global Search
                if ($search) {
                    $search = strtolower(trim($search));
                    $all = array_filter($all, function($res) use ($search) {
                        $resNo = strtolower($res['resNo'] ?? $res['conf'] ?? $res['resno'] ?? '');
                        $name = strtolower($res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? $res['custname'] ?? $res['guestname'] ?? '');
                        return str_contains($resNo, $search) || str_contains($name, $search);
                    });
                }

                $ids = collect($all)->map(fn($i) => $i['conf'] ?? $i['resNo'] ?? $i['resno'] ?? null)->filter()->unique()->toArray();
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection Error: ' . $e->getMessage());
        }

        if (empty($ids))
            return back()->with('error', 'No data to print.');

        $reservations = [];
        $calculator = app(\App\Services\RateCalculatorService::class);
        $paxIndices = [];
        $reservationPaxCounts = [];
        foreach (array_chunk(array_slice($ids, 0, 1000), 50) as $chunk) {
            $resp = Http::withHeaders(['Authorization' => $this->apiKey])->withoutVerifying()->get($this->detailApiUrl, ['resnolist' => implode(',', $chunk)]);
            if ($resp->successful()) {
                $msgs = $resp->json()['msg'] ?? [];
                $this->consolidateRates($msgs);

                foreach ($msgs as $res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    if (!isset($reservationPaxCounts[$resNo])) $reservationPaxCounts[$resNo] = 0;
                    $age = (int)($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                    if (!$isInfant) $reservationPaxCounts[$resNo]++;
                }

                foreach ($msgs as &$res) {
                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? '');
                    $res['rate_metadata'] = $rateMap[$resNo] ?? '';
                    
                    $age = (int)($res['age'] ?? 99);
                    $gstType = strtolower($res['gstType'] ?? '');
                    $isInfant = str_contains($gstType, 'infant') || (isset($res['age']) && $age >= 0 && $age <= 1);
                    
                    if (!isset($paxIndices[$resNo])) $paxIndices[$resNo] = 0;
                    $paxIndex = $paxIndices[$resNo];
                    $roomType = $res['roomtyp'] ?? $res['roomType'] ?? '';
                    $totalBillable = $reservationPaxCounts[$resNo] ?? 1;

                    $res['calculated_rates'] = $calculator->calculatePassengerRates($res, $paxIndex, $roomType, $totalBillable);
                    
                    // Sync rates back to the rate array for display
                    foreach($res['rate'] as &$r) {
                        $d = $r['date'] ?? '';
                        if ($d && isset($res['calculated_rates']['acc_dates'][$d])) {
                            $r['val'] = $res['calculated_rates']['acc_dates'][$d]['val'];
                            $r['breakdown'] = $res['calculated_rates']['acc_dates'][$d]['breakdown'];
                        }
                    }
                    unset($r);

                    if (!$isInfant) {
                        $paxIndices[$resNo]++;
                    }

                    $res['is_employee'] = $res['calculated_rates']['is_employee'] ?? false;
                    $res['village_name'] = $this->getVillageName($roomType);
                    $res['nationality_name'] = $this->getNationalityName($res['nationality'] ?? '');
                }
                unset($res); // Fix reference leak
                $reservations = array_merge($reservations, $msgs);
            }
        }
        return view('print', compact('reservations'));
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
                    $grouped[$key]['_rateMap'][$date] = ($grouped[$key]['_rateMap'][$date] ?? 0) + (float)($r['val'] ?? 0);
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
}
