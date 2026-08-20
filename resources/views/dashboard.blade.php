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
    .status-checkout, .status-completed, .status-NC-SYS-CANCELLED { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }

    .btn-fvn {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 0.75rem;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.1s;
    }
    .btn-fvn:hover {
        transform: scale(1.05);
        filter: brightness(1.1);
    }
    .btn-fvn:active {
        transform: scale(0.95);
    }
    .quick-fvn-options {
        background: rgba(0,0,0,0.2);
        padding: 6px;
        border-radius: 6px;
        border: 1px solid var(--border);
    }

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

    .rate-select {
        background: transparent;
        border: none;
        color: white;
        font-size: 0.72rem;
        font-weight: 700;
        width: 100%;
        text-align: center;
        padding: 0;
        outline: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
    }

    .rate-select option {
        background: #1e293b;
        color: white;
        font-weight: 600;
    }

    .rate-display {
        font-size: 0.78rem;
        font-weight: 700;
        color: white;
        cursor: pointer;
        flex: 1;
        text-align: center;
    }

    .fvn-display {
        color: #ffffff;
        font-size: 0.82rem;
    }

    .rate-editor {
        display: flex;
        align-items: center;
        gap: 0.2rem;
        flex: 1;
    }

    .stay-block.dragging {
        opacity: 0.4;
        cursor: grabbing;
    }

    .rate-cell.drag-over {
        outline: 2px dashed #6366f1;
        background: rgba(99, 102, 241, 0.12);
        border-radius: 0.5rem;
    }

    .stay-block {
        cursor: grab;
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
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.3;
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
        opacity: 1;
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
                @php
                    $propertyLabel = ['island' => '🏝️ Balesin Island', 'city' => '🏙️ Balesin City', 'pines' => '🌲 Balesin Pines'][$property] ?? 'Balesin Island';
                @endphp
                {{ $viewType === 'list' ? 'Reservation Summary' : 'Detailed Records' }}
                <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-muted); margin-left: 0.5rem;">— {{ $propertyLabel }}</span>
            </h2>
            @if($viewType === 'detail')
                <a href="javascript:void(0);" onclick="window.history.back();" class="btn btn-primary" style="background: rgba(255,255,255,0.1); font-size: 0.875rem;">
                    &larr; Back to List
                </a>
            @endif
        </div>

        <form id="dashboardForm" action="{{ route('dashboard') }}" method="GET" class="search-box" style="flex-direction: column;">
            <input type="hidden" name="sc_override" id="scOverrideInput" value="{{ $scOverrideRaw ?? '' }}">
            <input type="hidden" name="sc_remove"   id="scRemoveInput"   value="{{ $scRemoveRaw ?? '' }}">
            <input type="hidden" name="sc_target"   id="scTargetInput"   value="{{ $scTargetRaw ?? '' }}">
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
                <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Refresh Data</button>
                    @if($property === 'island')
                        <button type="submit" formaction="{{ route('export') }}" class="btn btn-primary btn-export-excel" style="background: var(--success)">
                            Export Excel
                        </button>
                        <button type="submit" formaction="{{ route('print') }}" formtarget="_blank" class="btn btn-primary btn-export-pdf" style="background: #ef4444">
                            Export PDF
                        </button>
                    @elseif($property === 'pines')
                        <button type="submit" formaction="{{ route('pines.export') }}" class="btn btn-primary btn-export-excel" style="background: var(--success)">
                            Export Excel
                        </button>
                        <button type="submit" formaction="{{ route('pines.print') }}" formtarget="_blank" class="btn btn-primary btn-export-pdf" style="background: #ef4444">
                            Export PDF
                        </button>
                    @elseif($property === 'city')
                        <button type="submit" formaction="{{ route('city.export') }}" class="btn btn-primary btn-export-excel" style="background: var(--success)">
                            Export Excel
                        </button>
                        <button type="submit" formaction="{{ route('city.print') }}" formtarget="_blank" class="btn btn-primary btn-export-pdf" style="background: #ef4444">
                            Export PDF
                        </button>
                    @endif
                </div>
            </div>

            <!-- Second Row: Filters -->
            <div style="display: flex; width: 100%; gap: 1rem; flex-wrap: wrap;">
                <!-- Property Selector -->
                <div class="input-group" style="flex: 0 0 180px; min-width: 160px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Property</label>
                    <select name="property" id="propertySelect" onchange="
                        var rn = document.querySelector('[name=resnolist]');
                        if (rn) rn.value = '';
                        this.form.submit();
                    " style="font-weight: 600;">
                        <option value="island" {{ $property === 'island' ? 'selected' : '' }}>🏝️ Balesin Island</option>
                        <option value="city"   {{ $property === 'city'   ? 'selected' : '' }}>🏙️ Balesin City</option>
                        <option value="pines"  {{ $property === 'pines'  ? 'selected' : '' }}>🌲 Balesin Pines</option>
                    </select>
                </div>

                @if($viewType !== 'detail' || !$resNoList)
                    <div class="input-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">From Date</label>
                        <input type="date" name="fromdate" value="{{ $fromDate }}">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">To Date</label>
                        <input type="date" name="todate" value="{{ $toDate }}">
                    </div>
                @else
                    <input type="hidden" name="fromdate" value="{{ $fromDate }}">
                    <input type="hidden" name="todate" value="{{ $toDate }}">
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
                            <label class="multi-select-option" style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                                <input type="checkbox" id="selectAllStatus" {{ count($selectedStatuses) === 12 ? 'checked' : '' }}>
                                <span style="font-weight: 600; color: var(--primary);">SELECT ALL</span>
                            </label>
                            @foreach(['CONFIRMED', 'NOT-CONFIRMED', 'ARRIVED', 'DEPARTED', 'CANCELLED', 'NOSHOW', 'SYSTEM CANCELLED', 'PARTLY ARRIVED', 'PARTIAL SYS CANCELLED', 'PARTLY CANCELLED', 'NC-SYS CANCELLED', 'VOID'] as $st)
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
                @if($viewType !== 'detail' && $property === 'island')
                <div class="input-group" style="max-width: 320px;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Res No List</label>
                    <input type="text" name="resnolist" value="{{ $resNoList }}" placeholder="IDs separated by comma...">
                </div>
                @elseif($viewType === 'detail')
                    <input type="hidden" name="resnolist" value="{{ $resNoList }}">
                @endif
            </div>
        </form>

        @if($viewType === 'detail')
            @php
                // Collect first non-empty value for each member meta field across reservations
                $detMemberName = '';
                $detMemberNo  = '';
                $detBookDate  = '';
                $detContactNo = '';
                foreach ($reservations as $_r) {
                    if (!$detMemberName) {
                        $cCandidate = trim((string)($_r['customer_name'] ?? $_r['custName'] ?? $_r['customer'] ?? $_r['cust_name'] ?? $_r['guestName'] ?? $_r['gstName'] ?? ''));
                        if ($cCandidate !== '') $detMemberName = $cCandidate;
                    }
                    if (!$detMemberNo) {
                        $mCandidate = trim((string)($_r['memNo'] ?? $_r['memberNo'] ?? $_r['member_no'] ?? $_r['MemberNo'] ?? $_r['memberno'] ?? $_r['mem_no'] ?? ''));
                        if ($mCandidate !== '') $detMemberNo = $mCandidate;
                    }
                    if (!$detBookDate  && !empty($_r['bookDate']))        $detBookDate  = $_r['bookDate'];
                    if (!$detContactNo && !empty($_r['conactNo']))        $detContactNo = $_r['conactNo'];
                    elseif (!$detContactNo && !empty($_r['contactNo']))   $detContactNo = $_r['contactNo'];
                    if ($detMemberName && $detMemberNo && $detBookDate && $detContactNo) break;
                }
                // Format booking date
                $detBookDateFormatted = '';
                if ($detBookDate) {
                    try { $detBookDateFormatted = (new \DateTime($detBookDate))->format('l, d F Y'); } catch (\Exception $e) { $detBookDateFormatted = $detBookDate; }
                }
                $detMemNoClean = trim((string)$detMemberNo);
                $detMemNoLower = strtolower($detMemNoClean);
                $hasDetMemberNo = $detMemNoClean !== '' && !in_array($detMemNoLower, ['n/a', 'na', 'none', 'null', '0', 'false', 'no']);
            @endphp
            @if($detMemberName || $detMemberNo || $detBookDate || $detContactNo)
            <div style="margin-bottom: 1rem;">
                @if($detMemberName)
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; margin-bottom: 0.35rem;">
                    {{ $detMemberName }}
                </div>
                @endif
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    @if($hasDetMemberNo)
                    <a href="{{ route('member.search', ['member_no' => $detMemberNo]) }}" target="_blank" title="View Member Reservation Filter for {{ $detMemberNo }}" style="font-size: 0.8rem; font-family: monospace; font-weight: 700; color: #818cf8; letter-spacing: 0.04em; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>{{ $detMemberNo }}
                    </a>
                    @elseif($detMemberNo)
                    <span style="font-size: 0.8rem; font-family: monospace; font-weight: 700; color: var(--text-muted); letter-spacing: 0.04em;">
                        {{ $detMemberNo }}
                    </span>
                    @endif
                    @if($detBookDateFormatted)
                    <span style="font-size: 0.75rem; color: var(--text-muted);">·</span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $detBookDateFormatted }}</span>
                    @endif
                    @if($detContactNo)
                    <span style="font-size: 0.75rem; color: var(--text-muted);">·</span>
                    <span style="font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">{{ $detContactNo }}</span>
                    @endif
                </div>
            </div>
            @endif
            <div class="timeline-container">
                <div class="timeline-scroll">
                    <table class="timeline-table">
                        <thead>
                            <tr>
                                <th class="sticky-col-1" style="width: 100px;">RSVN#</th>
                                <th class="sticky-col-2" style="width: 120px;">Village</th>
                                @if($property === 'island' || $property === 'pines')
                                <th style="text-align:center; min-width:72px; font-size:0.75rem; padding: 0.5rem 0.4rem;">
                                    SC / PWD
                                    <div style="font-size:0.6rem;font-weight:normal;opacity:0.65;margin-top:2px">Override</div>
                                </th>
                                @endif
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
                                // ─────────────────────────────────────────────────────────────
                                // Pre‑calculate spans & build display rows (including splits)
                                // ─────────────────────────────────────────────────────────────
                                $reservations = array_values($reservations);
                                $resDataForJs = [];
                                $resGroups = [];
                                foreach($reservations as $r) {
                                    $c = trim($r['resNo'] ?? $r['conf'] ?? 'N/A');
                                    $resGroups[$c][] = $r;
                                }

                                $scTargets = [];
                                foreach (explode(',', $scTargetRaw ?? '') as $pair) {
                                    $p = explode(':', trim($pair));
                                    if (count($p) === 3) $scTargets[trim($p[0]) . ':' . (int)$p[1]] = strtolower(trim($p[2]));
                                }
                                $scOverrides = [];
                                foreach (explode(',', $scOverrideRaw ?? '') as $pair) {
                                    $p = explode(':', trim($pair));
                                    if (count($p) === 2) $scOverrides[] = trim($p[0]) . ':' . (int)$p[1];
                                }
                                $scRemoves = [];
                                foreach (explode(',', $scRemoveRaw ?? '') as $pair) {
                                    $p = explode(':', trim($pair));
                                    if (count($p) === 2) $scRemoves[] = trim($p[0]) . ':' . (int)$p[1];
                                }

                                $displayRows = []; // will hold [res, spanInfo] for each displayed row
                                $accSpans = [];

                                foreach($resGroups as $rn => $rows) {
                                    $count = count($rows);
                                    // Determine base_pax
                                    $basePax = 4;
                                    if ($property === 'pines') {
                                        $basePax = 2;
                                    } elseif ($property === 'island') {
                                        foreach ($rows as $r) {
                                            $bp = $r['calculated_rates']['base_pax'] ?? 4;
                                            if ($bp === 8) { $basePax = 8; break; }
                                        }
                                    }
                                    
                                    $curr = 0;
                                    while ($curr < $count) {
                                        $blockStart = $curr;
                                        $isBlockExtra = ($curr >= $basePax);
                                        $blockSize = ($property === 'island' || $property === 'pines') ? ($isBlockExtra ? 1 : min($count - $curr, $basePax)) : 1;
                                        
                                        if ($property === 'island' || $property === 'pines') {
                                            // Build groupTotals from current occupant if extra person, or first occupant in block if base block
                                            $groupTotals = [];
                                            foreach($dateCols as $d) {
                                                $uv = 0;
                                                $firstPassenger = $isBlockExtra ? ($rows[$curr] ?? null) : ($rows[$blockStart] ?? null);
                                                if ($firstPassenger) {
                                                    foreach ($firstPassenger['rate'] ?? [] as $rt) {
                                                        if (($rt['date'] ?? '') === $d) {
                                                            $rawVal = (float)($rt['val'] ?? 0);
                                                            $breakdown = $rt['breakdown'] ?? null;

                                                            // ── FVN guard: 0.01 = 1 FVN, 0.02 = 1.5 FVN, 0.03 = 0.5 FVN split, 0.5 = 0.5 FVN
                                                            // These are rate CODE markers — never do arithmetic on them
                                                            $rawFrac = round($rawVal - floor($rawVal), 2);
                                                            $isFvnMarker = in_array($rawFrac, [0.01, 0.02, 0.03, 0.5]) && $rawVal < 10;
                                                            if ($isFvnMarker) {
                                                                $uv = $rawVal; // pass through unchanged
                                                                break;
                                                            }

                                                            $apiPriv = strtolower(trim((string)($firstPassenger['privCard'] ?? $firstPassenger['privcard'] ?? '')));
                                                            $isApiSc = !in_array($apiPriv, ['', 'n', 'no', 'false', '0', 'none', 'null']) && (str_contains($apiPriv, 'senior') || str_contains($apiPriv, 'pwd'));

                                                            if ($breakdown && !empty($breakdown['gross_share'])) {
                                                                $uv = (float)$breakdown['gross_share'];
                                                                if ($uv > 0 && $uv < 3000 && $blockSize > 1 && !$isBlockExtra) {
                                                                    $uv = $uv * $blockSize;
                                                                }
                                                            } elseif ($isApiSc || ($breakdown && !empty($breakdown['is_discounted']))) {
                                                                $fullGross = round(($rawVal * 14 / 11), 2);
                                                                if (abs($fullGross - round($fullGross)) < 0.15) {
                                                                    $fullGross = round($fullGross);
                                                                }
                                                                if ($fullGross < 3000 && $blockSize > 1 && !$isBlockExtra) {
                                                                    $fullGross = $fullGross * $blockSize;
                                                                }
                                                                $uv = $fullGross;
                                                            } else {
                                                                $uv = $rawVal;
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                $groupTotals[$d] = $uv;
                                            }

                                            for ($i = 0; $i < $blockSize; $i++) {
                                                $isExtraPerson = (($curr + $i) >= $basePax && isset($rows[$curr + $i]));
                                                $res = $rows[$curr + $i] ?? null;
                                                if (!$res) continue;

                                                // Check if this block has .03 and is not extra person
                                                $has03 = false;
                                                foreach ($groupTotals as $val) {
                                                    $frac = round($val - floor($val), 2);
                                                    if ($frac == 0.03 && !$isExtraPerson) {
                                                        $has03 = true;
                                                        break;
                                                    }
                                                }

                                                if ($has03) {
                                                    // Create TWO rows: paid part and FVN part
                                                    $paidTotals = [];
                                                    $fvnTotals  = [];
                                                    foreach ($dateCols as $d) {
                                                        $val = $groupTotals[$d] ?? 0;
                                                        $frac = round($val - floor($val), 2);
                                                        if ($frac == 0.03) {
                                                            $paidTotals[$d] = floor($val); // integer part
                                                            $fvnTotals[$d]  = 0.03;        // marker for .5 FVN
                                                        } else {
                                                            $paidTotals[$d] = $val;
                                                            $fvnTotals[$d]  = 0;
                                                        }
                                                    }

                                                    // FVN row
                                                    $displayRows[] = [
                                                        'res' => $res,
                                                        'spanInfo' => [
                                                            'span'            => 1,
                                                            'totals'          => $fvnTotals,
                                                            'group_size'      => $blockSize,
                                                            'is_extra_person' => $isExtraPerson,
                                                            'is_split'        => true,
                                                            'split_type'      => 'fvn',
                                                            'occupant_idx'    => ($curr + $i)
                                                        ]
                                                    ];
                                                    // Paid row
                                                    $displayRows[] = [
                                                        'res' => $res,
                                                        'spanInfo' => [
                                                            'span'            => 1,
                                                            'totals'          => $paidTotals,
                                                            'group_size'      => $blockSize,
                                                            'is_extra_person' => $isExtraPerson,
                                                            'is_split'        => true,
                                                            'split_type'      => 'paid',
                                                            'occupant_idx'    => ($curr + $i)
                                                        ]
                                                    ];
                                                    
                                                } else {
                                                    // Check if accommodation has a discount (or SC/PWD override for Villa/Both)
                                                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? 'N/A');
                                                    $occupantIdx = $curr + $i;
                                                    $key = $resNo . ':' . $occupantIdx;

                                                    $apiPrivCard = trim((string)($res['privCard'] ?? $res['privcard'] ?? ''));
                                                    $apiPrivLower = strtolower($apiPrivCard);
                                                    $alreadySc = !in_array($apiPrivLower, ['', 'n', 'no', 'false', '0', 'none', 'null'])
                                                                 && (str_contains($apiPrivLower, 'senior') || str_contains($apiPrivLower, 'pwd') || str_contains($apiPrivLower, 'person with'));

                                                    $targetMode = $scTargets[$key] ?? null;
                                                    $hasVillaDiscount = false;
                                                    if ($targetMode === 'villa' || $targetMode === 'both') {
                                                        $hasVillaDiscount = true;
                                                    } elseif ($targetMode === 'air' || $targetMode === 'none') {
                                                        $hasVillaDiscount = false;
                                                    } else {
                                                        if (in_array($key, $scOverrides)) $hasVillaDiscount = true;
                                                        elseif (in_array($key, $scRemoves)) $hasVillaDiscount = false;
                                                        else $hasVillaDiscount = $alreadySc;
                                                    }

                                                    // Check if any occupant in this block has a Villa discount applied
                                                    $blockHasDiscount = $hasVillaDiscount;
                                                    if (!$blockHasDiscount) {
                                                        for ($j = 0; $j < $blockSize; $j++) {
                                                            $rCheck = $rows[$curr + $j] ?? null;
                                                            if ($rCheck) {
                                                                $kCheck = $resNo . ':' . ($curr + $j);
                                                                $tmCheck = $scTargets[$kCheck] ?? null;
                                                                if ($tmCheck === 'villa' || $tmCheck === 'both') {
                                                                    $blockHasDiscount = true;
                                                                    break;
                                                                }
                                                                if ($tmCheck === null) {
                                                                    $pCheck = strtolower(trim((string)($rCheck['privCard'] ?? $rCheck['privcard'] ?? '')));
                                                                    if (!in_array($pCheck, ['', 'n', 'no', 'false', '0', 'none', 'null']) && (str_contains($pCheck, 'senior') || str_contains($pCheck, 'pwd')) && !in_array($kCheck, $scRemoves)) {
                                                                        $blockHasDiscount = true;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($blockHasDiscount) {
                                                        // DO NOT use groupspan! Show per-occupant discounted amount per row (span = 1)
                                                        $passTotals = [];
                                                        foreach ($dateCols as $d) {
                                                            $uv = 0;
                                                            foreach ($res['rate'] ?? [] as $rt) {
                                                                if (($rt['date'] ?? '') === $d) {
                                                                    $uv = (float)($rt['val'] ?? 0);
                                                                    break;
                                                                }
                                                            }
                                                            if ($uv == 0) {
                                                                $uv = $groupTotals[$d] ?? 0;
                                                            }
                                                            // FVN markers (0.01, 0.02, 0.03, 0.5 with val < 10) — pass through unchanged, never divide
                                                            $uvFrac = round($uv - floor($uv), 2);
                                                            $isUvFvn = in_array($uvFrac, [0.01, 0.02, 0.03, 0.5]) && $uv < 10;
                                                            if (!$isUvFvn) {
                                                                // If rate is full room rate (e.g. 7857.14), divide by group size to show per-occupant share (1964.29)
                                                                if ($uv > 100 && $blockSize > 1 && !$isExtraPerson) {
                                                                    if (abs($uv - ($groupTotals[$d] ?? 0)) < 1.0 || $uv > 3000) {
                                                                        $uv = round($uv / $blockSize, 2);
                                                                    }
                                                                }
                                                            }
                                                            $passTotals[$d] = $uv;
                                                        }

                                                        $displayRows[] = [
                                                            'res' => $res,
                                                            'spanInfo' => [
                                                                'span'            => 1,
                                                                'totals'          => $passTotals,
                                                                'group_size'      => $blockSize,
                                                                'is_extra_person' => $isExtraPerson,
                                                                'is_split'        => false,
                                                                'occupant_idx'    => ($curr + $i),
                                                                'has_discount'    => true
                                                            ]
                                                        ];
                                                    } else {
                                                        // Regular: keep standard group span
                                                        $span = ($i === 0) ? $blockSize : 0;
                                                        $displayRows[] = [
                                                            'res' => $res,
                                                            'spanInfo' => [
                                                                'span'            => $span,
                                                                'totals'          => $groupTotals,
                                                                'group_size'      => $blockSize,
                                                                'is_extra_person' => $isExtraPerson,
                                                                'is_split'        => false,
                                                                'occupant_idx'    => ($curr + $i),
                                                                'has_discount'    => false
                                                            ]
                                                        ];
                                                    }
                                                }
                                            }
                                        } else {
                                            // City: no merging
                                            for ($i = 0; $i < $blockSize; $i++) {
                                                $res = $rows[$curr + $i] ?? null;
                                                if (!$res) continue;
                                                $passTotals = [];
                                                foreach ($dateCols as $d) {
                                                    $uv = 0;
                                                    foreach ($res['rate'] ?? [] as $rt) {
                                                        if (($rt['date'] ?? '') === $d) { $uv = (float)($rt['val'] ?? 0); break; }
                                                    }
                                                    $passTotals[$d] = $uv;
                                                }
                                                $displayRows[] = [
                                                    'res' => $res,
                                                    'spanInfo' => [
                                                        'span'            => 1,
                                                        'totals'          => $passTotals,
                                                        'group_size'      => 1,
                                                        'is_extra_person' => false,
                                                        'is_split'        => false,
                                                        'occupant_idx'    => ($curr + $i)
                                                    ]
                                                ];
                                            }
                                        }
                                        $curr += $blockSize;
                                    }
                                }

                                // Build a map of reservation number -> total number of rows (including splits)
                                $resRowCounts = [];
                                foreach ($displayRows as $row) {
                                    $rn = trim($row['res']['resNo'] ?? $row['res']['conf'] ?? 'N/A');
                                    $resRowCounts[$rn] = ($resRowCounts[$rn] ?? 0) + 1;
                                }

                                // Prepare data for JS and accSpans array
                                $resDataForJs = [];
                                foreach ($displayRows as $idx => $row) {
                                    $resDataForJs[$idx] = $row['res'];
                                }
                                $accSpans = array_column($displayRows, 'spanInfo');
                                $renderedRes = [];
                            @endphp
                            @foreach($displayRows as $idx => $row)
                                @php
                                    $res = $row['res'];
                                    $spanInfo = $row['spanInfo'];
                                    $resNo = trim($res['resNo'] ?? $res['conf'] ?? 'N/A');
                                    $isFirstInGroup = !isset($renderedRes[$resNo]);
                                    if($isFirstInGroup) $renderedRes[$resNo] = true;

                                    $isSplit = $spanInfo['is_split'] ?? false;
                                    $splitType = $spanInfo['split_type'] ?? 'paid';
                                    $groupSize = $spanInfo['group_size'] ?? 1;

                                    // Rowspan for RSVN# and Room Type
                                    $rowspan = $isFirstInGroup ? ($resRowCounts[$resNo] ?? 1) : 0;
                                @endphp
                                <tr class="{{ $isSplit ? 'split-row' : '' }}" data-res-no="{{ $resNo }}" data-split-type="{{ $splitType }}">
                                    @if($isFirstInGroup)
                                        <td rowspan="{{ $rowspan }}" class="sticky-col-1" style="vertical-align: top; padding-top: 1.5rem;">
                                            <span class="res-no">#{{ $resNo }}</span>
                                        </td>
                                        <td rowspan="{{ $rowspan }}" class="sticky-col-2" style="vertical-align: top; padding-top: 1.5rem; font-weight: 600;">
                                            <div>{{ strtoupper($res['village_name'] ?? 'N/A') }}</div>
                                            @if(!empty($res['unit_type_label']))
                                                <div>{{ strtoupper($res['unit_type_label']) }}</div>
                                            @endif
                                        </td>
                                    @endif

                                    @if($property === 'island' || $property === 'pines')
                                    @php
                                        $occupantIdx   = $spanInfo['occupant_idx'] ?? 0;
                                        $isExtraOcc    = $spanInfo['is_extra_person'] ?? false;
                                        $splitType2    = $spanInfo['split_type'] ?? 'paid';
                                        $overrideKey   = $resNo . ':' . $occupantIdx;

                                        $apiPrivCard   = trim((string)($res['privCard'] ?? $res['privcard'] ?? ''));
                                        $apiPrivLower  = strtolower($apiPrivCard);
                                        $alreadySc     = !in_array($apiPrivLower, ['', 'n', 'no', 'false', '0', 'none', 'null'])
                                                         && (str_contains($apiPrivLower, 'senior') || str_contains($apiPrivLower, 'pwd') || str_contains($apiPrivLower, 'person with'));

                                        $scTargets = [];
                                        foreach (explode(',', $scTargetRaw ?? '') as $pair) {
                                            $p = explode(':', trim($pair));
                                            if (count($p) === 3) $scTargets[trim($p[0]) . ':' . (int)$p[1]] = strtolower(trim($p[2]));
                                        }
                                        $scOverrides = [];
                                        foreach (explode(',', $scOverrideRaw ?? '') as $pair) {
                                            $p = explode(':', trim($pair));
                                            if (count($p) === 2) $scOverrides[] = trim($p[0]) . ':' . (int)$p[1];
                                        }
                                        $scRemoves = [];
                                        foreach (explode(',', $scRemoveRaw ?? '') as $pair) {
                                            $p = explode(':', trim($pair));
                                            if (count($p) === 2) $scRemoves[] = trim($p[0]) . ':' . (int)$p[1];
                                        }

                                        $activeMode = $scTargets[$overrideKey] ?? null;
                                        if (!$activeMode) {
                                            if (in_array($overrideKey, $scOverrides)) $activeMode = 'both';
                                            elseif (in_array($overrideKey, $scRemoves)) $activeMode = 'none';
                                            elseif ($alreadySc) $activeMode = 'both';
                                            else $activeMode = 'none';
                                        }

                                         $showScCheckbox = ($splitType2 !== 'fvn');
                                    @endphp
                                    <td style="text-align:center; vertical-align:middle; padding: 0.4rem;">
                                        @if($showScCheckbox)
                                            <button type="button" 
                                                    class="btn open-sc-modal-btn" 
                                                    data-override-key="{{ $overrideKey }}"
                                                    data-mode="{{ $activeMode }}"
                                                    data-guest-name="{{ $res['gstName'] ?? $res['guestName'] ?? 'Occupant' }}"
                                                    data-res-idx="{{ $idx }}"
                                                    style="padding: 2px 7px; font-size: 0.7rem; font-weight: 600; border-radius: 6px; white-space: nowrap; border: 1px solid transparent; transition: all 0.2s;">
                                                @if($activeMode === 'both')
                                                    <span class="sc-badge-label" style="color: #4ade80; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); padding: 2px 6px; border-radius: 4px;">🌟 Both SC</span>
                                                @elseif($activeMode === 'villa')
                                                    <span class="sc-badge-label" style="color: #c084fc; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.4); padding: 2px 6px; border-radius: 4px;">🏡 Villa Only</span>
                                                @elseif($activeMode === 'air')
                                                    <span class="sc-badge-label" style="color: #60a5fa; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); padding: 2px 6px; border-radius: 4px;">✈️ Airfare Only</span>
                                                @else
                                                    <span class="sc-badge-label" style="color: var(--text-muted, #94a3b8); background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 2px 6px; border-radius: 4px;">Regular</span>
                                                @endif
                                            </button>
                                        @endif
                                    </td>
                                    @endif

                                    <td class="sticky-col-3" title="{{ $res['gstName'] ?? $res['guestName'] ?? '' }}">
                                        <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white;">
                                            {{ $res['gstName'] ?? $res['guestName'] ?? 'Unknown' }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted)">
                                            {{ $res['gstType'] ?? 'Guest' }}
                                        </div>
                                    </td>

                                    @php
                                        $resAge      = $res['age'] ?? null;
                                        $resGstType  = strtolower(trim($res['gstType'] ?? ''));
                                        $ageInt      = ($resAge !== null && $resAge !== '') ? (int)$resAge : 99;
                                        $isInfant    = str_contains($resGstType, 'infant') || ($resAge !== null && $resAge !== '' && $ageInt >= 0 && $ageInt <= 1);
                                    @endphp

                                    @if($spanInfo['span'] > 0)
                                        @foreach($dateCols as $date)
                                            @php 
                                                $dayRate = (float)($spanInfo['totals'][$date] ?? 0);
                                                $fullRoomBaseRate = (float)($spanInfo['full_group_totals'][$date] ?? $dayRate);
                                            @endphp
                                            <td class="rate-cell" data-date="{{ $date }}" data-base-rate="{{ $fullRoomBaseRate }}" data-orig-rowspan="{{ $spanInfo['span'] }}" rowspan="{{ $spanInfo['span'] }}" style="vertical-align: middle; text-align: center; cursor: pointer;">
                                                @if($isInfant)
                                                    {{-- Child / Infant: no accommodation charge --}}
                                                    <div class="stay-block" style="background: rgba(148,163,184,0.15); border: 1px dashed rgba(148,163,184,0.3);">
                                                        <span class="rate-display" style="color: var(--text-muted); font-size: 0.7rem;">Child</span>
                                                    </div>
                                                @elseif(isset($spanInfo['totals'][$date]))
                                                    @php 
                                                        $rounded = round($dayRate, 2);
                                                        $frac = round($rounded - floor($rounded), 2);
                                                        $integerPart = floor($rounded);
                                                        $isExtra = $spanInfo['is_extra_person'] ?? false;
                                                    @endphp
                                                    <div class="stay-block" draggable="true">
                                                        <span class="rate-display {{ (!$isExtra && in_array($frac, [0.01, 0.02, 0.03])) ? 'fvn-display' : '' }}">
                                                            @if($isExtra)
                                                                @if($rounded <= 1.0 || abs($rounded - 3700) < 1.0)
                                                                    3,700.00
                                                                @else
                                                                    {{ number_format($rounded, 2) }}
                                                                @endif
                                                            @else
                                                                @if($frac == 0.01)
                                                                    1 FVN
                                                                @elseif($frac == 0.02)
                                                                    1.5 FVN
                                                                @elseif($frac == 0.03)
                                                                    @if($integerPart > 0)
                                                                        {{ number_format($integerPart, 0) }}
                                                                    @else
                                                                        .5 FVN
                                                                    @endif
                                                                @else
                                                                    {{ number_format($rounded, 2) }}
                                                                @endif
                                                            @endif
                                                        </span>
                                                    </div>
                                                @else
                                                    <button type="button" class="add-cell-btn" onclick="addRate(this)">+</button>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endif

                                    @php
                                        $r = $res['calculated_rates'] ?? [];
                                        
                                        // Compute the total accommodation for this group (non-FVN only)
                                        $groupTotals = $spanInfo['totals'] ?? [];
                                        $groupAccSum = 0;
                                        foreach ($groupTotals as $gv) {
                                            $grv = round((float)$gv, 2);
                                            if ($grv !== 0.01 && $grv !== 0.02 && $grv !== 0.5) {
                                                $groupAccSum += (float)$gv;
                                            }
                                        }
                                        $groupSize = max(1, (int) ($spanInfo['group_size'] ?? 1));
                                        $span = $spanInfo['span'] ?? 1;
                                        $hasDiscount = $spanInfo['has_discount'] ?? false;
                                        if ($span === 1 && $hasDiscount) {
                                            // Per-occupant discounted totals — already the correct individual amount
                                            $perOccupantAcc = $groupAccSum;
                                        } else {
                                            // Regular span (span>1 = first row, span=0 = non-first row) — full room rate, divide by group size
                                            $perOccupantAcc = $groupAccSum / $groupSize;
                                        }

                                        // For FVN split rows, set total to 0
                                        if ($splitType === 'fvn') {
                                            $perOccupantAcc = 0;
                                        }

                                        $total = $perOccupantAcc + ($r['air'] ?? 0) + ($r['han'] ?? 0) + ($r['avi'] ?? 0) + ($r['env'] ?? 0);
                                        $baseFees = ($r['air'] ?? 0) + ($r['han'] ?? 0) + ($r['avi'] ?? 0) + ($r['env'] ?? 0);
                                    @endphp
                                    <td class="row-total" style="text-align: right; font-weight: 700; color: var(--primary);">
                                        <span class="total-val" data-base-fees="{{ $baseFees }}">{{ number_format($total, 2) }}</span>
                                        <button type="button" class="info-btn show-breakdown-btn" data-res-idx="{{ $idx }}" data-bs-toggle="modal" data-bs-target="#breakdownModal" title="Calculation Breakdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <script>
                        window.__resData = {!! json_encode($resDataForJs ?? []) !!};
                        window.__property = '{{ $property }}';
                        window.__accSpans = {!! json_encode(array_map(fn($s) => ['span' => $s['span'], 'group_size' => $s['group_size'], 'is_extra_person' => $s['is_extra_person'] ?? false], $accSpans)) !!};
                    </script>
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
                            @php
                                $currentResNo = $res['resNo'] ?? $res['conf'] ?? null;
                                $memNo = trim((string) (
                                    $res['memberNo']    ??
                                    $res['memNo']       ??
                                    $res['member_no']   ??
                                    $res['memberno']    ??
                                    $res['MemberNo']    ??
                                    $res['mem_no']      ??
                                    $res['custNo']      ??
                                    $res['customer_no'] ??
                                    ''
                                ));
                                $memNoLower = strtolower($memNo);
                                $hasMemberNo = $memNo !== '' && !in_array($memNoLower, ['n/a', 'na', 'none', 'null', '0', 'false', 'no']);
                            @endphp
                            <tr data-pax="{{ (int)($res['noPax'] ?? $res['noOfPax'] ?? 0) }}" data-status="{{ strtoupper(trim($res['status'] ?? '')) }}">
                                <td style="text-align: center;">
                                    @if($currentResNo)
                                        <input type="checkbox" class="res-checkbox custom-checkbox" value="{{ $currentResNo }}" {{ in_array($currentResNo, array_filter(explode(',', request('resnolist', '')))) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td>
                                    @if($currentResNo)
                                        <a href="{{ route('dashboard', ['resnolist' => $currentResNo, 'property' => $property]) }}" style="text-decoration: none;">
                                             <span class="res-no">#{{ $currentResNo }}</span>
                                        </a>
                                    @else
                                        <span class="res-no">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $primaryName = $res['gstName'] ?? $res['guestName'] ?? $res['custName'] ?? $res['customer'] ?? $res['customer_name'] ?? 'Unknown';
                                        $secondaryName = $res['custName'] ?? $res['customer'] ?? $res['customer_name'] ?? null;
                                        $showSecondary = $secondaryName && strtolower(trim($secondaryName)) !== strtolower(trim($primaryName));
                                    @endphp
                                    <div>{{ $primaryName }}</div>
                                    @if($showSecondary)
                                        <div style="font-size: 0.75rem; color: var(--text-muted)">{{ $secondaryName }}</div>
                                    @endif
                                    @if($hasMemberNo)
                                        <div style="margin-top: 0.25rem;">
                                            <a href="{{ route('member.search', ['member_no' => $memNo]) }}" 
                                               target="_blank"
                                               title="View Member Reservation Filter for {{ $memNo }}" 
                                               style="font-size: 0.75rem; font-family: monospace; color: #818cf8; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 600;"
                                               onmouseover="this.style.textDecoration='underline'"
                                               onmouseout="this.style.textDecoration='none'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                                <span>{{ $memNo }}</span>
                                            </a>
                                        </div>
                                    @endif
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

@section('modals')
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

<!-- SC/PWD Discount Option Modal -->
<div class="modal fade" id="scDiscountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content" style="background: var(--bg-card, #1e293b); border: 1px solid var(--border, #334155); border-radius: 12px; color: white;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1rem 1.25rem;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 1.1rem; color: var(--primary, #fbbf24); display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    Apply SC / PWD Privilege
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <p id="scModalOccupantName" style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem; color: #f8fafc;"></p>
                <p style="font-size: 0.8rem; color: var(--text-muted, #94a3b8); margin-bottom: 1.25rem;">Select which component(s) should receive the Senior Citizen / PWD privilege discount for this occupant:</p>
                
                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn sc-option-btn text-start p-3" data-mode="air" style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); border-radius: 8px; color: white; transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 700; color: #60a5fa; font-size: 0.95rem;">✈️ Airfare Only</div>
                                <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 2px;">Applies 12% VAT exemption to Airfare. Room rate remains regular.</div>
                            </div>
                            <span class="badge bg-primary" style="font-size: 0.7rem;">Airfare</span>
                        </div>
                    </button>

                    <button type="button" class="btn sc-option-btn text-start p-3" data-mode="villa" style="background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.3); border-radius: 8px; color: white; transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 700; color: #c084fc; font-size: 0.95rem;">🏡 Villa Only</div>
                                <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 2px;">Applies 12% VAT exemption & 20% discount to Villa accommodation.</div>
                            </div>
                            <span class="badge" style="font-size: 0.7rem; background: #9333ea; color: white;">Villa</span>
                        </div>
                    </button>

                    <button type="button" class="btn sc-option-btn text-start p-3" data-mode="both" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 8px; color: white; transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 700; color: #4ade80; font-size: 0.95rem;">🌟 Both (Airfare & Villa)</div>
                                <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 2px;">Applies Senior/PWD discount to BOTH Airfare and Villa accommodation.</div>
                            </div>
                            <span class="badge bg-success" style="font-size: 0.7rem;">Full SC</span>
                        </div>
                    </button>

                    <button type="button" class="btn sc-option-btn text-start p-3" data-mode="none" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; color: white; transition: all 0.2s;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 700; color: #f87171; font-size: 0.95rem;">❌ Remove Discount (None)</div>
                                <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 2px;">Removes SC/PWD discount. Standard regular rates will apply.</div>
                            </div>
                            <span class="badge bg-danger" style="font-size: 0.7rem;">Regular</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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

        // Dynamic stats update on every DataTables draw (search, filter, page)
        function updateStats() {
            var api = table;
            var totalRes = 0;
            var totalPax = 0;
            var arrivedPax = 0;
            // Iterate all rows matching current search/filter (across all pages)
            api.rows({ search: 'applied' }).nodes().each(function(row) {
                var $row = $(row);
                var pax = parseInt($row.data('pax')) || 0;
                var status = ($row.data('status') || '').toUpperCase().trim();
                totalRes++;
                totalPax += pax;
                if (status === 'ARRIVED') {
                    arrivedPax += pax;
                }
            });
            $('#stat-total-res').text(totalRes);
            $('#stat-total-pax').text(totalPax);
            $('#stat-total-arrived').text(arrivedPax);
        }

        // Hook into DataTables draw event
        table.on('draw', function() {
            updateStats();
        });

        // Initial stats update
        updateStats();

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

        $('#selectAllStatus').on('change', function() {
            $('.status-option-checkbox').prop('checked', this.checked);
            updateStatusLabel();
        });

        $(document).on('change', '.status-option-checkbox', function() {
            const allCount = $('.status-option-checkbox').length;
            const checkedCount = $('.status-option-checkbox:checked').length;
            $('#selectAllStatus').prop('checked', allCount === checkedCount);
            updateStatusLabel();
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
            const tr = $(row);
            const resNo = tr.data('res-no');
            const resBtn = tr.find('.show-breakdown-btn');
            const resIdx = resBtn.length ? parseInt(resBtn.data('res-idx'), 10) : -1;
            const res = (resIdx !== -1 && window.__resData) ? window.__resData[resIdx] : null;

            // 1. Sync target row's edited cell rate into model
            tr.find('.rate-cell').each(function() {
                const cell = $(this);
                const date = cell.data('date');
                const inp  = cell.find('.rate-input');
                if (!inp.length) return;
                const val  = parseFloat(inp.val()) || 0;
                const isFvn = (Math.round(val * 100) / 100 === 0.01 || Math.round(val * 100) / 100 === 0.02 || Math.round(val * 100) / 100 === 0.03 || Math.round(val * 100) / 100 === 0.5);

                if (res && date) {
                    if (!res.rate) res.rate = [];
                    let rObj = res.rate.find(r => r.date === date);
                    if (!rObj) {
                        rObj = { date: date, val: 0, breakdown: {} };
                        res.rate.push(rObj);
                    }
                    rObj.val = val;
                    
                    // Simple live breakdown calculation
                    if (isFvn) {
                        rObj.breakdown = { is_fvn: true, fvn_rate: val, base: val, sc: 0, vat: 0, gross_share: val };
                    } else {
                        const isDisc = rObj.breakdown ? rObj.breakdown.is_discounted : false;
                        let base, sc, vat, gross;
                        gross = val;
                        if (isDisc) {
                            base = gross / 0.9;
                            sc = base * 0.1;
                            vat = 0;
                            rObj.breakdown = { is_discounted: true, base: base, sc: sc, vat: 0, discount: base * 0.2, gross_share: base * 1.12 };
                        } else {
                            base = gross / 1.22;
                            sc = base * 0.1;
                            vat = base * 0.12;
                            rObj.breakdown = { is_discounted: false, base: base, sc: sc, vat: vat, gross_share: gross };
                        }
                    }
                }
            });

            // 2. Perform group-wide calculation
            if (resNo) {
                const groupRows = $('tr[data-res-no="' + resNo + '"]');
                const groupSize = Math.max(1, groupRows.length);
                const firstRow  = groupRows.first();

                // Read group accommodation total from DOM (first row cells — unchanged for grouped view)
                let groupAccTotal = 0;
                firstRow.find('.rate-cell').each(function() {
                    const cell = $(this);
                    const inp  = cell.find('.rate-input');
                    let val = 0;
                    if (inp.length) {
                        val = parseFloat(inp.val()) || 0;
                    } else {
                        const txt = cell.find('.rate-display').text().replace(/,/g, '').trim();
                        val = parseFloat(txt) || 0;
                    }
                    const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                    const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5 || isNaN(val));
                    if (!isFvn) {
                        groupAccTotal += val;
                    }
                });

                // Update the total rate for each row using per-occupant model data when available
                groupRows.each(function() {
                    const currentRow = $(this);
                    const totalSpan  = currentRow.find('.total-val');
                    const firstCell  = currentRow.find('.rate-cell').first();
                    const spanAttr   = firstCell.length ? parseInt(firstCell.attr('rowspan') || '1', 10) : 1;

                    const rowResBtn = currentRow.find('.show-breakdown-btn');
                    const rowResIdx = rowResBtn.length ? parseInt(rowResBtn.data('res-idx'), 10) : -1;
                    const rowRes = (rowResIdx !== -1 && window.__resData) ? window.__resData[rowResIdx] : null;
                    const rowSpanInfo = (window.__accSpans && window.__accSpans[rowResIdx]) ? window.__accSpans[rowResIdx] : {};
                    const rowIsExtra = rowSpanInfo.is_extra_person || currentRow.data('is-extra') === true;

                    let rowAcc;

                    // ── Model-first: if this occupant has computed rate data in window.__resData, use it ──
                    // This ensures per-occupant SC discounts are isolated (e.g., occupant 2 SC ≠ occupant 1)
                    if (rowRes && rowRes.rate && rowRes.rate.length > 0 && rowRes.rate[0].breakdown) {
                        let modelSum = 0;
                        rowRes.rate.forEach(function(r) {
                            const val = parseFloat(r.val || 0);
                            const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                            const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5 || isNaN(val));
                            if (!isFvn) modelSum += val;
                        });
                        rowAcc = rowIsExtra ? modelSum : (modelSum / groupSize);
                    } else if (firstCell.length && spanAttr === 1) {
                        // Unspanned DOM cells: read this row's own cells
                        let sum = 0;
                        currentRow.find('.rate-cell').each(function() {
                            const cell = $(this);
                            const inp  = cell.find('.rate-input');
                            let val = 0;
                            if (inp.length) {
                                val = parseFloat(inp.val()) || 0;
                            } else {
                                const txt = cell.find('.rate-display').text().replace(/,/g, '').trim();
                                val = parseFloat(txt) || 0;
                            }
                            const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                            const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5 || isNaN(val));
                            if (!isFvn) sum += val;
                        });
                        rowAcc = sum > 0 ? sum : (groupAccTotal / groupSize);
                    } else {
                        // Grouped DOM cells: share first-row total divided by group size
                        rowAcc = groupAccTotal / groupSize;
                    }

                    let currentFees = 0;
                    if (rowRes && rowRes.calculated_rates) {
                        const cr = rowRes.calculated_rates;
                        currentFees = (parseFloat(cr.air) || 0) + (parseFloat(cr.han) || 0) + (parseFloat(cr.avi) || 0) + (parseFloat(cr.env) || 0);
                    } else {
                        currentFees = parseFloat(totalSpan.data('base-fees')) || 0;
                    }

                    const passengerTotal = rowAcc + currentFees;
                    totalSpan.text(passengerTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    if (rowRes) {
                        if (!rowRes.calculated_rates) rowRes.calculated_rates = {};
                        rowRes.calculated_rates.acc = rowAcc;
                    }
                });
            } else {
                // Fallback for single row
                let accTotal = 0;
                tr.find('.rate-cell').each(function() {
                    const cell = $(this);
                    const inp  = cell.find('.rate-input');
                    if (!inp.length) return;
                    const val  = parseFloat(inp.val()) || 0;
                    const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                    const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5);
                    if (!isFvn) {
                        accTotal += val;
                    }
                });
                const totalSpan = tr.find('.total-val');
                const baseFees = parseFloat(totalSpan.data('base-fees')) || 0;
                const grandTotal = accTotal + baseFees;
                
                totalSpan.text(grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                
                if (res) {
                    if (!res.calculated_rates) res.calculated_rates = {};
                    res.calculated_rates.acc = accTotal;
                }
            }

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

        // ── Drag & Drop ───────────────────────────────────────────────
        let dragSourceCell = null;
        let dragHTML       = null;

        $(document).on('dragstart', '.stay-block', function(e) {
            dragSourceCell = $(this).closest('td.rate-cell');
            dragHTML       = $(this).closest('td.rate-cell').html();
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', 'rate-block');
        });

        $(document).on('dragend', '.stay-block', function() {
            $(this).removeClass('dragging');
            $('.rate-cell').removeClass('drag-over');
        });

        $(document).on('dragover', 'td.rate-cell', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', 'td.rate-cell', function() {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', 'td.rate-cell', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            const targetCell = $(this);
            if (!dragSourceCell || dragSourceCell.is(targetCell)) return;

            // Only allow drop within the same row
            if (!dragSourceCell.closest('tr').is(targetCell.closest('tr'))) return;

            // Swap HTML
            const targetHTML = targetCell.html();
            targetCell.html(dragHTML);
            dragSourceCell.html(targetHTML || '<button type="button" class="add-cell-btn" onclick="addRate(this)">+</button>');

            // Synchronize the underlying window.__resData model so the Breakdown Modal recalculates correctly
            const row = targetCell.closest('tr');
            const resBtn = row.find('.show-breakdown-btn');
            if (resBtn.length) {
                const resIdx = parseInt(resBtn.data('res-idx'), 10);
                if (!isNaN(resIdx) && window.__resData && window.__resData[resIdx]) {
                    const sourceDate = dragSourceCell.data('date');
                    const targetDate = targetCell.data('date');
                    const rates = window.__resData[resIdx].rate || [];
                    
                    const sourceRateObj = rates.find(r => r.date === sourceDate);
                    const targetRateObj = rates.find(r => r.date === targetDate);
                    
                    if (sourceRateObj && targetRateObj) {
                        // Swap ONLY the mathematical values, not the dates
                        const tempVal = sourceRateObj.val;
                        const tempBreakdown = sourceRateObj.breakdown;
                        
                        sourceRateObj.val = targetRateObj.val;
                        sourceRateObj.breakdown = targetRateObj.breakdown;
                        
                        targetRateObj.val = tempVal;
                        targetRateObj.breakdown = tempBreakdown;
                    }
                }
            }

            recalculateRow(row);
            dragSourceCell = null;
            dragHTML = null;
        });
        // ─────────────────────────────────────────────────────────────

        window.addRate = function(btn) {
            const cell = $(btn).closest('td');
            const date = cell.data('date');
            const row  = cell.closest('tr');

            // Check if this row/group has a .01 or .02 FVN ratecode on the first day
            const isFirstDate = date === '{{ $dateCols[0] ?? '' }}';
            const rateMetadata = row.find('[data-res]').length
                ? (row.find('[data-res]').data('res') || {}).rate_metadata || ''
                : '';
            const isFvn01 = isFirstDate && (rateMetadata.includes('.01') || rateMetadata.includes('KEY-VILLA'));
            const isFvn02 = isFirstDate && (rateMetadata.includes('.02') || rateMetadata.includes('KEY-SUITE'));
            const isFvn05 = isFirstDate && rateMetadata.includes('.5FVN');

            let defaultSelectVal = 'custom';
            let defaultInputVal  = 5000;
            let defaultDisplay   = '';
            let isFvn = false;

            if (isFvn01)      { defaultSelectVal = '0.01'; defaultInputVal = 0.01; defaultDisplay = '1 FVN'; isFvn = true; }
            else if (isFvn02) { defaultSelectVal = '0.02'; defaultInputVal = 0.02; defaultDisplay = '1.5 FVN'; isFvn = true; }
            else if (isFvn05) { defaultSelectVal = '0.5';  defaultInputVal = 0.5;  defaultDisplay = '.5 FVN';  isFvn = true; }
            else {
                // Not automatically FVN, but check if it's currently a magic number
                if (defaultInputVal == 0.01) { defaultSelectVal = '0.01'; defaultDisplay = '1 FVN'; isFvn = true; }
                else if (defaultInputVal == 0.02) { defaultSelectVal = '0.02'; defaultDisplay = '1.5 FVN'; isFvn = true; }
                else if (defaultInputVal == 0.5) { defaultSelectVal = '0.5';  defaultDisplay = '.5 FVN';  isFvn = true; }
                else { defaultDisplay = defaultInputVal.toLocaleString(undefined, {minimumFractionDigits: 2}); }
            }

            cell.html(`
                <div class="stay-block">
                    <span class="rate-display ${isFvn ? 'fvn-display' : ''}">${defaultDisplay}</span>
                </div>
            `);
            
            recalculateRow(cell.closest('tr'));
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
            const property = $('#propertySelect').val() || 'island';

            const params = new URLSearchParams();
            params.append('property', property);
            params.append('fromdate', fromDate);
            params.append('todate', toDate);
            params.append('resnolist', resNoList);
            params.append('search', search);
            params.append('per_page', perPage);

            // Carry the SC/PWD override, remove, and target lists into export/print
            const scOverride = $('#scOverrideInput').val() || '';
            if (scOverride) params.append('sc_override', scOverride);
            const scRemove = $('#scRemoveInput').val() || '';
            if (scRemove) params.append('sc_remove', scRemove);
            const scTarget = $('#scTargetInput').val() || '';
            if (scTarget) params.append('sc_target', scTarget);
            
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

        // ── SC / PWD Option Modal Logic ──────────────────────────────────
        let currentScTargetBtn = null;

        $(document).on('click', '.open-sc-modal-btn', function() {
            currentScTargetBtn = $(this);
            const guestName   = currentScTargetBtn.data('guest-name') || 'Occupant';
            const currentMode = currentScTargetBtn.data('mode') || 'none';

            $('#scModalOccupantName').text(guestName);

            const scModalEl = document.getElementById('scDiscountModal');
            if (scModalEl) {
                const scModal = bootstrap.Modal.getOrCreateInstance(scModalEl);
                scModal.show();
            }
        });

        $(document).on('click', '.sc-option-btn', function() {
            if (!currentScTargetBtn) return;
            const selectedMode = $(this).data('mode'); // "air", "villa", "both", "none"
            const key = currentScTargetBtn.data('override-key');
            const tr  = currentScTargetBtn.closest('tr');

            // Hide modal
            const scModalEl = document.getElementById('scDiscountModal');
            if (scModalEl) {
                const modalInstance = bootstrap.Modal.getInstance(scModalEl);
                if (modalInstance) modalInstance.hide();
            }

            // 1. Sync hidden input field sc_target
            const scTargetInput = $('#scTargetInput');
            let list = scTargetInput.val() ? scTargetInput.val().split(',').map(s => s.trim()).filter(Boolean) : [];
            list = list.filter(item => !item.startsWith(key + ':'));
            list.push(key + ':' + selectedMode);
            scTargetInput.val(list.join(','));

            // Clean legacy override/remove for this key
            const removeInput = $('#scRemoveInput');
            let remList = removeInput.val() ? removeInput.val().split(',').map(s => s.trim()).filter(Boolean) : [];
            remList = remList.filter(k => k !== key);
            if (selectedMode === 'none') {
                remList.push(key);
            }
            removeInput.val(remList.join(','));

            const overrideInput = $('#scOverrideInput');
            let ovList = overrideInput.val() ? overrideInput.val().split(',').map(s => s.trim()).filter(Boolean) : [];
            ovList = ovList.filter(k => k !== key);
            overrideInput.val(ovList.join(','));

            // Sync Export links
            updateLinks();

            // 2. Update UI Badge on button
            currentScTargetBtn.data('mode', selectedMode).attr('data-mode', selectedMode);
            let badgeHtml = '';
            if (selectedMode === 'both') {
                badgeHtml = '<span class="sc-badge-label" style="color: #4ade80; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.4); padding: 2px 6px; border-radius: 4px;">🌟 Both SC</span>';
            } else if (selectedMode === 'villa') {
                badgeHtml = '<span class="sc-badge-label" style="color: #c084fc; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.4); padding: 2px 6px; border-radius: 4px;">🏡 Villa Only</span>';
            } else if (selectedMode === 'air') {
                badgeHtml = '<span class="sc-badge-label" style="color: #60a5fa; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.4); padding: 2px 6px; border-radius: 4px;">✈️ Airfare Only</span>';
            } else {
                badgeHtml = '<span class="sc-badge-label" style="color: var(--text-muted, #94a3b8); background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 2px 6px; border-radius: 4px;">Regular</span>';
            }
            currentScTargetBtn.html(badgeHtml);

            // 3. Update Model in window.__resData
            const resBtn = tr.find('.show-breakdown-btn');
            const resIdx = resBtn.length ? parseInt(resBtn.data('res-idx'), 10) : -1;
            const res    = (resIdx !== -1 && window.__resData) ? window.__resData[resIdx] : null;

            if (res) {
                const hasVillaSc = (selectedMode === 'villa' || selectedMode === 'both');
                const hasAirSc   = (selectedMode === 'air'   || selectedMode === 'both');

                res.sc_mode = selectedMode;
                res.privCard = hasVillaSc ? 'Senior Citizen' : 'N';

                // Recalculate Airfare rate in res.calculated_rates
                let currentAir = parseFloat(res.calculated_rates.air || 0);
                const isEmployee = !!res.is_employee;
                const isMember   = ['member', 'spouse', 'dependent', 'authorized'].some(t => (res.gstType || '').toLowerCase().includes(t));
                const defaultRegular = isEmployee ? 0 : (isMember ? 4200 : 8400);

                let baseAir = 0;
                if (res.calculated_rates.air_base !== undefined) {
                    baseAir = res.calculated_rates.air_base;
                } else {
                    if (currentAir > 0 && Math.abs(currentAir - Math.round(defaultRegular / 1.12)) < 50) {
                        baseAir = defaultRegular;
                    } else if (currentAir > 0) {
                        baseAir = currentAir;
                    } else {
                        baseAir = defaultRegular;
                    }
                    res.calculated_rates.air_base = baseAir;
                }

                if (hasAirSc && !isEmployee) {
                    res.calculated_rates.air = Math.round((baseAir / 1.12) * 100) / 100;
                } else {
                    res.calculated_rates.air = baseAir;
                }

                // Update Villa daily rate calculations for reservation group
                const resNo = tr.data('res-no');
                const groupRows = window.__resData.filter(item => (item.resNo || item.conf) === (res.resNo || res.conf));
                const firstRes  = groupRows[0] || res;

                (res.rate || []).forEach(r => {
                    const val  = parseFloat(r.val || 0);
                    const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                    const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5);

                    if (!isFvn && r.breakdown) {
                        let gross = r.breakdown.gross_share || 0;
                        if (!gross || gross === val) {
                            gross = r.breakdown.is_discounted ? Math.round((val * 14 / 11) * 100) / 100 : val;
                            r.breakdown.gross_share = gross;
                        }
                        if (hasVillaSc && !r.breakdown.is_discounted) {
                            const base = gross / 1.12;
                            const discount = base * 0.20;
                            const netBase = base - discount;
                            const sc = netBase * 0.10;
                            const newDailyVal = Math.round((netBase + sc) * 100) / 100;

                            r.val = newDailyVal;
                            r.breakdown.is_discounted = true;
                            r.breakdown.base = base;
                            r.breakdown.discount = discount;
                            r.breakdown.sc = sc;
                            r.breakdown.vat = 0;
                        } else if (!hasVillaSc && r.breakdown.is_discounted) {
                            const base = gross / 1.12;
                            const sc = base * 0.10;
                            const vat = base * 0.12;

                            r.val = Math.round(gross * 100) / 100;
                            r.breakdown.is_discounted = false;
                            r.breakdown.base = base;
                            r.breakdown.discount = 0;
                            r.breakdown.sc = sc;
                            r.breakdown.vat = vat;
                        }
                    }
                });

            }

            // 4. Recalculate this occupant's row first
            recalculateRow(tr);

            // 5. Unspan / Respan accommodation cells so each occupant sees their own per-person rate
            const resNo     = tr.data('res-no');
            const groupTrs  = $('tr[data-res-no="' + resNo + '"]');
            const groupSize = groupTrs.length;

            // Only manage span for groups of 2+ rows
            if (groupSize > 1) {
                // Check if ANY base occupant (not extra) currently has villa/both SC mode
                let anyVillaDiscount = false;
                groupTrs.each(function() {
                    const row     = $(this);
                    const rowBtn  = row.find('.show-breakdown-btn');
                    const rowIdx  = rowBtn.length ? parseInt(rowBtn.data('res-idx'), 10) : -1;
                    const si      = (window.__accSpans && window.__accSpans[rowIdx]) ? window.__accSpans[rowIdx] : {};
                    const isExtra = si.is_extra_person || row.data('is-extra') === true;
                    if (!isExtra) {
                        const scBtn = row.find('.open-sc-modal-btn');
                        const mode = scBtn.attr('data-mode') || scBtn.data('mode') || 'none';
                        if (mode === 'villa' || mode === 'both') anyVillaDiscount = true;
                    }
                });

                const firstTr   = groupTrs.first();
                const dateCells = firstTr.find('.rate-cell');

                // ── Helper: per-occupant nightly value from model ──
                // Reads res.rate from window.__resData so SC vs regular is already baked in.
                function perOccFromModel(rowTr) {
                    const rowBtn = rowTr.find('.show-breakdown-btn');
                    const rowIdx = rowBtn.length ? parseInt(rowBtn.data('res-idx'), 10) : -1;
                    const rowRes = (rowIdx !== -1 && window.__resData) ? window.__resData[rowIdx] : null;
                    const si     = (window.__accSpans && window.__accSpans[rowIdx]) ? window.__accSpans[rowIdx] : {};
                    const isExtra = si.is_extra_person || rowTr.data('is-extra') === true;
                    if (!rowRes || !rowRes.rate) return null;
                    let modelSum = 0;
                    rowRes.rate.forEach(function(r) {
                        const val  = parseFloat(r.val || 0);
                        const frac = Math.round((val - Math.floor(val)) * 100) / 100;
                        // 0.01 = 1 FVN, 0.02 = 1.5 FVN, 0.03 = 0.5 FVN (split marker), 0.5 = 0.5 FVN — never add these to the sum
                        const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5 || isNaN(val));
                        if (!isFvn) modelSum += val;
                    });
                    const nights = rowRes.rate.filter(r => {
                        const v = parseFloat(r.val || 0);
                        const f = Math.round((v - Math.floor(v)) * 100) / 100;
                        return !(f === 0.01 || f === 0.02 || f === 0.03 || f === 0.5 || isNaN(v));
                    }).length || 1;
                    const perNight = modelSum / nights;
                    let perOccShare = perNight;
                    if (perNight > 4000 && groupSize > 1 && !isExtra) {
                        perOccShare = perNight / groupSize;
                    }
                    return isExtra ? perNight : perOccShare;
                }

                if (anyVillaDiscount) {
                    // ── UNSPAN: give each row its own accommodation cell showing per-occupant amount ──

                    // Step A: shrink first row cells to rowspan=1 and update display
                    dateCells.each(function() {
                        const cell = $(this);
                        const date = cell.data('date');
                        const baseRate = parseFloat(cell.data('base-rate') || cell.attr('data-base-rate') || 0);
                        const frac = Math.round((baseRate - Math.floor(baseRate)) * 100) / 100;
                        const isFvn = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5);

                        cell.attr('rowspan', 1);
                        if (!isFvn) {
                            // Read per-occupant value from first row's model
                            const perOcc = perOccFromModel(firstTr);
                            if (perOcc !== null) {
                                cell.find('.rate-display').text(
                                    perOcc.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                );
                            }
                        }
                    });

                    // Step B: insert/update cells for all non-first rows
                    groupTrs.each(function(i) {
                        if (i === 0) return;
                        const rowTr  = $(this);
                        const perOcc = perOccFromModel(rowTr);

                        dateCells.each(function() {
                            const refCell  = $(this);
                            const date     = refCell.data('date');
                            const baseRate = parseFloat(refCell.data('base-rate') || refCell.attr('data-base-rate') || 0);
                            const frac     = Math.round((baseRate - Math.floor(baseRate)) * 100) / 100;
                            const isFvn    = (frac === 0.01 || frac === 0.02 || frac === 0.03 || frac === 0.5);
                            const dispVal  = (perOcc !== null && !isFvn)
                                ? perOcc.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                : baseRate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                            const existing = rowTr.find('.rate-cell[data-date="' + date + '"]');
                            if (existing.length === 0) {
                                const newCell = $('<td class="rate-cell" style="vertical-align: middle; text-align: center; cursor: pointer;"></td>');
                                newCell.attr('data-date', date);
                                newCell.attr('data-base-rate', baseRate);
                                newCell.attr('data-orig-rowspan', 0);
                                newCell.attr('rowspan', 1);
                                newCell.attr('data-inserted', '1');
                                newCell.html('<div class="stay-block"><span class="rate-display">' + dispVal + '</span></div>');
                                const totalCell = rowTr.find('.row-total');
                                if (totalCell.length) newCell.insertBefore(totalCell);
                                else rowTr.append(newCell);
                            } else if (!isFvn) {
                                existing.find('.rate-display').text(dispVal);
                            }
                        });
                    });

                } else {
                    // ── RESPAN: remove non-first row rate cells (Blade-rendered or JS-inserted), restore first row cell ──
                    groupTrs.each(function(i) {
                        if (i === 0) return;
                        const rowTr = $(this);
                        dateCells.each(function() {
                            const date = $(this).data('date');
                            rowTr.find('.rate-cell[data-date="' + date + '"]').remove();
                        });
                    });

                    dateCells.each(function() {
                        const cell = $(this);
                        const rawBase = parseFloat(cell.attr('data-base-rate') || cell.data('base-rate') || 0);

                        // ── FVN guard: never touch FVN marker cells (0.01 = 1 FVN, 0.02 = 1.5 FVN, 0.03 split, 0.5 = 0.5 FVN) ──
                        const rawFrac = Math.round((rawBase - Math.floor(rawBase)) * 100) / 100;
                        const isFvn  = (rawFrac === 0.01 || rawFrac === 0.02 || rawFrac === 0.03 || rawFrac === 0.5);

                        cell.attr('rowspan', groupSize);

                        if (!isFvn) {
                            // Use cached immutable orig base if available; compute and cache it once
                            let origBase = parseFloat(cell.attr('data-orig-base-rate') || cell.data('orig-base-rate') || 0);
                            if (!origBase || isNaN(origBase)) {
                                origBase = rawBase;
                                // Scale per-occupant share up to full room rate if needed
                                if (origBase > 100 && origBase < 3500 && groupSize > 1) {
                                    origBase = Math.round(origBase * groupSize * 100) / 100;
                                }
                                // Convert SC-discounted gross back to regular gross (e.g. 11,785.68 → 15,000)
                                const calcGross = Math.round((origBase * 14 / 11) * 100) / 100;
                                const rounded   = Math.round(calcGross);
                                if (Math.abs(calcGross - rounded) < 0.15 && calcGross > origBase && calcGross <= origBase * 1.3) {
                                    origBase = rounded;
                                } else if (calcGross > origBase && calcGross <= origBase * 1.3) {
                                    origBase = calcGross;
                                }
                                cell.attr('data-orig-base-rate', origBase).data('orig-base-rate', origBase);
                            }
                            cell.find('.rate-display').text(
                                origBase.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            );
                        }
                    });
                }

                // Recalculate all rows — model-first logic gives each occupant their own correct total
                groupTrs.each(function() { recalculateRow(this); });
            }
        });

        // Breakdown Modal Logic
        $(document).on('click', '.show-breakdown-btn', function() {
            const idx = $(this).data('res-idx');
            const res = window.__resData[idx];
            if (!res) return;

            const property = window.__property || 'island';
            const spanInfo = (window.__accSpans && window.__accSpans[idx]) ? window.__accSpans[idx] : { span: 1, group_size: 1, is_extra_person: false };
            const isExtraPerson = spanInfo.is_extra_person || false;

            // Find all occupants of this reservation group to compute distributed share
            const groupRows = window.__resData.filter(item => (item.resNo || item.conf) === (res.resNo || res.conf));
            const firstRes = groupRows[0] || res;
            let groupSize = isExtraPerson ? 1 : (res.group_size || spanInfo.group_size || Math.max(1, groupRows.length));
            if (property === 'pines' && !isExtraPerson && groupSize < 2) {
                groupSize = 2;
            }
            const ratesToUse = (res.rate && res.rate.length > 0) ? res.rate : (firstRes.rate || []);

            const privCard = (res.privCard || res.privcard || '').trim();
            const privCardLower = privCard.toLowerCase();
            const isValidCard = privCard !== '' && !['n', 'no', 'false', '0', 'none', 'null'].includes(privCardLower);

            // For pines: determine unit label
            const unitLabel = res.village_name || 'N/A';
            let extraPersonNote = '';
            if (property === 'pines' && isExtraPerson) {
                extraPersonNote = ' <span style="font-size: 0.7rem; background: rgba(251,191,36,0.2); color: #fbbf24; border-radius: 4px; padding: 2px 6px;">Extra Person</span>';
            }

            let html = `
                <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                    <div style="font-weight: bold; font-size: 1.25rem; color: var(--primary);">${res.gstName || res.guestName || 'Unknown'}${extraPersonNote}</div>
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                        Reservation <span style="color: white; font-weight: 600;">#${res.resNo || res.conf}</span> | Unit: <span style="color: white; font-weight: 600;">${unitLabel}</span>
                        ${res.is_employee ? ' | <span class="badge badge-member" style="font-size: 0.65rem;">Employee</span>' : ''}
                        ${isValidCard ? ` | <span class="badge badge-member" style="font-size: 0.65rem; background: var(--primary); color: black;">${privCard}</span>` : ''}
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

            (ratesToUse || []).forEach(r => {
                const b = r.breakdown;
                if (!b) return;

                const isFvn = (Math.round(r.val * 100) / 100 === 0.01 || Math.round(r.val * 100) / 100 === 0.02 || Math.round(r.val * 100) / 100 === 0.03 || Math.round(r.val * 100) / 100 === 0.5);

                const rawVal = parseFloat(r.val || 0);
                const rawBase = parseFloat(b.base || 0);
                const isRoomFullRate = (rawVal > 3000 || rawBase > 3000);
                const divisor = (isRoomFullRate && groupSize > 1 && !isExtraPerson) ? groupSize : 1;

                const dailyVal      = isFvn ? r.val : (rawVal / divisor);
                const baseVal       = rawBase / divisor;
                const scVal         = parseFloat(b.sc || 0) / divisor;
                const vatVal        = parseFloat(b.vat || 0) / divisor;
                const discVal       = parseFloat(b.discount || 0) / divisor;
                const grossShareVal = parseFloat(b.gross_share || 0) / divisor;

                if (!isFvn) {
                    overallTotal += dailyVal;
                }
                if (b.is_discounted) hasDiscount = true;

                const dateStr = r.date;
                let detailHtml = '';

                // Pines: show SC/VAT/Discount breakdown for base occupants and extra person
                // Island: full breakdown as before
                const showBreakdown = (property === 'island' || property === 'pines');

                if (showBreakdown) {
                    if (b.is_discounted) {
                        detailHtml = `
                            <div style="font-size: 0.7rem; color: #4ade80;">- 20% Disc: ₱${discVal.toFixed(2)}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">+ 10% SC: ₱${scVal.toFixed(2)}</div>
                            <div style="font-size: 0.7rem; color: #f87171;">VAT EXEMPT</div>
                        `;
                    } else {
                        detailHtml = `
                            <div style="font-size: 0.7rem; color: var(--text-muted);">+ 10% SC: ₱${scVal.toFixed(2)}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">+ 12% VAT: ₱${vatVal.toFixed(2)}</div>
                        `;
                    }
                }

                if (isFvn) {
                    const fvnLabelMap = { 0.01: '1', 0.02: '1.5', 0.5: '.5' };
                    const numericVal = Math.round(r.val * 100) / 100; // avoid floating‑point quirks
                    const fvnLabel = fvnLabelMap[numericVal] || numericVal;
                    html += `
                        <tr style="background: rgba(239, 68, 68, 0.1);">
                            <td style="white-space: nowrap; font-weight: 600;">${dateStr}</td>
                            <td>
                                <div style="font-weight: 500; color: #ef4444;">${fvnLabel} Free Villa Night</div>
                            </td>
                            <td style="color: rgba(255,255,255,0.2);">-</td>
                            <td style="color: rgba(255,255,255,0.2);">-</td>
                            <td style="font-weight: bold; text-align: right; color: #ef4444;">
                                ₱0.00
                            </td>
                        </tr>
                    `;
                } else if (property === 'pines' && isExtraPerson) {
                    // Extra person row: show flat 3,700/night rate
                    html += `
                        <tr style="background: rgba(251,191,36,0.05);">
                            <td style="white-space: nowrap; font-weight: 600;">${dateStr}</td>
                            <td>
                                <div style="font-weight: 500; color: #fbbf24;">Extra Person Charge${b.is_discounted ? ' <span style="font-size: 0.65rem; color: #4ade80;">(Senior/PWD)</span>' : ''}</div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Base Rate: ₱3,700.00/night</div>
                            </td>
                            <td>₱${dailyVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>${showBreakdown ? detailHtml : '-'}</td>
                            <td style="font-weight: bold; text-align: right; color: #fbbf24;">
                                ₱${dailyVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        </tr>
                    `;
                } else {
                    html += `
                        <tr>
                            <td style="white-space: nowrap; font-weight: 600;">${dateStr}</td>
                            <td>
                                <div style="font-weight: 500;">${b.is_discounted ? (isValidCard ? privCard : 'Senior/PWD') : 'Regular'} ${groupSize > 1 ? '<span style="font-size: 0.7rem; color: var(--primary);">(' + groupSize + '-Pax Share)</span>' : ''}</div>
                                <div style="font-size: 0.65rem; color: var(--text-muted);">Gross Share: ₱${grossShareVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            </td>
                            <td>₱${baseVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>${showBreakdown ? detailHtml : '-'}</td>
                            <td style="font-weight: bold; text-align: right; color: white;">
                                ₱${dailyVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        </tr>
                    `;
                }

            });

            // Add other fees — only for Island (Pines has no additional airfare/hangar/aviation/env fees)
            if (property !== 'pines') {
                const fees = res.calculated_rates || {};
                const feeItems = [
                    { label: 'Airfare', val: fees.air },
                    { label: 'Hangar Fee', val: fees.han },
                    { label: 'Aviation Operational Fee', val: fees.avi },
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
            }

            html += `
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid var(--primary);">
                            <td colspan="4" style="text-align: right; padding-top: 1.5rem; font-weight: bold; color: var(--text-muted);">TOTAL BILLABLE:</td>
                            <td style="text-align: right; padding-top: 1.5rem; font-weight: 900; color: var(--primary); font-size: 1.4rem;">
                                ₱${overallTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
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



    window.openRateEditor = function(block) {
        // Disabled for now
        return;
    }

    window.closeRateEditor = function(inp) {
        const editor  = inp.closest('.rate-editor');
        const block   = inp.closest('.stay-block');
        const display = block.querySelector('.rate-display');
        const val     = parseFloat(inp.value) || 0;
        const fvnMap  = { 0.01: '1 FVN', 0.02: '1.5 FVN', 0.5: '.5 FVN' };
        if (fvnMap[val]) {
            display.textContent = fvnMap[val];
            display.className = 'rate-display fvn-display';
        } else if (Math.round(val * 100) / 100 === 3700.01) {
            display.textContent = '3700.01';
            display.className = 'rate-display';
        } else {
            display.textContent = val > 0 ? val.toLocaleString(undefined,{minimumFractionDigits:2}) : '';
            display.className = 'rate-display';
        }
        editor.style.display = 'none';
        display.style.display = '';
    }

    window.onRateSelectChange = function(sel) {
        // Obsolete with quick buttons, but keeping for compatibility
    }

    window.applyQuickRate = function(btn, val) {
        const editor  = btn.closest('.rate-editor');
        const block   = btn.closest('.stay-block');
        const display = block.querySelector('.rate-display');
        const inp     = editor.querySelector('.rate-input');
        const fvnMap  = { '0.01': '1 FVN', '0.02': '1.5 FVN', '0.5': '.5 FVN' };

        inp.value = val;
        display.textContent = fvnMap[val] || val;
        display.className = 'rate-display fvn-display';
        editor.style.display = 'none';
        display.style.display = '';
        
        recalculateRow(block.closest('tr'));
    }

    window.toggleCustomInput = function(btn) {
        const editor = btn.closest('.rate-editor');
        const inp    = editor.querySelector('.rate-input');
        inp.style.display = '';
        inp.focus();
        inp.select();
    }
</script>
@endsection
