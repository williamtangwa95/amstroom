@extends('layouts.app')
@section('title', 'Stock Requests')
@section('page-title', 'Stock Requests')
@section('breadcrumb')
<li class="breadcrumb-item active">Stock Requests</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Stock Requests</h5>
        <small style="color:var(--text-secondary);">Request inventory from central warehouse</small>
    </div>
    @if(!auth()->user()->isOwner())
    <a href="{{ route('stock-requests.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> New Request</a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="requestsTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Requester</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th class="no-sort">Actions</th>
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
    $(() => {
        var table = $('#requestsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: "{{ route('stock-requests.data') }}",
            columns: [
                { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
                { data: 'shop', name: 'shop' },
                { data: 'requester', name: 'requester' },
                { data: 'date', name: 'date' },
                { data: 'status', name: 'status' },
                { data: 'items', name: 'items' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[3, 'desc']]
        });

        var detailsCache = {};

        $('#requestsTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var reqId = $(this).data('id');
            var icon = $(this).find('i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                if (detailsCache[reqId]) {
                    row.child(detailsCache[reqId]).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    var loadingHtml = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading details...</div>';
                    row.child(loadingHtml).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                    $.ajax({
                        url: "{{ url('stock-requests') }}/" + reqId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[reqId] = html;
                            if (row.child.isShown()) {
                                row.child(html).show();
                            }
                        },
                        error: function() {
                            var errorHtml = '<div class="p-3 text-center text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load request details.</div>';
                            row.child(errorHtml).show();
                        }
                    });
                }
            }
        });
    });
</script>
@endpush