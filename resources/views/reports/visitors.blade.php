@extends('layouts.app')
@section('title', 'Visitor Analytics')
@section('page-title', 'Visitor Analytics')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.sales') }}">Reports</a></li>
<li class="breadcrumb-item active">Visitor Analytics</li>
@endsection

@push('styles')
<style>
:root {
    --an-blue:   #0088cc;
    --an-green:  #10b981;
    --an-amber:  #f59e0b;
    --an-purple: #8b5cf6;
    --an-teal:   #14b8a6;
    --an-red:    #ef4444;
}
.visitor-kpi-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--card-border);
    padding: 1.25rem 1.4rem;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    transition: transform .2s, box-shadow .2s;
    position: relative;
    overflow: hidden;
    height: 100%;
}
.visitor-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.08);
}
.visitor-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    background: var(--kpi-color, var(--an-blue));
}
.kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.kpi-label {
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-secondary);
}
.kpi-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.2;
}

.an-card {
    background: #fff;
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 1.3rem;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    height: 100%;
}
.an-section-title {
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--text-secondary);
    padding-bottom: .6rem;
    border-bottom: 2px solid var(--card-border);
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.an-section-title i {
    font-size: 1rem;
}

.progress-bar-wrap {
    background: #f1f5f9;
    border-radius: 20px;
    height: 8px;
    overflow: hidden;
    margin-top: 6px;
}
.progress-bar-fill {
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(90deg, var(--an-blue), #00b0ff);
}

.badge-method-get {
    background: rgba(0, 136, 204, 0.1);
    color: var(--an-blue);
    border: 1px solid rgba(0, 136, 204, 0.25);
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}
.badge-method-post {
    background: rgba(16, 185, 129, 0.1);
    color: var(--an-green);
    border: 1px solid rgba(16, 185, 129, 0.25);
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}
.badge-user-account {
    background: rgba(0, 136, 204, 0.08);
    color: var(--an-blue);
    border: 1px solid rgba(0, 136, 204, 0.18);
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-guest {
    color: var(--text-secondary);
    font-size: 0.75rem;
}

.stat-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.55rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.stat-list-item:last-child {
    border-bottom: none;
}
.stat-list-val {
    font-weight: 700;
    color: var(--text-primary);
}
</style>
@endpush

@section('content')
{{-- KPI CARDS ROW --}}
<div class="row g-3 mb-4">
    @php
    $kpis = [
        [
            'label' => 'Total Page Views',
            'value' => number_format($totalPageViews),
            'icon'  => 'bi-eye',
            'color' => 'var(--an-blue)',
            'bg'    => 'rgba(0, 136, 204, 0.1)'
        ],
        [
            'label' => 'Unique Visitors',
            'value' => number_format($uniqueVisitors),
            'icon'  => 'bi-people',
            'color' => 'var(--an-green)',
            'bg'    => 'rgba(16, 185, 129, 0.1)'
        ],
        [
            'label' => 'Top Device',
            'value' => $topDevice ?: 'Desktop',
            'icon'  => 'bi-laptop',
            'color' => 'var(--an-amber)',
            'bg'    => 'rgba(245, 158, 11, 0.1)'
        ],
        [
            'label' => 'Top Country',
            'value' => $topCountry ?: 'Tanzania',
            'icon'  => 'bi-globe',
            'color' => 'var(--an-purple)',
            'bg'    => 'rgba(139, 92, 246, 0.1)'
        ],
    ];
    @endphp

    @foreach($kpis as $kpi)
    <div class="col-6 col-md-3">
        <div class="visitor-kpi-card" style="--kpi-color: {{ $kpi['color'] }}">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-label mb-1">{{ $kpi['label'] }}</div>
                    <div class="kpi-value">{{ $kpi['value'] }}</div>
                </div>
                <div class="kpi-icon" style="background: {{ $kpi['bg'] }}; color: {{ $kpi['color'] }};">
                    <i class="bi {{ $kpi['icon'] }}"></i>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- MIDDLE DETAILS SECTION --}}
