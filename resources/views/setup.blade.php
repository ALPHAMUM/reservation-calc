@extends('layouts.app')

@section('styles')
<style>
    .setup-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .setup-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: var(--primary);
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        background: var(--glass-bg);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        color: white;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
    }

    select.form-control option {
        background-color: #1e293b;
        color: white;
    }

    .checkbox-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .peak-period-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: flex-end;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.2);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    .setup-section {
        position: relative;
    }

    .info-btn {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
    }

    .info-btn:hover {
        color: var(--primary);
        background: rgba(99, 102, 241, 0.1);
        border-color: var(--primary);
        transform: scale(1.1);
    }

    .help-box {
        display: none;
        position: absolute;
        top: 3.5rem;
        right: 1.5rem;
        width: 320px;
        background: rgba(30, 41, 59, 0.98);
        backdrop-filter: blur(12px);
        border: 1px solid var(--primary);
        padding: 1.25rem;
        border-radius: 1rem;
        z-index: 100;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        animation: fadeIn 0.2s ease-out;
    }

    .help-box strong {
        color: var(--primary);
        display: block;
        font-size: 0.9375rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        padding-bottom: 0.25rem;
    }

    .help-box div {
        color: #e2e8f0;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .help-close {
        position: absolute;
        top: 0.5rem;
        right: 0.75rem;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 1.25rem;
    }
</style>
@endsection

