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
        }

        th {
            background: #f8fafc;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #64748b;
        }

        .num {
            text-align: right;
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

    <table>
        <thead>
            <tr>
                <th>RSVN#</th>
                <th>Village</th>
                <th>Guest Name</th>
                <th>Age</th>
                <th>Relation</th>
                <th>Check-In</th>
                <th>Check-Out</th>
                <th>Accommodation</th>
                <th>Airfare</th>
                <th>Hangar</th>
                <th>Aviation</th>
                <th>Env.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotals = ['acc' => 0, 'air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
                $lastResNo = null;
            @endphp
            @foreach($reservations as $res)
                @php
                    $resNo = $res['resNo'] ?? $res['conf'] ?? '';
                    $isFirst = ($resNo !== $lastResNo);
                    if ($isFirst)
                        $lastResNo = $resNo;

                    $rates = ['acc' => 0, 'air' => 0, 'han' => 0, 'avi' => 0, 'env' => 0];
                    if (isset($res['calculated_rates'])) {
                        $rates = $res['calculated_rates'];
                        $grandTotals['acc'] += $rates['acc'];
                        $grandTotals['air'] += $rates['air'];
                        $grandTotals['han'] += $rates['han'];
                        $grandTotals['avi'] += $rates['avi'];
                        $grandTotals['env'] += $rates['env'];
                    }

                    // Build per-date accommodation breakdown
                    $accLines = [];
                    foreach ($res['rate'] ?? [] as $r) {
                        $rDate = $r['date'] ?? '';
                        $rVal = (float) ($r['val'] ?? 0);
                        if ($rDate) {
                            $dt = new \DateTime($rDate);
                            $accLines[] = $dt->format('Y-m-d') . ' (' . $dt->format('D') . '): ' . number_format($rVal, 2);
                        }
                    }
                    $accDisplay = implode("\n", $accLines);
                @endphp
                <tr>
                    <td>{{ $isFirst ? $resNo : '' }}</td>
                    <td>{{ $isFirst ? ($res['roomtyp'] ?? $res['roomType'] ?? '') : '' }}</td>
                    <td>{{ $res['gstName'] ?? $res['guestName'] ?? '' }}</td>
                    <td>{{ $res['age'] ?? '' }}</td>
                    <td>{{ $res['gstType'] ?? '' }}</td>
                    <td>{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</td>
                    <td>{{ $res['depdt'] ?? $res['depDt'] ?? '' }}</td>
                    <td class="acc">{{ $accDisplay ?? '' }}</td>
                    <td class="num">{{ number_format($rates['air'], 2) }}</td>
                    <td class="num">{{ number_format($rates['han'], 2) }}</td>
                    <td class="num">{{ number_format($rates['avi'], 2) }}</td>
                    <td class="num">{{ number_format($rates['env'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" style="text-align: right;">GRAND TOTALS:</td>
                <td class="num">{{ number_format($grandTotals['acc'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['air'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['han'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['avi'], 2) }}</td>
                <td class="num">{{ number_format($grandTotals['env'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>