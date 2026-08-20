@extends('layouts.app')
@section('title', 'User Activity Logs')
@section('page-title', 'User Activity Logs')
@section('breadcrumb')
<li class="breadcrumb-item active">Activity Logs</li>
@endsection
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <p class="text-secondary mb-0 small">Track system modifications, configurations, and administrative actions.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" id="btnExportExcel" class="btn btn-sm btn-success px-3">
            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
        </button>
        <button type="button" id="btnExportPdf" class="btn btn-sm btn-danger px-3" style="background-color: #dc3545; border-color: #dc3545;">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-center">
            @if($users->isNotEmpty())
            <div class="col-md-4 col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="background:var(--card-bg); border-color:var(--card-border); color:var(--text-secondary);">
                        <i class="bi bi-person"></i>
                    </span>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            <div class="col-md-4 col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="background:var(--card-bg); border-color:var(--card-border); color:var(--text-secondary);">
                        <i class="bi bi-clock"></i>
                    </span>
                    <select name="timeframe" class="form-select form-select-sm">
                        <option value="all" {{ request('timeframe') === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ request('timeframe') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('timeframe') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="last_7_days" {{ request('timeframe') === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last_30_days" {{ request('timeframe') === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ request('timeframe') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('timeframe') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <button type="submit" class="btn btn-sm btn-accent w-100">
                    <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-activity me-2" style="color:var(--accent-color);"></i>System Activity Timeline (Last 1000 logs)</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="activityLogsTable">
            <thead>
                <tr>
                    <th style="width: 150px;">Time</th>
                    <th style="width: 180px;">User</th>
                    <th style="width: 120px;">Action</th>
                    <th>Activity Details</th>
                    <th style="width: 250px;">Origin Info</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>
                        <div class="fw-600" style="font-size: .82rem;">
                            {{ $log->created_at->format('M d, H:i:s') }}
                        </div>
                        <div class="small text-muted" style="font-size: .73rem;">
                            {{ $log->created_at->diffForHumans() }}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $initials = collect(explode(' ', $log->user ? $log->user->name : 'System'))
                                    ->map(fn($n) => mb_substr($n, 0, 1))
                                    ->take(2)
                                    ->join('');
                            @endphp
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-700 text-white" 
                                 style="width: 32px; height: 32px; font-size: .8rem; background-color: #3b82f6;">
                                {{ strtoupper($initials) }}
                            </div>
                            <div>
                                <div class="fw-600" style="font-size: .83rem;">
                                    {{ $log->user ? $log->user->name : 'System' }}
                                </div>
                                <div class="text-muted small" style="font-size: .7rem;">
                                    {{ $log->user ? ucfirst($log->user->role) : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php
                            $badgeMap = [
                                'LOGIN'   => ['bg' => 'rgba(59,130,246,0.12)', 'color' => '#3b82f6', 'icon' => 'bi-box-arrow-in-right'],
                                'LOGOUT'  => ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#6b7280', 'icon' => 'bi-box-arrow-left'],
                                'CREATED' => ['bg' => 'rgba(16,185,129,0.12)', 'color' => '#10b981', 'icon' => 'bi-plus-circle'],
                                'UPDATED' => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b', 'icon' => 'bi-pencil-square'],
                                'DELETED' => ['bg' => 'rgba(239,68,68,0.12)', 'color' => '#ef4444', 'icon' => 'bi-trash'],
                                'CONFIG'  => ['bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6', 'icon' => 'bi-gear'],
                            ];
                            $meta = $badgeMap[$log->action] ?? ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#6b7280', 'icon' => 'bi-info-circle'];
                        @endphp
                        <span style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; padding: .25rem .5rem; border-radius: 6px; font-size: .72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="bi {{ $meta['icon'] }}"></i> {{ $log->action }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-500" style="font-size: .83rem; margin-bottom: .25rem;">
                            {{ $log->description }}
                        </div>
                        @if($log->changes && is_array($log->changes))
                        <div class="mt-2" style="max-width: 500px;">
                            <table class="table table-sm table-bordered mb-0" style="font-size: .75rem; border-color: var(--card-border) !important;">
                                <thead style="background: rgba(255,255,255,0.03);">
                                    <tr>
                                        <th style="color:var(--text-secondary); width: 30%;">FIELD</th>
                                        <th style="color:var(--text-secondary); width: 35%;">OLD VALUE</th>
                                        <th style="color:var(--text-secondary); width: 35%;">NEW VALUE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($log->changes as $field => $values)
                                    <tr>
                                        <td class="fw-600 text-secondary">{{ strtoupper($field) }}</td>
                                        <td>
                                            <span style="background: rgba(239,68,68,0.08); color: #ef4444; padding: 2px 6px; border-radius: 4px; display: inline-block; word-break: break-all;">
                                                {{ is_array($values['old']) || is_object($values['old']) ? json_encode($values['old']) : ($values['old'] ?? 'NULL') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="background: rgba(16,185,129,0.08); color: #10b981; padding: 2px 6px; border-radius: 4px; display: inline-block; word-break: break-all;">
                                                {{ is_array($values['new']) || is_object($values['new']) ? json_encode($values['new']) : ($values['new'] ?? 'NULL') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 small text-secondary" style="font-size: .78rem;">
                            <i class="bi bi-pc-display"></i> {{ $log->ip_address ?: 'N/A' }}
                        </div>
                        <div class="text-muted small text-truncate" style="font-size: .68rem; max-width: 230px;" title="{{ $log->user_agent }}">
                            {{ $log->user_agent }}
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#activityLogsTable').DataTable({
        dom: 'lfrtipB',
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none',
                title: 'User Activity Logs'
            },
            {
                extend: 'pdfHtml5',
                className: 'd-none',
                title: 'User Activity Logs',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            }
        ],
        order: [[0, 'desc']]
    });

    $('#btnExportExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
    });

    $('#btnExportPdf').on('click', function() {
        table.button('.buttons-pdf').trigger();
    });
});
</script>
@endpush