<div class="row g-3 mb-4">
    {{-- Left: Top Locations --}}
    <div class="col-lg-6">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-geo-alt-fill text-primary"></i> Top Visitor Locations
            </div>
            <div class="location-list">
                @php $maxHits = $locations->max('hits') ?: 1; @endphp
                @forelse($locations as $loc)
                    @php $perc = $totalPageViews > 0 ? ($loc->hits / $totalPageViews) * 100 : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: .78rem;">
                            <span style="font-weight: 600;">
                                <i class="bi bi-geo-alt text-muted me-1"></i>
                                {{ $loc->city ?: 'Unknown' }}, {{ $loc->country ?: 'Unknown' }}
                            </span>
                            <span class="text-secondary" style="font-size: .75rem; font-weight: 600;">
                                {{ number_format($loc->hits) }} hits ({{ number_format($perc, 1) }}%)
                            </span>
                        </div>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" style="width: {{ $perc }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4" style="font-size: .8rem;">No location records found.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right: Device & Browser Stats --}}
    <div class="col-lg-6">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-laptop-fill text-success"></i> Device & Browser Stats
            </div>
            <div class="row">
                {{-- Devices column --}}
                <div class="col-md-5 border-end">
                    <h6 class="text-uppercase text-secondary font-weight-bold mb-3" style="font-size: .65rem; letter-spacing: .06em;">Devices</h6>
                    @forelse($deviceStats as $dStat)
                        <div class="stat-list-item">
                            <span style="font-size: .78rem; font-weight: 600;">
                                @if(strtolower($dStat->device_type) === 'mobile')
                                    <i class="bi bi-phone text-muted me-1"></i>
                                @else
                                    <i class="bi bi-laptop text-muted me-1"></i>
                                @endif
                                {{ $dStat->device_type }}
                            </span>
                            <span class="stat-list-val" style="font-size: .78rem;">{{ number_format($dStat->count) }}</span>
                        </div>
                    @empty
                        <p class="text-muted" style="font-size: .75rem;">No devices.</p>
                    @endforelse
                </div>

                {{-- Browsers column --}}
                <div class="col-md-7 ps-md-4">
                    <h6 class="text-uppercase text-secondary font-weight-bold mb-3" style="font-size: .65rem; letter-spacing: .06em;">Top Browsers</h6>
                    @forelse($browserStats as $bStat)
                        <div class="stat-list-item">
                            <span style="font-size: .78rem; font-weight: 600;">
                                @if(strtolower($bStat->browser) === 'chrome')
                                    <i class="bi bi-chrome text-danger me-1"></i>
                                @elseif(strtolower($bStat->browser) === 'edge')
                                    <i class="bi bi-edge text-primary me-1"></i>
                                @elseif(strtolower($bStat->browser) === 'safari')
                                    <i class="bi bi-compass text-info me-1"></i>
                                @elseif(strtolower($bStat->browser) === 'firefox')
                                    <i class="bi bi-browser-firefox text-warning me-1"></i>
                                @else
                                    <i class="bi bi-question-circle text-muted me-1"></i>
                                @endif
                                {{ $bStat->browser }}
                            </span>
                            <span class="stat-list-val" style="font-size: .78rem;">{{ number_format($bStat->count) }}</span>
                        </div>
                    @empty
                        <p class="text-muted" style="font-size: .75rem;">No browsers.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- VISITOR LOG TABLE SECTION --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-card-list me-2" style="color: var(--an-blue);"></i>Visitor Request Log (Last 1000 hits)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="visitorLogTable">
                <thead>
                    <tr>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">Time</th>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">IP Address</th>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">Location</th>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">Device / Browser</th>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">Request</th>
                        <th style="font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;">User Account</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#visitorLogTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: "{{ route('reports.visitors.data') }}",
        columns: [
            { data: 'time', name: 'time' },
            { data: 'ip', name: 'ip' },
            { data: 'location', name: 'location' },
            { data: 'device', name: 'device' },
            { data: 'request', name: 'request' },
            { data: 'user', name: 'user' }
        ],
        order: [[0, 'desc']],
        dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom"lf>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries"
        }
    });
});
</script>
@endpush
