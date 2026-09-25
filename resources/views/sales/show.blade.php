@extends('layouts.app')
@section('title', 'Sale #SL-' . $sale->id)
@section('page-title', 'Sale Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">#SL-{{ $sale->id }}</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-cart-check me-2" style="color:#3fb950;"></i>Sale #SL-{{ $sale->id }}
                    @if($sale->status === 'draft_proforma')
                        <span class="badge ms-2" style="background:#fef3c7;color:#92400e;font-size:.72rem;">Proforma / Draft</span>
                    @else
                        <span class="badge ms-2" style="background:#d1fae5;color:#065f46;font-size:.72rem;">Completed</span>
                    @endif
                </span>
                <div class="d-flex gap-2 flex-wrap">
                    @if($sale->status === 'completed')
                        {{-- Completed sale: Invoice + Proforma + Delivery Note available --}}
                        <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-sm btn-outline-custom" target="_blank" title="Print Invoice">
                            <i class="bi bi-file-earmark-text me-1"></i>Invoice
                        </a>
                        <a href="{{ route('sales.proforma', $sale) }}" class="btn btn-sm btn-outline-custom" target="_blank" title="Print Proforma/Quotation">
                            <i class="bi bi-file-earmark me-1"></i>Proforma
                        </a>
                        <a href="{{ route('sales.delivery-note', $sale) }}" class="btn btn-sm btn-outline-custom" target="_blank" title="Print Delivery Note">
                            <i class="bi bi-truck me-1"></i>Delivery Note
                        </a>
                        <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-sm btn-accent" title="Print Receipt">
                            <i class="bi bi-receipt me-1"></i>Receipt
                        </a>
                    @else
                        {{-- Draft Proforma: Proforma printable; Invoice + Delivery Note locked --}}
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Invoice requires a completed/committed sale. Convert this proforma first.">
                            <i class="bi bi-file-earmark-text me-1"></i>Invoice
                        </button>
                        <a href="{{ route('sales.proforma', $sale) }}" class="btn btn-sm btn-outline-custom" target="_blank" title="Print Proforma Invoice">
                            <i class="bi bi-file-earmark me-1"></i>Proforma
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Delivery Note requires a completed sale. Stock must be committed first.">
                            <i class="bi bi-truck me-1"></i>Delivery Note
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" disabled title="Receipt is only available for completed sales.">
                            <i class="bi bi-receipt me-1"></i>Receipt
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3" style="font-size:.85rem;">
                    <div class="col-6">
                        <p class="mb-1" style="color:var(--text-secondary);">Shop: <strong style="color:var(--text-primary);">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Seller: <strong style="color:var(--text-primary);">{{ $sale->seller->name }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">
                            Customer: <strong style="color:var(--text-primary);">{{ $sale->customer_name ?: 'Walk-in' }}</strong>
                            <button type="button" class="btn btn-link btn-xs p-0 ms-1 edit-customer-btn" style="color:var(--accent);" data-id="{{ $sale->id }}" data-name="{{ $sale->customer_name }}" title="Add / Edit Customer Name">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </p>

                        @if($sale->customer_id)<p class="mb-1" style="color:var(--text-secondary);">Customer ID: <strong style="color:var(--text-primary);">{{ $sale->customer_id }}</strong></p>@endif
                        @if($sale->customer_po_box)<p class="mb-1" style="color:var(--text-secondary);">P.O. Box: <strong style="color:var(--text-primary);">{{ $sale->customer_po_box }}</strong></p>@endif
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1" style="color:var(--text-secondary);">Date: <strong style="color:var(--text-primary);">{{ $sale->sale_date->format('F d, Y') }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Payment Method: <strong style="color:var(--text-primary);">{{ str_replace('_',' ',ucfirst($sale->payment_method)) }}</strong></p>
                        @if($sale->deliver_to)<p class="mb-1" style="color:var(--text-secondary);">Deliver To: <strong style="color:var(--text-primary);">{{ $sale->deliver_to }}</strong></p>@endif
                        @if($sale->delivery_date)<p class="mb-1" style="color:var(--text-secondary);">Delivery: <strong style="color:var(--text-primary);">{{ $sale->delivery_date->format('d M Y') }}@if($sale->delivery_time) at {{ \Carbon\Carbon::parse($sale->delivery_time)->format('H:i') }}@endif</strong></p>@endif
                        @if($sale->validity_date)<p class="mb-1" style="color:var(--text-secondary);">Valid Until: <strong style="color:var(--text-primary);">{{ $sale->validity_date->format('d M Y') }}</strong></p>@endif
                        @if($sale->terms_of_payment)<p class="mb-1" style="color:var(--text-secondary);">Terms: <strong style="color:var(--text-primary);">{{ $sale->terms_of_payment }}</strong></p>@endif
                    </div>
                </div>

                <h6 class="fw-700 mb-2 mt-4">Items Sold</h6>
                <div class="table-responsive">
                    <table class="table mb-4">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Selling Price</th>
                                <th>Subtotal</th>
                                <th class="text-center" style="width: 70px;">Components</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $isOwner = auth()->check() && auth()->user()->isOwner();
                            $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
                        @endphp
                        @foreach($sale->items->where('parent_id', null) as $item)
                        @if($isOwner && $item->is_admin_stock)
                            @continue
                        @endif
                        @php
                            $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                            $displaySubtotal = $displayPrice * $item->quantity;
                            $canAddComponents = $item->allowsComponents();
                        @endphp
                        <tr>
                            <td style="font-weight:600;">
                                {{ $item->display_name }}
                                @if($canAddComponents)
                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem; border:1px solid var(--card-border);">Bundle Item</span>
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>TZS {{ number_format($displayPrice, 0) }}</td>
                            <td><strong style="color:#3fb950;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                            <td class="text-center">
                                @if($canAddComponents)
                                    <button type="button" class="btn btn-xs btn-outline-success open-add-component-modal-btn py-0 px-1"
                                        data-sale-id="{{ $sale->id }}"
                                        data-item-id="{{ $item->id }}"
                                        data-item-name="{{ $item->display_name }}"
                                        title="Add Component">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                @else
                                    <span class="text-muted" style="font-size:.72rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @if($item->components->isNotEmpty())
                            @foreach($item->components as $component)
                                <tr style="background-color: rgba(0,0,0,0.015);">
                                    <td style="font-size:.8rem; padding-left: 1.5rem; color: var(--text-secondary);">
                                        <span class="text-muted">└─</span> {{ $component->display_name }}
                                    </td>
                                    <td style="font-size:.8rem; color: var(--text-secondary);">
                                        {{ $component->quantity }}
                                    </td>
                                    <td style="font-size:.8rem; color: var(--text-secondary); font-style: italic;">
                                        Included
                                    </td>
                                    <td style="font-size:.8rem; color: var(--text-secondary); font-style: italic;">
                                        Included
                                    </td>
                                    <td class="text-center">
                                        @if($canAddComponents)
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 remove-component-btn"
                                                data-sale-id="{{ $sale->id }}"
                                                data-component-id="{{ $component->id }}"
                                                data-component-name="{{ $component->display_name }}"
                                                title="Remove this component">
                                                <i class="bi bi-trash" style="font-size:.7rem;"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-700">{{ $isOwner ? 'Total Revenue Realized:' : 'Total Amount Paid:' }}</td>
                                <td><strong style="color:#3fb950;font-size:1.1rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3 flex-wrap gap-2">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-custom">Back to Sales</a>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        @if($sale->status === 'draft_proforma')
                            <form method="POST" action="{{ route('sales.convert', $sale) }}"
                                  onsubmit="return confirm('Convert this proforma to a completed sale? This will deduct stock.')"
                                  style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check2-circle me-1"></i> Convert to Completed Sale
                                </button>
                            </form>
                        @else
                            @php
                                $hasPendingReturn = \App\Models\SaleReturn::where('sale_id', $sale->id)->where('status', 'pending')->exists();
                            @endphp
                            @if($hasPendingReturn)
                                <span class="badge bg-warning text-dark p-2"><i class="bi bi-hourglass-split me-1"></i> Return Request Pending</span>
                            @else
                                <a href="{{ route('sales-returns.create', $sale) }}" class="btn btn-danger">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Return Items / Refund
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
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
                        timer: 2000,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    }).then(() => {
                        window.location.reload();
                    });
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
                                timer: 2000,
                                showConfirmButton: false,
                                background: '#161b22',
                                color: '#e6edf3'
                            }).then(() => {
                                window.location.reload();
                            });
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


