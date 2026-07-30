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

    <!-- AI Helper Suggestion Banner -->
    @if(!empty($aiSuggestion))
        <div id="aiHelperBanner" class="ai-helper-banner animate-in" style="background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(192,132,252,0.12)); border: 1px solid rgba(168,85,247,0.3); border-radius: 0.85rem; padding: 1rem 1.3rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="font-size: 1.6rem; background: rgba(168,85,247,0.2); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 0.65rem; shrink: 0;">🤖</div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #c084fc; margin-bottom: 0.25rem;">AI Helper Suggestion</div>
                    <div style="font-size: 0.92rem; color: var(--text); line-height: 1.4;">
                        @if(!empty($aiSuggestion['tag']))
                            Your entered membership number is missing <strong style="color: #f472b6;">"{{ $aiSuggestion['tag'] }}"</strong>. Did you mean <span style="font-family: monospace; font-weight: 700; color: #818cf8; background: rgba(99,102,241,0.15); padding: 0.15rem 0.5rem; border-radius: 0.35rem; border: 1px solid rgba(99,102,241,0.3);">{{ $aiSuggestion['suggested'] }}</span>?
                        @else
                            Did you mean <span style="font-family: monospace; font-weight: 700; color: #818cf8; background: rgba(99,102,241,0.15); padding: 0.15rem 0.5rem; border-radius: 0.35rem; border: 1px solid rgba(99,102,241,0.3);">{{ $aiSuggestion['suggested'] }}</span>?
                        @endif
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <button type="button" onclick="applySuggestion('{{ $aiSuggestion['suggested'] }}')" class="btn-submit" style="background: linear-gradient(135deg, #818cf8, #a855f7); font-size: 0.85rem; padding: 0.55rem 1.1rem; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(168,85,247,0.3);">
                    ⚡ Apply &amp; Search
                </button>
                <button type="button" onclick="document.getElementById('aiHelperBanner').style.display='none'" title="Dismiss" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); border-radius: 0.5rem; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); font-size: 1rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.14)'" onmouseout="this.style.background='rgba(255,255,255,0.07)'">
                    ✕
                </button>
            </div>
        </div>
    @endif

    <!-- Search Form -->
    <div class="search-container">
        <form method="GET" action="{{ route('member.search') }}" id="searchForm">
            <div class="search-box">
                <div class="input-group" style="flex: 2;">
                    <label for="member_no">Member Number</label>
                    <input type="text" id="member_no" name="member_no" list="member_list_options" placeholder="e.g. 01917-BIC-100-00-C4" value="{{ $memberNo }}" required autofocus autocomplete="off">
                    @if(!empty($allMembers))
                        <datalist id="member_list_options">
                            @foreach($allMembers as $mNo)
                                <option value="{{ $mNo }}">
                            @endforeach
                        </datalist>
                    @endif
                </div>
                <div class="input-group">
                    <label for="fromdate">From Date</label>
                    <input type="date" id="fromdate" name="fromdate" value="{{ $fromDate }}" max="{{ $toDate }}">
                </div>
                <div class="input-group">
                    <label for="todate">To Date</label>
                    <input type="date" id="todate" name="todate" value="{{ $toDate }}" min="{{ $fromDate }}">
                </div>
                <div>
                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <span class="loading-spinner" id="spinner"></span>
                        <span id="btnText">Search</span>
                    </button>
                </div>
            </div>
            <div id="dateRangeError" role="alert" aria-live="polite" style="min-height: 1.25rem; margin-top: 0.5rem; color: #f87171; font-size: 0.8rem; line-height: 1.25rem;"></div>
        </form>
    </div>

    <!-- Results Table -->
    @if($memberNo)
        @php
            $upcomingBookings = $upcomingBookings ?? [];
            $upcomingFvn = (float) ($upcomingFvn ?? 0);
            $intimusFvn = (float) ($intimusFvn ?? ($memberDetail['roomBal'] ?? 0));
            $projectedRemaining = $projectedRemaining ?? round($intimusFvn - $upcomingFvn, 2);
            $isOverCommitted = $isOverCommitted ?? ($projectedRemaining < 0);

            $fmtFvn = function ($v) {
                $v = (float) $v;
                if (abs($v - round($v)) < 0.001) return (string) (int) round($v);
                return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
            };

            $totalFvn = 0;
            foreach ($reservations as $r) {
                $totalFvn += (float) ($r['fvn_used'] ?? 0);
            }
        @endphp
        <div class="table-container animate-in">

            {{-- Member Detail Balance Cards --}}
            @if(!empty($memberDetail))

            {{-- Member Identity Header --}}
            <div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
                <div style="font-size: 1.35rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; margin-bottom: 0.3rem;">
                    {{ $memberDetail['memberName'] ?? '—' }}
                </div>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 0.8rem; font-family: monospace; background: rgba(99,102,241,0.12); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); padding: 0.2rem 0.65rem; border-radius: 0.4rem; font-weight: 600; letter-spacing: 0.04em;">
                        {{ $memberDetail['memberNo'] ?? '—' }}
                    </span>
                    @if(!empty($memberDetail['memScheme']))
                    <span style="font-size: 0.78rem; color: var(--text-muted); background: rgba(255,255,255,0.05); border: 1px solid var(--border); padding: 0.2rem 0.65rem; border-radius: 0.4rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $memberDetail['memScheme'] }}
                    </span>
                    @endif
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">

                {{-- Intimus FVN (already deducted for arrived) --}}
                <div style="background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(99,102,241,0.05)); border: 1px solid rgba(99,102,241,0.3); border-radius: 1rem; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #818cf8;">Intimus FVN Balance</div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">
                        {{ $fmtFvn($intimusFvn) }}
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Deducted only when booking has arrived</div>
                </div>

                {{-- Upcoming FVN commitment --}}
                <div style="background: linear-gradient(135deg, rgba(251,191,36,0.15), rgba(251,191,36,0.05)); border: 1px solid rgba(251,191,36,0.35); border-radius: 1rem; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #fbbf24;">Upcoming FVN</div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums;">
                        {{ $fmtFvn($upcomingFvn) }}
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ count($upcomingBookings) }} future booking(s) not yet deducted</div>
                </div>

                {{-- Projected remaining --}}
                <div style="background: linear-gradient(135deg, {{ $isOverCommitted ? 'rgba(248,113,113,0.18), rgba(248,113,113,0.05)' : 'rgba(74,222,128,0.15), rgba(74,222,128,0.05)' }}); border: 1px solid {{ $isOverCommitted ? 'rgba(248,113,113,0.45)' : 'rgba(74,222,128,0.35)' }}; border-radius: 1rem; padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: {{ $isOverCommitted ? '#f87171' : '#4ade80' }};">Projected Remaining</div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: {{ $isOverCommitted ? '#fca5a5' : 'var(--text)' }}; font-variant-numeric: tabular-nums;">
                        {{ $fmtFvn($projectedRemaining) }}
                    </div>
                    <div style="font-size: 0.72rem; color: {{ $isOverCommitted ? '#f87171' : 'var(--text-muted)' }};">
                        @if($isOverCommitted)
                            Over-committed — upcoming FVN exceeds Intimus balance
                        @else
                            Intimus − upcoming commitment
                        @endif
                    </div>
                </div>

            </div>

            @if($isOverCommitted)
                <div style="margin-bottom: 1.5rem; padding: 0.85rem 1.1rem; border-radius: 0.75rem; border: 1px solid rgba(248,113,113,0.35); background: rgba(248,113,113,0.1); color: #fca5a5; font-size: 0.88rem;">
                    Warning: this member is over-committed by <strong>{{ $fmtFvn(abs($projectedRemaining)) }} FVN</strong> after upcoming bookings.
                </div>
            @endif
            @endif

            {{-- Upcoming bookings (all future matched, independent of From/To filter) --}}
            @if(count($upcomingBookings) > 0)
                <div style="margin-bottom: 1.5rem; border: 1px solid rgba(251,191,36,0.25); background: rgba(251,191,36,0.06); border-radius: 1rem; padding: 1rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.85rem;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.05em;">Upcoming Bookings</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Committed FVN: <strong style="color: #fbbf24;">{{ $fmtFvn($upcomingFvn) }}</strong></div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width:100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="color: var(--text-muted); text-align: left;">
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Property</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Res No</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Arrival</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Departure</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border); text-align:center;">Nights</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border); text-align:center;">Rooms</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Rate</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border); text-align:center;">FVN</th>
                                    <th style="padding: 0.45rem 0.5rem; border-bottom: 1px solid var(--border);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingBookings as $ub)
                                    @php
                                        $uArr = $ub['arrDt'] ?? $ub['arrdt'] ?? '';
                                        $uDep = $ub['depDt'] ?? $ub['depdt'] ?? '';
                                        try { $uArrFmt = $uArr ? \Carbon\Carbon::parse($uArr)->format('M d, Y') : ''; } catch (\Exception $e) { $uArrFmt = $uArr; }
                                        try { $uDepFmt = $uDep ? \Carbon\Carbon::parse($uDep)->format('M d, Y') : ''; } catch (\Exception $e) { $uDepFmt = $uDep; }
                                    @endphp
                                    <tr>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04);">{{ $ub['property_label'] ?? $ub['property'] ?? '' }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); color: var(--primary); font-weight: 600;">#{{ $ub['conf'] ?? $ub['resNo'] ?? '—' }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); white-space: nowrap;">{{ $uArrFmt }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); white-space: nowrap;">{{ $uDepFmt }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); text-align:center;">{{ $ub['fvn_nights'] ?? 0 }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); text-align:center;">{{ $ub['fvn_rooms'] ?? ($ub['noRooms'] ?? 0) }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); font-family: monospace; font-size: 0.78rem;">{{ $ub['fvn_raw_rate'] ?: '—' }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); text-align:center; font-weight: 700; color: #fbbf24;">{{ $fmtFvn($ub['fvn_used'] ?? 0) }}</td>
                                        <td style="padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04);">{{ $ub['status'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.875rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 0.5rem; border: 1px solid var(--border)">
                    Found <strong>{{ count($reservations) }}</strong> reservation(s) in date range &mdash; <span id="filteredCount">{{ count($reservations) }}</span> shown &mdash; Used FVN: <strong id="fvnTotal">{{ $fmtFvn($totalFvn) }}</strong>
                </div>

                {{-- Status Filter --}}
                <div id="statusFilterBar" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-right: 0.25rem;">Filter:</span>
                    @php
                        $statusFilters = [
                            'CONFIRMED'      => ['label' => 'Confirmed',      'color' => '#4ade80', 'bg' => 'rgba(74,222,128,0.1)',  'border' => 'rgba(74,222,128,0.3)'],
                            'ARRIVED'        => ['label' => 'Arrived',        'color' => '#60a5fa', 'bg' => 'rgba(96,165,250,0.1)',  'border' => 'rgba(96,165,250,0.3)'],
                            'NO SHOW'        => ['label' => 'No Show',        'color' => '#f87171', 'bg' => 'rgba(248,113,113,0.1)', 'border' => 'rgba(248,113,113,0.3)'],
                            'PARTLY ARRIVED' => ['label' => 'Partly Arrived', 'color' => '#fb923c', 'bg' => 'rgba(251,146,60,0.1)',  'border' => 'rgba(251,146,60,0.3)'],
                            'CHECKOUT'       => ['label' => 'Checkout',       'color' => '#a78bfa', 'bg' => 'rgba(167,139,250,0.1)', 'border' => 'rgba(167,139,250,0.3)'],
                            'CANCELLED'      => ['label' => 'Cancelled',      'color' => '#9ca3af', 'bg' => 'rgba(156,163,175,0.1)', 'border' => 'rgba(156,163,175,0.3)'],
                            'PENDING'        => ['label' => 'Pending',        'color' => '#fbbf24', 'bg' => 'rgba(251,191,36,0.1)',  'border' => 'rgba(251,191,36,0.3)'],
                        ];
                        $defaultOn = ['CONFIRMED', 'ARRIVED', 'NO SHOW', 'PARTLY ARRIVED'];
                    @endphp
                    @foreach($statusFilters as $sfKey => $sf)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer; padding: 0.3rem 0.7rem; border-radius: 0.5rem; border: 1px solid {{ $sf['border'] }}; background: {{ $sf['bg'] }}; font-size: 0.78rem; font-weight: 600; color: {{ $sf['color'] }};">
                            <input type="checkbox" class="status-filter-cb" data-status="{{ $sfKey }}"
                                {{ in_array($sfKey, $defaultOn) ? 'checked' : '' }}
                                style="accent-color: {{ $sf['color'] }}; width: 13px; height: 13px;">
                            {{ $sf['label'] }}
                        </label>
                    @endforeach
                </div>
            </div>

            @if(count($reservations) > 0)
                <div style="overflow-x: auto;">
                    <table id="resTable">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Res No</th>
                                <th>Book Date</th>
                                <th>Guest Name</th>
                                <th># Rooms</th>
                                <th>Rate</th>
                                <th>FVN Used</th>
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

                                    // FVN Used = rateUnit × nights × rooms (precomputed in controller)
                                    $fvnUsed = (float) ($res['fvn_used'] ?? 0);
                                    $fvnUnit = (float) ($res['fvn_unit'] ?? 0);
                                    $fvnNights = (int) ($res['fvn_nights'] ?? 0);
                                    $fvnRooms = (int) ($res['fvn_rooms'] ?? 0);
                                    $rawRate = $res['fvn_raw_rate'] ?? strtoupper(trim((string)($res['rateCode'] ?? $res['ratecode'] ?? $res['rate_code'] ?? $res['rate'] ?? '')));
                                    $fvnDisplay = ($fvnUsed > 0) ? $fmtFvn($fvnUsed) : '—';
                                    $fvnDetail = ($fvnUsed > 0)
                                        ? ($fmtFvn($fvnUnit) . ' × ' . $fvnNights . 'n × ' . $fvnRooms . 'r')
                                        : '';

                                    $bDate = $res['bookDate'] ?? null;
                                    $bDateFormatted = 'N/A';
                                    if ($bDate && $bDate !== 'N/A') {
                                        try { $bDateFormatted = \Carbon\Carbon::parse($bDate)->format('F d, Y'); } catch (\Exception $e) { $bDateFormatted = $bDate; }
                                    }

                                    $aDate = $res['arrDt'] ?? $res['arrdt'] ?? null;
                                    $aDateFormatted = '';
                                    if ($aDate) {
                                        try { $aDateFormatted = \Carbon\Carbon::parse($aDate)->format('F d, Y'); } catch (\Exception $e) { $aDateFormatted = $aDate; }
                                    }

                                    $dDate = $res['depDt'] ?? $res['depdt'] ?? null;
                                    $dDateFormatted = '';
                                    if ($dDate) {
                                        try { $dDateFormatted = \Carbon\Carbon::parse($dDate)->format('F d, Y'); } catch (\Exception $e) { $dDateFormatted = $dDate; }
                                    }
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
                                            <span class="res-no">#{{ $resNo }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap;">{{ $bDateFormatted }}</td>
                                    <td>
                                        <div style="font-weight: 500;">{{ $res['guestName'] ?? $res['gstName'] ?? 'Unknown' }}</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted)">{{ $res['customer'] ?? '' }}</div>
                                    </td>
                                    <td style="text-align: center;">{{ $res['noRooms'] ?? $res['noOfRooms'] ?? '0' }}</td>
                                    <td style="font-family: monospace; font-size: 0.8rem;">{{ $rawRate ?: '0' }}</td>
                                    <td style="text-align: center; font-weight: 700; color: {{ $fvnUsed > 0 ? '#818cf8' : 'var(--text-muted)' }};">
                                        <div>{{ $fvnDisplay }}</div>
                                        @if($fvnDetail)
                                            <div style="font-size: 0.68rem; font-weight: 500; color: var(--text-muted); margin-top: 0.15rem;">{{ $fvnDetail }}</div>
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap;">{{ $aDateFormatted }}</td>
                                    <td style="white-space: nowrap;">{{ $dDateFormatted }}</td>
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
    // --- FVN data embedded from server ---
    const rowFvnData = {};
    @foreach($reservations as $idx => $res)
        rowFvnData[{{ $idx }}] = { fvn: {{ (float) ($res['fvn_used'] ?? 0) }}, status: '{{ strtoupper(trim($res['status'] ?? '')) }}' };
    @endforeach

    // --- Status filter logic ---
    function applyStatusFilter() {
        const checkboxes = document.querySelectorAll('.status-filter-cb');
        const activeStatuses = new Set();
        checkboxes.forEach(cb => {
            if (cb.checked) activeStatuses.add(cb.dataset.status.toUpperCase());
        });

        const rows = document.querySelectorAll('#resTable tbody tr');
        let shown = 0, totalFvn = 0;

        rows.forEach((row, idx) => {
            const data = rowFvnData[idx];
            if (!data) return;
            const rowStatus = data.status;

            // Match row status: check if row status starts with or equals any active status
            let visible = false;
            activeStatuses.forEach(s => {
                if (rowStatus === s || rowStatus.startsWith(s)) visible = true;
            });

            row.style.display = visible ? '' : 'none';
            if (visible) {
                shown++;
                totalFvn += data.fvn;
            }
        });

        document.getElementById('filteredCount').textContent = shown;
        const fvnEl = document.getElementById('fvnTotal');
        fvnEl.textContent = Number.isInteger(totalFvn) ? totalFvn : totalFvn.toFixed(1).replace(/\.0$/, '');
    }

    document.querySelectorAll('.status-filter-cb').forEach(cb => {
        cb.addEventListener('change', applyStatusFilter);
    });

    // Apply on load
    document.addEventListener('DOMContentLoaded', applyStatusFilter);

    // --- AI Helper ---
    function applySuggestion(suggestedVal) {
        var input = document.getElementById('member_no');
        if (input) {
            input.value = suggestedVal;
            var form = document.getElementById('searchForm');
            if (form) {
                var btn = document.getElementById('btnSubmit');
                var spinner = document.getElementById('spinner');
                var btnText = document.getElementById('btnText');
                if (btn) btn.disabled = true;
                if (spinner) spinner.style.display = 'inline-block';
                if (btnText) btnText.textContent = 'Searching...';
                form.submit();
            }
        }
    }

    (function () {
        var fromInput = document.getElementById('fromdate');
        var toInput = document.getElementById('todate');
        var dateError = document.getElementById('dateRangeError');

        function syncDateBounds() {
            if (fromInput.value) toInput.min = fromInput.value;
            else toInput.removeAttribute('min');
            if (toInput.value) fromInput.max = toInput.value;
            else fromInput.removeAttribute('max');
        }

        function isDateRangeValid() {
            if (!fromInput.value || !toInput.value) return true;
            return fromInput.value <= toInput.value;
        }

        function showDateError(show) {
            if (!dateError) return;
            if (show) {
                dateError.textContent = 'From Date cannot be later than To Date.';
                fromInput.style.borderColor = '#f87171';
                toInput.style.borderColor = '#f87171';
            } else {
                dateError.textContent = '';
                fromInput.style.borderColor = '';
                toInput.style.borderColor = '';
            }
        }

        fromInput.addEventListener('change', function () {
            syncDateBounds();
            showDateError(!isDateRangeValid());
        });
        toInput.addEventListener('change', function () {
            syncDateBounds();
            showDateError(!isDateRangeValid());
        });
        syncDateBounds();

        document.getElementById('searchForm').addEventListener('submit', function (e) {
            if (!isDateRangeValid()) {
                e.preventDefault();
                showDateError(true);
                fromInput.focus();
                return;
            }
            showDateError(false);
            var btn = document.getElementById('btnSubmit');
            var spinner = document.getElementById('spinner');
            var btnText = document.getElementById('btnText');
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Searching...';
        });
    })();
</script>
@endsection
