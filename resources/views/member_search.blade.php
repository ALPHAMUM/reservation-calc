@extends('layouts.app')

@section('styles')
<style>
    .search-container {
        margin-bottom: 2rem;
    }

    .search-box {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .input-group {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .input-group label {
        font-size: 0.875rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .input-group input {
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

    .input-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .table-container {
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border);
        padding: 1.5rem;
        margin-top: 1rem;
    }

    #resTable {
        width: 100%;
        border-collapse: collapse;
        color: var(--text);
    }

    #resTable th {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid var(--border);
    }

    #resTable td {
        padding: 1rem;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }

    #resTable tbody tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .res-no {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }

    .res-no:hover {
        color: var(--primary-hover);
        text-decoration: underline;
    }

    /* Property badges */
    .prop-badge {
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        white-space: nowrap;
    }
    .prop-island { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); }
    .prop-city { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
    .prop-pines { background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.3); }

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
    .status-confirmed, .status-active { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .status-not-confirmed, .status-pending, .status-tentative { background: rgba(234, 179, 8, 0.2); color: #facc15; }
    .status-arrived, .status-in-house, .status-checkedin, .status-partly-arrived { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    .status-cancelled, .status-system-cancelled, .status-partial-sys-cancelled, .status-partly-cancelled, .status-nc-sys-cancelled, .status-deleted { 
        background: rgba(239, 68, 68, 0.2); color: #f87171; 
    }
    .status-checkout, .status-completed { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }

    .btn-submit {
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        background-color: var(--primary);
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-submit:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .alert-warning {
        background: rgba(234, 179, 8, 0.1);
        color: #facc15;
        border-color: rgba(234, 179, 8, 0.2);
    }
    
    .loading-spinner {
        display: none;
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spin 0.75s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<div class="card animate-in">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: inline-block;">Member Reservation Filter</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Search all reservations matching a Member Number across Balesin Island, Balesin City, and Balesin Pines.</p>
    </div>

    <!-- Warnings / Errors -->
    @if(!empty($errors))
        @foreach($errors as $err)
            <div class="alert alert-warning animate-in" style="padding: 1rem 1.5rem; border-radius: 0.75rem; margin-bottom: 1rem; border: 1px solid rgba(234, 179, 8, 0.2);">
                ⚠️ {{ $err }}
            </div>
        @endforeach
    @endif

    <!-- Search Form -->
    <div class="search-container">
        <form method="GET" action="{{ route('member.search') }}" id="searchForm">
            <div class="search-box">
                <div class="input-group" style="flex: 2;">
                    <label for="member_no">Member Number</label>
                    <input type="text" id="member_no" name="member_no" placeholder="e.g. 01917-BIC-100-00-C4" value="{{ $memberNo }}" required autofocus>
                </div>
                <div class="input-group">
                    <label for="fromdate">From Date</label>
                    <input type="date" id="fromdate" name="fromdate" value="{{ $fromDate }}">
                </div>
                <div class="input-group">
                    <label for="todate">To Date</label>
                    <input type="date" id="todate" name="todate" value="{{ $toDate }}">
                </div>
                <div>
                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <span class="loading-spinner" id="spinner"></span>
                        <span id="btnText">Search</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    @if($memberNo)
        <div class="table-container animate-in">
            <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h4 style="font-weight: 600; margin: 0;">
                    Search Results for Member: <span style="color: var(--primary)">{{ $memberNo }}</span>
                </h4>
                <div style="font-size: 0.875rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border)">
                    Found <strong>{{ count($reservations) }}</strong> reservation(s)
                </div>
            </div>

            @if(count($reservations) > 0)
                <div style="overflow-x: auto;">
                    <table id="resTable">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Res No</th>
                                <th>Member No</th>
                                <th>Book Date</th>
                                <th>Guest Name</th>
                                <th>Contact No</th>
                                <th>Rooms</th>
                                <th>Pax</th>
                                <th>Arrival</th>
                                <th>Departure</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $res)
                                @php
                                    $resNo = $res['resNo'] ?? $res['conf'] ?? $res['resno'] ?? null;
                                    $prop = $res['property'] ?? 'island';
                                    $propLabel = $res['property_label'] ?? 'Island';
                                    $status = strtoupper(trim($res['status'] ?? ''));
                                @endphp
                                <tr>
                                    <td>
                                        <span class="prop-badge prop-{{ $prop }}">
                                            @if($prop === 'island') 🏝️ @elseif($prop === 'city') 🏙️ @else 🌲 @endif
                                            {{ $propLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($resNo)
                                            <a href="{{ route('dashboard', ['resnolist' => $resNo, 'property' => $prop]) }}" target="_blank" class="res-no" title="View details in Dashboard">
                                                #{{ $resNo }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="font-weight: 600; font-family: monospace;">{{ $res['memberNo'] ?? 'N/A' }}</td>
                                    <td>{{ $res['bookDate'] ?? 'N/A' }}</td>
                                    <td>
                                        <div style="font-weight: 500;">{{ $res['guestName'] ?? $res['gstName'] ?? 'Unknown' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted)">{{ $res['customer'] ?? '' }}</div>
                                    </td>
                                    <td style="font-family: monospace;">{{ $res['conactNo'] ?? $res['contactNo'] ?? 'N/A' }}</td>
                                    <td style="text-align: center;">{{ $res['noRooms'] ?? $res['noOfRooms'] ?? '0' }}</td>
                                    <td style="text-align: center;">{{ $res['noPax'] ?? $res['noOfPax'] ?? '0' }}</td>
                                    <td style="white-space: nowrap;">{{ $res['arrDt'] ?? $res['arrdt'] ?? '' }}</td>
                                    <td style="white-space: nowrap;">{{ $res['depDt'] ?? $res['depdt'] ?? '' }}</td>
                                    <td>
                                        @php
                                            $statusClass = 'status-confirmed';
                                            if (in_array($status, ['CONFIRMED', 'ACTIVE'])) {
                                                $statusClass = 'status-confirmed';
                                            } elseif (in_array($status, ['ARRIVED', 'IN HOUSE', 'CHECKEDIN', 'PARTLY ARRIVED'])) {
                                                $statusClass = 'status-arrived';
                                            } elseif (in_array($status, ['PENDING', 'TENTATIVE', 'NOT CONFIRMED', 'NOT-CONFIRMED'])) {
                                                $statusClass = 'status-not-confirmed';
                                            } elseif (in_array($status, ['CANCELLED', 'SYSTEM CANCELLED', 'SYSTEM-CANCELLED', 'PARTIAL SYS CANCELLED', 'PARTLY CANCELLED', 'DELETED'])) {
                                                $statusClass = 'status-cancelled';
                                            } elseif (in_array($status, ['CHECKOUT', 'COMPLETED'])) {
                                                $statusClass = 'status-checkout';
                                            }
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">{{ $status ?: 'UNKNOWN' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <h5 style="font-weight: 600; margin-bottom: 0.25rem;">No Reservations Found</h5>
                    <p style="margin: 0; font-size: 0.875rem;">Double check the Member Number or adjust the search date range.</p>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
    document.getElementById('searchForm').addEventListener('submit', function() {
        var btn = document.getElementById('btnSubmit');
        var spinner = document.getElementById('spinner');
        var btnText = document.getElementById('btnText');
        
        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.textContent = 'Searching...';
    });
</script>
@endsection
