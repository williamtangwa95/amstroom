@extends('layouts.app')
@section('title', 'Shop Inventory')
@section('page-title', 'Shop Stock')
@section('breadcrumb')
<li class="breadcrumb-item active">Shop Stock</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Shop Inventory</h5>
        <small style="color:var(--text-secondary);">Available products in retail shops</small>
    </div>
    @if($lowStockItems > 0)
    <div class="alert alert-warning py-1 px-3 mb-0" style="font-size:.8rem;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $lowStockItems }} item(s) low in stock!
    </div>
    @endif
</div>

@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('shop-stock.index') }}" class="row align-items-center g-2">
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
        <table class="table table-hover mb-0" id="shopStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Initial Qty</th>
                    <th>Remaining Qty</th>
                    <th>Alert Threshold</th>
                    <th>Selling Price</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $st)
                <tr class="{{ $st->isLowStock() ? 'low-stock-row' : '' }}">
                    <td style="font-size:.82rem;font-weight:600;">{{ $loop->iteration }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $st->shop->shop_name }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($st->item->image_path)
                            <img src="{{ asset('storage/' . $st->item->image_path) }}" alt="{{ $st->item->item_name }}" class="rounded" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);">
                            @else
                            <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 32px; height: 32px; border: 1px solid var(--card-border);">
                                <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:.83rem;">{{ $st->item->item_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-secondary);">{{ $st->item->brand }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $st->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">{{ $st->quantity }}</td>
                    <td>
                        <strong style="color:{{ $st->isLowStock() ? '#e94560' : '#3fb950' }};font-size:.9rem;">
                            {{ $st->remaining_quantity }}
                        </strong>
                        @if($st->isLowStock())
                        <i class="bi bi-exclamation-triangle-fill ms-1" style="color:#e94560;font-size:.75rem;" title="Low Stock!"></i>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ $st->low_stock_alert }} units</td>
                    <td style="font-size:.82rem;font-weight:600;">
                        TZS {{ number_format($st->selling_price, 0) }}
                        @if($st->is_price_pending)
                        <div class="small mt-1 text-warning" title="Pending Owner Approval">
                            <i class="bi bi-hourglass-split"></i> Pending: <strong>TZS {{ number_format($st->pending_selling_price, 0) }}</strong>
                        </div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($st->is_price_pending && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $st->shop_id)))
                            <button type="button" class="btn btn-xs btn-success btn-approve-price"
                                data-url="{{ route('shop-stock.approve-price', $st) }}"
                                data-pending-price="{{ $st->pending_selling_price }}"
                                data-buying-price="{{ $st->buying_price }}"
                                data-item-name="{{ $st->item->item_name }}"
                                title="Approve Price Change">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                            @endif
                            <a href="{{ route('shop-stock.show', $st) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Approve Price Modal -->
<div class="modal fade" id="approvePriceModal" tabindex="-1" aria-labelledby="approvePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="approvePriceModalLabel"><i class="bi bi-check-circle-fill text-success me-2"></i>Approve Price Change</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvePriceForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="mb-3 text-secondary" style="font-size:0.88rem;">
                        Assign a new selling price for <strong id="modalItemName" class="text-white"></strong>.
                        The owner proposed TZS <span id="modalProposedPrice" class="fw-bold text-success"></span>.
                    </p>
                    <div class="mb-3">
                        <label for="modalSellingPrice" class="form-label" style="font-size:0.8rem;">Selling Price (TZS)</label>
                        <input type="text" class="form-control" id="modalSellingPrice" required autocomplete="off">
                        <input type="hidden" id="modalSellingPriceHidden" name="selling_price">
                        <div id="modalBuyingPriceHelp" class="form-text text-muted" style="font-size:0.75rem;"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Save & Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#shopStockTable').DataTable();

        function formatNumber(val) {
            let clean = val.replace(/,/g, '');
            if (isNaN(parseFloat(clean))) return '';
            let parts = clean.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // Auto-comma formatter
        $('#modalSellingPrice').on('input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;

            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }

            this.value = formatNumber(cleanVal);
            $('#modalSellingPriceHidden').val(cleanVal);

            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        $('.btn-approve-price').on('click', function() {
            const url = $(this).data('url');
            const pendingPrice = $(this).data('pending-price');
            const buyingPrice = $(this).data('buying-price');
            const itemName = $(this).data('item-name');

            $('#approvePriceForm').attr('action', url);
            $('#modalItemName').text(itemName);
            $('#modalProposedPrice').text(parseFloat(pendingPrice).toLocaleString());

            // Set values
            const formattedPrice = formatNumber(pendingPrice.toString());
            $('#modalSellingPrice').val(formattedPrice).data('buying-price', buyingPrice);
            $('#modalSellingPriceHidden').val(pendingPrice);

            $('#modalBuyingPriceHelp').text('Minimum required price (Buying Price): TZS ' + parseFloat(buyingPrice).toLocaleString());

            const modal = new bootstrap.Modal(document.getElementById('approvePriceModal'));
            modal.show();
        });

        // Form validation
        $('#approvePriceForm').on('submit', function(e) {
            const buyingPrice = parseFloat($('#modalSellingPrice').data('buying-price'));
            const enteredPrice = parseFloat($('#modalSellingPriceHidden').val());

            if (enteredPrice < buyingPrice) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Price Too Low',
                    text: 'Selling price cannot be less than the buying price TZS ' + buyingPrice.toLocaleString() + '.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });
    });
</script>
@endpush