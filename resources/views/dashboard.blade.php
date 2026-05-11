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

    /* Timeline Scheduler UI */
    .timeline-container {
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid var(--border);
        overflow: hidden;
        margin-top: 1rem;
    }

    .timeline-scroll {
        overflow: auto;
        max-height: 75vh;
    }

    .timeline-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        color: var(--text);
    }

    .timeline-table th, .timeline-table td {
        padding: 0.75rem 1rem;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        white-space: nowrap;
    }

    /* Sticky Headers */
    .timeline-table thead tr:first-child th {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #1e293b;
        border-bottom: 2px solid var(--border);
    }

    /* Sticky Columns with Locked Widths */
    .timeline-table .sticky-col-1 { 
        position: sticky; left: 0; z-index: 90; background: #0f172a; 
        width: 90px; min-width: 90px; max-width: 90px;
        border-right: 2px solid var(--border) !important; 
    }
    .timeline-table .sticky-col-2 { 
        position: sticky; left: 90px; z-index: 90; background: #0f172a; 
        width: 120px; min-width: 120px; max-width: 120px;
        border-right: 2px solid var(--border) !important; 
    }
    .timeline-table .sticky-col-3 { 
        position: sticky; left: 210px; z-index: 90; background: #0f172a; 
        width: 280px; min-width: 280px; max-width: 280px;
        border-right: 2px solid var(--border) !important; 
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Corner Cells (The intersection of sticky headers and columns) */
    .timeline-table thead th.sticky-col-1,
    .timeline-table thead th.sticky-col-2,
    .timeline-table thead th.sticky-col-3 {
        z-index: 120 !important;
        background: #1e293b !important;
    }

    /* Ensure body columns stay above sliding date headers during horizontal scroll */
    .timeline-table tbody td.sticky-col-1,
    .timeline-table tbody td.sticky-col-2,
    .timeline-table tbody td.sticky-col-3 {
        z-index: 110;
    }

    /* Sticky Right (Total Rate) */
    .timeline-table .row-total, .timeline-table .sticky-total-head {
        position: sticky;
        right: 0;
        background: #0f172a;
        border-left: 2px solid var(--border);
    }

    .timeline-table .row-total {
        z-index: 95;
    }

    .timeline-table .sticky-total-head {
        z-index: 105;
        background: #1e293b !important;
    }

    .stay-block {
        background: linear-gradient(90deg, var(--primary), #818cf8);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        min-width: 90px;
        transition: all 0.2s;
    }

    .stay-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .rate-input {
        background: transparent;
        border: none;
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        width: 100%;
        text-align: center;
        padding: 0;
        outline: none;
    }

    .rate-input::-webkit-inner-spin-button,
    .rate-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .remove-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .remove-btn:hover {
        background: #ef4444;
    }

    .add-cell-btn {
        display: none;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px dashed var(--border);
        border-radius: 0.5rem;
        color: var(--text-muted);
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    td:hover .add-cell-btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .add-cell-btn:hover {
        background: rgba(99, 102, 241, 0.1);
        border-color: var(--primary);
        color: var(--primary);
    }

    .btn-add-row {
        background: rgba(16, 185, 129, 0.1);
        border: 1px dashed #10b981;
        color: #10b981;
        width: 100%;
        padding: 1rem;
        margin-top: 1rem;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-add-row:hover {
        background: rgba(16, 185, 129, 0.2);
        transform: translateY(-2px);
    }

    /* Breakdown Modal Styling */
    .modal-content {
        background: #1e293b !important;
        border: 1px solid var(--primary) !important;
        border-radius: 1rem !important;
        color: white !important;
    }
    .modal-header { border-bottom: 1px solid var(--border) !important; }
    .modal-footer { border-top: 1px solid var(--border) !important; }
    .breakdown-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .breakdown-table th { text-align: left; color: var(--text-muted); font-size: 0.75rem; padding: 0.5rem; border-bottom: 1px solid var(--border); }
    .breakdown-table td { padding: 0.5rem; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .info-btn {
        background: transparent;
        border: none;
        color: var(--primary);
        cursor: pointer;
        padding: 0 0.25rem;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s;
    }
    .info-btn:hover { color: #818cf8; transform: scale(1.1); }
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

        @if($viewType === 'detail')
            <div class="timeline-container">
                <div class="timeline-scroll">
                    <table class="timeline-table">
                        <thead>
                            <tr>
                                <th class="sticky-col-1" style="width: 100px;">RSVN#</th>
                                <th class="sticky-col-2" style="width: 120px;">Village</th>
                                <th class="sticky-col-3" style="width: 200px;">Occupant</th>
                                @foreach($dateCols as $date)
                                    <th class="date-header">
                                        <span class="date-day">{{ \Carbon\Carbon::parse($date)->format('D') }}</span>
                                        <span class="date-val">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                                    </th>
                                @endforeach
                                <th class="sticky-total-head" style="color: var(--primary); font-weight: bold;">
                                    Total Rate
                                    <div style="font-size: 0.65rem; font-weight: normal; opacity: 0.7; margin-top: 2px; color: var(--text-muted)">(Accomodation + fees)</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $resCounts = [];
                                foreach($reservations as $r) {
                                    $c = $r['resNo'] ?? $r['conf'] ?? 'N/A';
                                    $resCounts[$c] = ($resCounts[$c] ?? 0) + 1;
                                }
                                $renderedRes = [];
                            @endphp
                            @foreach($reservations as $res)
                                @php 
                                    $resNo = $res['resNo'] ?? $res['conf'] ?? 'N/A';
                                    $isFirstInGroup = !isset($renderedRes[$resNo]);
                                    if($isFirstInGroup) $renderedRes[$resNo] = true;
                                @endphp
                                <tr class="{{ $isFirstInGroup ? 'res-group-header' : '' }}">
                                    @if($isFirstInGroup)
                                        <td rowspan="{{ $resCounts[$resNo] }}" class="sticky-col-1" style="vertical-align: top; padding-top: 1.5rem;">
                                            <span class="res-no">#{{ $resNo }}</span>
                                        </td>
                                        <td rowspan="{{ $resCounts[$resNo] }}" class="sticky-col-2" style="vertical-align: top; padding-top: 1.5rem; font-weight: 600;">
                                            {{ $res['village_name'] ?? 'N/A' }}
                                        </td>
                                    @endif
                                    <td class="sticky-col-3" title="{{ $res['gstName'] ?? $res['guestName'] ?? '' }}">
                                        <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white;">
                                            {{ $res['gstName'] ?? $res['guestName'] ?? 'Unknown' }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted)">
                                            {{ $res['gstType'] ?? 'Guest' }}
                                            @if(!empty($res['is_employee'])) (Emp) @endif
                                        </div>
                                    </td>
                                    @foreach($dateCols as $date)
                                        @php 
                                            $dayRate = 0;
                                            foreach($res['rate'] ?? [] as $r) {
                                                if(($r['date'] ?? '') === $date) {
                                                    $dayRate = (float)($r['val'] ?? 0);
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <td class="rate-cell" data-date="{{ $date }}">
                                            @if($dayRate > 0)
                                                <div class="stay-block">
                                                    <input type="number" class="rate-input" value="{{ (int)$dayRate }}" step="100">
                                                    <button type="button" class="remove-btn" onclick="removeRate(this)">&times;</button>
                                                </div>
                                            @else
                                                <button type="button" class="add-cell-btn" onclick="addRate(this)">+</button>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="row-total" style="text-align: right; font-weight: 700; color: var(--primary);">
                                        @php
                                            $r = $res['calculated_rates'] ?? [];
                                            $total = floor($r['acc'] ?? 0) + floor($r['air'] ?? 0) + floor($r['han'] ?? 0) + floor($r['avi'] ?? 0) + floor($r['env'] ?? 0);
                                            // Store base fees for live calc
                                            $baseFees = floor($r['air'] ?? 0) + floor($r['han'] ?? 0) + floor($r['avi'] ?? 0) + floor($r['env'] ?? 0);
                                        @endphp
                                        <span class="total-val" data-base-fees="{{ $baseFees }}">{{ number_format($total, 2) }}</span>
                                        <button type="button" class="info-btn show-breakdown-btn" data-res='@json($res)' title="Calculation Breakdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn-add-row" onclick="addNewRow()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add New Record Row
                </button>
            </div>
        @else
            <div class="table-container">
                <table id="resTable" class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllRes" class="custom-checkbox"></th>
                            <th>Res No</th>
                            <th>Guest Name</th>
                            <th>Rooms</th>
                            <th>Pax</th>
                            <th>Rate</th>
                            <th>Rate Code</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservations as $res)
                            @php $currentResNo = $res['resNo'] ?? $res['conf'] ?? null; @endphp
                            <tr>
                                <td style="text-align: center;">
                                    @if($currentResNo)
                                        <input type="checkbox" class="res-checkbox custom-checkbox" value="{{ $currentResNo }}" {{ in_array($currentResNo, array_filter(explode(',', request('resnolist', '')))) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td>
                                    @if($currentResNo)
                                        <a href="{{ route('dashboard', ['resnolist' => $currentResNo]) }}" style="text-decoration: none;">
                                            <span class="res-no">#{{ $currentResNo }}</span>
                                        </a>
                                    @else
                                        <span class="res-no">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? 'Unknown' }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted)">{{ $res['custName'] ?? $res['customer'] ?? '' }}</div>
                                </td>
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Client-side pagination handled by DataTables --}}
        <div class="mt-4 d-flex justify-content-center" id="paginationContainer">
        </div>
    </div>
</div>

<!-- Breakdown Modal -->
<div class="modal fade" id="breakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calculation Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="breakdownContent">
                    <!-- Dynamic Content -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        var table;
        @if($viewType !== 'detail')
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
                "scrollX": false,
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
        @endif

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

        // Live Editing Functions
        window.recalculateRow = function(row) {
            let accTotal = 0;
            $(row).find('.rate-input').each(function() {
                accTotal += parseFloat($(this).val()) || 0;
            });
            
            const totalSpan = $(row).find('.total-val');
            const baseFees = parseFloat(totalSpan.data('base-fees')) || 0;
            const grandTotal = accTotal + baseFees;
            
            totalSpan.text(grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            updateGlobalStats();
        };

        window.updateGlobalStats = function() {
            let totalPax = 0;
            let totalArrived = 0;
            
            if ("{{ $viewType }}" === 'detail') {
                totalPax = $('.timeline-table tbody tr').length;
                // For simplicity in detail view, count all visible rows as pax
                $('#stat-total-pax').text(totalPax);
                // Total Rate stats could also be updated if we had a dedicated card for it
            }
        };

        $(document).on('input', '.rate-input', function() {
            recalculateRow($(this).closest('tr'));
        });

        window.removeRate = function(btn) {
            const cell = $(btn).closest('td');
            cell.html('<button type="button" class="add-cell-btn" onclick="addRate(this)">+</button>');
            recalculateRow(cell.closest('tr'));
        };

        window.addRate = function(btn) {
            const cell = $(btn).closest('td');
            // Default rate from settings or 0
            const defaultRate = 5000; 
            cell.html(`
                <div class="stay-block">
                    <input type="number" class="rate-input" value="${defaultRate}" step="100">
                    <button type="button" class="remove-btn" onclick="removeRate(this)">&times;</button>
                </div>
            `);
            recalculateRow(cell.closest('tr'));
            cell.find('.rate-input').focus().select();
        };

        window.addNewRow = function() {
            const tbody = $('.timeline-table tbody');
            const dateCount = {{ count($dateCols) }};
            
            let newRowHtml = `
                <tr>
                    <td class="sticky-col-1"><span class="text-primary" style="font-size: 0.8rem; font-weight: bold;">NEW</span></td>
                    <td class="sticky-col-2"><span class="text-white" style="font-size: 0.8rem; opacity: 0.7;">MANUAL</span></td>
                    <td class="sticky-col-3">
                        <input type="text" placeholder="Guest Name" class="bg-transparent border-0 text-white w-100" style="font-weight: 500; outline: none;" autofocus>
                        <div style="font-size: 0.7rem; color: var(--text-muted)">Manual Entry</div>
                    </td>
            `;
            
            for(let i=0; i<dateCount; i++) {
                newRowHtml += `<td class="rate-cell"><button type="button" class="add-cell-btn" onclick="addRate(this)">+</button></td>`;
            }
            
            newRowHtml += `
                    <td class="row-total" style="text-align: right; font-weight: 700; color: var(--primary); position: sticky; right: 0; background: #0f172a; z-index: 10; border-left: 2px solid var(--border);">
                        <span class="total-val" data-base-fees="0">0.00</span>
                    </td>
                </tr>
            `;
            
            tbody.append(newRowHtml);
            updateGlobalStats();
        };

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
        });        // Initialize Tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Breakdown Modal Logic
        $(document).on('click', '.show-breakdown-btn', function() {
            const res = $(this).data('res');
            let html = `
                <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                    <div style="font-weight: bold; font-size: 1.25rem; color: var(--primary);">${res.gstName || res.guestName || 'Unknown'}</div>
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        Reservation <span style="color: white; font-weight: 600;">#${res.resNo || res.conf}</span> | Unit: <span style="color: white; font-weight: 600;">${res.village_name || 'N/A'}</span>
                        ${res.is_employee ? ' | <span class="badge badge-member" style="font-size: 0.65rem;">Employee</span>' : ''}
                    </div>
                </div>
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Base Share</th>
                            <th>SC / VAT / Disc</th>
                            <th style="text-align: right;">Daily Total</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            let overallTotal = 0;
            let hasDiscount = false;

            (res.rate || []).forEach(r => {
                const b = r.breakdown;
                if (!b) return;

                overallTotal += parseFloat(r.val || 0);
                if (b.is_discounted) hasDiscount = true;

                const dateStr = r.date;
                let detailHtml = '';
                if (b.is_discounted) {
                    detailHtml = `
                        <div style="font-size: 0.7rem; color: #4ade80;">- 20% Disc: ₱${parseFloat(b.discount).toFixed(2)}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">+ 10% SC: ₱${parseFloat(b.sc).toFixed(2)}</div>
                        <div style="font-size: 0.7rem; color: #f87171;">VAT EXEMPT</div>
                    `;
                } else {
                    detailHtml = `
                        <div style="font-size: 0.7rem; color: var(--text-muted);">+ 10% SC: ₱${parseFloat(b.sc).toFixed(2)}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">+ 12% VAT: ₱${parseFloat(b.vat).toFixed(2)}</div>
                    `;
                }

                html += `
                    <tr>
                        <td style="white-space: nowrap; font-weight: 600;">${dateStr}</td>
                        <td>
                            <div style="font-weight: 500;">${b.is_discounted ? 'Senior/PWD' : 'Regular'}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">Gross: ₱${parseFloat(b.gross_share).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                        </td>
                        <td>₱${parseFloat(b.base).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td>${detailHtml}</td>
                        <td style="font-weight: bold; text-align: right; color: white;">₱${parseFloat(r.val).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });

            // Add other fees
            const fees = res.calculated_rates || {};
            const feeItems = [
                { label: 'Airfare', val: fees.air },
                { label: 'Hangar Fee', val: fees.han },
                { label: 'Aviation Ops Fee', val: fees.avi },
                { label: 'Environmental Fee', val: fees.env }
            ];

            let hasFees = false;
            feeItems.forEach(f => {
                if (f.val > 0) {
                    if (!hasFees) {
                        html += `<tr><td colspan="5" style="padding: 1rem 0.5rem 0.5rem 0.5rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: none;">Additional Fees</td></tr>`;
                        hasFees = true;
                    }
                    html += `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td colspan="2">${f.label}</td>
                            <td></td>
                            <td></td>
                            <td style="font-weight: bold; text-align: right; color: white;">₱${parseFloat(f.val).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                    overallTotal += parseFloat(f.val);
                }
            });

            html += `
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid var(--primary);">
                            <td colspan="4" style="text-align: right; padding-top: 1.5rem; font-weight: bold; color: var(--text-muted);">TOTAL BILLABLE:</td>
                            <td style="text-align: right; padding-top: 1.5rem; font-weight: 900; color: var(--primary); font-size: 1.4rem;">
                                ₱${overallTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                ${hasDiscount ? `
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(34, 197, 94, 0.1); border-radius: 0.75rem; border: 1px solid rgba(34, 197, 94, 0.2);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: #4ade80; font-size: 0.85rem; font-weight: bold; margin-bottom: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            FISCAL DISCOUNT APPLIED
                        </div>
                        <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                            VAT-Exempt status and 20% discount on base room rates have been applied for this occupant based on their Senior Citizen/PWD status.
                        </p>
                    </div>
                ` : ''}
            `;

            $('#breakdownContent').html(html);
            const modal = new bootstrap.Modal(document.getElementById('breakdownModal'));
            modal.show();
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
