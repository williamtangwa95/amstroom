@extends('layouts.app')
@section('title', 'Sales vs Expenses')
@section('page-title', 'Revenue vs Expenses Analysis')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales vs Expenses</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.sales-vs-expenses') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" disabled>
                    <option value="{{ auth()->user()->shop_id }}" selected>{{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</option>
                </select>
            </div>
            @endif
            @if($period === 'custom')
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
            </div>
            @endif
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
                    <i class="bi bi-envelope-at"></i>
                    <span>Send via Email</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Sales Revenue</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalSales, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Approved Expenses</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalExpenses, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @if($netProfit >= 0)
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #3498db !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Net Profit (Surplus)</h6>
                    <h3 class="mb-0 fw-800 text-success" style="font-size: 1.6rem;">TZS {{ number_format($netProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #3498db; opacity: 0.25;"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Net Loss (Deficit)</h6>
                    <h3 class="mb-0 fw-800 text-danger" style="font-size: 1.6rem;">TZS {{ number_format($netProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-dash-circle-fill"></i></div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-pie-chart-fill me-2" style="color:#bc8cff;"></i>Visual Summary</div>
    <div class="card-body py-4 text-center">
        @php
            $totalAmount = $totalSales + $totalExpenses;
            $salesPercentage = $totalAmount > 0 ? ($totalSales / $totalAmount) * 100 : 50;
            $expensesPercentage = $totalAmount > 0 ? ($totalExpenses / $totalAmount) * 100 : 50;
        @endphp
        
        <h6 class="mb-3 text-secondary">Ratio: Sales vs Approved Expenses</h6>
        <div class="progress" style="height: 25px; border-radius: 6px; overflow: hidden; background-color: var(--card-border);">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $salesPercentage }}%" aria-valuenow="{{ $salesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                Sales: {{ number_format($salesPercentage, 1) }}%
            </div>
            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $expensesPercentage }}%" aria-valuenow="{{ $expensesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                Expenses: {{ number_format($expensesPercentage, 1) }}%
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-6 text-end">
                <span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#198754;"></span>
                <span class="text-secondary small">Total Sales: <strong>TZS {{ number_format($totalSales) }}</strong></span>
            </div>
            <div class="col-6 text-start">
                <span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#dc3545;"></span>
                <span class="text-secondary small">Total Expenses: <strong>TZS {{ number_format($totalExpenses) }}</strong></span>
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
