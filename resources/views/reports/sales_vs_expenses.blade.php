@extends('layouts.app')
@section('title', 'Sales vs Expenses')
@section('page-title', 'Sales vs Expenses')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales vs Expenses</li>
@endsection

@push('styles')
@include('reports._styles')
@endpush

@section('content')
{{-- Compact Header --}}
<div class="rpt-header">
    <div class="rpt-title-row">
        <h5 class="rpt-title">
            <i class="bi bi-file-earmark-bar-graph text-primary me-1"></i>Revenue vs Expenses
        </h5>
        <span class="rpt-pill">
            <i class="bi bi-calendar3"></i>{{ ucfirst($period) }}
        </span>
    </div>
    <div class="rpt-controls">
        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
            <i class="bi bi-envelope-at"></i> Send via Email
        </button>
    </div>
</div>

{{-- Filter Bar --}}
<div class="rpt-card mb-3">
    <div class="p-3">
        <form method="GET" action="{{ route('reports.sales-vs-expenses') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="rpt-filter-label">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Today</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-6 col-md-3">
                <label class="rpt-filter-label">Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-6 col-md-3">
                <label class="rpt-filter-label">Shop</label>
                <select name="shop_id" class="form-select form-select-sm" disabled>
                    <option value="{{ auth()->user()->shop_id }}" selected>{{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</option>
                </select>
            </div>
            @endif
            @if($period === 'custom')
            <div class="col-6 col-md-2">
                <label class="rpt-filter-label">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="rpt-filter-label">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100" style="height:31px;">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Executive KPI Cards --}}
<div class="rpt-kpi-grid rpt-kpi-grid-3">
    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">Sales Revenue</p>
            <div class="rpt-kpi-val is-currency" style="color:#059669;">TZS {{ number_format($totalSales, 0) }}</div>
            <div class="rpt-kpi-sub">Gross sales total</div>
        </div>
    </div>

    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">Approved Expenses</p>
            <div class="rpt-kpi-val is-currency" style="color:#e11d48;">TZS {{ number_format($totalExpenses, 0) }}</div>
            <div class="rpt-kpi-sub">Total expenditure</div>
        </div>
    </div>

    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:{{ $netProfit >= 0 ? 'rgba(0,119,182,.1)' : 'rgba(225,29,72,.1)' }};color:{{ $netProfit >= 0 ? '#0077b6' : '#e11d48' }};">
            <i class="bi {{ $netProfit >= 0 ? 'bi-cash-coin' : 'bi-dash-circle-fill' }}"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">{{ $netProfit >= 0 ? 'Net Surplus' : 'Net Deficit' }}</p>
            <div class="rpt-kpi-val is-currency" style="color:{{ $netProfit >= 0 ? '#059669' : '#e11d48' }};">TZS {{ number_format($netProfit, 0) }}</div>
            <div class="rpt-kpi-sub">Revenue minus expenses</div>
        </div>
    </div>
</div>

<div class="rpt-card mb-4">
    <div class="rpt-card-header">
        <h6 class="rpt-card-title"><i class="bi bi-pie-chart-fill" style="color:#7c3aed;"></i>Sales vs Expenses Ratio</h6>
    </div>
    <div class="p-3 p-md-4">
        @php
            $totalAmount = $totalSales + $totalExpenses;
            $salesPercentage = $totalAmount > 0 ? ($totalSales / $totalAmount) * 100 : 50;
            $expensesPercentage = $totalAmount > 0 ? ($totalExpenses / $totalAmount) * 100 : 50;
        @endphp

        <div class="progress" style="height: 22px; border-radius: 99px; overflow: hidden; background-color: #f1f5f9;">
            <div class="progress-bar bg-success fw-600" role="progressbar" style="width: {{ $salesPercentage }}%; font-size:.74rem;" aria-valuenow="{{ $salesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                {{ number_format($salesPercentage, 1) }}%
            </div>
            <div class="progress-bar bg-danger fw-600" role="progressbar" style="width: {{ $expensesPercentage }}%; font-size:.74rem;" aria-valuenow="{{ $expensesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                {{ number_format($expensesPercentage, 1) }}%
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-4 mt-3">
            <div class="d-flex align-items-center gap-2 small">
                <span class="rounded-circle" style="width:10px;height:10px;background:#198754;display:inline-block;"></span>
                <span class="text-secondary">Sales: <strong class="text-dark">TZS {{ number_format($totalSales) }}</strong></span>
            </div>
            <div class="d-flex align-items-center gap-2 small">
                <span class="rounded-circle" style="width:10px;height:10px;background:#dc3545;display:inline-block;"></span>
                <span class="text-secondary">Expenses: <strong class="text-dark">TZS {{ number_format($totalExpenses) }}</strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Send Filtered Sales vs Expenses Report to Email -->
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white border-bottom border-secondary">
                <h5 class="modal-title fs-6 fw-bold" id="emailReportModalLabel">
                    <i class="bi bi-envelope-paper me-2 text-info"></i> Send Sales vs Expenses Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendSalesVsExpensesReportEmailForm">
                @csrf
                <div class="modal-body py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Report Summary</div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-calendar3 me-1"></i> {{ ucfirst($period) }} @if($period === 'custom' && request('date_from')) ({{ request('date_from') }} to {{ request('date_to') }}) @endif</span>
                            @if(auth()->user()->isOwner())
                                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-shop me-1"></i> {{ request('shop_id') === 'owner' ? 'Main Store (Owner)' : (request('shop_id') ? ($shops->firstWhere('id', request('shop_id'))?->shop_name ?? 'Shop') : 'All Shops') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-shop me-1"></i> {{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 0.8rem;">
                            <span class="text-muted">Sales: <strong class="text-success">TZS {{ number_format($totalSales, 0) }}</strong></span>
                            <span class="text-muted">Expenses: <strong class="text-danger">TZS {{ number_format($totalExpenses, 0) }}</strong></span>
                            <span class="text-muted">Net Margin: <strong class="{{ $netProfit >= 0 ? 'text-primary' : 'text-danger' }}">TZS {{ number_format($netProfit, 0) }}</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reportRecipientEmails" class="form-label fw-bold small text-dark mb-1">
                            Recipient Email Address(es) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="reportRecipientEmails" name="emails" 
                               value="{{ auth()->user()->email }}" placeholder="e.g. owner@example.com, manager@example.com" required>
                        <div class="form-text" style="font-size: 0.72rem;">Separate multiple email addresses with a comma.</div>
                    </div>

                    <div class="mb-2">
                        <label for="reportEmailNote" class="form-label fw-bold small text-dark mb-1">
                            Custom Note / Message <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="reportEmailNote" name="note" rows="3" 
                                  placeholder="Add an optional comment or explanation to be included at the top of the email..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent d-flex align-items-center gap-1" id="btnSubmitSendSalesVsExpensesReportEmail">
                        <i class="bi bi-send-fill"></i>
                        <span>Send Email Report</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(() => {
    $('#sendSalesVsExpensesReportEmailForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSubmitSendSalesVsExpensesReportEmail');
        const originalBtnHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...');

        const formData = {
            _token: $('input[name="_token"]').val() || '{{ csrf_token() }}',
            emails: $('#reportRecipientEmails').val(),
            note: $('#reportEmailNote').val(),
            period: "{{ $period }}",
            shop_id: "{{ request('shop_id') }}",
            date_from: "{{ request('date_from') }}",
            date_to: "{{ request('date_to') }}"
        };

        $.ajax({
            url: "{{ route('reports.sales-vs-expenses.send-email') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                $('#emailReportModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Report Sent!',
                    text: res.message || 'Sales vs Expenses report has been successfully sent to email.',
                    confirmButtonColor: '#0088cc'
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = 'Failed to send report email.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Sending Failed',
                    html: msg,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });
});
</script>
@endpush