@section('content')
<div class="animate-in" style="animation-delay: 0.1s">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 600;">Calculator Setup & Configuration</h2>
    </div>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ url('/setup') }}" method="POST">
        @csrf

        <!-- Base Rates -->
        <div class="setup-section">
            <div class="info-btn" onclick="toggleHelp('help-base')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h3 class="setup-title">Base Airfare Rates</h3>
            <div id="help-base" class="help-box">
                <span class="help-close" onclick="toggleHelp('help-base')">&times;</span>
                <strong>Base Airfare Rates</strong>
                <div>Configure the standard, year-round airfare rates for Members and Guests. Use the checkbox to define if infants (under 24 months) are billed or fly for free.</div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Member Airfare Rate (₱)</label>
                    <input type="number" step="0.01" name="base_rates[member]" class="form-control" value="{{ $settings['base_rates']['member'] ?? 5600 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Guest Airfare Rate (₱)</label>
                    <input type="number" step="0.01" name="base_rates[guest]" class="form-control" value="{{ $settings['base_rates']['guest'] ?? 12320 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Employee Airfare Rate (₱)</label>
                    <input type="number" step="0.01" name="base_rates[employee]" class="form-control" value="{{ $settings['base_rates']['employee'] ?? 2800 }}">
                </div>
            </div>
            <div class="checkbox-wrap">
                <input type="checkbox" id="airfare_free" name="infants[airfare_free]" {{ !empty($settings['infants']['airfare_free']) ? 'checked' : '' }}>
                <label for="airfare_free" class="form-label" style="margin:0">Infants (≤23 mos) travel free of charge</label>
            </div>
        </div>

        <!-- Promo Rates -->
        <div class="setup-section">
            <div class="info-btn" onclick="toggleHelp('help-promo')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h3 class="setup-title">Promo Rates</h3>
            <div id="help-promo" class="help-box">
                <span class="help-close" onclick="toggleHelp('help-promo')">&times;</span>
                <strong>Promo Rates</strong>
                <div>Set temporary rates for specific promotion dates. 
                <br>• Peak Periods: Any dates added here will bypass promo rates and revert to Base Rates automatically (e.g., Christmas, New Year).</div>
            </div>
            <div class="checkbox-wrap" style="margin-bottom: 1.5rem;">
                <input type="checkbox" id="promo_active" name="promo_rates[active]" {{ !empty($settings['promo_rates']['active']) ? 'checked' : '' }}>
                <label for="promo_active" class="form-label" style="margin:0">Enable Promo Rates</label>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Promo Description</label>
                <textarea name="promo_rates[description]" class="form-control" rows="2" placeholder="Enter promotion description (e.g. Summer Promo 2024)">{{ $settings['promo_rates']['description'] ?? '' }}</textarea>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Promo Member Rate (₱)</label>
                    <input type="number" step="0.01" name="promo_rates[member]" class="form-control" value="{{ $settings['promo_rates']['member'] ?? 4200 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Promo Guest Rate (₱)</label>
                    <input type="number" step="0.01" name="promo_rates[guest]" class="form-control" value="{{ $settings['promo_rates']['guest'] ?? 6200 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Promo Employee Rate (₱)</label>
                    <input type="number" step="0.01" name="promo_rates[employee]" class="form-control" value="{{ $settings['promo_rates']['employee'] ?? 2800 }}">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 1rem;">
                <div class="form-group">
                    <label class="form-label">Promo Start Date</label>
                    <input type="date" name="promo_rates[start_date]" class="form-control" value="{{ $settings['promo_rates']['start_date'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Promo End Date</label>
                    <input type="date" name="promo_rates[end_date]" class="form-control" value="{{ $settings['promo_rates']['end_date'] ?? '' }}">
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Peak Periods (Promo rates do not apply during these dates)</label>
                <div id="peak-periods-container">
                    @forelse($settings['promo_rates']['peak_periods'] ?? [] as $period)
                        <div class="peak-period-row">
                            <div style="flex:1.5">
                                <input type="text" name="peak_periods[label][]" class="form-control" placeholder="Description (e.g. Holy Week)" value="{{ $period['label'] ?? '' }}">
                            </div>
                            <div style="flex:1">
                                <input type="date" name="peak_periods[start][]" class="form-control" value="{{ $period['start'] }}">
                            </div>
                            <div style="color:var(--text-muted)">to</div>
                            <div style="flex:1">
                                <input type="date" name="peak_periods[end][]" class="form-control" value="{{ $period['end'] }}">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                        </div>
                    @empty
                        <div class="peak-period-row">
                            <div style="flex:1.5"><input type="text" name="peak_periods[label][]" class="form-control" placeholder="Description"></div>
                            <div style="flex:1"><input type="date" name="peak_periods[start][]" class="form-control"></div>
                            <div style="color:var(--text-muted)">to</div>
                            <div style="flex:1"><input type="date" name="peak_periods[end][]" class="form-control"></div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                        </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="addPeakPeriod()" style="margin-top:0.5rem; background: rgba(255,255,255,0.1)">+ Add Peak Period</button>
            </div>
        </div>

        <!-- Discounts & VAT -->
        <div class="setup-section">
            <div class="info-btn" onclick="toggleHelp('help-discounts')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h3 class="setup-title">Discounts & VAT</h3>
            <div id="help-discounts" class="help-box">
                <span class="help-close" onclick="toggleHelp('help-discounts')">&times;</span>
                <strong>Discounts & VAT</strong>
                <div>Manage tax rules. For Senior Citizens (SC) and PWDs, standard policy usually involves removing the 12% VAT first. The "Additional Discount" is applied after VAT removal if applicable.</div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Standard VAT Rate (%)</label>
                    <input type="number" step="0.1" name="discounts[vat_rate]" class="form-control" value="{{ $settings['discounts']['vat_rate'] ?? 12 }}">
                </div>
            </div>
            <div class="checkbox-wrap">
                <input type="checkbox" id="remove_vat" name="discounts[sc_pwd][remove_vat]" {{ !empty($settings['discounts']['sc_pwd']['remove_vat']) ? 'checked' : '' }}>
                <label for="remove_vat" class="form-label" style="margin:0">Remove VAT for Senior Citizens / PWDs</label>
            </div>
            <div class="form-group" style="margin-top: 1rem; max-width: 50%;">
                <label class="form-label">Additional SC/PWD Discount (%) [Default: 0 if rates already discounted]</label>
                <input type="number" step="0.1" name="discounts[sc_pwd][additional_discount_percent]" class="form-control" value="{{ $settings['discounts']['sc_pwd']['additional_discount_percent'] ?? 0 }}">
            </div>
        </div>

        <!-- Additional Fees -->
        <div class="setup-section">
            <div class="info-btn" onclick="toggleHelp('help-fees')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h3 class="setup-title">Additional Fees</h3>
            <div id="help-fees" class="help-box">
                <span class="help-close" onclick="toggleHelp('help-fees')">&times;</span>
                <strong>Additional Fees</strong>
                <div>Configure mandatory surcharges. 
                <br>• Hangar Fee: Per passenger fee for terminal usage.
                <br>• AOF (₱2,000): Aviation Operational Fee. Applies to Members &amp; Guests during active periods.
                <br>&nbsp;&nbsp;<strong>Always exempt:</strong> Infants, Employees, Ex-deals, Priests, Consultants, Consignors, Inspectors, and other Authorized Personnel.
                <br>• Environmental Fee: Island maintenance fee, typically applied to Guests.</div>
            </div>
            
            <div class="form-group" style="padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                <label class="form-label" style="font-size: 1rem; color: white;">Hangar Fee</label>
                <div class="grid-2">
                    <div>
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" step="0.01" name="fees[hangar][amount]" class="form-control" value="{{ $settings['fees']['hangar']['amount'] ?? 400 }}">
                    </div>
                    <div>
                        <label class="form-label">Apply To</label>
                        @php $hangarApplies = $settings['fees']['hangar']['apply_to'] ?? ['member', 'guest']; @endphp
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="member" {{ in_array('member', $hangarApplies) ? 'checked' : '' }}> Members</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="guest" {{ in_array('guest', $hangarApplies) ? 'checked' : '' }}> Guests</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="employee" {{ in_array('employee', $hangarApplies) ? 'checked' : '' }}> Employees</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="infant" {{ in_array('infant', $hangarApplies) ? 'checked' : '' }}> Infants</div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                <label class="form-label" style="font-size: 1rem; color: white;">Aviation Operational Fee (AOF)</label>
                <div class="grid-2">
                    <div>
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" step="0.01" name="fees[aof][amount]" class="form-control" value="{{ $settings['fees']['aof']['amount'] ?? 2000 }}">
                        <label class="form-label" style="margin-top:1rem;">Active Periods
                            <span style="color: var(--text-muted); font-size: 0.75rem;"> — Leave Year blank to apply every year for that month</span>
                        </label>
                        <div id="aof-periods-container" style="margin-top: 0.75rem;">
                            @php $periods = $settings['fees']['aof']['active_periods'] ?? []; @endphp
                            @if(count($periods) === 0)
                                @php $periods = [['month' => 4, 'year' => date('Y')]]; @endphp
                            @endif

                            @foreach($periods as $period)
                                <div class="peak-period-row">
                                    <div style="flex:1.5">
                                        <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Month</label>
                                        <select name="fees[aof][active_periods][month][]" class="form-control">
                                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                                <option value="{{ $i+1 }}" {{ ($period['month'] ?? 0) == $i+1 ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="flex:1">
                                        <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Year (blank = every year)</label>
                                        <input type="number" name="fees[aof][active_periods][year][]" class="form-control" placeholder="Any year" value="{{ ($period['year'] ?? 0) > 0 ? $period['year'] : '' }}">
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444; margin-bottom: 2px;">Remove</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addAofPeriod()" style="margin-top: 0.5rem; background: rgba(255,255,255,0.1)">+ Add Period</button>
                    </div>
                    <div>
                        <label class="form-label">Apply To</label>
                        @php $aofApplies = $settings['fees']['aof']['apply_to'] ?? ['member', 'guest']; @endphp
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="member" {{ in_array('member', $aofApplies) ? 'checked' : '' }}> Members</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="guest" {{ in_array('guest', $aofApplies) ? 'checked' : '' }}> Guests</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="employee" {{ in_array('employee', $aofApplies) ? 'checked' : '' }}> Employees</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="infant" {{ in_array('infant', $aofApplies) ? 'checked' : '' }}> Infants</div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="padding-top: 1.5rem;">
                <label class="form-label" style="font-size: 1rem; color: white;">Environmental Fee</label>
                <div class="grid-2">
                    <div>
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" step="0.01" name="fees[environmental][amount]" class="form-control" value="{{ $settings['fees']['environmental']['amount'] ?? 200 }}">
                    </div>
                    <div>
                        <label class="form-label">Apply To</label>
                        @php $envApplies = $settings['fees']['environmental']['apply_to'] ?? ['guest']; @endphp
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[environmental][apply_to][]" value="member" {{ in_array('member', $envApplies) ? 'checked' : '' }}> Members</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[environmental][apply_to][]" value="guest" {{ in_array('guest', $envApplies) ? 'checked' : '' }}> Guests</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[environmental][apply_to][]" value="employee" {{ in_array('employee', $envApplies) ? 'checked' : '' }}> Employees</div>
                        <div class="checkbox-wrap"><input type="checkbox" name="fees[environmental][apply_to][]" value="infant" {{ in_array('infant', $envApplies) ? 'checked' : '' }}> Infants</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: right;">
            <button type="submit" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2.5rem; background: var(--success);">
                Save Setup Configuration
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function addPeakPeriod() {
        const container = document.getElementById('peak-periods-container');
        const row = document.createElement('div');
        row.className = 'peak-period-row';
        row.innerHTML = `
            <div style="flex:1.5"><input type="text" name="peak_periods[label][]" class="form-control" placeholder="Description"></div>
            <div style="flex:1"><input type="date" name="peak_periods[start][]" class="form-control"></div>
            <div style="color:var(--text-muted)">to</div>
            <div style="flex:1"><input type="date" name="peak_periods[end][]" class="form-control"></div>
            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
        `;
        container.appendChild(row);
    }

    function addAofPeriod() {
        const container = document.getElementById('aof-periods-container');
        const row = document.createElement('div');
        row.className = 'peak-period-row';
        row.innerHTML = `
            <div style="flex:1.5">
                <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Month</label>
                <select name="fees[aof][active_periods][month][]" class="form-control">
                    <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                    <option value="4" selected>April</option><option value="5">May</option><option value="6">June</option>
                    <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                    <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                </select>
            </div>
            <div style="flex:1">
                <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Year (blank = every year)</label>
                <input type="number" name="fees[aof][active_periods][year][]" class="form-control" placeholder="Any year" value="">
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444; margin-bottom: 2px;">Remove</button>
        `;
        container.appendChild(row);
    }

    function toggleHelp(id) {
        const el = document.getElementById(id);
        const isOpen = el.style.display === 'block';
        
        // Hide all first
        document.querySelectorAll('.help-box').forEach(box => box.style.display = 'none');

        // Toggle this one
        if (!isOpen) {
            el.style.display = 'block';
        }
    }

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.setup-section')) {
            document.querySelectorAll('.help-box').forEach(box => box.style.display = 'none');
        }
    });
</script>
@endsection
