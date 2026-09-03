@extends('layouts.app')
@section('title', 'Sales History')
@section('page-title', 'Sales Transactions')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">
            @if(request('status') === 'draft_proforma')
                Proforma Quotes
            @elseif(request('status') === 'completed')
                Completed Invoices / Sales
            @else
                Sales Transactions
            @endif
        </h5>
        <small style="color:var(--text-secondary);">Total Revenue: <strong id="totalRevenueText" style="color:#3fb950;">TZS {{ number_format($totalRevenue, 0) }}</strong></small>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> New Sale</a>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form id="filterForm" method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="draft_proforma" {{ request('status') === 'draft_proforma' ? 'selected' : '' }}>Proforma</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="salesTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Sale ID</th>
                    <th>Shop</th>
                    <th>Seller</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="editCustomerModalLabel"><i class="bi bi-person-gear me-2" style="color:var(--accent);"></i>Update Customer Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalCustomerName" class="form-label fw-600">Customer Name</label>
                        <input type="text" name="customer_name" id="modalCustomerName" class="form-control" placeholder="e.g. John Doe / Company Name" autofocus>
                        <small class="text-muted">Enter or update the customer name for this sale transaction.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(() => {
        var table = $('#salesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('sales.data') }}",
                data: function(d) {
                    d.date_from = $('input[name="date_from"]').val();
                    d.date_to = $('input[name="date_to"]').val();
                    d.status = $('select[name="status"]').val();
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
                { data: 'sale_id', name: 'sale_id' },
                { data: 'shop', name: 'shop' },
                { data: 'seller', name: 'seller' },
                { data: 'customer', name: 'customer' },
                { data: 'items', name: 'items' },
                { data: 'payment_method', name: 'payment_method' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'status', name: 'status' },
                { data: 'date', name: 'date' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[9, 'desc']],
            drawCallback: function(settings) {
                if (settings.json && settings.json.formattedTotalRevenue) {
                    $('#totalRevenueText').text(settings.json.formattedTotalRevenue);
                }
            }
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            table.draw();
        });

        var detailsCache = {};

        $('#salesTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var saleId = $(this).data('id');
            var icon = $(this).find('i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                if (detailsCache[saleId]) {
                    row.child(detailsCache[saleId]).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    var loadingHtml = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading details...</div>';
                    row.child(loadingHtml).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[saleId] = html;
                            if (row.child.isShown()) {
                                row.child(html).show();
                            }
                        },
                        error: function() {
                            var errorHtml = '<div class="p-3 text-center text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load sale details.</div>';
                            row.child(errorHtml).show();
                        }
                    });
                }
            }
        });

        $(document).on('click', '.edit-customer-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('id');
            var customerName = $(this).data('name') || '';
            var actionUrl = "{{ url('sales') }}/" + saleId + "/customer";
            
            $('#editCustomerForm').attr('action', actionUrl);
            $('#modalCustomerName').val(customerName);
            $('#editCustomerModal').modal('show');
        });
    });
</script>
@endpush
