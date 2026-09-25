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
            <div class="{{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-3' }}">
                <label class="form-label mb-1" style="font-size:.75rem;">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="{{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-3' }}">
                <label class="form-label mb-1" style="font-size:.75rem;">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Shop Name</label>
                <select name="shop_id" class="form-select form-select-sm">
                    <option value="">All Shops</option>
                    <option value="main_store" {{ request('shop_id') === 'main_store' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="{{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-2' }}">
                <label class="form-label mb-1" style="font-size:.75rem;">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="draft_proforma" {{ request('status') === 'draft_proforma' ? 'selected' : '' }}>Proforma</option>
                </select>
            </div>
            <div class="{{ auth()->user()->isOwner() ? 'col-md-1' : 'col-md-2' }}">
                <button type="submit" class="btn btn-sm btn-accent w-100"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
            <div class="{{ auth()->user()->isOwner() ? 'col-md-1' : 'col-md-2' }}">
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

<!-- Add Component Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addComponentForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="addComponentModalLabel">
                        <i class="bi bi-plus-circle-dotted me-2" style="color:var(--accent);"></i>Add Component to Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 mb-1" style="font-size:.82rem;">Parent Product</label>
                        <input type="text" id="modalTargetItemName" class="form-control form-control-sm" readonly disabled style="background:var(--input-bg);">
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentSelect" class="form-label fw-600 mb-1" style="font-size:.82rem;">Select Component Item *</label>
                        <select name="component_item_id" id="modalComponentSelect" class="form-select form-select-sm" required style="width:100%;">
                            <option value="" disabled selected>Loading available items...</option>
                        </select>
                        <div id="modalStockAlert" class="mt-1" style="font-size:.75rem; color:var(--text-secondary);"></div>
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentQty" class="form-label fw-600 mb-1" style="font-size:.82rem;">Quantity *</label>
                        <input type="number" name="quantity" id="modalComponentQty" class="form-control form-control-sm" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm" id="modalSubmitComponentBtn">
                        <i class="bi bi-check-lg me-1"></i> Add Component
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
                    d.shop_id = $('select[name="shop_id"]').val();
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
        var activeSaleIdForComp = null;

        function refreshSaleDetails(saleId) {
            delete detailsCache[saleId];
            var btn = $('.toggle-details[data-id="' + saleId + '"]');
            var tr = btn.closest('tr');
            var row = table.row(tr);
            if (row.child.isShown()) {
                $.ajax({
                    url: "{{ url('sales') }}/" + saleId + "/details",
                    method: 'GET',
                    success: function(html) {
                        detailsCache[saleId] = html;
                        row.child(html).show();
                    }
                });
            }
        }

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

        $(document).on('click', '.open-add-component-modal-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');

            activeSaleIdForComp = saleId;
            $('#modalTargetItemName').val(itemName);
            $('#modalComponentQty').val(1);
            $('#modalStockAlert').html('');
            $('#modalSubmitComponentBtn').prop('disabled', true);

            var actionUrl = "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/components";
            $('#addComponentForm').attr('action', actionUrl);

            var select = $('#modalComponentSelect');
            select.html('<option value="" disabled selected>Loading available items...</option>');

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            $('#addComponentModal').modal('show');

            $.ajax({
                url: "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/available-components",
                method: 'GET',
                success: function(res) {
                    if (res.success && res.items && res.items.length > 0) {
                        var options = '<option value="" disabled selected>Search / select item...</option>';
                        res.items.forEach(function(item) {
                            var escapedName = $('<div>').text(item.item_name).html();
                            options += '<option value="' + item.id + '" data-stock="' + item.stock + '">' + escapedName + ' (' + item.brand + ') [Stock: ' + item.stock + ']</option>';
                        });
                        select.html(options);
                        $('#modalSubmitComponentBtn').prop('disabled', false);
                    } else {
                        select.html('<option value="" disabled selected>No available items in stock</option>');
                        $('#modalStockAlert').html('<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>No stock available for components in this store.</span>');
                    }

                    select.select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('#addComponentModal'),
                        width: '100%'
                    });
                },
                error: function() {
                    select.html('<option value="" disabled selected>Error loading items</option>');
                    $('#modalStockAlert').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Failed to load items.</span>');
                }
            });
        });

        $('#modalComponentSelect').on('change', function() {
            var opt = $(this).find('option:selected');
            var stock = opt.data('stock');
            if (stock !== undefined) {
                $('#modalStockAlert').html('<span class="text-success"><i class="bi bi-box-seam me-1"></i>Available in stock: <strong>' + stock + '</strong></span>');
                $('#modalComponentQty').attr('max', stock);
            }
        });

        $('#addComponentForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = $('#modalSubmitComponentBtn');
            var origHtml = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Adding...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(res) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    $('#addComponentModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Component Added',
                        text: res.message || 'Component added successfully.',
                        timer: 2500,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    if (activeSaleIdForComp) {
                        refreshSaleDetails(activeSaleIdForComp);
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    var msg = 'Failed to add component.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                }
            });
        });

        $(document).on('click', '.remove-component-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var compId = $(this).data('component-id');
            var compName = $(this).data('component-name') || 'this component';

            Swal.fire({
                title: 'Remove Component?',
                text: 'Are you sure you want to remove "' + compName + '" from this sale? The quantity will be restored to inventory stock.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e94560',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/components/" + compId,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed',
                                text: res.message || 'Component removed successfully.',
                                timer: 2500,
                                showConfirmButton: false,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                            refreshSaleDetails(saleId);
                        },
                        error: function(xhr) {
                            var msg = 'Failed to remove component.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush

