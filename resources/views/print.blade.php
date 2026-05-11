<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Print - {{ date('Y-m-d') }}</title>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 20px;
            color: #1e293b;
            background: white;
        }

        .no-print {
            margin-bottom: 20px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-secondary {
            background: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #475569;
            border: 1px solid #b4c6e7;
        }

        .dh {
            background: #f1f5f9 !important;
        }

        .num {
            text-align: right;
        }

        .merged {
            text-align: center !important;
        }

        .acc {
            vertical-align: top;
            font-size: 11px;
            white-space: pre-line;
        }

        .total-row {
            font-weight: bold;
            background: #f8fafc;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }

            table {
                border-color: #000;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <div>
            <strong>Print Preview</strong>
            <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">Use the button on the right or press Ctrl+P to
                save as PDF.</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="margin-right: 10px;">Back to
                Dashboard</a>
            <button onclick="window.print()" class="btn">Print / Save as PDF</button>
        </div>
    </div>

    <h1>Reservation Detailed Report</h1>
    <div style="display: flex; gap: 20px; font-size: 14px; color: #64748b; margin-bottom: 20px;">
        <div><strong>Period:</strong> {{ request('fromdate', 'N/A') }} to {{ request('todate', 'N/A') }}</div>
        @if(request('resnolist'))
            <div><strong>IDs:</strong> {{ request('resnolist') }}</div>
        @endif
        <div><strong>Generated:</strong> {{ date('F j, Y, g:i a') }}</div>
    </div>

    @php
        $grandTotals = ['air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
        $dateTotals = array_fill_keys($dateCols, 0);
        $accGrandTotal = 0;
        $lastResNo = null;
    @endphp

    <table>
        <thead>
            <tr class="hdr">
                <th rowspan="2">RSVN#</th>
                <th rowspan="2">VILLAGE</th>
                <th rowspan="2">OCCUPANTS</th>
                <th rowspan="2">RELATION</th>
                <th rowspan="2">RATE CODE</th>
                <th rowspan="2">AGE</th>
                <th rowspan="2">BIRTHDAY</th>
                <th rowspan="2">NATIONALITY</th>
                <th rowspan="2">CHECK-IN</th>
                <th rowspan="2">CHECK-OUT</th>
                <th colspan="{{ count($dateCols) }}" class="dh" style="text-align: center;">ACCOMMODATION</th>
                <th rowspan="2">AIRFARE</th>
                <th rowspan="2">HANGAR</th>
                <th rowspan="2">AVIATION</th>
                <th rowspan="2">ENVIRONMENTAL</th>
            </tr>
            <tr class="hdr">
                @foreach($dateCols as $d)
                    @php
                        $label = $d;
                        try {
                            $dt = new \DateTime($d);
                            $label = strtoupper($dt->format('M-d, D'));
                        } catch (\Exception $e) {
                        }
                    @endphp
                    <th class="dh" style="font-size: 9px; min-width: 60px;">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $res)
                @php
                    $resNo = $res['resNo'] ?? $res['conf'] ?? '';
                    $isFirst = ($resNo !== $lastResNo);
                    if ($isFirst)
                        $lastResNo = $resNo;

                    $rates = ['acc' => 0, 'air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
                    if (isset($res['calculated_rates'])) {
                        $rates = $res['calculated_rates'];
                        $grandTotals['air'] += floor($rates['air']);
                        $grandTotals['han'] += floor($rates['han']);
                        $grandTotals['avi'] += floor($rates['avi']);
                        $grandTotals['env'] += floor($rates['env']);
                    }

                    $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                    $privCardLower = strtolower($privCard);
                    $isValidCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

                    // Index rates by date for this passenger
                    $rateMap = [];
                    foreach ($res['rate'] ?? [] as $r) {
                        $d = $r['date'] ?? '';
                        if ($d) {
                            $rateMap[$d] = (float) ($r['val'] ?? 0);
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $isFirst ? $resNo : '' }}</td>
                    <td>{{ $isFirst ? ($res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? '') : '' }}</td>
                    <td>{{ $res['gstName'] ?? $res['guestName'] ?? '' }}</td>
                    <td>
                        {{ $res['gstType'] ?? '' }}
                        @if(!empty($res['is_employee'])) (Employee) @endif
                        {{ $isValidCard ? ' (' . $privCard . ')' : '' }}
                    </td>
                    <td style="text-align: center;">{{ $res['rate_metadata'] ?? '' }}</td>
                    <td>{{ $res['age'] ?? '' }}</td>
                    <td>{{ $res['dateOfBirth'] ?? '' }}</td>
                    <td>{{ $res['nationality_name'] ?? $res['nationality'] ?? '' }}</td>
                    <td>{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</td>
                    <td>{{ $res['depdt'] ?? $res['depDt'] ?? '' }}</td>

                    @php 
                        $passengerAccTotal = 0;
                        $spanInfo = $accSpans[$loop->index] ?? ['span' => 1, 'totals' => []];
                    @endphp

                    @if($spanInfo['span'] > 0)
                        @foreach($dateCols as $d)
                            @php 
                                $val = $spanInfo['totals'][$d] ?? 0;
                                $dateTotals[$d] += (float)$val;
                                
                                $displayVal = ($val > 0 ? number_format($val, 2) : '');
                                if ($val == 0.01 || $val == 0.02) {
                                    $displayVal = number_format($val, 2) . ' FVN';
                                }
                            @endphp
                            <td class="num {{ $spanInfo['span'] > 1 ? 'merged' : '' }}" rowspan="{{ $spanInfo['span'] }}">{{ $displayVal }}</td>
                        @endforeach
                    @endif

                    @foreach($dateCols as $d)
                        @php 
                            $val = $rateMap[$d] ?? 0;
                            $passengerAccTotal += (float)$val;
                        @endphp
                    @endforeach

                    @php $accGrandTotal += $passengerAccTotal; @endphp
                        <td class="num">{{ number_format($res['calculated_rates']['air'] ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($res['calculated_rates']['han'] ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($res['calculated_rates']['avi'] ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($res['calculated_rates']['env'] ?? 0, 2) }}</td>
                    </tr>
            @endforeach
            <tr style="display: none;">
                <td>
                    <?php
                        $overallGrandTotal = $accGrandTotal + $grandTotals['air'] + $grandTotals['han'] + $grandTotals['avi'] + $grandTotals['env'];
                        $totalPax = count($reservations);
                        $fullColspan = 10 + count($dateCols) + 4;
                        $labelColspan = 10 + count($dateCols) + 3;
                    ?>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL PAX:</td>
                <td style="text-align: center;">{{ $totalPax }}</td>
                <td colspan="7" style="text-align: right;">GRAND TOTALS:</td>
                @foreach($dateCols as $d)
                    <td class="num">{{ number_format($dateTotals[$d], 2) }}</td>
                @endforeach
                <td class="num">{{ number_format($grandTotals['air'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['han'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['avi'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['env'], 2) }}</td>
            </tr>
            <tr><td colspan="{{ $fullColspan }}" style="border: none; padding: 10px;"></td></tr>
            <tr>
                <td colspan="10" style="text-align: right; border: none; font-weight: bold;">TOTAL AMOUNT DUE:</td>
                <td colspan="{{ count($dateCols) }}" style="border: none;"></td>
                <td colspan="4" class="num" style="border: none; font-weight: bold;">&#8369;{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="10" style="text-align: right; border: none; font-style: italic; color: #64748b; padding-bottom: 15px;">Room Rates include service charge (10%) and VAT (12%)</td>
                <td colspan="{{ count($dateCols) + 4 }}" style="border: none;"></td>
            </tr>
            
            <tr>
                <td colspan="10" style="text-align: right; border: none; font-weight: bold;">LESS PAYMENT/S:</td>
                <td colspan="{{ count($dateCols) }}" style="border: none;"></td>
                <td colspan="4" style="border: none;"></td>
            </tr>
            <tr>
                <td colspan="10" style="text-align: right; border: none;">OVERPAYMENT/CREDIT FROM FOLIO</td>
                <td colspan="{{ count($dateCols) }}" style="border: none;"></td>
                <!-- <td colspan="4" class="num" style="border: none;">&#8369;0.00</td> -->
            </tr>
            <tr>
                <td colspan="10" style="text-align: right; border: none; padding-bottom: 20px;">COLLECTION RECEIPT</td>
                <td colspan="{{ count($dateCols) }}" style="border: none;"></td>
                <!-- <td colspan="4" class="num" style="border: none; padding-bottom: 20px;">&#8369;0.00</td> -->
            </tr>
            <tr>
                <td colspan="10" style="text-align: right; border: none; font-weight: bold;">BALANCE TO SETTLE</td>
                <td colspan="{{ count($dateCols) }}" style="border: none;"></td>
                <td colspan="4" class="num" style="border: none; font-weight: bold; border-top: 1px solid #000;">&#8369;{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>