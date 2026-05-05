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
</style>
@endsection

@section('content')
<div class="animate-in" style="animation-delay: 0.1s">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Records Found</div>
            <div class="stat-value">{{ $reservations->total() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Showing Page</div>
            <div class="stat-value">{{ $reservations->currentPage() }} of {{ $reservations->lastPage() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Results on Page</div>
            <div class="stat-value">{{ $reservations->count() }}</div>
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

        <form action="{{ route('dashboard') }}" method="GET" class="search-box" style="flex-direction: column;">
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
                <div class="input-group" style="flex: 2;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">Res No List</label>
                    <input type="text" name="resnolist" value="{{ $resNoList }}" placeholder="114481,114475...">
                </div>
            </div>
        </form>

        <div class="table-container">
            <table id="resTable" class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>Res No</th>
                        <th>Guest Name</th>
                        @if($viewType === 'detail')
                            <th>Type</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>Total Rate</th>
                        @else
                            <th>Arrival</th>
                            <th>Departure</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $res)
                        @php $currentResNo = $res['resNo'] ?? $res['conf'] ?? null; @endphp
                        <tr>
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
                            @if($viewType === 'detail')
                                <td>
                                    <span class="badge {{ ($res['gstType'] ?? '') === 'Member' ? 'badge-member' : 'badge-guest' }}">
                                        {{ $res['gstType'] ?? 'Guest' }}
                                    </span>
                                </td>
                                <td>{{ $res['roomtyp'] ?? $res['roomType'] ?? 'N/A' }}</td>
                                <td>
                                    <div style="font-size: 0.875rem">{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted)">to {{ $res['depdt'] ?? $res['depDt'] ?? '' }}</div>
                                </td>
                                <td>
                                    @php
                                        $total = 0;
                                        if (isset($res['rate']) && is_array($res['rate'])) {
                                            foreach($res['rate'] as $r) $total += (float)($r['val'] ?? 0);
                                        }
                                    @endphp
                                    {{ number_format($total, 2) }}
                                </td>
                            @else
                                <td>{{ $res['arrdt'] ?? $res['arrDt'] ?? '' }}</td>
                                <td>{{ $res['depdt'] ?? $res['depDt'] ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $reservations->appends(request()->query())->links('pagination::bootstrap-4') }}
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
        if (!$.fn.DataTable.isDataTable('#resTable')) {
            $('#resTable').DataTable({
                "paging": false,
                "info": false,
                "searching": false,
                "ordering": true,
                "order": [],
                "columnDefs": [{ "orderable": false, "targets": "_all" }]
            });
        }

        // Function to update export/print links based on current inputs
        function updateLinks() {
            const fromDate = $('input[name="fromdate"]').val() || '';
            const toDate = $('input[name="todate"]').val() || '';
            const resNoList = $('input[name="resnolist"]').val() || '';
            const perPage = $('select[name="per_page"]').val() || '10';

            const params = new URLSearchParams({
                fromdate: fromDate,
                todate: toDate,
                resnolist: resNoList,
                per_page: perPage
            }).toString();

            $('.btn-export-excel').attr('href', "{{ route('export') }}?" + params);
            $('.btn-export-pdf').attr('href', "{{ route('print') }}?" + params);
        }

        $('input, select').on('change input', updateLinks);
        updateLinks(); // Initialize
    });
</script>
@endsection
