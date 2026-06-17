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
            border: 1px solid #cbd5e1;
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
            text-align: center;
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

            table,
            th,
            td {
                border: 1px solid #cbd5e1 !important;
            }
        }

        .guidelines-section {
            margin-top: 25px;
            border: 1px solid #cbd5e1;
            padding: 15px;
            width: 58%;
            float: left;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .guidelines-title {
            font-size: 12px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
        }

        .guidelines-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .guideline-item {
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.4;
        }

        .guideline-subtitle {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .guideline-item p {
            margin: 0;
            text-align: justify;
        }

        .highlight-red {
            color: #dc2626;
        }

        .payments-section {
            width: 38%;
            float: right;
            box-sizing: border-box;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .payments-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .payments-table td {
            border: 1px solid #cbd5e1 !important;
            padding: 6px 10px;
            font-size: 10.5px;
        }

        .balance-container {
            border: 1.5px solid #dc2626;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #fff5f5;
        }

        .balance-label {
            font-weight: bold;
            color: #dc2626;
            font-size: 11.5px;
        }

        .balance-value {
            font-weight: bold;
            color: #dc2626;
            font-size: 13.5px;
        }

        .total-due-label,
        .total-due-value {
            background-color: #2b5a97 !important;
            color: white !important;
            border: none !important;
            font-size: 13px !important;
            padding: 8px 15px !important;
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
        <div><strong>Period:</strong> {{ !empty($fromDate) ? \Carbon\Carbon::parse($fromDate)->format('M d, Y') : request('fromdate', 'N/A') }} to {{ !empty($toDate) ? \Carbon\Carbon::parse($toDate)->format('M d, Y') : request('todate', 'N/A') }}</div>
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

        // Pre-calculate rowspan counts per reservation number
        $resGroupCounts = [];
        $firstMemberName = '';
        $firstCheckIn = '';
        foreach ($reservations as $r) {
            $rn = trim((string)($r['resNo'] ?? $r['conf'] ?? ''));
            $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
            if (!$firstMemberName && !empty($r['customer_name'])) {
                $firstMemberName = $r['customer_name'];
            }
        }
    @endphp

    <table>
        <thead>
            <tr>
                <td rowspan="4" colspan="2" style="text-align: center; vertical-align: middle; border: none; padding-right: 20px;">
                    <img src="{{ $balesinLogoUrl }}" alt="Balesin Island" style="max-height: 70px; object-fit: contain;" />
                </td>
                <td style="font-weight: bold; background-color: #dbeafe; border: 1px solid #cbd5e1; padding: 5px; text-align: center;">MEMBER'S NAME:</td>
                <td colspan="6" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">{{ $firstMemberName }}</td>
                <td colspan="{{ count($dateCols) + 5 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #dbeafe; border: 1px solid #cbd5e1; padding: 5px; text-align: center;">MEMBERSHIP NUMBER:</td>
                <td colspan="6" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;"></td>
                <td colspan="{{ count($dateCols) + 5 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #dbeafe; border: 1px solid #cbd5e1; padding: 5px; text-align: center;">CONTACT NUMBER:</td>
                <td colspan="6" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;"></td>
                <td colspan="{{ count($dateCols) + 5 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #dbeafe; border: 1px solid #cbd5e1; padding: 5px; text-align: center;">BOOKING DATE:</td>
                <td colspan="6" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;"></td>
                <td colspan="{{ count($dateCols) + 5 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td colspan="{{ 9 + count($dateCols) + 5 }}" style="border: none; height: 10px;"></td>
            </tr>
            <tr class="hdr">
                <th rowspan="2">RSVN#</th>
                <th rowspan="2">VILLAGE</th>
                <th rowspan="2">OCCUPANTS</th>
                <th rowspan="2">RELATION</th>
                <th rowspan="2">AGE</th>
                <th rowspan="2">BIRTHDAY</th>
                <th rowspan="2">NATIONALITY</th>
                <th rowspan="2">CHECK-IN</th>
                <th rowspan="2">CHECK-OUT</th>
                <th colspan="{{ count($dateCols) }}" class="dh" style="text-align: center;">ACCOMMODATION</th>
                <th rowspan="2" style="width: 100px;">AIRFARE</th>
                <th rowspan="2" style="width: 100px;">HANGAR FEE</th>
                <th rowspan="2" style="width: 100px;">AVIATION OPERATIONAL FEE</th>
                <th rowspan="2" style="width: 100px;">ENVIRONMENTAL FEE</th>
                <th rowspan="2" style="width: 100px;">TOTAL RATE (Per Occupant)</th>
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
            @foreach($reservations as $idx => $res)
                @php
                    $resNo = trim((string)($res['resNo'] ?? $res['conf'] ?? ''));
                    $isFirst = ($resNo !== $lastResNo);
                    if ($isFirst)
                        $lastResNo = $resNo;

                    $rates = ['acc' => 0, 'air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
                    if (isset($res['calculated_rates'])) {
                        $rates = $res['calculated_rates'];
                        $grandTotals['air'] += (float) ($rates['air']);
                        $grandTotals['han'] += (float) ($rates['han']);
                        $grandTotals['avi'] += (float) ($rates['avi']);
                        $grandTotals['env'] += (float) ($rates['env']);
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
                    @if($isFirst)
                        <td rowspan="{{ $resGroupCounts[$resNo] ?? 1 }}" style="vertical-align: middle; text-align: center; font-weight: 600;">{{ $resNo }}</td>
                        <td rowspan="{{ $resGroupCounts[$resNo] ?? 1 }}" style="vertical-align: middle; text-align: center;">
                            <div>{{ strtoupper($res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? '') }}</div>
                            @if(!empty($res['unit_type_label']))
                                <div>{{ strtoupper($res['unit_type_label']) }}</div>
                            @endif
                        </td>
                    @endif
                    <td style="text-align: center;">{{ $res['gstName'] ?? $res['guestName'] ?? '' }}</td>
                    <td style="text-align: center; text-transform: uppercase;">
                        {{ $res['gstType'] ?? '' }}
                        @if(!empty($res['is_employee'])) (EMPLOYEE) @endif
                        {{ $isValidCard ? ' (' . $privCard . ')' : '' }}
                    </td>
                    <td style="text-align: center;">{{ $res['age'] ?? '' }}</td>
                    <td>{{ $res['dateOfBirth'] ?? '' }}</td>
                    <td style="text-align: center; text-transform: uppercase;">{{ $res['nationality_name'] ?? $res['nationality'] ?? '' }}</td>
                    <td>{{ ($d = $res['arrdt'] ?? $res['arrDt'] ?? '') ? date('m/d/Y', strtotime($d)) : '' }}</td>
                    <td>{{ ($d = $res['depdt'] ?? $res['depDt'] ?? '') ? date('m/d/Y', strtotime($d)) : '' }}</td>

                    @php
                        $span        = $res['acc_span'] ?? 1;
                        $groupTotals = $res['acc_group_totals'] ?? [];

                        // Compute the total accommodation for this group (non-FVN only)
                        $groupAccSum = 0;
                        foreach ($groupTotals as $gv) {
                            $grv = round((float)$gv, 2);
                            if ($grv !== 0.01 && $grv !== 0.02 && $grv !== 0.5) {
                                $groupAccSum += (float)$gv;
                            }
                        }
                        // Each occupant's fair share of the shared accommodation
                        $groupSize = max(1, (int) ($res['acc_group_size'] ?? $span ?? 1));
                        $perOccupantAcc = $groupAccSum / $groupSize;
                    @endphp
                    @if($span > 0)
                        @foreach($dateCols as $d)
                            @php
                                $val = $groupTotals[$d] ?? 0;
                                $rv = round($val, 2);
                                $displayVal = ($val > 0 ? number_format($val, 2) : '');
                                if ($rv == 0.01)        $displayVal = '1 FVN';
                                elseif ($rv == 0.02)    $displayVal = '1.5 FVN';
                                elseif ($rv == 0.5)     $displayVal = '.5 FVN';
                                elseif ($rv == 3700.01) $displayVal = '3700.01';
                            @endphp
                            <td class="num {{ $span > 1 ? 'merged' : '' }}" rowspan="{{ $span }}" style="text-align: center;">{{ $displayVal }}</td>
                        @endforeach
                    @endif

                    {{-- Accumulate date totals only once per group (when the merged cell is first rendered) --}}
                    @if($span > 0)
                        @foreach($dateCols as $d)
                            @php
                                $val = $groupTotals[$d] ?? 0;
                                $rv  = round($val, 2);
                                // FVN marker rates do not contribute to grand totals
                                if ($rv !== 0.01 && $rv !== 0.02 && $rv !== 0.5) {
                                    $dateTotals[$d]    += (float) $val;
                                }
                            @endphp
                        @endforeach
                    @endif
                    @php $accGrandTotal += $perOccupantAcc; @endphp
                                <td style="text-align: center;">{{ ($res['calculated_rates']['air'] ?? 0) > 0 ? number_format($res['calculated_rates']['air'], 2) : '' }}</td>
                                <td style="text-align: center;">{{ ($res['calculated_rates']['han'] ?? 0) > 0 ? number_format($res['calculated_rates']['han'], 2) : '' }}</td>
                                <td style="text-align: center;">{{ ($res['calculated_rates']['avi'] ?? 0) > 0 ? number_format($res['calculated_rates']['avi'], 2) : '' }}</td>
                                <td style="text-align: center;">{{ ($res['calculated_rates']['env'] ?? 0) > 0 ? number_format($res['calculated_rates']['env'], 2) : '' }}</td>

                                @php
                                    // Per-occupant total = distributed acc share + individual fees
                                    $passengerTotalRate = $perOccupantAcc
                                        + ($res['calculated_rates']['air'] ?? 0)
                                        + ($res['calculated_rates']['han'] ?? 0)
                                        + ($res['calculated_rates']['avi'] ?? 0)
                                        + ($res['calculated_rates']['env'] ?? 0);
                                @endphp

                                <td style="text-align: center; font-weight: bold;">{{ $passengerTotalRate > 0 ? number_format($passengerTotalRate, 2) : '' }}</td>
                                </tr>
            @endforeach

    <tr style="display: none;">

                <td>

                                        <?php
$overallGrandTotal = $accGrandTotal + $grandTotals['air'] + $grandTotals['han'] + $grandTotals['avi'] + $grandTotals['env'];
$totalPax = count($reservations);
$fullColspan = 9 + count($dateCols) + 5;
$labelColspan = 9 + count($dateCols) + 4;
                    ?>
                </td>
</tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL PAX:</td>
                <td style="text-align: center;">{{ $totalPax }}</td>
                <td colspan="6" style="text-align: right;">GRAND TOTALS:</td>
                @foreach($dateCols as $d)
                    <td class="num" style="text-align: center;">{{ number_format($dateTotals[$d], 2) }}</td>
                @endforeach
                <td class="num" style="text-align: center;">{{ number_format($grandTotals['air'], 2) }}</td>
                <td class="num" style="text-align: center;">{{ number_format($grandTotals['han'], 2) }}</td>
                <td class="num" style="text-align: center;">{{ number_format($grandTotals['avi'], 2) }}</td>
                <td class="num" style="text-align: center;">{{ number_format($grandTotals['env'], 2) }}</td>
                <td class="num" style="font-weight: bold; text-align: center;">{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
            <tr><td colspan="{{ $fullColspan }}" style="border: none; padding: 10px;"></td></tr>


            <tr>
                <td colspan="9" style="border: none;"></td>
                <td colspan="{{ count($dateCols) + 1 }}" class="total-due-label">TOTAL AMOUNT DUE:</td>
                <td colspan="4" class="num total-due-value">&#8369;{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="9" style="border: none;"></td>
                <td colspan="{{ count($dateCols) + 5 }}" style="text-align: right; border: none; font-style: italic; color: #64748b; font-size: 11px; padding: 5px 0 15px 0;">Room Rates include service charge (10%) and VAT (12%)</td>
            </tr>
            
        </tfoot>
    </table>

    <div style="margin-top: 25px; width: 100%; display: flow-root; page-break-inside: avoid;">
        <!-- Left: Booking Guidelines -->
        <div class="guidelines-section">
            <div class="guidelines-title">Booking Guidelines:</div>
            <div class="guidelines-content">
                <div class="guideline-item">
                    <div class="guideline-subtitle">Booking Confirmation</div>
                    <p>Full payment is required within seven (7) days of receiving the computation of charges; Unpaid reservations after this period will be released to other Members to ensure maximum availability.</p>
                </div>
                <div class="guideline-item">
                    <div class="guideline-subtitle">Flight Schedule</div>
                    <p>With the increasing number of international and domestic flights at NAIA, the Civil Aviation Authority of the Philippines (CAAP) has issued a Memorandum that limits General Aviation, which we are part of, from flying via the NAIA runway from 9 AM to 7 PM daily. Given this restriction, kindly expect that our flights to/from the island may depart from/arrive at Clark Airport (Pampanga).</p>
                </div>
                <div class="guideline-item">
                    <div class="guideline-subtitle">Expectant Mother</div>
                    <p>Guests 20 weeks pregnant or less must present a medical certificate confirming fitness for air travel (issued within 7 days before the flight).</p>
                </div>
                <div class="guideline-item">
                    <div class="guideline-subtitle">Cancellation Policy</div>
                    <p>The deadline for cancellation is one week (7 days) prior to the scheduled check-in date. <span class="highlight-red">If a cancellation is made within this 7-day period, a fee equivalent to the one-way airfare per passenger and cost of one night per villa will be applied.</span> However, for cancellations made with more than 7 days' notice, any member overpayments may be applied to travel expenses across three key locations and will expire on the member's anniversary date.</p>
                </div>
                <div class="guideline-item">
                    <div class="guideline-subtitle">Rebooking/ Name Change/ Pax replacement</div>
                    <p>A fee equivalent to one-way airfare will apply to any changes made within 7 days of the scheduled departure date and time.</p>
                </div>
            </div>
        </div>

        <!-- Right: Payments and Balance -->
        <div class="payments-section">
            <div class="payments-title">PAYMENT/S:</div>
            <table class="payments-table">
                <tbody>
                    <tr>
                        <td style="width: 70%; height: 25px; border: 1px solid #cbd5e1 !important;"></td>
                        <td style="width: 30%; text-align: right; font-weight: bold; border: 1px solid #cbd5e1 !important;"></td>
                    </tr>
                    <tr>
                        <td style="height: 25px; border: 1px solid #cbd5e1 !important;"></td>
                        <td style="border: 1px solid #cbd5e1 !important;"></td>
                    </tr>
                    <tr>
                        <td style="height: 25px; border: 1px solid #cbd5e1 !important;"></td>
                        <td style="border: 1px solid #cbd5e1 !important;"></td>
                    </tr>
                </tbody>
            </table>
            <div class="balance-container">
                <div class="balance-label">BALANCE TO SETTLE</div>
                <div class="balance-value">&#8369;{{ number_format($overallGrandTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
</body>

</html>