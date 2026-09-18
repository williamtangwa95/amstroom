@extends('layouts.app')
@section('title', 'Transfer Report')
@section('page-title', 'Stock Transfer Requests Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Transfer Report</li>
@endsection
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f39c12 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pending Requests</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['pending'] }}</h3>
                </div>
                <div class="fs-2" style="color: #f39c12; opacity: 0.25;"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Approved & Transferred</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['approved'] }}</h3>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Rejected Requests</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $stats['rejected'] }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-arrow-left-right me-2" style="color:#58a6ff;"></i>Request & Transfer Log</span>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('reports.transfer') }}?status=all" class="btn {{ $status==='all' ? 'btn-accent' : 'btn-outline-custom' }}">All</a>
                <a href="{{ route('reports.transfer') }}?status=pending" class="btn {{ $status==='pending' ? 'btn-accent' : 'btn-outline-custom' }}">Pending</a>
                <a href="{{ route('reports.transfer') }}?status=approved" class="btn {{ $status==='approved' ? 'btn-accent' : 'btn-outline-custom' }}">Approved</a>
                <a href="{{ route('reports.transfer') }}?status=rejected" class="btn {{ $status==='rejected' ? 'btn-accent' : 'btn-outline-custom' }}">Rejected</a>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
            <i class="bi bi-envelope-at"></i>
            <span>Send via Email</span>
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsTransferTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Request ID</th>
                    <th>Shop</th>
                    <th>Requester</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Items</th>
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
