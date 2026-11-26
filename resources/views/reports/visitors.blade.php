@extends('layouts.app')
@section('title', 'Visitor Analytics')
@section('page-title', 'Visitor Analytics')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.sales') }}">Reports</a></li>
<li class="breadcrumb-item active">Visitor Analytics</li>
@endsection

@push('styles')
@include('reports._styles')
<style>
:root {
    --an-blue:   #0077b6;
    --an-green:  #059669;
    --an-amber:  #d97706;
    --an-purple: #7c3aed;
    --an-teal:   #0d9488;
    --an-red:    #dc2626;
}
.an-card {
    background: #fff;
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: 1.15rem 1.3rem;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    height: 100%;
}
.an-section-title {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-secondary);
    padding-bottom: .55rem;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 1.1rem;
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
    background: rgba(0, 119, 182, 0.1);
    color: var(--an-blue);
    border: 1px solid rgba(0, 119, 182, 0.25);
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
    background: rgba(0, 119, 182, 0.08);
    color: var(--an-blue);
    border: 1px solid rgba(0, 119, 182, 0.18);
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
{{-- Compact Header --}}
<div class="rpt-header">
    <div class="rpt-title-row">
        <h5 class="rpt-title">
            <i class="bi bi-globe text-primary me-1"></i>Visitor Analytics
        </h5>
        <span class="rpt-pill">
            <i class="bi bi-activity"></i>Traffic &amp; Devices
        </span>
    </div>
</div>

{{-- Executive KPI Grid --}}
@php
$kpis = [
    [
        'label' => 'Page Views',
        'value' => number_format($totalPageViews),
        'sub'   => 'Total recorded hits',
        'icon'  => 'bi-eye',
        'color' => '#0077b6',
        'bg'    => 'rgba(0, 119, 182, 0.1)'
    ],
    [
        'label' => 'Unique Visitors',
        'value' => number_format($uniqueVisitors),
        'sub'   => 'Distinct IP addresses',
        'icon'  => 'bi-people',
        'color' => '#059669',
        'bg'    => 'rgba(16, 185, 129, 0.12)'
    ],
    [
        'label' => 'Top Device',
        'value' => $topDevice ?: 'Desktop',
        'sub'   => 'Most common platform',
        'icon'  => 'bi-laptop',
        'color' => '#d97706',
        'bg'    => 'rgba(217, 119, 6, 0.12)'
    ],
    [
        'label' => 'Top Country',
        'value' => $topCountry ?: 'Tanzania',
        'sub'   => 'Primary visitor region',
        'icon'  => 'bi-globe',
        'color' => '#7c3aed',
        'bg'    => 'rgba(124, 58, 237, 0.12)'
    ],
];
@endphp

<div class="rpt-kpi-grid rpt-kpi-grid-4">
    @foreach($kpis as $kpi)
    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:{{ $kpi['bg'] }};color:{{ $kpi['color'] }};">
            <i class="bi {{ $kpi['icon'] }}"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">{{ $kpi['label'] }}</p>
            <div class="rpt-kpi-val" style="color:{{ $kpi['color'] }};">{{ $kpi['value'] }}</div>
            <div class="rpt-kpi-sub">{{ $kpi['sub'] }}</div>
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
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center"><i class="bi bi-card-list me-2 text-accent"></i><strong class="fw-700">Visitor Request Log</strong> <span class="badge badge-soft-secondary ms-2 small">Last 1000 hits</span></div>
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="visitorLogTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all compact-lead-col">Time</th>
                    <th data-priority="3" class="min-tablet-l">IP Address</th>
                    <th data-priority="2" class="min-tablet-l">Location</th>
                    <th data-priority="3" class="min-tablet-l">Device / Browser</th>
                    <th data-priority="2" class="min-tablet-l">Request</th>
                    <th data-priority="1" class="all primary-col">User Account</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
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
