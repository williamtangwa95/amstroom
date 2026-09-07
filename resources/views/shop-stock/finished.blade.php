@extends('layouts.app')
@section('title', 'Finished Stocks')
@section('page-title', 'Finished Stocks (Out of Stock)')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Shop Stock</a></li>
<li class="breadcrumb-item active">Finished Stocks</li>
@endsection

@section('content')
<style>
    #finishedStockTable .bi {
        font-size: 0.78rem !important;
    }
    #finishedStockTable .btn .bi {
        font-size: 0.72rem !important;
    }
    #finishedStockTable .btn,
    #finishedStockTable .btn-xs {
        padding: 4px 8px !important;
        font-size: 0.76rem !important;
        line-height: 1.3 !important;
    }
    .hover-lift {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .hover-lift:hover {
        transform: translateY(-1.5px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
    }
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h5 class="mb-0 fw-700" style="color: var(--text-primary); font-size: 1.25rem;">Finished Stocks</h5>
        <small style="color: var(--text-secondary); font-size: 0.82rem;">Products with zero total remaining quantity across all batches. Easily review sales velocity and restock.</small>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('shop-stock.index') }}" class="btn btn-sm btn-outline-secondary fw-600 rounded-3 shadow-xs hover-lift d-inline-flex align-items-center px-3 py-1.5" style="font-size: 0.82rem;">
            <i class="bi bi-arrow-left me-1.5"></i> Back to Shop Stocks
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card premium-stat-card p-3 d-flex align-items-center gap-3" style="border-left: 4px solid var(--accent-red) !important;">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(239, 68, 68, 0.12); color: var(--accent-red); width: 42px; height: 42px; font-size: 1.1rem; border-radius: 10px; flex-shrink: 0;">
                <i class="bi bi-archive-fill"></i>
            </div>
            <div>
                <div class="stat-value mb-0 text-danger" style="font-size: 1.35rem; font-weight: 800; line-height: 1.2;">{{ number_format($finishedCount) }}</div>
                <div class="stat-label text-muted" style="font-size: 0.75rem; font-weight: 600;">Total Finished Products</div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('shop-stock.finished') }}" class="row align-items-center g-2">
            <div class="col-auto"><label class="form-label mb-0">Filter by Shop:</label></div>
            <div class="col-auto">
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="finishedStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Initial Qty</th>
                    <th>Remaining Qty</th>
                    <th>Average Sales</th>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th>Buying Price</th>
                    @endif
                    <th>Selling Price</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Restock Modal -->
<div class="modal fade" id="quickRestockModal" tabindex="-1" aria-labelledby="quickRestockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="quickRestockModalLabel"><i class="bi bi-plus-circle text-success me-2"></i>Quick Restock Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.quick-restock') }}" method="POST" id="quickRestockForm">
                @csrf
                <input type="hidden" name="shop_id" id="restockShopId">
                <input type="hidden" name="item_id" id="restockItemId">
                <input type="hidden" name="buying_price" id="restockBuyingPrice">
                <input type="hidden" name="selling_price" id="restockSellingPrice">
                <input type="hidden" name="low_stock_alert" id="restockLowStockAlert">
                <input type="hidden" name="is_admin_stock" id="restockIsAdminStock">

                <div class="modal-body">
                    <div class="p-3 mb-3 rounded" style="background:rgba(255,255,255,0.03); border:1px solid var(--card-border);">
                        <div class="fw-bold fs-6 text-white" id="restockItemName">Product Name</div>
                        <small class="text-secondary" id="restockDetailsHelp">Restocking item back into active inventory</small>
                    </div>

                    @if(auth()->user()->isOwner())
                    <div class="mb-3 p-2 rounded" style="background:rgba(2, 132, 199, 0.08); border:1px solid rgba(2, 132, 199, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-building me-1"></i> Available in Main Warehouse:</span>
                            <span class="fw-bold text-accent" id="restockWarehouseAvailable">Loading...</span>
                        </div>
                        <div id="restockWarehouseWarning" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Quantity exceeds available stock in Main Warehouse!
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="restock_quantity" class="form-label">Quantity to Add *</label>
                        <input type="number" name="quantity" id="restock_quantity" class="form-control" min="1" required autocomplete="off" placeholder="e.g. 10">
                    </div>

                    <div class="mb-3">
                        <label for="restock_date_received" class="form-label">Date Received *</label>
                        <input type="date" name="date_received" id="restock_date_received" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="restockSubmitBtn"><i class="bi bi-plus-lg me-1"></i> Add Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const columns = [
            { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
            { data: 'shop', name: 'shop' },
            { data: 'product', name: 'product' },
            { data: 'category', name: 'category' },
            { data: 'initial_qty', name: 'initial_qty' },
            { data: 'remaining_qty', name: 'remaining_qty' },
            { data: 'average_sales', name: 'average_sales', orderable: false, searchable: false },
            @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
            { data: 'buying_price', name: 'buying_price' },
            @endif
            { data: 'selling_price', name: 'selling_price' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ];

        const table = $('#finishedStockTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('shop-stock.finished-data') }}",
                data: function(d) {
                    d.shop_id = $('select[name="shop_id"]').val();
                }
            },
            columns: columns,
            order: [[2, 'asc']]
        });

        // Quick Restock Modal Handler
        $('#finishedStockTable tbody').on('click', '.btn-quick-restock', function() {
            const shopId = $(this).data('shop-id');
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');
            const buyingPrice = $(this).data('buying-price');
            const sellingPrice = $(this).data('selling-price');
            const lowStockAlert = $(this).data('low-stock-alert');
            const isAdminStock = $(this).data('is-admin-stock');

            $('#restockShopId').val(shopId);
            $('#restockItemId').val(itemId);
            $('#restockItemName').text(itemName);
            $('#restockBuyingPrice').val(buyingPrice);
            $('#restockSellingPrice').val(sellingPrice);
            $('#restockLowStockAlert').val(lowStockAlert);
            $('#restockIsAdminStock').val(isAdminStock);

            $('#restock_quantity').val('').removeClass('is-invalid');
            $('#restockWarehouseWarning').addClass('d-none');
            $('#restockSubmitBtn').prop('disabled', false);

            @if(auth()->user()->isOwner())
            $('#restockWarehouseAvailable').text('Loading...');
            $.get('{{ route("shop-stock.warehouse-available") }}', { item_id: itemId })
                .done(function(res) {
                    const available = parseInt(res.available || 0);
                    $('#restockWarehouseAvailable').text(available.toLocaleString());
                    $('#restockWarehouseAvailable').data('available', available);
                })
                .fail(function() {
                    $('#restockWarehouseAvailable').text('N/A');
                });
            @endif

            new bootstrap.Modal(document.getElementById('quickRestockModal')).show();
        });
    });
</script>
@endpush
@endsection
