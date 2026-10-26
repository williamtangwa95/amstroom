@extends('layouts.app')
@section('title', 'Edit Stock Batch')
@section('page-title', 'Edit Stock Batch')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Shop Stock</a></li>
<li class="breadcrumb-item active">Edit Batch</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square" style="color:#3fb950;"></i>
                <span class="fw-600">{{ $shopStock->item->item_name }}</span>
                <span class="text-muted small ms-auto">{{ $shopStock->shop->shop_name }}</span>
            </div>
            <div class="card-body">

                <div class="p-3 rounded mb-4" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <div class="row g-2" style="font-size:0.84rem;">
                        <div class="col-6">
                            <span class="text-muted">Category</span><br>
                            <strong>{{ $shopStock->item->category->category_name ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Type</span><br>
                            @if($shopStock->is_admin_stock)
                            <span class="badge" style="background:#1f6feb22;color:#58a6ff;border:1px solid #1f6feb;">Admin Stock</span>
                            @else
                            <span class="badge" style="background:#388bfd22;color:#79c0ff;border:1px solid #388bfd;">Owner Stock</span>
                            @endif
                        </div>
                        @if($shopStock->item->brand)
                        <div class="col-6 mt-1">
                            <span class="text-muted">Brand</span><br>
                            <strong>{{ $shopStock->item->brand }}</strong>
                        </div>
                        @endif
                        @if($shopStock->item->model)
                        <div class="col-6 mt-1">
                            <span class="text-muted">Model</span><br>
                            <strong>{{ $shopStock->item->model }}</strong>
                        </div>
                        @endif
                    </div>
                </div>

                @if($shopStock->is_admin_stock && $shopStock->remaining_quantity > 0 && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $shopStock->shop_id)))
                <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded flex-wrap gap-2" style="background:rgba(227, 179, 65, 0.1); border:1px solid rgba(227, 179, 65, 0.3);">
                    <div>
                        <h6 class="mb-1 text-warning fw-700"><i class="bi bi-arrow-left-right me-1"></i> Stocked as Admin Stock by mistake?</h6>
                        <p class="mb-0 text-secondary" style="font-size:0.84rem;">
                            Transfer the remaining <strong>{{ $shopStock->remaining_quantity }}</strong> unit(s) of this batch directly to <strong>Owner Stock</strong> without needing to delete and recreate.
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#transferEditModal">
                        <i class="bi bi-arrow-left-right me-1"></i>Transfer to Owner
                    </button>
                </div>
                @endif

                @if(auth()->user()->isShopAdmin() && !$shopStock->is_admin_stock)
                {{-- Shop Admin editing owner-transferred stock: selling price only --}}
                <form method="POST" action="{{ route('shop-stock.update', $shopStock) }}" id="editStockForm">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Buying Price</label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" class="form-control" style="background-color: var(--input-bg); opacity: 0.7;"
                                    value="{{ number_format($shopStock->buying_price, 0) }}" readonly>
                                <input type="hidden" name="buying_price" value="{{ (int)$shopStock->buying_price }}">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-600" for="selling_price_display">Selling Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" id="selling_price_display" class="form-control @error('selling_price') is-invalid @enderror"
                                    value="{{ number_format(old('selling_price', (int)$shopStock->selling_price), 0) }}" autocomplete="off">
                                <input type="hidden" id="selling_price" name="selling_price" value="{{ old('selling_price', (int)$shopStock->selling_price) }}">
                            </div>
                            <div id="priceWarning" class="text-danger small mt-1" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Selling price cannot be less than buying price (TZS {{ number_format($shopStock->buying_price, 0) }}).
                            </div>
                            <small class="text-muted">Minimum: TZS {{ number_format($shopStock->buying_price, 0) }}</small>
                            @error('selling_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="remaining_quantity" value="{{ $shopStock->remaining_quantity }}">
                    <input type="hidden" name="date_received" value="{{ $shopStock->date_received ? $shopStock->date_received->format('Y-m-d') : '' }}">

                    <div class="alert alert-warning py-2 px-3 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Only selling price can be edited for owner-transferred stock.
                        @if(\App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'DEPENDENT')
                        The change will be sent for <strong>owner approval</strong>.
                        @else
                        The change will be applied <strong>immediately</strong>.
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent" id="submitBtn">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('shop-stock.index', ['shop_id' => $shopStock->shop_id]) }}" class="btn btn-outline-custom">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                    </div>
                </form>

                @else
                {{-- Owner or Admin editing admin stock: full edit --}}
                <form method="POST" action="{{ route('shop-stock.update', $shopStock) }}" id="editStockForm">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        @if(auth()->user()->isOwner())
                        <input type="hidden" id="buying_price" name="buying_price" value="{{ old('buying_price', (int)$shopStock->buying_price) }}">
                        <input type="hidden" id="selling_price" name="selling_price" value="{{ old('selling_price', (int)$shopStock->selling_price) }}">
                        @php
                            $msStock = \App\Models\MainStock::where('item_id', $shopStock->item_id)->orderByDesc('date_received')->first();
                        @endphp
                        @if($msStock)
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Main Store Buying Price (BP)</label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" class="form-control" style="background-color: var(--input-bg); opacity: 0.7;"
                                    value="{{ number_format($msStock->buying_price, 0) }}" readonly>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Main Store Selling Price (SP)</label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" class="form-control" style="background-color: var(--input-bg); opacity: 0.7;"
                                    value="{{ number_format($msStock->selling_price, 0) }}" readonly>
                            </div>
                        </div>
                        @endif
                        @else
                        <div class="col-sm-6">
                            <label class="form-label fw-600">Buying Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" id="buying_price_display" class="form-control @error('buying_price') is-invalid @enderror"
                                    value="{{ number_format(old('buying_price', (int)$shopStock->buying_price), 0) }}" autocomplete="off">
                                <input type="hidden" id="buying_price" name="buying_price" value="{{ old('buying_price', (int)$shopStock->buying_price) }}">
                            </div>
                            @error('buying_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-600">Selling Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="text" id="selling_price_display" class="form-control @error('selling_price') is-invalid @enderror"
                                    value="{{ number_format(old('selling_price', (int)$shopStock->selling_price), 0) }}" autocomplete="off">
                                <input type="hidden" id="selling_price" name="selling_price" value="{{ old('selling_price', (int)$shopStock->selling_price) }}">
                            </div>
                            <div id="priceWarning" class="text-danger small mt-1" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Must be greater than or equal to buying price.
                            </div>
                            @error('selling_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="col-sm-4">
                            <label class="form-label fw-600">Stocked Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="quantity" name="quantity"
                                class="form-control @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity', $shopStock->quantity) }}" min="1" required autocomplete="off">
                            <small class="text-muted">Must be at least 1</small>
                            @error('quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label fw-600">Remaining Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="remaining_quantity" name="remaining_quantity"
                                class="form-control @error('remaining_quantity') is-invalid @enderror"
                                value="{{ old('remaining_quantity', $shopStock->remaining_quantity) }}" min="0" required autocomplete="off">
                            <small class="text-muted">Cannot exceed stocked qty</small>
                            @error('remaining_quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label fw-600">Date Received <span class="text-danger">*</span></label>
                            <input type="date" id="date_received" name="date_received"
                                class="form-control @error('date_received') is-invalid @enderror"
                                value="{{ old('date_received', $shopStock->date_received ? $shopStock->date_received->format('Y-m-d') : '') }}" required>
                            @error('date_received')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div id="qtyWarning" class="alert alert-danger py-2 px-3 small mb-0" style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i><span id="qtyWarningMsg"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);font-size:0.84rem;">
                        <div class="row g-0 text-center">
                            <div class="col">
                                <div class="text-muted mb-1">Stocked</div>
                                <div class="fw-700" style="font-size:1.1rem;" id="summary_stocked">{{ $shopStock->quantity }}</div>
                            </div>
                            <div class="col border-start border-end">
                                <div class="text-muted mb-1">Remaining</div>
                                <div class="fw-700" style="font-size:1.1rem;color:{{ $shopStock->isLowStock() ? '#e94560' : '#3fb950' }};" id="summary_remaining">{{ $shopStock->remaining_quantity }}</div>
                            </div>
                            <div class="col">
                                <div class="text-muted mb-1">Sold</div>
                                <div class="fw-700" style="font-size:1.1rem;" id="summary_sold">{{ $shopStock->quantity - $shopStock->remaining_quantity }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent" id="submitBtn">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('shop-stock.index', ['shop_id' => $shopStock->shop_id]) }}" class="btn btn-outline-custom">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                        <a href="{{ route('shop-stock.show', $shopStock) }}" class="btn btn-outline-custom ms-auto">
                            <i class="bi bi-eye me-1"></i>View Details
                        </a>
                    </div>
                </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function formatNum(val) {
        var n = val.toString().replace(/[^0-9]/g, '');
        return n ? parseInt(n, 10).toLocaleString('en-US') : '';
    }

    @if(auth()->user()->isShopAdmin() && !$shopStock->is_admin_stock)
    var buyingPrice = {{ (int)$shopStock->buying_price }};
    var spDisplay = $('#selling_price_display');
    var spHidden  = $('#selling_price');
    var warning   = $('#priceWarning');
    var submitBtn = $('#submitBtn');

    spDisplay.on('input', function () {
        var clean = this.value.replace(/[^0-9]/g, '');
        this.value = formatNum(clean);
        spHidden.val(clean);
        if (parseInt(clean || 0) < buyingPrice) {
            warning.show(); spDisplay.addClass('is-invalid'); submitBtn.prop('disabled', true);
        } else {
            warning.hide(); spDisplay.removeClass('is-invalid'); submitBtn.prop('disabled', false);
        }
    });

    @else
    var bpDisplay = $('#buying_price_display');
    var bpHidden  = $('#buying_price');
    var spDisplay = $('#selling_price_display');
    var spHidden  = $('#selling_price');
    var priceWarning = $('#priceWarning');
    var qtyWarning   = $('#qtyWarning');
    var qtyWarningMsg = $('#qtyWarningMsg');
    var submitBtn = $('#submitBtn');
    var qtyInput  = $('#quantity');
    var remInput  = $('#remaining_quantity');

    function validateForm() {
        var bp = parseInt(bpHidden.val() || 0);
        var sp = parseInt(spHidden.val() || 0);
        var stocked = parseInt(qtyInput.val() || 0);
        var remaining = parseInt(remInput.val() || 0);

        var priceValid = true;
        var qtyValid = true;

        if (spDisplay.length && priceWarning.length) {
            if (sp < bp) {
                priceWarning.show(); spDisplay.addClass('is-invalid'); priceValid = false;
            } else {
                priceWarning.hide(); spDisplay.removeClass('is-invalid');
            }
        }

        if (stocked < 1) {
            qtyWarningMsg.text('Stocked quantity cannot be zero (must be at least 1).');
            qtyWarning.show();
            qtyInput.addClass('is-invalid');
            qtyValid = false;
        } else if (remaining > stocked) {
            qtyWarningMsg.text('Remaining quantity (' + remaining + ') cannot be greater than stocked quantity (' + stocked + ').');
            qtyWarning.show();
            remInput.addClass('is-invalid');
            qtyValid = false;
        } else if (remaining < 0) {
            qtyWarningMsg.text('Remaining quantity cannot be negative.');
            qtyWarning.show();
            remInput.addClass('is-invalid');
            qtyValid = false;
        } else {
            qtyWarning.hide();
            qtyInput.removeClass('is-invalid');
            remInput.removeClass('is-invalid');
        }

        $('#summary_stocked').text(stocked);
        $('#summary_remaining').text(remaining);
        $('#summary_sold').text(stocked - remaining);

        submitBtn.prop('disabled', !(priceValid && qtyValid));
    }

    if (bpDisplay.length) {
        bpDisplay.on('input', function () {
            var clean = this.value.replace(/[^0-9]/g, '');
            this.value = formatNum(clean);
            bpHidden.val(clean);
            validateForm();
        });
    }

    if (spDisplay.length) {
        spDisplay.on('input', function () {
            var clean = this.value.replace(/[^0-9]/g, '');
            this.value = formatNum(clean);
            spHidden.val(clean);
            validateForm();
        });
    }

    qtyInput.on('input change', validateForm);
    remInput.on('input change', validateForm);
    @endif

    // Transfer Modal Price formatting & validation
    function validateEditTransferPrices() {
        var bp = parseFloat($('#editTransferBP').val() || 0);
        var sp = parseFloat($('#editTransferSP').val() || 0);
        var warning = $('#editTransferPriceWarning');
        var btn = $('#editConfirmTransferBtn');

        if (sp < bp) {
            warning.show();
            $('#editTransferSPDisplay').addClass('is-invalid');
            btn.prop('disabled', true);
        } else {
            warning.hide();
            $('#editTransferSPDisplay').removeClass('is-invalid');
            btn.prop('disabled', false);
        }
    }

    $('#editTransferBPDisplay').on('input', function() {
        var clean = this.value.replace(/[^0-9]/g, '');
        this.value = formatNum(clean);
        $('#editTransferBP').val(clean);
        validateEditTransferPrices();
    });

    $('#editTransferSPDisplay').on('input', function() {
        var clean = this.value.replace(/[^0-9]/g, '');
        this.value = formatNum(clean);
        $('#editTransferSP').val(clean);
        validateEditTransferPrices();
    });
});
</script>

@if($shopStock->is_admin_stock && $shopStock->remaining_quantity > 0 && (auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && auth()->user()->shop_id == $shopStock->shop_id)))
<!-- Transfer Modal for Edit Page -->
<div class="modal fade" id="transferEditModal" tabindex="-1" aria-labelledby="transferEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="transferEditModalLabel">
                    <i class="bi bi-arrow-left-right text-warning me-2"></i>Transfer to Owner Stock
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('shop-stock.transfer-to-owner', $shopStock) }}">
                @csrf
                <div class="modal-body">
                    <div class="p-3 rounded mb-3" style="background:var(--input-bg); border:1px solid var(--input-border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Product:</span>
                            <strong class="text-white text-end">{{ $shopStock->item->item_name ?? 'Product' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Batch ID:</span>
                            <span class="badge bg-secondary">#{{ $shopStock->id }}</span>
                        </div>
                        <hr class="my-2" style="border-color:var(--card-border);">
                        <div class="row g-2 text-center" style="font-size:0.82rem;">
                            <div class="col-4">
                                <span class="text-muted d-block small">Initial Qty</span>
                                <strong class="fs-6">{{ $shopStock->quantity }}</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block small">Sold Qty</span>
                                <strong class="fs-6 text-info">{{ max(0, $shopStock->quantity - $shopStock->remaining_quantity) }}</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block small">To Transfer</span>
                                <strong class="fs-6 text-success">{{ $shopStock->remaining_quantity }}</strong>
                            </div>
                        </div>
                    </div>

                    @php
                        $soldQty = max(0, $shopStock->quantity - $shopStock->remaining_quantity);
                    @endphp
                    @if($soldQty === 0)
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> <strong>Full Transfer:</strong> No sales have occurred for this batch yet. All <strong>{{ $shopStock->remaining_quantity }}</strong> unit(s) will be converted directly into Owner Stock.
                    </div>
                    @else
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Partial Transfer:</strong> <strong>{{ $soldQty }}</strong> unit(s) have already been sold as Admin Stock and will be <strong>retained as finished stock</strong> with 0 remaining to safeguard past receipts & audit reports. The remaining <strong>{{ $shopStock->remaining_quantity }}</strong> unit(s) will be transferred into a new Owner Stock batch.
                    </div>
                    @endif

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-600 mb-1">Buying Price (TZS)</label>
                            <input type="text" class="form-control form-control-sm" id="editTransferBPDisplay" value="{{ number_format($shopStock->buying_price, 0) }}" required autocomplete="off">
                            <input type="hidden" id="editTransferBP" name="buying_price" value="{{ (float)$shopStock->buying_price }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-600 mb-1">Selling Price (TZS)</label>
                            <input type="text" class="form-control form-control-sm" id="editTransferSPDisplay" value="{{ number_format($shopStock->selling_price, 0) }}" required autocomplete="off">
                            <input type="hidden" id="editTransferSP" name="selling_price" value="{{ (float)$shopStock->selling_price }}">
                        </div>
                    </div>
                    <div id="editTransferPriceWarning" class="text-danger small mt-1" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Selling price cannot be less than buying price!
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold" id="editConfirmTransferBtn">
                        <i class="bi bi-arrow-left-right me-1"></i>Confirm Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endpush
