@extends('layouts.app')

@section('styles')
<style>
    /* Property Navigation Tabs */
    .property-nav-tabs {
        display: flex;
        gap: 0.875rem;
        margin-bottom: 2rem;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(12px);
        padding: 0.5rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
    }

    .property-nav-tab {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.85rem;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        text-align: left;
        font-family: inherit;
    }

    .property-nav-tab:hover:not(:disabled) {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
        transform: translateY(-1px);
    }

    .property-nav-tab:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }

    .property-nav-tab.active {
        color: #ffffff;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(168, 85, 247, 0.15) 100%);
        border-color: rgba(99, 102, 241, 0.55);
        box-shadow: 0 4px 20px -2px rgba(99, 102, 241, 0.35);
    }

    .property-nav-tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 20%;
        right: 20%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #818cf8, transparent);
    }

    .property-nav-tab .tab-icon {
        font-size: 1.75rem;
        line-height: 1;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    .property-nav-tab .tab-meta {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .property-nav-tab .tab-title {
        font-weight: 700;
        font-size: 1.05rem;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }

    .property-nav-tab .tab-subtitle {
        font-size: 0.75rem;
        color: var(--text-muted);
        transition: color 0.2s;
        white-space: nowrap;
    }

    .property-nav-tab.active .tab-subtitle {
        color: #c7d2fe;
    }

    .property-tab-pane {
        display: none;
        animation: fadeInPane 0.25s ease-out;
    }

    .property-tab-pane.active {
        display: block;
    }

    @keyframes fadeInPane {
        from {
            opacity: 0;
            transform: translateY(6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .setup-section {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
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

    .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.5rem;
    }

    .grid-4 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 1.25rem;
    }

    @media (max-width: 900px) {
        .property-nav-tabs {
            flex-direction: column;
        }
        .grid-2, .grid-3, .grid-4 {
            grid-template-columns: 1fr;
        }
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
        border: 1px solid rgba(16, 185, 129, 0.2);
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
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
        width: 340px;
        background: rgba(30, 41, 59, 0.98);
        backdrop-filter: blur(14px);
        border: 1px solid var(--primary);
        padding: 1.25rem;
        border-radius: 1rem;
        z-index: 100;
        box-shadow: 0 12px 30px rgba(0,0,0,0.55);
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
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.25rem;">Calculator Setup &amp; Configuration</h2>
            <div style="font-size: 0.875rem; color: var(--text-muted);">Configure rates, fees, discounts, and capacity rules per property.</div>
        </div>
        <div id="islandExportBtn" style="{{ ($activeTab ?? 'island') === 'island' ? '' : 'display: none;' }}">
            <a href="{{ route('rates.export') }}" class="btn" style="background: var(--success); color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export tbl_rates (Excel)
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Property Navigation Tabs --}}
    <div class="property-nav-tabs">
        <button type="button" id="tab-btn-island" class="property-nav-tab {{ ($activeTab ?? 'island') === 'island' ? 'active' : '' }}" onclick="switchTab('island')">
            <span class="tab-icon">🏝️</span>
            <div class="tab-meta">
                <span class="tab-title">Balesin Island</span>
                <span class="tab-subtitle">Airfare, Fees &amp; Villa Rules</span>
            </div>
        </button>
        <button type="button" id="tab-btn-city" class="property-nav-tab {{ ($activeTab ?? 'island') === 'city' ? 'active' : '' }}" onclick="switchTab('city')" disabled>
            <span class="tab-icon">🏙️</span>
            <div class="tab-meta">
                <span class="tab-title">Balesin City</span>
                <span class="tab-subtitle">Studio, 2-BR &amp; Occupancy</span>
            </div>
        </button>
        <button type="button" id="tab-btn-pines" class="property-nav-tab {{ ($activeTab ?? 'island') === 'pines' ? 'active' : '' }}" onclick="switchTab('pines')" disabled>
            <span class="tab-icon">🌲</span>
            <div class="tab-meta">
                <span class="tab-title">Balesin Pines</span>
                <span class="tab-subtitle">Deluxe, Junior Suite &amp; Limits</span>
            </div>
        </button>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANE 1: BALESIN ISLAND (Existing Setup & Configuration Kept Complete) --}}
    {{-- ========================================================================= --}}
    <div id="tab-pane-island" class="property-tab-pane {{ ($activeTab ?? 'island') === 'island' ? 'active' : '' }}">
        <form action="{{ url('/setup') }}" method="POST">
            @csrf
            <input type="hidden" name="property" value="island">

            <!-- Base Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-base')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Base Airfare Rates</h3>
                <div id="help-base" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-base')">&times;</span>
                    <strong>Base Airfare Rates</strong>
                    <div>Configure the standard, year-round airfare rates for Members, Guests, and Employees. Use the checkbox to define if infants (under 24 months) are billed or fly for free.</div>
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
                    <button type="button" class="btn btn-primary btn-sm" onclick="addPeakPeriodIsland()" style="margin-top:0.5rem; background: rgba(255,255,255,0.1)">+ Add Peak Period</button>
                </div>
            </div>

            <!-- Extra Occupant Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-extra-occupants')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Extra Occupant Accommodation Rates</h3>
                <div id="help-extra-occupants" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-extra-occupants')">&times;</span>
                    <strong>Extra Occupants</strong>
                    <div>Configure the accommodation rate for extra occupants:
                    <br>• Villa: 5th+ guest.
                    <br>• Suite: 9th+ guest.
                    <br>You can choose to use the standard defaults returned from the API, or override them with a flat custom rate.</div>
                </div>
                <div class="grid-2">
                    <div class="form-group" style="padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <label class="form-label" style="font-size: 1rem; color: white; font-weight: 500; margin-bottom: 1rem;">Villa (5th+ Person)</label>
                        <div class="checkbox-wrap" style="margin-bottom: 1rem;">
                            <input type="checkbox" id="override_villa" name="extra_occupants[villa][override]" {{ !empty($settings['extra_occupants']['villa']['override']) ? 'checked' : '' }}>
                            <label for="override_villa" class="form-label" style="margin:0; color: white;">Override API default rate</label>
                        </div>
                        <label class="form-label">Override Rate (₱)</label>
                        <input type="number" step="0.01" name="extra_occupants[villa][amount]" class="form-control" value="{{ $settings['extra_occupants']['villa']['amount'] ?? 3700 }}">
                    </div>
                    <div class="form-group" style="padding: 1rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <label class="form-label" style="font-size: 1rem; color: white; font-weight: 500; margin-bottom: 1rem;">Suite (9th+ Person)</label>
                        <div class="checkbox-wrap" style="margin-bottom: 1rem;">
                            <input type="checkbox" id="override_suite" name="extra_occupants[suite][override]" {{ !empty($settings['extra_occupants']['suite']['override']) ? 'checked' : '' }}>
                            <label for="override_suite" class="form-label" style="margin:0; color: white;">Override API default rate</label>
                        </div>
                        <label class="form-label">Override Rate (₱)</label>
                        <input type="number" step="0.01" name="extra_occupants[suite][amount]" class="form-control" value="{{ $settings['extra_occupants']['suite']['amount'] ?? 3700 }}">
                    </div>
                </div>
            </div>

            <!-- Discounts & VAT -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-discounts')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Discounts &amp; VAT</h3>
                <div id="help-discounts" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-discounts')">&times;</span>
                    <strong>Discounts &amp; VAT</strong>
                    <div>Manage tax and discount rules for Senior Citizens (SC) and Persons with Disabilities (PWDs). 
                    <br>• <strong>Remove VAT:</strong> Standard policy removes 12% VAT.
                    <br>• <strong>Percentage Discount:</strong> Toggle whether to apply the percentage discount (default 20%) in accommodation/rate calculations. Uncheck if no 20% discount should be computed.</div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Standard VAT Rate (%)</label>
                        <input type="number" step="0.1" name="discounts[vat_rate]" class="form-control" value="{{ $settings['discounts']['vat_rate'] ?? 12 }}">
                    </div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="remove_vat" name="discounts[sc_pwd][remove_vat]" {{ !empty($settings['discounts']['sc_pwd']['remove_vat']) ? 'checked' : '' }}>
                    <label for="remove_vat" class="form-label" style="margin:0">Remove VAT for Senior Citizens / PWDs</label>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="apply_sc_pwd_discount" name="discounts[sc_pwd][apply_discount]" {{ !isset($settings['discounts']['sc_pwd']['apply_discount']) || !empty($settings['discounts']['sc_pwd']['apply_discount']) ? 'checked' : '' }}>
                    <label for="apply_sc_pwd_discount" class="form-label" style="margin:0">Apply Senior Citizen / PWD Percentage Discount in Computation</label>
                </div>
                <div class="form-group" style="margin-top: 0.75rem; max-width: 50%;">
                    <label class="form-label">Senior / PWD Discount Percentage (%) [Default: 20%]</label>
                    <input type="number" step="0.1" name="discounts[sc_pwd][additional_discount_percent]" class="form-control" value="{{ $settings['discounts']['sc_pwd']['additional_discount_percent'] ?? 20 }}">
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
                    <br>• Aviation Operational Fee (AOF): Applies to Members &amp; Guests.
                    <br>&nbsp;&nbsp;<strong>Note:</strong> Spouse and Dependent are treated as 'Members' if the option is enabled above.
                    <br>&nbsp;&nbsp;<strong>Always exempt:</strong> Infants, Employees (unless specified), and authorized personnel (treated as Members).</div>
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
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="member" {{ in_array('member', $hangarApplies) ? 'checked' : '' }}> Members <span style="font-size: 0.65rem; color: var(--text-muted); margin-left: 4px;">(incl. Spouse/Dependent)</span></div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="guest" {{ in_array('guest', $hangarApplies) ? 'checked' : '' }}> Guests</div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="employee" {{ in_array('employee', $hangarApplies) ? 'checked' : '' }}> Employees</div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[hangar][apply_to][]" value="infant" {{ in_array('infant', $hangarApplies) ? 'checked' : '' }}> Infants</div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                    <label class="form-label" style="font-size: 1rem; color: white; margin-bottom: 1.25rem;">Aviation Operational Fee (AOF)</label>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Apply To</label>
                        @php $aofApplies = $settings['fees']['aof']['apply_to'] ?? ['member', 'guest']; @endphp
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="member" {{ in_array('member', $aofApplies) ? 'checked' : '' }}> Members <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 2px;">(incl. Spouse/Dependent)</span></div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="guest" {{ in_array('guest', $aofApplies) ? 'checked' : '' }}> Guests</div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="employee" {{ in_array('employee', $aofApplies) ? 'checked' : '' }}> Employees</div>
                            <div class="checkbox-wrap"><input type="checkbox" name="fees[aof][apply_to][]" value="infant" {{ in_array('infant', $aofApplies) ? 'checked' : '' }}> Infants</div>
                        </div>
                    </div>

                    <div style="padding: 1.25rem; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <label class="form-label" style="font-size: 0.95rem; color: white; font-weight: 500; margin-bottom: 1rem;">Active AOF Periods (Start Date, End Date, and Amount in one row)</label>
                        <div id="aof-periods-container">
                            @php 
                                $periods = $settings['fees']['aof']['active_periods'] ?? [];
                                $todayStr = date('Y-m-d');
                            @endphp
                            @forelse($periods as $period)
                                @php
                                    $start = $period['start'] ?? '';
                                    $end = $period['end'] ?? '';
                                    $isActive = ($start && $end && $todayStr >= $start && $todayStr <= $end);
                                    $isPast = ($end && $todayStr > $end);
                                @endphp
                                <div class="peak-period-row" style="align-items: flex-end; margin-bottom: 1rem; padding: 0.75rem; border-radius: 0.5rem; transition: all 0.2s;
                                    @if($isActive)
                                        background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.3); border-left: 4px solid #10b981;
                                    @elseif($isPast)
                                        opacity: 0.65; pointer-events: none; background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.05);
                                    @else
                                        background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border);
                                    @endif">
                                    
                                    <div style="flex:1">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                            <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0;">Start Date</label>
                                            @if($isActive)
                                                <span style="font-size: 0.65rem; background: #10b981; color: white; padding: 1px 6px; border-radius: 9999px; font-weight: bold; text-transform: uppercase;">Active</span>
                                            @elseif($isPast)
                                                <span style="font-size: 0.65rem; background: #64748b; color: white; padding: 1px 6px; border-radius: 9999px; font-weight: bold; text-transform: uppercase;">Completed</span>
                                            @endif
                                        </div>
                                        <input type="date" name="fees[aof][active_periods][start][]" class="form-control" value="{{ $start }}" @if($isPast) readonly @endif>
                                    </div>
                                    <div style="color:var(--text-muted); margin-bottom: 12px; align-self: flex-end; padding: 0 0.5rem;">to</div>
                                    <div style="flex:1">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">End Date</label>
                                        <input type="date" name="fees[aof][active_periods][end][]" class="form-control" value="{{ $end }}" @if($isPast) readonly @endif>
                                    </div>
                                    <div style="flex:0.8">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">AOF Amount (₱)</label>
                                        <input type="number" step="0.01" name="fees[aof][active_periods][amount][]" class="form-control" value="{{ $period['amount'] ?? 2000 }}" @if($isPast) readonly @endif>
                                    </div>
                                    
                                    @if($isPast)
                                        <div style="width: 82px; height: 42px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            Locked
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444; height: 42px;">Remove</button>
                                    @endif
                                </div>
                            @empty
                                <div class="peak-period-row" style="align-items: flex-end;">
                                    <div style="flex:1">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Start Date</label>
                                        <input type="date" name="fees[aof][active_periods][start][]" class="form-control">
                                    </div>
                                    <div style="color:var(--text-muted); margin-bottom: 12px; align-self: flex-end; padding: 0 0.5rem;">to</div>
                                    <div style="flex:1">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">End Date</label>
                                        <input type="date" name="fees[aof][active_periods][end][]" class="form-control">
                                    </div>
                                    <div style="flex:0.8">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">AOF Amount (₱)</label>
                                        <input type="number" step="0.01" name="fees[aof][active_periods][amount][]" class="form-control" value="2000">
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444; height: 42px;">Remove</button>
                                </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addAofPeriod()" style="margin-top: 0.75rem; background: rgba(255,255,255,0.1)">+ Add AOF Period</button>
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
                <button type="submit" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2.5rem; background: var(--success); font-weight: 600; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                    Save Balesin Island Configuration
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANE 2: BALESIN CITY SETUP & CONFIGURATION                            --}}
    {{-- ========================================================================= --}}
    <div id="tab-pane-city" class="property-tab-pane {{ ($activeTab ?? 'island') === 'city' ? 'active' : '' }}">
        <form action="{{ url('/setup') }}" method="POST">
            @csrf
            <input type="hidden" name="property" value="city">

            <!-- City Base Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-city-rates')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Base Accommodation Rates</h3>
                <div id="help-city-rates" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-city-rates')">&times;</span>
                    <strong>Balesin City Rates</strong>
                    <div>Configure base nightly accommodation rates for Balesin City suites:
                    <br>• <strong>Studio-Type Suite:</strong> Standard Member and Guest rates (Default: ₱6,400 Member).
                    <br>• <strong>Two-Bedroom Suite:</strong> Standard Member and Guest rates (Default: ₱9,250 Member).</div>
                </div>
                <div class="grid-2">
                    <div class="form-group" style="padding: 1.25rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <div style="font-size: 1rem; color: #fff; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏢</span> Studio-Type Suite
                        </div>
                        <div class="grid-2">
                            <div>
                                <label class="form-label">Member Rate (₱)</label>
                                <input type="number" step="0.01" name="city[rates][studio_member]" class="form-control" value="{{ $settings['city']['rates']['studio_member'] ?? 6400 }}">
                            </div>
                            <div>
                                <label class="form-label">Guest Rate (₱)</label>
                                <input type="number" step="0.01" name="city[rates][studio_guest]" class="form-control" value="{{ $settings['city']['rates']['studio_guest'] ?? 8000 }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="padding: 1.25rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <div style="font-size: 1rem; color: #fff; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏙️</span> Two-Bedroom Suite
                        </div>
                        <div class="grid-2">
                            <div>
                                <label class="form-label">Member Rate (₱)</label>
                                <input type="number" step="0.01" name="city[rates][twobed_member]" class="form-control" value="{{ $settings['city']['rates']['twobed_member'] ?? 9250 }}">
                            </div>
                            <div>
                                <label class="form-label">Guest Rate (₱)</label>
                                <input type="number" step="0.01" name="city[rates][twobed_guest]" class="form-control" value="{{ $settings['city']['rates']['twobed_guest'] ?? 12500 }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- City Occupancy & Extra Person Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-city-occupancy')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Occupancy &amp; Extra Person Charges</h3>
                <div id="help-city-occupancy" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-city-occupancy')">&times;</span>
                    <strong>City Occupancy Rules</strong>
                    <div>Define base capacity thresholds and extra person charges:
                    <br>• <strong>Studio-Type Suite:</strong> Base capacity is 4 persons (5th+ person is charged extra person rate).
                    <br>• <strong>Two-Bedroom Suite:</strong> Base capacity is 6 persons (7th+ person is charged extra person rate).
                    <br>• <strong>Extra Person Rate:</strong> Flat surcharge per night for additional occupants (Default: ₱1,300).</div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Extra Person Nightly Rate (₱)</label>
                        <input type="number" step="0.01" name="city[extra_person][rate]" class="form-control" value="{{ $settings['city']['extra_person']['rate'] ?? 1300 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Billed for extra occupants beyond base capacity.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Studio-Type Base Occupancy (Pax)</label>
                        <input type="number" name="city[extra_person][studio_base_pax]" class="form-control" value="{{ $settings['city']['extra_person']['studio_base_pax'] ?? 4 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Standard: 4 occupants (5th+ is extra).</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Two-Bedroom Base Occupancy (Pax)</label>
                        <input type="number" name="city[extra_person][twobed_base_pax]" class="form-control" value="{{ $settings['city']['extra_person']['twobed_base_pax'] ?? 6 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Standard: 6 occupants (7th+ is extra).</div>
                    </div>
                </div>
            </div>

            <!-- City Discounts & VAT -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-city-discounts')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Discounts &amp; VAT</h3>
                <div id="help-city-discounts" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-city-discounts')">&times;</span>
                    <strong>City Discounts &amp; VAT</strong>
                    <div>Manage tax exemption and Senior Citizen / PWD percentage discount calculations for Balesin City accommodation bookings.</div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Standard VAT Rate (%)</label>
                        <input type="number" step="0.1" name="city[discounts][vat_rate]" class="form-control" value="{{ $settings['city']['discounts']['vat_rate'] ?? 12 }}">
                    </div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="city_remove_vat" name="city[discounts][sc_pwd][remove_vat]" {{ !empty($settings['city']['discounts']['sc_pwd']['remove_vat']) ? 'checked' : '' }}>
                    <label for="city_remove_vat" class="form-label" style="margin:0">Remove VAT for Senior Citizens / PWDs</label>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="city_apply_sc_pwd_discount" name="city[discounts][sc_pwd][apply_discount]" {{ !isset($settings['city']['discounts']['sc_pwd']['apply_discount']) || !empty($settings['city']['discounts']['sc_pwd']['apply_discount']) ? 'checked' : '' }}>
                    <label for="city_apply_sc_pwd_discount" class="form-label" style="margin:0">Apply Senior Citizen / PWD Percentage Discount in Computation</label>
                </div>
                <div class="form-group" style="margin-top: 0.75rem; max-width: 50%;">
                    <label class="form-label">Senior / PWD Discount Percentage (%) [Default: 20%]</label>
                    <input type="number" step="0.1" name="city[discounts][sc_pwd][additional_discount_percent]" class="form-control" value="{{ $settings['city']['discounts']['sc_pwd']['additional_discount_percent'] ?? 20 }}">
                </div>
            </div>

            <!-- City Promo & Peak Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-city-promo')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Promo Rates &amp; Peak Periods</h3>
                <div id="help-city-promo" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-city-promo')">&times;</span>
                    <strong>City Promo Rates</strong>
                    <div>Set temporary promotional rates for City accommodation during specified promo dates.
                    <br>• Dates added under Peak Periods automatically revert to Base Rates.</div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 1.5rem;">
                    <input type="checkbox" id="city_promo_active" name="city[promo_rates][active]" {{ !empty($settings['city']['promo_rates']['active']) ? 'checked' : '' }}>
                    <label for="city_promo_active" class="form-label" style="margin:0">Enable Balesin City Promo Rates</label>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Promo Description</label>
                    <textarea name="city[promo_rates][description]" class="form-control" rows="2" placeholder="e.g. City Summer Getaway Promo">{{ $settings['city']['promo_rates']['description'] ?? '' }}</textarea>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label class="form-label">Promo Studio Member (₱)</label>
                        <input type="number" step="0.01" name="city[promo_rates][studio_member]" class="form-control" value="{{ $settings['city']['promo_rates']['studio_member'] ?? 5500 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo Studio Guest (₱)</label>
                        <input type="number" step="0.01" name="city[promo_rates][studio_guest]" class="form-control" value="{{ $settings['city']['promo_rates']['studio_guest'] ?? 7000 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo 2-BR Member (₱)</label>
                        <input type="number" step="0.01" name="city[promo_rates][twobed_member]" class="form-control" value="{{ $settings['city']['promo_rates']['twobed_member'] ?? 8000 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo 2-BR Guest (₱)</label>
                        <input type="number" step="0.01" name="city[promo_rates][twobed_guest]" class="form-control" value="{{ $settings['city']['promo_rates']['twobed_guest'] ?? 11000 }}">
                    </div>
                </div>

                <div class="grid-2" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Promo Start Date</label>
                        <input type="date" name="city[promo_rates][start_date]" class="form-control" value="{{ $settings['city']['promo_rates']['start_date'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo End Date</label>
                        <input type="date" name="city[promo_rates][end_date]" class="form-control" value="{{ $settings['city']['promo_rates']['end_date'] ?? '' }}">
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <label class="form-label">City Peak Periods (Promo rates do not apply during these dates)</label>
                    <div id="city-peak-periods-container">
                        @forelse($settings['city']['promo_rates']['peak_periods'] ?? [] as $period)
                            <div class="peak-period-row">
                                <div style="flex:1.5">
                                    <input type="text" name="city[peak_periods][label][]" class="form-control" placeholder="Description (e.g. Long Weekend)" value="{{ $period['label'] ?? '' }}">
                                </div>
                                <div style="flex:1">
                                    <input type="date" name="city[peak_periods][start][]" class="form-control" value="{{ $period['start'] }}">
                                </div>
                                <div style="color:var(--text-muted)">to</div>
                                <div style="flex:1">
                                    <input type="date" name="city[peak_periods][end][]" class="form-control" value="{{ $period['end'] }}">
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                            </div>
                        @empty
                            <div class="peak-period-row">
                                <div style="flex:1.5"><input type="text" name="city[peak_periods][label][]" class="form-control" placeholder="Description"></div>
                                <div style="flex:1"><input type="date" name="city[peak_periods][start][]" class="form-control"></div>
                                <div style="color:var(--text-muted)">to</div>
                                <div style="flex:1"><input type="date" name="city[peak_periods][end][]" class="form-control"></div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addPeakPeriodCity()" style="margin-top:0.5rem; background: rgba(255,255,255,0.1)">+ Add Peak Period</button>
                </div>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2.5rem; background: var(--success); font-weight: 600; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                    Save Balesin City Configuration
                </button>
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- TAB PANE 3: BALESIN PINES SETUP & CONFIGURATION                           --}}
    {{-- ========================================================================= --}}
    <div id="tab-pane-pines" class="property-tab-pane {{ ($activeTab ?? 'island') === 'pines' ? 'active' : '' }}">
        <form action="{{ url('/setup') }}" method="POST">
            @csrf
            <input type="hidden" name="property" value="pines">

            <!-- Pines Base Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-pines-rates')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Deluxe Room &amp; Junior Suite Rates</h3>
                <div id="help-pines-rates" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-pines-rates')">&times;</span>
                    <strong>Balesin Pines Rates</strong>
                    <div>Configure published base nightly rates for Balesin Pines:
                    <br>• <strong>Deluxe Room:</strong> Published MRATE (Member) and GRATE (Guest).
                    <br>• <strong>Junior Suite:</strong> Published MRATE (Member) and GRATE (Guest).
                    <br>Rates are divided among base occupants (up to 2 pax) for individual folio shares.</div>
                </div>
                <div class="grid-2">
                    <div class="form-group" style="padding: 1.25rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <div style="font-size: 1rem; color: #fff; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🌲</span> Deluxe Room
                        </div>
                        <div class="grid-2">
                            <div>
                                <label class="form-label">Member Rate (MRATE) (₱)</label>
                                <input type="number" step="0.01" name="pines[rates][deluxe_member]" class="form-control" value="{{ $settings['pines']['rates']['deluxe_member'] ?? 10000 }}">
                            </div>
                            <div>
                                <label class="form-label">Guest Rate (GRATE) (₱)</label>
                                <input type="number" step="0.01" name="pines[rates][deluxe_guest]" class="form-control" value="{{ $settings['pines']['rates']['deluxe_guest'] ?? 14500 }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="padding: 1.25rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 0.75rem;">
                        <div style="font-size: 1rem; color: #fff; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏡</span> Junior Suite
                        </div>
                        <div class="grid-2">
                            <div>
                                <label class="form-label">Member Rate (MRATE) (₱)</label>
                                <input type="number" step="0.01" name="pines[rates][suite_member]" class="form-control" value="{{ $settings['pines']['rates']['suite_member'] ?? 15000 }}">
                            </div>
                            <div>
                                <label class="form-label">Guest Rate (GRATE) (₱)</label>
                                <input type="number" step="0.01" name="pines[rates][suite_guest]" class="form-control" value="{{ $settings['pines']['rates']['suite_guest'] ?? 21500 }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pines Extra Person & Capacity Rules -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-pines-occupancy')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Extra Occupancy &amp; Member Limits</h3>
                <div id="help-pines-occupancy" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-pines-occupancy')">&times;</span>
                    <strong>Pines Occupancy Rules</strong>
                    <div>Key business rules for Balesin Pines:
                    <br>• Deluxe Rooms &amp; Suites are designed for a maximum of 2 adults.
                    <br>• Third occupant (3rd+ pax) is billed the flat Extra Person Charge (Default: ₱3,700/night).
                    <br>• Maximum Units at Member Rate: Up to 3 rooms/suites per member. Any unit beyond this limit automatically reverts to the Guest Rate.</div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Extra Person Charge (3rd+ Pax) (₱)</label>
                        <input type="number" step="0.01" name="pines[extra_person][charge]" class="form-control" value="{{ $settings['pines']['extra_person']['charge'] ?? 3700 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Billed per night for the 3rd occupant.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base Occupancy (Pax)</label>
                        <input type="number" name="pines[extra_person][base_pax]" class="form-control" value="{{ $settings['pines']['extra_person']['base_pax'] ?? 2 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">Standard capacity (2 adults per room/suite).</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Member Rate Units / Member</label>
                        <input type="number" name="pines[rules][max_member_units]" class="form-control" value="{{ $settings['pines']['rules']['max_member_units'] ?? 3 }}">
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">4th unit onwards billed at Guest Rate.</div>
                    </div>
                </div>
            </div>

            <!-- Pines Free Villa Night (FVN) Rules -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-pines-fvn')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Free Villa Night (FVN) Conversion Rules</h3>
                <div id="help-pines-fvn" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-pines-fvn')">&times;</span>
                    <strong>Pines FVN Policies</strong>
                    <div>Configure behavior when FVN coupons/credits are applied at Balesin Pines:
                    <br>• <strong>Junior Suite FVN Fallback:</strong> If 0.5 or 1.0 FVN is applied to a Junior Suite, fallback accommodation calculates at the Deluxe Room Member Rate (₱10,000) rather than the Suite rate.
                    <br>• <strong>Extra Person FVN Policy:</strong> Extra occupants never receive free accommodation under FVN and are always charged the standard extra person fee (₱3,700).</div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.85rem;">
                    <input type="checkbox" id="suite_fvn_fallback" name="pines[rules][suite_fvn_fallback]" {{ !isset($settings['pines']['rules']['suite_fvn_fallback']) || !empty($settings['pines']['rules']['suite_fvn_fallback']) ? 'checked' : '' }}>
                    <label for="suite_fvn_fallback" class="form-label" style="margin:0">Enable Junior Suite Fallback to Deluxe Member Rate (₱10,000) on 0.5/1.0 FVN</label>
                </div>
                <div class="checkbox-wrap">
                    <input type="checkbox" id="extra_person_fvn_charge" name="pines[rules][extra_person_fvn_charge]" {{ !isset($settings['pines']['rules']['extra_person_fvn_charge']) || !empty($settings['pines']['rules']['extra_person_fvn_charge']) ? 'checked' : '' }}>
                    <label for="extra_person_fvn_charge" class="form-label" style="margin:0">Always Enforce Extra Person Charge (₱3,700) on FVN Reservations</label>
                </div>
            </div>

            <!-- Pines Discounts & VAT -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-pines-discounts')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Discounts &amp; VAT</h3>
                <div id="help-pines-discounts" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-pines-discounts')">&times;</span>
                    <strong>Pines Discounts &amp; VAT</strong>
                    <div>Manage tax exemption and Senior Citizen / PWD percentage discount calculations for Balesin Pines base occupants and extra persons.</div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Standard VAT Rate (%)</label>
                        <input type="number" step="0.1" name="pines[discounts][vat_rate]" class="form-control" value="{{ $settings['pines']['discounts']['vat_rate'] ?? 12 }}">
                    </div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="pines_remove_vat" name="pines[discounts][sc_pwd][remove_vat]" {{ !empty($settings['pines']['discounts']['sc_pwd']['remove_vat']) ? 'checked' : '' }}>
                    <label for="pines_remove_vat" class="form-label" style="margin:0">Remove VAT for Senior Citizens / PWDs</label>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 0.75rem;">
                    <input type="checkbox" id="pines_apply_sc_pwd_discount" name="pines[discounts][sc_pwd][apply_discount]" {{ !isset($settings['pines']['discounts']['sc_pwd']['apply_discount']) || !empty($settings['pines']['discounts']['sc_pwd']['apply_discount']) ? 'checked' : '' }}>
                    <label for="pines_apply_sc_pwd_discount" class="form-label" style="margin:0">Apply Senior Citizen / PWD Percentage Discount in Computation</label>
                </div>
                <div class="form-group" style="margin-top: 0.75rem; max-width: 50%;">
                    <label class="form-label">Senior / PWD Discount Percentage (%) [Default: 20%]</label>
                    <input type="number" step="0.1" name="pines[discounts][sc_pwd][additional_discount_percent]" class="form-control" value="{{ $settings['pines']['discounts']['sc_pwd']['additional_discount_percent'] ?? 20 }}">
                </div>
            </div>

            <!-- Pines Promo & Peak Rates -->
            <div class="setup-section">
                <div class="info-btn" onclick="toggleHelp('help-pines-promo')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </div>
                <h3 class="setup-title">Promo Rates &amp; Peak Periods</h3>
                <div id="help-pines-promo" class="help-box">
                    <span class="help-close" onclick="toggleHelp('help-pines-promo')">&times;</span>
                    <strong>Pines Promo Rates</strong>
                    <div>Set temporary promotional rates for Pines rooms during specified promotion dates.
                    <br>• Dates added under Peak Periods automatically revert to Base Rates.</div>
                </div>
                <div class="checkbox-wrap" style="margin-bottom: 1.5rem;">
                    <input type="checkbox" id="pines_promo_active" name="pines[promo_rates][active]" {{ !empty($settings['pines']['promo_rates']['active']) ? 'checked' : '' }}>
                    <label for="pines_promo_active" class="form-label" style="margin:0">Enable Balesin Pines Promo Rates</label>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Promo Description</label>
                    <textarea name="pines[promo_rates][description]" class="form-control" rows="2" placeholder="e.g. Pines Highland Escape Promo">{{ $settings['pines']['promo_rates']['description'] ?? '' }}</textarea>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label class="form-label">Promo Deluxe Member (₱)</label>
                        <input type="number" step="0.01" name="pines[promo_rates][deluxe_member]" class="form-control" value="{{ $settings['pines']['promo_rates']['deluxe_member'] ?? 8500 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo Deluxe Guest (₱)</label>
                        <input type="number" step="0.01" name="pines[promo_rates][deluxe_guest]" class="form-control" value="{{ $settings['pines']['promo_rates']['deluxe_guest'] ?? 12500 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo Suite Member (₱)</label>
                        <input type="number" step="0.01" name="pines[promo_rates][suite_member]" class="form-control" value="{{ $settings['pines']['promo_rates']['suite_member'] ?? 13000 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo Suite Guest (₱)</label>
                        <input type="number" step="0.01" name="pines[promo_rates][suite_guest]" class="form-control" value="{{ $settings['pines']['promo_rates']['suite_guest'] ?? 18500 }}">
                    </div>
                </div>

                <div class="grid-2" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Promo Start Date</label>
                        <input type="date" name="pines[promo_rates][start_date]" class="form-control" value="{{ $settings['pines']['promo_rates']['start_date'] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Promo End Date</label>
                        <input type="date" name="pines[promo_rates][end_date]" class="form-control" value="{{ $settings['pines']['promo_rates']['end_date'] ?? '' }}">
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <label class="form-label">Pines Peak Periods (Promo rates do not apply during these dates)</label>
                    <div id="pines-peak-periods-container">
                        @forelse($settings['pines']['promo_rates']['peak_periods'] ?? [] as $period)
                            <div class="peak-period-row">
                                <div style="flex:1.5">
                                    <input type="text" name="pines[peak_periods][label][]" class="form-control" placeholder="Description (e.g. Christmas / New Year)" value="{{ $period['label'] ?? '' }}">
                                </div>
                                <div style="flex:1">
                                    <input type="date" name="pines[peak_periods][start][]" class="form-control" value="{{ $period['start'] }}">
                                </div>
                                <div style="color:var(--text-muted)">to</div>
                                <div style="flex:1">
                                    <input type="date" name="pines[peak_periods][end][]" class="form-control" value="{{ $period['end'] }}">
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                            </div>
                        @empty
                            <div class="peak-period-row">
                                <div style="flex:1.5"><input type="text" name="pines[peak_periods][label][]" class="form-control" placeholder="Description"></div>
                                <div style="flex:1"><input type="date" name="pines[peak_periods][start][]" class="form-control"></div>
                                <div style="color:var(--text-muted)">to</div>
                                <div style="flex:1"><input type="date" name="pines[peak_periods][end][]" class="form-control"></div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addPeakPeriodPines()" style="margin-top:0.5rem; background: rgba(255,255,255,0.1)">+ Add Peak Period</button>
                </div>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2.5rem; background: var(--success); font-weight: 600; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                    Save Balesin Pines Configuration
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchTab(property) {
        // Remove active class from all tabs
        document.querySelectorAll('.property-nav-tab').forEach(t => t.classList.remove('active'));
        
        // Hide all panes
        document.querySelectorAll('.property-tab-pane').forEach(p => {
            p.classList.remove('active');
            p.style.display = 'none';
        });

        // Activate button
        const activeBtn = document.getElementById('tab-btn-' + property);
        if (activeBtn) activeBtn.classList.add('active');

        // Show active pane
        const activePane = document.getElementById('tab-pane-' + property);
        if (activePane) {
            activePane.classList.add('active');
            activePane.style.display = 'block';
        }

        // Toggle Island Export button
        const exportBtn = document.getElementById('islandExportBtn');
        if (exportBtn) {
            exportBtn.style.display = (property === 'island') ? 'inline-block' : 'none';
        }

        // Update URL query parameter without full reload
        try {
            const url = new URL(window.location);
            url.searchParams.set('property', property);
            window.history.replaceState({}, '', url);
        } catch(e) {}
    }

    function addPeakPeriodIsland() {
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

    function addPeakPeriodCity() {
        const container = document.getElementById('city-peak-periods-container');
        const row = document.createElement('div');
        row.className = 'peak-period-row';
        row.innerHTML = `
            <div style="flex:1.5"><input type="text" name="city[peak_periods][label][]" class="form-control" placeholder="Description"></div>
            <div style="flex:1"><input type="date" name="city[peak_periods][start][]" class="form-control"></div>
            <div style="color:var(--text-muted)">to</div>
            <div style="flex:1"><input type="date" name="city[peak_periods][end][]" class="form-control"></div>
            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
        `;
        container.appendChild(row);
    }

    function addPeakPeriodPines() {
        const container = document.getElementById('pines-peak-periods-container');
        const row = document.createElement('div');
        row.className = 'peak-period-row';
        row.innerHTML = `
            <div style="flex:1.5"><input type="text" name="pines[peak_periods][label][]" class="form-control" placeholder="Description"></div>
            <div style="flex:1"><input type="date" name="pines[peak_periods][start][]" class="form-control"></div>
            <div style="color:var(--text-muted)">to</div>
            <div style="flex:1"><input type="date" name="pines[peak_periods][end][]" class="form-control"></div>
            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444">Remove</button>
        `;
        container.appendChild(row);
    }

    function addAofPeriod() {
        const container = document.getElementById('aof-periods-container');
        const row = document.createElement('div');
        row.className = 'peak-period-row';
        row.style.alignItems = 'flex-end';
        row.innerHTML = `
            <div style="flex:1">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Start Date</label>
                <input type="date" name="fees[aof][active_periods][start][]" class="form-control">
            </div>
            <div style="color:var(--text-muted); margin-bottom: 12px; align-self: flex-end; padding: 0 0.5rem;">to</div>
            <div style="flex:1">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">End Date</label>
                <input type="date" name="fees[aof][active_periods][end][]" class="form-control">
            </div>
            <div style="flex:0.8">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">AOF Amount (₱)</label>
                <input type="number" step="0.01" name="fees[aof][active_periods][amount][]" class="form-control" value="2000">
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="this.parentElement.remove()" style="background: #ef4444; height: 42px;">Remove</button>
        `;
        container.appendChild(row);
    }

    function toggleHelp(id) {
        const el = document.getElementById(id);
        if (!el) return;
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

    // Initialize active tab on load
    document.addEventListener('DOMContentLoaded', function() {
        const currentActive = "{{ $activeTab ?? 'island' }}";
        switchTab(currentActive);
    });
</script>
@endsection
