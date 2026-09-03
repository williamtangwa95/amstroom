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
        <small style="color:var(--text-secondary);">Total Revenue: <strong style="color:#3fb950;">TZS {{ number_format($totalRevenue, 0) }}</strong></small>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> New Sale</a>
</div>

<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
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
                @foreach($sales as $sale)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.78rem;color:var(--text-secondary);">#SL-{{ $sale->id }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</td>
                    <td style="font-size:.82rem;">{{ $sale->seller->name }}</td>
                    <td style="font-size:.82rem;">
                        <span>{{ $sale->customer_name ?: 'Walk-in' }}</span>
                        <button type="button" class="btn btn-link btn-xs p-0 ms-1 edit-customer-btn" style="color:var(--accent);" data-id="{{ $sale->id }}" data-name="{{ $sale->customer_name }}" title="Add / Edit Customer Name">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                    <td><span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;">{{ $sale->items->count() }} item(s)</span></td>
                    <td style="font-size:.78rem;">{{ str_replace('_', ' ', ucfirst($sale->payment_method)) }}</td>
                    <td><strong style="color:#3fb950;font-size:.9rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong></td>
                    <td>
                        @if($sale->status === 'draft_proforma')
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;">Proforma</span>
                        @else
                            <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.72rem;">Completed</span>
                        @endif
                    </td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $sale->sale_date->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                            <button type="button" class="btn btn-xs btn-outline-custom edit-customer-btn" data-id="{{ $sale->id }}" data-name="{{ $sale->customer_name }}" title="Add / Edit Customer Name">
                                <i class="bi bi-person-gear"></i>
                            </button>
                            @if($sale->status === 'completed')
                                <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-xs btn-outline-custom" title="Print Invoice" target="_blank"><i class="bi bi-file-earmark-text"></i></a>
                                <a href="{{ route('sales.proforma', $sale) }}" class="btn btn-xs btn-outline-custom" title="Print Proforma" target="_blank"><i class="bi bi-file-earmark"></i></a>
                                <a href="{{ route('sales.delivery-note', $sale) }}" class="btn btn-xs btn-outline-custom" title="Print Delivery Note" target="_blank"><i class="bi bi-truck"></i></a>
                                <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-xs btn-accent" title="Print Receipt"><i class="bi bi-receipt"></i></a>
                            @else
                                <button class="btn btn-xs btn-outline-secondary" disabled title="Invoice requires a completed sale."><i class="bi bi-file-earmark-text"></i></button>
                                <a href="{{ route('sales.proforma', $sale) }}" class="btn btn-xs btn-outline-custom" title="Print Proforma Invoice" target="_blank"><i class="bi bi-file-earmark"></i></a>
                                <button class="btn btn-xs btn-outline-secondary" disabled title="Delivery Note requires a completed sale."><i class="bi bi-truck"></i></button>
                                <button class="btn btn-xs btn-outline-secondary" disabled title="Receipt requires a completed sale."><i class="bi bi-receipt"></i></button>
                            @endif
                            <button type="button" class="btn btn-xs btn-outline-custom toggle-details" data-id="{{ $sale->id }}" title="Toggle Details">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>


                        <!-- Hidden details template container -->
                        <div id="details-{{ $sale->id }}" class="d-none">
                            <div class="p-3 my-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
                                <h6 class="fw-700 mb-2" style="font-size:.9rem; color:var(--accent);"><i class="bi bi-list-task me-1"></i> Sold Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="background: var(--card-bg); border-color: var(--card-border);">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Selling Price</th>
                                                <th class="text-end">Subtotal</th>
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
                                                @endphp
                                                <tr>
                                                    <td style="font-weight:600; font-size:.82rem;">{{ $item->display_name }}</td>
                                                    <td class="text-center" style="font-size:.82rem;">{{ $item->quantity }}</td>
                                                    <td class="text-end" style="font-size:.82rem;">TZS {{ number_format($displayPrice, 0) }}</td>
                                                    <td class="text-end" style="font-size:.82rem;"><strong style="color:#3fb950;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                                                </tr>
                                                @if($item->components->isNotEmpty())
                                                    @foreach($item->components as $component)
                                                        <tr style="background-color: rgba(0,0,0,0.015);">
                                                            <td style="font-size:.78rem; padding-left: 1.5rem; color: var(--text-secondary);">
                                                                <span class="text-muted">└─</span> {{ $component->display_name }}
                                                            </td>
                                                            <td class="text-center" style="font-size:.78rem; color: var(--text-secondary);">
                                                                {{ $component->quantity }}
                                                            </td>
                                                            <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                                                Included
                                                            </td>
                                                            <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                                                Included
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-700" style="font-size:.82rem;">{{ $isOwner ? 'Total Revenue Realized:' : 'Total Amount Paid:' }}</td>
                                                <td class="text-end" style="font-size:.82rem;"><strong style="color:#3fb950;font-size:1rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
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
        var table = $('#salesTable').DataTable();

        $('#salesTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var saleId = $(this).data('id');
            var targetDiv = $('#details-' + saleId);
            var icon = $(this).find('i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                row.child(targetDiv.html()).show();
                tr.addClass('shown');
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
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

