<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--border);
            position: relative;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        footer {
            margin-top: auto;
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container">
        <header class="animate-in">
            <div class="logo">Reservation</div>
            <nav style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('dashboard') }}" class="btn" style="color: var(--text)">Dashboard</a>
                <a href="{{ route('member.search') }}" class="btn" style="color: var(--text)">Member Search</a>

                {{-- API Status Dropdown --}}
                <div id="apiDropdownWrapper" style="position: relative;">
                    <button id="apiDropdownBtn" onclick="toggleApiDropdown()" style="display:inline-flex; align-items:center; gap:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--text); border-radius:0.5rem; padding:0.5rem 0.9rem; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s; font-family:inherit;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                        <span id="apiSummaryDot" style="width:9px; height:9px; border-radius:50%; background:#475569; display:inline-block; flex-shrink:0; transition:background 0.3s, box-shadow 0.3s;"></span>
                        API Status
                        <svg style="opacity:0.5; transition:transform 0.2s;" id="apiDropdownChevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    {{-- Dropdown Panel --}}
                    <div id="apiDropdownPanel" style="display:none; position:absolute; right:0; top:calc(100% + 8px); width:260px; background:#1e293b; border:1px solid rgba(255,255,255,0.12); border-radius:0.875rem; box-shadow:0 16px 40px rgba(0,0,0,0.4); z-index:9999; overflow:hidden;">
                        <div style="padding:0.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#94a3b8;">API Health</span>
                            <span id="apiLastChecked" style="font-size:0.65rem; color:#475569;"></span>
                        </div>

                        @foreach(['island' => ['label'=>'Balesin Island','icon'=>'🏝️'], 'city' => ['label'=>'Balesin City','icon'=>'🏙️'], 'pines' => ['label'=>'Balesin Pines','icon'=>'🌲']] as $slug => $info)
                        <div id="api-row-{{ $slug }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.05); transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
                            <span id="api-dot-{{ $slug }}" style="width:10px; height:10px; border-radius:50%; background:#334155; flex-shrink:0; transition:background 0.3s, box-shadow 0.3s;"></span>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:0.8rem; font-weight:600; color:#f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $info['icon'] }} {{ $info['label'] }}</div>
                                <div id="api-detail-{{ $slug }}" style="font-size:0.65rem; color:#64748b; margin-top:1px;">Checking...</div>
                            </div>
                            <span id="api-badge-{{ $slug }}" style="font-size:0.65rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:999px; background:rgba(100,116,139,0.2); color:#94a3b8; flex-shrink:0;">—</span>
                        </div>
                        @endforeach

                        <div style="padding:0.65rem 1rem;">
                            <button onclick="fetchApiStatus()" id="navApiRefreshBtn" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.4rem; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); color:#818cf8; border-radius:0.5rem; padding:0.45rem; font-size:0.75rem; font-weight:600; cursor:pointer; transition:all 0.2s; font-family:inherit;" onmouseover="this.style.background='rgba(99,102,241,0.25)'" onmouseout="this.style.background='rgba(99,102,241,0.15)'">
                                <svg id="navApiSpinner" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                Refresh All
                            </button>
                        </div>
                    </div>
                </div>

                <a href="{{ url('/setup') }}" class="btn" style="color: var(--text); border: 1px solid var(--border);">Setup</a>
            </nav>

            <style>
                @keyframes spin { to { transform: rotate(360deg); } }
            </style>

            <script>
                function toggleApiDropdown() {
                    var panel   = document.getElementById('apiDropdownPanel');
                    var chevron = document.getElementById('apiDropdownChevron');
                    var open    = panel.style.display === 'block';
                    panel.style.display   = open ? 'none' : 'block';
                    chevron.style.transform = open ? '' : 'rotate(180deg)';
                }
                document.addEventListener('click', function(e) {
                    var wrapper = document.getElementById('apiDropdownWrapper');
                    if (wrapper && !wrapper.contains(e.target)) {
                        document.getElementById('apiDropdownPanel').style.display = 'none';
                        document.getElementById('apiDropdownChevron').style.transform = '';
                    }
                });

                // ── API Status Checker ────────────────────────────────────────────
                var _apiStatusTimer = null;

                function fetchApiStatus() {
                    var btn     = document.getElementById('navApiRefreshBtn');
                    var spinner = document.getElementById('navApiSpinner');
                    var summaryDot = document.getElementById('apiSummaryDot');

                    if (btn) btn.disabled = true;
                    if (spinner) spinner.style.animation = 'spin 1s linear infinite';
                    if (summaryDot) {
                        summaryDot.style.background = '#f59e0b'; // Amber while checking
                        summaryDot.style.boxShadow = '0 0 5px #f59e0b99';
                    }

                    fetch('{{ route("api.status") }}')
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            var lastChecked = '';
                            var allUp = true;
                            var anyResponse = false;

                            Object.keys(data).forEach(function(slug) {
                                anyResponse = true;
                                var d    = data[slug];
                                var dot  = document.getElementById('api-dot-' + slug);
                                var detail = document.getElementById('api-detail-' + slug);
                                var badge = document.getElementById('api-badge-' + slug);

                                if (!d.up) {
                                    allUp = false;
                                }

                                if (dot) {
                                    dot.style.background = d.up ? '#22c55e' : '#ef4444';
                                    dot.style.boxShadow  = d.up ? '0 0 6px #22c55e99' : '0 0 6px #ef444499';
                                }
                                if (detail) {
                                    detail.textContent = d.up ? d.ms + 'ms' : d.message;
                                }
                                if (badge) {
                                    badge.textContent = d.up ? 'UP' : 'DOWN';
                                    badge.style.background = d.up ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)';
                                    badge.style.color = d.up ? '#22c55e' : '#ef4444';
                                }

                                lastChecked = d.checked;
                            });

                            if (summaryDot && anyResponse) {
                                summaryDot.style.background = allUp ? '#22c55e' : '#ef4444';
                                summaryDot.style.boxShadow = allUp ? '0 0 6px #22c55e99' : '0 0 6px #ef444499';
                            }

                            var el = document.getElementById('apiLastChecked');
                            if (el && lastChecked) el.textContent = 'Checked ' + lastChecked;
                        })
                        .catch(function() {
                            ['island','city','pines'].forEach(function(slug) {
                                var dot  = document.getElementById('api-dot-' + slug);
                                var detail = document.getElementById('api-detail-' + slug);
                                var badge = document.getElementById('api-badge-' + slug);

                                if (dot) {
                                    dot.style.background = '#ef4444';
                                    dot.style.boxShadow  = 'none';
                                }
                                if (detail) {
                                    detail.textContent = 'Error checking status';
                                }
                                if (badge) {
                                    badge.textContent = 'ERR';
                                    badge.style.background = 'rgba(239,68,68,0.15)';
                                    badge.style.color = '#ef4444';
                                }
                            });

                            if (summaryDot) {
                                summaryDot.style.background = '#ef4444';
                                summaryDot.style.boxShadow = 'none';
                            }
                        })
                        .finally(function() {
                            if (btn) btn.disabled = false;
                            if (spinner) spinner.style.animation = '';
                        });

                    // auto-refresh every 60s
                    clearTimeout(_apiStatusTimer);
                    _apiStatusTimer = setTimeout(fetchApiStatus, 60000);
                }

                // run on page load
                document.addEventListener('DOMContentLoaded', function() {
                    fetchApiStatus();
                });
            </script>
        </header>

        <main>
            @if(session('error') || (isset($error) && $error))
                <div class="alert alert-error animate-in">
                    {{ session('error') ?? $error }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer>
            &copy; {{ date('Y') }} Alphaland Corporation All Rights Reserved
        </footer>
    </div>
    @yield('modals')
    @yield('scripts')
</body>
</html>
