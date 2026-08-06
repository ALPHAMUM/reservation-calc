<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balesin City Reservation Print - {{ date('Y-m-d') }}</title>
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
            background: #10206d;
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
            background: #10206d;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #ffffff;
            border: 1px solid #3a4fa3;
            text-align: center;
        }

        .dh {
            background: #10206d !important;
            color: #ffffff !important;
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
            background: #e8eaf6;
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
            width: 48%;
            float: left;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .guidelines-title {
            font-size: 12px;
            font-weight: bold;
            color: #10206d;
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

        .guideline-item ul {
            margin: 2px 0 0 15px;
            padding: 0;
        }

        .guideline-item li {
            margin-bottom: 2px;
        }

        .payments-section {
            width: 48%;
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
            border: 1.5px solid #10206d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #e8eaf6;
        }

        .balance-label {
            font-weight: bold;
            color: #10206d;
            font-size: 11.5px;
        }

        .balance-value {
            font-weight: bold;
            color: #10206d;
            font-size: 13.5px;
        }

        .total-due-label,
        .total-due-value {
            background-color: #10206d !important;
            color: #ffffff !important;
            border: none !important;
            font-size: 13px !important;
            padding: 8px 15px !important;
            font-weight: bold !important;
        }
    </style>
</head>

<body>
    <div class="no-print">
        <div>
            <strong>Balesin City Print Preview</strong>
            <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">Use the button on the right or press Ctrl+P to save as PDF.</p>
        </div>
        <div>
            <a href="{{ route('dashboard', ['property' => 'city']) }}" class="btn btn-secondary" style="margin-right: 10px;">Back to Dashboard</a>
            <button onclick="window.print()" class="btn">Print / Save as PDF</button>
        </div>
    </div>

    <h1>Balesin City Detailed Report</h1>
    <div style="display: flex; gap: 20px; font-size: 14px; color: #64748b; margin-bottom: 20px;">
        <div><strong>Period:</strong> {{ !empty($fromDate) ? \Carbon\Carbon::parse($fromDate)->format('M d, Y') : request('fromdate', 'N/A') }} to {{ !empty($toDate) ? \Carbon\Carbon::parse($toDate)->format('M d, Y') : request('todate', 'N/A') }}</div>
        @if(request('resnolist'))
            <div><strong>IDs:</strong> {{ request('resnolist') }}</div>
        @endif
        <div><strong>Generated:</strong> {{ date('F j, Y, g:i a') }}</div>
    </div>

    @php
        $dateTotals = array_fill_keys($dateCols, 0);
        $accGrandTotal = 0;
        $lastResNo = null;

        $resGroupCounts = [];
        $firstMemberName = '';
        $firstMemberNo = '';
        $firstContactNo = '';
        $firstBookDate = '';
        foreach ($reservations as $r) {
            $rn = trim((string)($r['resNo'] ?? $r['conf'] ?? ''));
            $resGroupCounts[$rn] = ($resGroupCounts[$rn] ?? 0) + 1;
            if (!$firstMemberName && !empty($r['customer_name'])) {
                $firstMemberName = $r['customer_name'];
            }
            if (!$firstMemberNo && !empty($r['memberNo'])) {
                $firstMemberNo = $r['memberNo'];
            }
            if (!$firstContactNo && !empty($r['conactNo'])) {
                $firstContactNo = (string)$r['conactNo'];
            } elseif (!$firstContactNo && !empty($r['contactNo'])) {
                $firstContactNo = (string)$r['contactNo'];
            }
            if (!$firstBookDate && !empty($r['bookDate'])) {
                $firstBookDate = $r['bookDate'];
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

        $totalCols = 8 + count($dateCols) + 1;
    @endphp

    <table>
        <thead>
            <tr>
                <td rowspan="4" colspan="2" style="text-align: center; vertical-align: middle; border: none; padding-right: 20px;">
                    <img src="https://balesin.com/wp-content/uploads/2025/09/balesin-island-logo-dark-line.png" alt="Balesin City" style="max-height: 70px; object-fit: contain;" />
                </td>
                <td style="font-weight: bold; background-color: #10206d; color: #ffffff; border: 1px solid #3a4fa3; padding: 5px; text-align: center;">MEMBER'S NAME:</td>
                <td colspan="5" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">{{ $firstMemberName }}</td>
                <td colspan="{{ count($dateCols) + 1 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #10206d; color: #ffffff; border: 1px solid #3a4fa3; padding: 5px; text-align: center;">MEMBERSHIP NUMBER:</td>
                <td colspan="5" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">{{ $firstMemberNo }}</td>
                <td colspan="{{ count($dateCols) + 1 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #10206d; color: #ffffff; border: 1px solid #3a4fa3; padding: 5px; text-align: center;">CONTACT NUMBER:</td>
                <td colspan="5" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold; mso-number-format:'\@';">{{ $firstContactNo }}</td>
                <td colspan="{{ count($dateCols) + 1 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #10206d; color: #ffffff; border: 1px solid #3a4fa3; padding: 5px; text-align: center;">BOOKING DATE:</td>
                <td colspan="5" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">{{ $formattedBookDate }}</td>
                <td colspan="{{ count($dateCols) + 1 }}" style="border: none;"></td>
            </tr>
            <tr>
                <td colspan="{{ $totalCols }}" style="border: none; height: 10px;"></td>
            </tr>
            <tr class="hdr">
                <th rowspan="2">RSVN#</th>
                <th rowspan="2">ROOM TYPE</th>
                <th rowspan="2">OCCUPANTS</th>
                <th rowspan="2">RELATION</th>
                <th rowspan="2">AGE</th>
                <th rowspan="2">BIRTHDAY</th>
                <th rowspan="2">CHECK-IN</th>
                <th rowspan="2">CHECK-OUT</th>
                <th colspan="{{ count($dateCols) }}" class="dh" style="text-align: center;">ACCOMMODATION</th>
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

                    $rates = ['acc' => 0];
                    if (isset($res['calculated_rates'])) {
                        $rates = $res['calculated_rates'];
                    }

                    $privCard = trim((string) ($res['privCard'] ?? $res['privcard'] ?? ''));
                    $privCardLower = strtolower($privCard);
                    $isValidCard = $privCard !== '' && !in_array($privCardLower, ['n', 'no', 'false', '0', 'none', 'null']);

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

                    <td>{{ ($d = $res['arrdt'] ?? $res['arrDt'] ?? '') ? date('m/d/Y', strtotime($d)) : '' }}</td>
                    <td>{{ ($d = $res['depdt'] ?? $res['depDt'] ?? '') ? date('m/d/Y', strtotime($d)) : '' }}</td>

                    @php
                        $span        = $res['acc_span'] ?? 1;
                        $groupTotals = $res['acc_group_totals'] ?? [];

                        $groupAccSum = 0;
                        foreach ($groupTotals as $gv) {
                            $grv = round((float)$gv, 2);
                            if ($grv !== 0.01 && $grv !== 0.02 && $grv !== 0.5) {
                                $groupAccSum += (float)$gv;
                            }
                        }
                        $groupSize = max(1, (int) ($res['acc_group_size'] ?? $span ?? 1));
                        $perOccupantAcc = $rates['acc'] ?? ($groupAccSum / $groupSize);
                    @endphp
                    @if($span > 0)
                        @foreach($dateCols as $d)
                            @php
                                $val = $rates['acc_dates'][$d]['val'] ?? ($groupTotals[$d] ?? 0);
                                $rv = round($val, 2);
                                $displayVal = ($val > 0 ? number_format($val, 2) : '');
                            @endphp
                            <td class="num {{ $span > 1 ? 'merged' : '' }}" rowspan="{{ $span }}" style="text-align: center;">{{ $displayVal }}</td>
                        @endforeach
                    @endif

                    @if($span > 0)
                        @foreach($dateCols as $d)
                            @php
                                $val = $rates['acc_dates'][$d]['val'] ?? ($groupTotals[$d] ?? 0);
                                $rv  = round($val, 2);
                                if ($rv !== 0.01 && $rv !== 0.02 && $rv !== 0.5) {
                                    $dateTotals[$d] += (float) $val;
                                }
                            @endphp
                        @endforeach
                    @endif
                    @php $accGrandTotal += $perOccupantAcc; @endphp

                    <td style="text-align: center; font-weight: bold;">{{ $perOccupantAcc > 0 ? number_format($perOccupantAcc, 2) : '' }}</td>
                </tr>
            @endforeach

            @php
                $overallGrandTotal = $accGrandTotal;
                $totalPax = count($reservations);
                $fullColspan = 8 + count($dateCols) + 1;
            @endphp
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL PAX:</td>
                <td style="text-align: center;">{{ $totalPax }}</td>
                <td colspan="5" style="text-align: right;">GRAND TOTALS:</td>
                @foreach($dateCols as $d)
                    <td class="num" style="text-align: center;">{{ number_format($dateTotals[$d], 2) }}</td>
                @endforeach
                <td class="num" style="font-weight: bold; text-align: center;">{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
            <tr><td colspan="{{ $fullColspan }}" style="border: none; padding: 10px;"></td></tr>

            <tr>
                <td colspan="6" style="border: none;"></td>
                <td colspan="{{ count($dateCols) + 2 }}" class="total-due-label">TOTAL AMOUNT DUE:</td>
                <td colspan="1" class="total-due-value num" style="font-size: 15px !important;">&#8369;{{ number_format($overallGrandTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" style="border: none;"></td>
                <td colspan="{{ count($dateCols) + 3 }}" style="text-align: right; border: none; font-style: italic; color: #64748b; font-size: 11px; padding: 5px 0 15px 0;">Room Rates include service charge (10%) and VAT (12%)</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 25px; width: 100%; display: flow-root; page-break-inside: avoid;">
        <!-- Left: Balesin City Guidelines -->
        <div class="guidelines-section">
            <div class="guidelines-title">Booking Guidelines:</div>
            <div class="guidelines-content">
                <div class="guideline-item">
                    <div class="guideline-subtitle">Room Capacity</div>
                    <ul>
                        <li>1 Bedroom Suite: 4 persons (2 adults and 2 kids); 2-Bedroom Suite: 6 persons (4 adults and 2 kids)</li>
                        <li>1 additional occupant may be allowed per suite at Php1,300/night</li>
                        <li>Kids are defined as 12 years old and younger; infants younger than 24 months are not counted</li>
                    </ul>
                </div>
                <div class="guideline-item">
                    <div class="guideline-subtitle">Cancellation</div>
                    <p>The deadline for cancellations is one week (7 days) prior to the scheduled check-in date. If a cancellation is made within this 7-day period, a fee equivalent to the cost of one night per room will be forfeited. However, for cancellations made with more than 7 days notice, any member overpayments may be applied to future bookings.</p>
                </div>
            </div>
        </div>

        <!-- Right: Payments and Balance -->
        <div class="payments-section">
            <div class="payments-title">PAYMENT/S:</div>
            <table class="payments-table" id="paymentsTable">
                <tbody>
                    <tr>
                        <td contenteditable="true" class="payment-desc" style="width: 70%; height: 25px; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                        <td contenteditable="true" class="payment-amount" style="width: 30%; text-align: right; font-weight: bold; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                    </tr>
                    <tr>
                        <td contenteditable="true" class="payment-desc" style="height: 25px; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                        <td contenteditable="true" class="payment-amount" style="text-align: right; font-weight: bold; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                    </tr>
                    <tr>
                        <td contenteditable="true" class="payment-desc" style="height: 25px; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                        <td contenteditable="true" class="payment-amount" style="text-align: right; font-weight: bold; border: 1px solid #cbd5e1 !important; outline: none;"></td>
                    </tr>
                </tbody>
            </table>
            <button class="no-print btn btn-secondary" style="font-size: 10px; padding: 4px 8px; margin-bottom: 10px; border-radius: 4px;" onclick="addPaymentRow()">+ Add Row</button>
            <div class="balance-container">
                <div class="balance-label">BALANCE TO SETTLE</div>
                <div class="balance-value" id="balanceValue">&#8369;{{ number_format($overallGrandTotal, 2) }}</div>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>

    <script>
        const totalAmountDue = {{ $overallGrandTotal }};
        
        function formatNumber(num) {
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calculateBalance() {
            let totalPayments = 0;
            const amountCells = document.querySelectorAll('.payment-amount');
            
            amountCells.forEach(cell => {
                const valStr = cell.innerText.replace(/[^0-9.-]+/g, '');
                const val = parseFloat(valStr);
                if (!isNaN(val)) {
                    totalPayments += val;
                }
            });

            const balance = totalAmountDue - totalPayments;
            document.getElementById('balanceValue').innerHTML = '&#8369;' + formatNumber(balance);
        }

        function addPaymentRow() {
            const tbody = document.querySelector('#paymentsTable tbody');
            const tr = document.createElement('tr');
            
            const tdDesc = document.createElement('td');
            tdDesc.contentEditable = "true";
            tdDesc.className = "payment-desc";
            tdDesc.style.cssText = "height: 25px; border: 1px solid #cbd5e1 !important; outline: none;";
            
            const tdAmount = document.createElement('td');
            tdAmount.contentEditable = "true";
            tdAmount.className = "payment-amount";
            tdAmount.style.cssText = "text-align: right; font-weight: bold; border: 1px solid #cbd5e1 !important; outline: none;";
            tdAmount.addEventListener('input', calculateBalance);
            
            tr.appendChild(tdDesc);
            tr.appendChild(tdAmount);
            tbody.appendChild(tr);
        }

        document.querySelectorAll('.payment-amount').forEach(cell => {
            cell.addEventListener('input', calculateBalance);
        });
    </script>
</body>

</html>
