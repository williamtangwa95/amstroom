@extends('layouts.app')
@section('title', 'Add Stock')
@section('page-title', 'Add to Main Store')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">Add Stock</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-building-fill me-2" style="color:#e94560;"></i>Receive Stock to Main Warehouse</div>
            <div class="card-body">
                <form method="POST" action="{{ route('main-stock.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product *</label>
                            <select name="item_id" id="item_id_select" class="form-select" required>
                                <option value="">Select product...</option>
                                @foreach($items as $item)
                                @php
                                    $hasMainStock = $item->mainStock && $item->mainStock->selling_price > 0;
                                    $buyingPrice = $hasMainStock ? number_format($item->mainStock->buying_price, 0, '', '') : '';
                                    $sellingPrice = $hasMainStock ? number_format($item->mainStock->selling_price, 0, '', '') : '';
                                @endphp
                                <option value="{{ $item->id }}"
                                    data-buying-price="{{ $buyingPrice }}"
                                    data-selling-price="{{ $sellingPrice }}"
                                    {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                    [{{ $item->category->category_name }}] {{ $item->item_name }} {{ $item->brand ? "($item->brand)" : '' }}
                                    @if($hasMainStock)
                                        (Available: BP {{ number_format($item->mainStock->buying_price, 0) }} / SP {{ number_format($item->mainStock->selling_price, 0) }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            <div id="priceSuggestionNotice" class="text-info small mt-1" style="display: none;">
                                <i class="bi bi-info-circle-fill me-1"></i> Suggested current Main Store prices auto-filled below. You can keep or update them.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Buying Price (TZS) *</label>
                            <input type="text" name="buying_price" id="main_buying_price" class="form-control currency-input" value="{{ old('buying_price') }}" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (TZS) *</label>
                            <input type="text" name="selling_price" id="main_selling_price" class="form-control currency-input" value="{{ old('selling_price') }}" min="0" required>
                            <div id="mainStockWarning" class="text-danger small mt-1" style="display: none;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="stocked_quantity" class="form-control" value="{{ old('stocked_quantity') }}" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Received *</label>
                            <input type="date" name="date_received" class="form-control" value="{{ old('date_received', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Add to Warehouse</button>
                        <a href="{{ route('main-stock.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const buyingInput = $('#main_buying_price');
    const sellingInput = $('#main_selling_price');
    const warning = $('#mainStockWarning');
    const suggestionNotice = $('#priceSuggestionNotice');
    const itemSelect = $('#item_id_select');
    const submitBtn = buyingInput.closest('form').find('button[type="submit"]');

    function validatePrices() {
        const buying = parseFloat(buyingInput.val().replace(/,/g, '') || 0);
        const selling = parseFloat(sellingInput.val().replace(/,/g, '') || 0);
        
        if (selling > 0 && buying > 0 && selling < buying) {
            warning.show();
            sellingInput.addClass('is-invalid');
            submitBtn.prop('disabled', true);
        } else {
            warning.hide();
            sellingInput.removeClass('is-invalid');
            submitBtn.prop('disabled', false);
        }
    }

    itemSelect.on('change', function() {
        const selectedOpt = $(this).find('option:selected');
        const buyingPrice = selectedOpt.data('buying-price');
        const sellingPrice = selectedOpt.data('selling-price');

        if (buyingPrice !== undefined && buyingPrice !== '' && sellingPrice !== undefined && sellingPrice !== '') {
            buyingInput.val(buyingPrice).trigger('input');
            sellingInput.val(sellingPrice).trigger('input');
            suggestionNotice.slideDown(200);
        } else {
            suggestionNotice.slideUp(200);
        }
    });

    buyingInput.on('input', validatePrices);
    sellingInput.on('input', validatePrices);

    // Trigger suggestion on page load if item is pre-selected (e.g. old input)
    if (itemSelect.val()) {
        itemSelect.trigger('change');
    }
});
</script>
@endpush
