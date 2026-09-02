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
                        @if(\App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'DEPENDENT')
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

                        <div class="col-sm-6">
                            <label class="form-label fw-600">Remaining Quantity <span class="text-danger">*</span></label>
                            <input type="number" id="remaining_quantity" name="remaining_quantity"
                                class="form-control @error('remaining_quantity') is-invalid @enderror"
                                value="{{ old('remaining_quantity', $shopStock->remaining_quantity) }}" min="0" required>
                            <small class="text-muted">Current stocked qty: <strong>{{ $shopStock->quantity }}</strong>. Stocked qty auto-adjusts.</small>
                            @error('remaining_quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-600">Date Received <span class="text-danger">*</span></label>
                            <input type="date" id="date_received" name="date_received"
                                class="form-control @error('date_received') is-invalid @enderror"
                                value="{{ old('date_received', $shopStock->date_received ? $shopStock->date_received->format('Y-m-d') : '') }}" required>
                            @error('date_received')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="p-3 rounded mt-3" style="background:var(--input-bg);border:1px solid var(--input-border);font-size:0.84rem;">
                        <div class="row g-0 text-center">
                            <div class="col">
                                <div class="text-muted mb-1">Stocked</div>
                                <div class="fw-700" style="font-size:1.1rem;">{{ $shopStock->quantity }}</div>
                            </div>
                            <div class="col border-start border-end">
                                <div class="text-muted mb-1">Remaining</div>
                                <div class="fw-700" style="font-size:1.1rem;color:{{ $shopStock->isLowStock() ? '#e94560' : '#3fb950' }};">{{ $shopStock->remaining_quantity }}</div>
                            </div>
                            <div class="col">
                                <div class="text-muted mb-1">Sold</div>
                                <div class="fw-700" style="font-size:1.1rem;">{{ $shopStock->quantity - $shopStock->remaining_quantity }}</div>
                            </div>
                        </div>
                        <div class="text-muted text-center mt-2" style="font-size:0.76rem;">
                            <i class="bi bi-info-circle me-1"></i>Changing remaining qty proportionally adjusts stocked qty.
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
    var warning   = $('#priceWarning');
    var submitBtn = $('#submitBtn');

    function validatePrices() {
        var bp = parseInt(bpHidden.val() || 0);
        var sp = parseInt(spHidden.val() || 0);
        if (sp < bp) {
            warning.show(); spDisplay.addClass('is-invalid'); submitBtn.prop('disabled', true);
        } else {
            warning.hide(); spDisplay.removeClass('is-invalid'); submitBtn.prop('disabled', false);
        }
    }

    bpDisplay.on('input', function () {
        var clean = this.value.replace(/[^0-9]/g, '');
        this.value = formatNum(clean);
        bpHidden.val(clean);
        validatePrices();
    });

    spDisplay.on('input', function () {
        var clean = this.value.replace(/[^0-9]/g, '');
        this.value = formatNum(clean);
        spHidden.val(clean);
        validatePrices();
    });
    @endif
});
</script>
@endpush
