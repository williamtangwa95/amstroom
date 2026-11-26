{{-- Reusable Handover-Locked Record Unlock Request Modal & Owner Approve/Reject Handlers --}}
<div class="modal fade no-print" id="requestHandoverEditModal" tabindex="-1" aria-labelledby="requestHandoverEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="requestHandoverEditForm" method="POST" action="{{ route('handover-edit-requests.store') }}">
                @csrf
                <input type="hidden" name="record_type" id="herRecordType" value="">
                <input type="hidden" name="record_id" id="herRecordId" value="">
                <div class="modal-header" style="background:linear-gradient(135deg,#0077b6,#003a6c);color:#fff;">
                    <h6 class="modal-title fw-700" id="requestHandoverEditLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i>Request Owner Unlock
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 py-2 px-3 small mb-3" style="border-radius:10px;">
                        <i class="bi bi-lock-fill me-1"></i>
                        <strong id="herRecordLabelText">This record</strong> is locked under submitted Cash Handover
                        <strong id="herHandoverNoText"></strong>. Owner approval is required before making changes.
                    </div>

                    <div class="mb-3">
                        <label for="herActionType" class="form-label small fw-600">Action Needed <span class="text-danger">*</span></label>
                        <select name="action_type" id="herActionType" class="form-select form-select-sm no-select2" required></select>
                    </div>

                    <div class="mb-2">
                        <label for="herReason" class="form-label small fw-600">Reason for Request <span class="text-danger">*</span></label>
                        <textarea name="reason" id="herReason" class="form-control" rows="3" required minlength="3" maxlength="1000" placeholder="Explain clearly why this handover-locked record needs to be edited, deleted, or returned..."></textarea>
                    </div>
                    <div class="small text-danger fw-600 d-none" id="herErrorBox"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent fw-700" id="herSubmitBtn">
                        <i class="bi bi-send-fill me-1"></i>Submit Request to Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    if (window.__handoverLockHandlersBound) return;
    window.__handoverLockHandlersBound = true;

    function refreshTablesOrPage() {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#salesTable')) {
            $('#salesTable').DataTable().ajax.reload(null, false);
            return;
        }
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#expensesTable')) {
            $('#expensesTable').DataTable().ajax.reload(null, false);
            return;
        }
        window.location.reload();
    }

    // Open Request Edit Modal (Shop Admin or Seller)
    $(document).on('click', '.request-handover-edit-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const recordType = $btn.data('record-type');
        const recordId = $btn.data('record-id');
        const recordLabel = $btn.data('record-label') || 'This record';
        const handoverNo = $btn.data('handover-no') || '';
        const defaultAction = $btn.data('default-action') || '';

        $('#herRecordType').val(recordType);
        $('#herRecordId').val(recordId);
        $('#herRecordLabelText').text(recordLabel);
        $('#herHandoverNoText').text(handoverNo ? '(' + handoverNo + ')' : '');
        $('#herReason').val('');
        $('#herErrorBox').addClass('d-none').text('');

        const $select = $('#herActionType');
        $select.empty();
        if (recordType === 'sale') {
            $select.append('<option value="return">Process Sale Return / Refund</option>');
            $select.append('<option value="edit">Edit Sale Details (Customer / Components)</option>');
        } else {
            $select.append('<option value="edit">Edit Expense</option>');
            $select.append('<option value="delete">Delete Expense</option>');
        }
        if (defaultAction) {
            $select.val(defaultAction);
        }

        const modalEl = document.getElementById('requestHandoverEditModal');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    // Submit Request Edit Modal via AJAX
    $(document).on('submit', '#requestHandoverEditForm', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#herSubmitBtn');
        const $err = $('#herErrorBox');
        const origHtml = $btn.html();

        $err.addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: $form.serialize(),
            success: function(res) {
                $btn.prop('disabled', false).html(origHtml);
                delete $form[0].dataset.submitting;
                const modalEl = document.getElementById('requestHandoverEditModal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Sent to Owner',
                        text: res.message || 'Unlock request submitted successfully.',
                        timer: 2400,
                        showConfirmButton: false
                    }).then(refreshTablesOrPage);
                } else {
                    refreshTablesOrPage();
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origHtml);
                delete $form[0].dataset.submitting;
                let msg = 'Could not submit unlock request.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey) msg = xhr.responseJSON.errors[firstKey][0];
                    } else if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                }
                $err.removeClass('d-none').text(msg);
            }
        });
    });

    // Owner: Approve Unlock Request
    $(document).on('click', '.owner-approve-unlock-btn', function(e) {
        e.preventDefault();
        const requestId = $(this).data('request-id');
        const label = $(this).data('label') || 'this record';
        const reason = $(this).data('reason') || '';
        const url = "{{ url('handover-edit-requests') }}/" + requestId + "/approve";

        const doApprove = function(remarks) {
            $.ajax({
                url: url,
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                data: { _token: "{{ csrf_token() }}", review_remarks: remarks || '' },
                success: function(res) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Unlock Approved',
                            text: res.message || 'Record unlocked for one-time modification.',
                            timer: 2200,
                            showConfirmButton: false
                        }).then(refreshTablesOrPage);
                    } else {
                        refreshTablesOrPage();
                    }
                },
                error: function(xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to approve unlock.';
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Approve Handover Unlock?',
                html: `<div class="small text-start">
                        <p class="mb-2">Approve unlock for <strong>${$('<div>').text(label).html()}</strong>?</p>
                        ${reason ? `<div class="p-2 rounded bg-light border mb-2"><strong>Requester Reason:</strong> ${$('<div>').text(reason).html()}</div>` : ''}
                        <div class="text-muted">Once the change is completed, the record will automatically re-lock and handover totals will recalculate.</div>
                       </div>`,
                input: 'text',
                inputPlaceholder: 'Optional Owner remarks...',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Approve Unlock'
            }).then(function(result) {
                if (result.isConfirmed) {
                    doApprove(result.value);
                }
            });
        } else if (confirm('Approve unlock for ' + label + '?')) {
            doApprove('');
        }
    });

    // Owner: Reject Unlock Request
    $(document).on('click', '.owner-reject-unlock-btn', function(e) {
        e.preventDefault();
        const requestId = $(this).data('request-id');
        const label = $(this).data('label') || 'this record';
        const url = "{{ url('handover-edit-requests') }}/" + requestId + "/reject";

        const doReject = function(remarks) {
            $.ajax({
                url: url,
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                data: { _token: "{{ csrf_token() }}", review_remarks: remarks },
                success: function(res) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Request Rejected',
                            text: res.message || 'Record remains locked.',
                            timer: 2200,
                            showConfirmButton: false
                        }).then(refreshTablesOrPage);
                    } else {
                        refreshTablesOrPage();
                    }
                },
                error: function(xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to reject request.';
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Reject Unlock Request?',
                text: 'Provide a reason for keeping ' + label + ' locked:',
                input: 'textarea',
                inputPlaceholder: 'Enter rejection reason...',
                inputValidator: function(value) {
                    if (!value || value.trim().length < 2) {
                        return 'Please enter a rejection reason.';
                    }
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Reject Request'
            }).then(function(result) {
                if (result.isConfirmed) {
                    doReject(result.value);
                }
            });
        }
    });
})();
</script>
@endpush
