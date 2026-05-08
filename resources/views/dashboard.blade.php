@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .search-box {
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .input-group {
        flex: 1;
        min-width: 200px;
        position: relative;
    }

    .input-group input:focus,
    .input-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    /* Fix for option text visibility */
    .input-group select option {
        background-color: #1e293b;
        color: white;
    }

    .input-group input, 
    .input-group select {
        width: 100%;
        padding: 0.75rem 1.25rem;
        background: var(--glass-bg);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        color: white;
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.2s;
    }

    .table-container {
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border);
        padding: 1.5rem;
        margin-top: 1rem;
    }

    /* Fix DataTables Dark Mode UI */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: var(--glass-bg) !important;
        border: 1px solid var(--border) !important;
        color: white !important;
        border-radius: 0.5rem !important;
        padding: 0.4rem 0.8rem !important;
    }

    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        color: var(--text-muted) !important;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    #resTable {
        border-color: var(--border) !important;
        color: var(--text) !important;
    }

    #resTable thead th {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: var(--text-muted) !important;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--border) !important;
    }

    #resTable tbody td {
        border-bottom: 1px solid var(--border) !important;
        vertical-align: middle;
    }

    .res-no {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
    }

    .badge {
        padding: 0.4rem 0.8rem;
        border-radius: 0.5rem;
    }

    /* Status Badges */
    .status-badge {
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        white-space: nowrap;
    }
    /* Confirmed - Green */
    .status-confirmed, .status-active { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    /* Pending/Not-Confirmed - Yellow/Orange */
    .status-not-confirmed, .status-pending, .status-tentative { background: rgba(234, 179, 8, 0.2); color: #facc15; }
    /* Arrived - Blue */
    .status-arrived, .status-in-house, .status-checkedin, .status-partly-arrived { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    /* Cancelled - Red */
    .status-cancelled, .status-system-cancelled, .status-partial-sys-cancelled, .status-partly-cancelled, .status-nc-sys-cancelled, .status-deleted { 
        background: rgba(239, 68, 68, 0.2); color: #f87171; 
    }
    /* Completed - Slate */
    .status-checkout, .status-completed { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }

    .badge-guest { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
    .badge-member { background: rgba(16, 185, 129, 0.15); color: #34d399; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        padding: 1.5rem;
        background: var(--glass-bg);
        border: 1px solid var(--border);
        border-radius: 1rem;
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* Custom Pagination Styling - Robust Fix */
    .pagination {
        display: flex;
        padding-left: 0;
        list-style: none;
        margin-top: 2rem;
        gap: 0.25rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .page-item {
        margin: 0 2px;
    }

    .page-link {
        position: relative;
        display: block;
        padding: 0.5rem 1rem;
        color: var(--text) !important;
        text-decoration: none;
        background-color: var(--glass-bg) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.5rem !important;
        transition: all 0.2s;
    }

    /* DataTables Specific Pagination Fixes */
    #paginationContainer {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        margin-top: 1.5rem;
    }
    .dataTables_paginate {
        display: flex !important;
        gap: 0.5rem !important;
        background: transparent !important;
        padding: 0 !important;
    }
    /* Reset DataTables default padding/border on the <a> tags */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: transparent !important;
    }
    .dataTables_paginate .paginate_button {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        background: var(--glass-bg) !important;
        border: 1px solid var(--border) !important;
        border-radius: 0.75rem !important;
        cursor: pointer !important;
        color: var(--text) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
        font-size: 0.9rem !important;
        box-shadow: none !important;
    }
    .dataTables_paginate .paginate_button:hover {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: #fff !important;
        transform: translateY(-2px);
    }
    .dataTables_paginate .paginate_button.current {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: #fff !important;
        font-weight: bold !important;
    }
    .dataTables_paginate .paginate_button.disabled {
        opacity: 0.2 !important;
        cursor: not-allowed !important;
        transform: none !important;
    }
    .dataTables_paginate span {
        display: flex !important;
        gap: 0.5rem;
    }
    .dataTables_info {
        color: var(--text-muted) !important;
        font-size: 0.85rem !important;
        text-align: center;
        margin-top: 0.5rem;
        width: 100%;
    }

    .page-item.active .page-link {
        z-index: 3;
        color: #fff !important;
        background-color: var(--primary) !important;
        border-color: var(--primary) !important;
    }

    .page-item.disabled .page-link {
        color: var(--text-muted) !important;
        pointer-events: none;
        background-color: transparent !important;
        border-color: var(--border) !important;
    }

    .page-link:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: translateY(-2px);
    }

    /* Custom Checkbox Styling */
    .custom-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid var(--border);
        border-radius: 0.375rem;
        background-color: rgba(15, 23, 42, 0.3);
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
        display: inline-block;
        vertical-align: middle;
        margin: 0;
    }

    .custom-checkbox:hover {
        border-color: var(--primary);
        background-color: rgba(99, 102, 241, 0.1);
    }

    .custom-checkbox:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .custom-checkbox:checked::after {
        content: '';
        position: absolute;
        left: 32%;
        top: 15%;
        width: 30%;
        height: 50%;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    /* Custom Multi-Select Dropdown */
    .multi-select-container {
        position: relative;
        width: 100%;
    }

    .multi-select-display {
        width: 100%;
        padding: 0.75rem 1.25rem;
        background: var(--glass-bg);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }

    .multi-select-display:hover {
        border-color: var(--primary);
    }

    .multi-select-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #1e293b;
        border: 1px solid var(--primary);
        border-radius: 0.75rem;
        margin-top: 0.5rem;
        z-index: 1000;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        padding: 0.5rem;
    }

    .multi-select-option {
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: all 0.2s;
        color: #e2e8f0;
        font-size: 0.9rem;
    }

    .multi-select-option:hover {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }

    .multi-select-option input {
        width: 1.1rem;
        height: 1.1rem;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="animate-in" style="animation-delay: 0.1s">
    @if(!empty($settings['promo_rates']['active']))
        <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2)); border: 1px solid var(--primary); border-radius: 1rem; padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem;">
            <div style="background: var(--primary); width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0; color: white; font-weight: 600;">{{ $settings['promo_rates']['description'] ?? 'Active Promotion' }}</h4>
                <p style="margin: 0.25rem 0 0 0; color: var(--text-muted); font-size: 0.875rem;">
                    Promo Period: <strong>{{ \Carbon\Carbon::parse($settings['promo_rates']['start_date'])->format('M d, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($settings['promo_rates']['end_date'])->format('M d, Y') }}</strong>
                </p>
            </div>
            <div style="text-align: right; font-size: 0.875rem;">
                <div style="color: var(--text-muted);">Member: <span style="color: var(--primary); font-weight: bold;">₱{{ number_format($settings['promo_rates']['member'], 2) }}</span></div>
                <div style="color: var(--text-muted);">Guest: <span style="color: var(--primary); font-weight: bold;">₱{{ number_format($settings['promo_rates']['guest'], 2) }}</span></div>
            </div>
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Reservations</div>
            <div class="stat-value" id="stat-total-res">{{ count($reservations) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Pax</div>
            <div class="stat-value" id="stat-total-pax">
                @php 
                    $totalPax = 0;
                    foreach($reservations as $r) $totalPax += (int)($r['noPax'] ?? $r['noOfPax'] ?? 0);
                    echo $totalPax;
                @endphp
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Pax Arrived</div>
            <div class="stat-value" id="stat-total-arrived">
                @php 
                    $arrivedPax = 0;
                    foreach($reservations as $r) {
                        if(strtoupper(trim($r['status'] ?? '')) === 'ARRIVED') {
                            $arrivedPax += (int)($r['noPax'] ?? $r['noOfPax'] ?? 0);
                        }
                    }
                    echo $arrivedPax;
                @endphp
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 600; color: var(--text)">
                {{ $viewType === 'list' ? 'Reservation Summary' : 'Detailed Records' }}
            </h2>
            @if($viewType === 'detail')
                <a href="javascript:void(0);" onclick="window.history.back();" class="btn btn-primary" style="background: rgba(255,255,255,0.1); font-size: 0.875rem;">
                    &larr; Back to List
                </a>
            @endif
        </div>

        <form id="dashboardForm" action="{{ route('dashboard') }}" method="GET" class="search-box" style="flex-direction: column;">
            <!-- First Row: Show Selection and Actions -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end; width: 100%; gap: 1rem;">
                <div class="input-group" style="flex: 0 0 100px; min-width: 100px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Show</label>
                    <select name="per_page" onchange="this.form.submit()">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Refresh Data</button>
                    <a href="{{ route('export', ['fromdate' => $fromDate, 'todate' => $toDate, 'resnolist' => $resNoList]) }}" class="btn btn-primary btn-export-excel" style="background: var(--success)">
                        Export Excel
                    </a>
                    <a href="{{ route('print', ['fromdate' => $fromDate, 'todate' => $toDate, 'resnolist' => $resNoList]) }}" target="_blank" class="btn btn-primary btn-export-pdf" style="background: #ef4444">
                        Export PDF
                    </a>
                </div>
            </div>

            <!-- Second Row: Filters -->
            <div style="display: flex; width: 100%; gap: 1rem; flex-wrap: wrap;">
                @if($viewType !== 'detail' || !$resNoList)
                    <div class="input-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">From Date</label>
                        <input type="date" name="fromdate" value="{{ $fromDate }}">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">To Date</label>
                        <input type="date" name="todate" value="{{ $toDate }}">
                    </div>
                @endif
                @if($viewType !== 'detail')
                <div class="input-group">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Status Filter</label>
                    <div class="multi-select-container">
                        <div class="multi-select-display" id="statusDisplay">
                            <span id="statusLabel">All Statuses</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                        <div class="multi-select-dropdown" id="statusDropdown">
                            @php $selectedStatuses = (array)($statusFilter ?? []); @endphp
                            @foreach(['CONFIRMED', 'NOT-CONFIRMED', 'ARRIVED', 'CANCELLED', 'SYSTEM CANCELLED', 'PARTLY ARRIVED', 'PARTIAL SYS CANCELLED', 'PARTLY CANCELLED', 'NC-SYS CANCELLED'] as $st)
                                <label class="multi-select-option">
                                    <input type="checkbox" name="status_filter[]" value="{{ $st }}" {{ in_array($st, $selectedStatuses) ? 'checked' : '' }} class="status-option-checkbox">
                                    <span>{{ $st }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                <div class="input-group" style="max-width: 320px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Search (Name, Res No...)</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search all records..." autocomplete="off">
                </div>
                @if($viewType !== 'detail')
                <div class="input-group" style="max-width: 320px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Res No List</label>
                    <input type="text" name="resnolist" value="{{ $resNoList }}" placeholder="IDs separated by comma...">
                </div>
                @else
                    <input type="hidden" name="resnolist" value="{{ $resNoList }}">
                @endif
            </div>
        </form>

        <div class="table-container">
            <table id="resTable" class="table table-dark table-hover">
                <thead>
                    <tr>
                        @if($viewType !== 'detail')
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllRes" class="custom-checkbox"></th>
                        @endif
                        <th>Res No</th>
                        <th>Guest Name</th>
                        @if($viewType === 'detail')
                            <th>Nationality</th>
                            <th>Type</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>
                                Total Rate
                                <div style="font-size: 0.65rem; font-weight: normal; opacity: 0.7; margin-top: 2px;">(Accommodation + Fees)</div>
                            </th>
                        @else
                            <th>Rooms</th>
                            <th>Pax</th>
                            <th>Rate</th>
                            <th>Rate Code</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $res)
                        @php $currentResNo = $res['resNo'] ?? $res['conf'] ?? null; @endphp
                        <tr>
                            @if($viewType !== 'detail')
                                <td style="text-align: center;">
                                    @if($currentResNo)
                                        <input type="checkbox" class="res-checkbox custom-checkbox" value="{{ $currentResNo }}" {{ in_array($currentResNo, array_filter(explode(',', request('resnolist', '')))) ? 'checked' : '' }}>
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($currentResNo)
                                    @if($viewType === 'detail')
                                        <span class="res-no">#{{ $currentResNo }}</span>
                                    @else
                                        <a href="{{ route('dashboard', ['resnolist' => $currentResNo]) }}" style="text-decoration: none;">
                                            <span class="res-no">#{{ $currentResNo }}</span>
                                        </a>
                                    @endif
                                @else
                                    <span class="res-no">N/A</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? 'Unknown' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted)">{{ $res['custName'] ?? $res['customer'] ?? '' }}</div>
                            </td>
                            @if($viewType === 'detail')
                                <td>{{ $res['nationality_name'] ?? $res['nationality'] ?? '' }}</td>
                                <td>
                                    <span class="badge {{ ($res['gstType'] ?? '') === 'Member' ? 'badge-member' : 'badge-guest' }}">
                                        {{ $res['gstType'] ?? 'Guest' }}
                                        @if(!empty($res['is_employee']))
                                            (Employee)
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $res['village_name'] ?? $res['roomtyp'] ?? $res['roomType'] ?? 'N/A' }}</td>
                                <td>
                                    <div style="font-size: 0.875rem">{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted)">to {{ $res['depdt'] ?? $res['depDt'] ?? '' }}</div>
                                </td>
                                <td>
                                    @php
                                        $total = 0;
                                        if (isset($res['calculated_rates'])) {
                                            $r = $res['calculated_rates'];
                                            // Match export logic: floor each component
                                            $total = floor($r['acc'] ?? 0) + 
                                                     floor($r['air'] ?? 0) + 
                                                     floor($r['han'] ?? 0) + 
                                                     floor($r['avi'] ?? 0) + 
                                                     floor($r['env'] ?? 0);
                                        } elseif (isset($res['rate']) && is_array($res['rate'])) {
                                            foreach($res['rate'] as $rateItem) $total += floor((float)($rateItem['val'] ?? 0));
                                        }
                                    @endphp
                                    {{ number_format($total, 2) }}
                                </td>
                            @else
                                <td>{{ $res['noRooms'] ?? $res['noOfRooms'] ?? '0' }}</td>
                                <td>{{ $res['noPax'] ?? $res['noOfPax'] ?? '0' }}</td>
                                <td>
                                    @php
                                        if (isset($res['rate']) && is_array($res['rate'])) {
                                            $rateVal = 0;
                                            foreach($res['rate'] as $r) $rateVal += (float)($r['val'] ?? 0);
                                            echo number_format($rateVal, 2);
                                        } else {
                                            echo htmlspecialchars($res['rate'] ?? 'N/A');
                                        }
                                    @endphp
                                </td>
                                <td>{{ $res['rateCode'] ?? $res['ratecode'] ?? 'N/A' }}</td>
                                <td style="white-space: nowrap;">{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</td>
                                <td style="white-space: nowrap;">{{ $res['depdt'] ?? $res['depDt'] ?? '' }}</td>
                                <td>
                                    @php
                                        $statusRaw = $res['status'] ?? 'PENDING';
                                        $statusClass = str_replace([' ', '_'], '-', strtolower($statusRaw));
                                    @endphp
                                    <span class="status-badge status-{{ $statusClass }}">{{ trim($statusRaw) }}</span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Client-side pagination handled by DataTables --}}
        <div class="mt-4 d-flex justify-content-center" id="paginationContainer">
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table;
        if (!$.fn.DataTable.isDataTable('#resTable')) {
            table = $('#resTable').DataTable({
                "paging": true,
                "pageLength": {{ request('per_page', 10) }},
                "lengthChange": false, // Disable "Show X entries"
                "info": true,
                "searching": true, 
                "ordering": true,
                "order": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search all records...",
                    "paginate": {
                        "previous": "&laquo;",
                        "next": "&raquo;"
                    }
                },
                "columnDefs": [
                    { "orderable": false, "targets": [0] } // Disable ordering on checkbox
                ],
                "dom": 'rt<"bottom"ip><"clear">'
            });
            // Hide the default DataTables search box and use ours
            $('.dataTables_filter').hide();
            // Move DataTables pagination and info to our container
            const info = $('.dataTables_info').detach();
            const paginate = $('.dataTables_paginate').detach();
            $('#paginationContainer').append(paginate).after(info);
        } else {
            table = $('#resTable').DataTable();
        }

        // Live Search logic
        $('input[name="search"]').on('keyup input', function() {
            table.search(this.value).draw();
        });

        // Custom Multi-Select Logic
        const statusDisplay = $('#statusDisplay');
        const statusDropdown = $('#statusDropdown');
        
        statusDisplay.on('click', function(e) {
            e.stopPropagation();
            statusDropdown.toggle();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.multi-select-container').length) {
                statusDropdown.hide();
            }
        });

        function updateStatusLabel() {
            const selected = [];
            $('.status-option-checkbox:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (selected.length === 0) {
                $('#statusLabel').text('All Statuses').css('color', 'var(--text-muted)');
            } else {
                $('#statusLabel').text(selected.join(', ')).css('color', 'white');
            }

            // Update DataTables
            if ("{{ $viewType }}" !== 'detail') {
                if (selected.length === 0) {
                    table.column(9).search('').draw();
                } else {
                    const regex = '^\\s*(' + selected.join('|').replace('-', '\\-') + ')\\s*$';
                    table.column(9).search(regex, true, false).draw();
                }
            }
            updateLinks();
        }

        $('.status-option-checkbox').on('change', updateStatusLabel);
        updateStatusLabel(); // Initial call

        // Apply initial filters if present
        if ($('input[name="search"]').val()) {
            table.search($('input[name="search"]').val()).draw();
        }
        // (Removed old status filter initializer as updateStatusLabel handles it)

        // Form submit should only happen for new date/status range
        $('#dashboardForm').on('submit', function(e) {
            // No changes needed here, just ensures date range is valid
        });

        // Reset Res No List when Date or Status Filter changes
        $('input[name="fromdate"], input[name="todate"], .status-option-checkbox').on('change', function() {
            $('input[name="resnolist"]').val('');
            $('.res-checkbox').prop('checked', false);
            $('#selectAllRes').prop('checked', false);
            updateLinks();
        });

        // Function to update export/print links based on current inputs
        function updateLinks() {
            const fromDate = $('input[name="fromdate"]').val() || '';
            const toDate = $('input[name="todate"]').val() || '';
            const resNoList = $('input[name="resnolist"]').val() || '';
            const statusFilter = [];
            $('.status-option-checkbox:checked').each(function() { statusFilter.push($(this).val()); });
            const search = $('input[name="search"]').val() || '';
            const perPage = $('select[name="per_page"]').val() || '10';

            const params = new URLSearchParams();
            params.append('fromdate', fromDate);
            params.append('todate', toDate);
            params.append('resnolist', resNoList);
            params.append('search', search);
            params.append('per_page', perPage);
            
            if (Array.isArray(statusFilter)) {
                statusFilter.forEach(s => params.append('status_filter[]', s));
            } else if (statusFilter) {
                params.append('status_filter[]', statusFilter);
            }
            
            const paramsString = params.toString();

            $('.btn-export-excel').attr('href', "{{ route('export') }}?" + paramsString);
            $('.btn-export-pdf').attr('href', "{{ route('print') }}?" + paramsString);
        }

        $('input[name], select[name]').on('change input', updateLinks);
        updateLinks(); // Initialize

        // Checkbox logic for selective export
        function syncResNoList() {
            let listInput = $('input[name="resnolist"]');
            let currentList = listInput.val().split(',').map(s => s.trim()).filter(s => s !== '');
            let currentSet = new Set(currentList);

            $('.res-checkbox').each(function() {
                let val = $(this).val();
                if ($(this).is(':checked')) {
                    currentSet.add(val);
                } else {
                    currentSet.delete(val);
                }
            });

            listInput.val(Array.from(currentSet).join(','));
            updateLinks();
        }

        // When a checkbox is toggled, update the input
        $('.res-checkbox').on('change', function() {
            syncResNoList();
            if (!$(this).is(':checked')) {
                $('#selectAllRes').prop('checked', false);
            }
        });

        // "Select All" toggle
        $('#selectAllRes').on('change', function() {
            let isChecked = $(this).is(':checked');
            $('.res-checkbox').prop('checked', isChecked);
            syncResNoList();
        });

        // When the input is typed into, update the checkboxes
        $('input[name="resnolist"]').on('input change', function() {
            let currentList = $(this).val().split(',').map(s => s.trim()).filter(s => s !== '');
            $('.res-checkbox').each(function() {
                $(this).prop('checked', currentList.includes($(this).val()));
            });
            updateLinks();
        });
    });
</script>
@endsection
