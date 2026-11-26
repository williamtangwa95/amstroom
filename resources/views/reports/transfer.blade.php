@extends('layouts.app')
@section('title', 'Transfer Report')
@section('page-title', 'Transfer Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Transfer Report</li>
@endsection

@push('styles')
@include('reports._styles')
@endpush

@section('content')
{{-- Compact Header --}}
<div class="rpt-header">
    <div class="rpt-title-row">
        <h5 class="rpt-title">
            <i class="bi bi-arrow-left-right text-primary me-1"></i>Transfer Report
        </h5>
        <span class="rpt-pill">
            <i class="bi bi-funnel"></i>{{ ucfirst($status) }}
        </span>
    </div>
    <div class="rpt-controls">
        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
            <i class="bi bi-envelope-at"></i> Send via Email
        </button>
    </div>
</div>

{{-- Executive KPI Cards --}}
<div class="rpt-kpi-grid rpt-kpi-grid-3">
    <a href="{{ route('reports.transfer') }}?status=pending" class="text-decoration-none">
        <div class="rpt-kpi-card {{ $status === 'pending' ? 'is-active' : '' }}">
            <div class="rpt-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="rpt-kpi-body">
                <p class="rpt-kpi-label">Pending Requests</p>
                <div class="rpt-kpi-val" style="color:#d97706;">{{ number_format($stats['pending']) }}</div>
                <div class="rpt-kpi-sub">Awaiting approval</div>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.transfer') }}?status=approved" class="text-decoration-none">
        <div class="rpt-kpi-card {{ $status === 'approved' ? 'is-active' : '' }}">
            <div class="rpt-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="rpt-kpi-body">
                <p class="rpt-kpi-label">Approved &amp; Transferred</p>
                <div class="rpt-kpi-val" style="color:#059669;">{{ number_format($stats['approved']) }}</div>
                <div class="rpt-kpi-sub">Dispatched to shops</div>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.transfer') }}?status=rejected" class="text-decoration-none">
        <div class="rpt-kpi-card {{ $status === 'rejected' ? 'is-active' : '' }}">
            <div class="rpt-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="rpt-kpi-body">
                <p class="rpt-kpi-label">Rejected Requests</p>
                <div class="rpt-kpi-val" style="color:#dc2626;">{{ number_format($stats['rejected']) }}</div>
                <div class="rpt-kpi-sub">Declined requests</div>
            </div>
        </div>
    </a>
</div>

<div class="rpt-card">
    <div class="rpt-card-header">
        <div class="rpt-pills">
            <a href="{{ route('reports.transfer') }}?status=all" class="rpt-pill-link {{ $status === 'all' ? 'active' : '' }}">All</a>
            <a href="{{ route('reports.transfer') }}?status=pending" class="rpt-pill-link {{ $status === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('reports.transfer') }}?status=approved" class="rpt-pill-link {{ $status === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('reports.transfer') }}?status=rejected" class="rpt-pill-link {{ $status === 'rejected' ? 'active' : '' }}">Rejected</a>
        </div>
    </div>
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="reportsTransferTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Request ID</th>
                    <th data-priority="2" class="min-tablet-l">Shop</th>
                    <th data-priority="3" class="min-tablet-l">Requester</th>
                    <th data-priority="3" class="min-tablet-l">Request Date</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="2" class="min-tablet-l">Items</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Send Filtered Transfer Report to Email -->
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white border-bottom border-secondary">
                <h5 class="modal-title fs-6 fw-bold" id="emailReportModalLabel">
                    <i class="bi bi-envelope-paper me-2 text-info"></i> Send Transfer Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendTransferReportEmailForm">
                @csrf
                <div class="modal-body py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Report Summary</div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-funnel me-1"></i> Filter Status: {{ ucfirst($status) }}</span>
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-clock-history me-1"></i> Pending: {{ $stats['pending'] }}</span>
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-check-circle me-1"></i> Approved: {{ $stats['approved'] }}</span>
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-x-circle me-1"></i> Rejected: {{ $stats['rejected'] }}</span>
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
                    <button type="submit" class="btn btn-sm btn-accent d-flex align-items-center gap-1" id="btnSubmitSendTransferReportEmail">
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
    $('#reportsTransferTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('reports.transfer.data') }}",
            data: function(d) {
                d.status = "{{ $status }}";
            }
        },
        columns: [
            { data: 'iteration', name: 'iteration' },
            { data: 'request_id', name: 'request_id' },
            { data: 'shop', name: 'shop' },
            { data: 'requester', name: 'requester' },
            { data: 'request_date', name: 'request_date' },
            { data: 'status', name: 'status' },
            { data: 'items', name: 'items', orderable: false, searchable: false }
        ],
        order: [[4, 'desc']],
        dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom" <"d-flex align-items-center gap-3"lB> f>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
        buttons: [
            {
                text: '<i class="bi bi-envelope-at me-1"></i> Email Report',
                className: 'btn btn-sm btn-primary me-2',
                action: function() {
                    $('#emailReportModal').modal('show');
                }
            },
            {
                extend: 'excelHtml5',
                className: 'btn btn-sm btn-accent me-2',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                title: 'Stock Transfer Requests Report'
            },
            {
                extend: 'pdfHtml5',
                className: 'btn btn-sm btn-outline-custom',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                title: 'Stock Transfer Requests Report'
            }
        ]
    });

    /* ── Send Transfer Report AJAX ── */
    $('#sendTransferReportEmailForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSubmitSendTransferReportEmail');
        const originalBtnHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...');

        const formData = {
            _token: $('input[name="_token"]').val() || '{{ csrf_token() }}',
            emails: $('#reportRecipientEmails').val(),
            note: $('#reportEmailNote').val(),
            status: "{{ $status }}"
        };

        $.ajax({
            url: "{{ route('reports.transfer.send-email') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                $('#emailReportModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Report Sent!',
                    text: res.message || 'Transfer report has been successfully sent to email.',
                    confirmButtonColor: '#0088cc'
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = 'Failed to send transfer report email.';
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
