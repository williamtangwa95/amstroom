@extends('layouts.app')
@section('title', 'Propose Product Swap')
@section('page-title', 'Propose Product Swap')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-swaps.index') }}">Product Swaps</a></li>
<li class="breadcrumb-item active">Propose Swap</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-custom p-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-arrow-left-right fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-700">Propose Inter-Shop Product Swap</h6>
                        <small class="text-muted" style="font-size:.75rem;">
                            Exchange an item from <strong>{{ $sourceShop->shop_name }}</strong> with an item from another branch.
                        </small>
                    </div>
                </div>
                <a href="{{ route('shop-swaps.index') }}" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-arrow-left me-1"></i> Back to Swaps
                </a>
            </div>

            <div class="card-body p-4">
                @if($errors->any())
                <div class="alert alert-danger border-0 rounded-3 mb-4" style="font-size:.85rem;">
                    <div class="fw-700 mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please resolve the following errors:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('shop-swaps.store') }}" id="createSwapForm">
                    @csrf

                    {{-- Step 1: Source Item (Shop A offers) --}}
                    <div class="p-3 rounded-3 mb-4 border" style="background:var(--input-bg); border-color:var(--input-border) !important;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary px-2.5 py-1.5 fw-700" style="font-size:.78rem;">
                                <i class="bi bi-box-arrow-up-right me-1"></i> 1. Product Offered (Shop A: {{ $sourceShop->shop_name }})
                            </span>
                            @if(auth()->user()->isOwner())
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0">Source Shop:</label>
                                <select name="source_shop_id" id="sourceShopSelect" class="form-select form-select-sm" style="width: 190px;" onchange="window.location.href='{{ route('shop-swaps.create') }}?source_shop_id=' + this.value">
                                    @foreach($shops as $s)
                                    <option value="{{ $s->id }}" {{ $s->id == $sourceShop->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            <input type="hidden" name="source_shop_id" id="sourceShopSelect" value="{{ $sourceShop->id }}">
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-700">Select Item to Swap Out <span class="text-danger">*</span></label>
                                <select name="source_item_id" id="sourceItemSelect" class="form-select form-select-sm" required>
                                    <option value="">Choose item currently in stock at {{ $sourceShop->shop_name }}...</option>
                                    @foreach($sourceStockItems as $st)
                                    <option value="{{ $st->item_id }}" 
                                        data-name="{{ $st->item->item_name }}"
                                        data-cat-id="{{ $st->item->category_id }}"
                                        data-cat-name="{{ $st->item->category?->category_name ?? 'General' }}"
                                        data-unit="{{ $st->item->unit_symbol }}"
                                        data-avail="{{ $st->remaining_quantity }}"
                                        data-brand="{{ $st->item->brand }}"
                                        data-model="{{ $st->item->model }}"
                                        data-spec="{{ $st->item->specification }}"
                                        {{ (old('source_item_id', $preselectedItemId) == $st->item_id) ? 'selected' : '' }}>
                                        [{{ $st->item->category?->category_name ?? 'General' }}] {{ $st->item->item_name }} (Avail: {{ $st->remaining_quantity }} {{ $st->item->unit_symbol }})
                                    </option>
                                    @endforeach
                                </select>
                                <div id="sourceItemMeta" class="mt-1 text-muted small d-none" style="font-size:.75rem;"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-700">Quantity to Swap Out <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="source_quantity" id="sourceQtyInput" class="form-control text-center fw-700" value="{{ old('source_quantity', 1) }}" min="1" required>
                                    <span class="input-group-text bg-light text-muted" id="sourceUnitSpan">pc</span>
                                </div>
                                <small class="text-muted d-block mt-1" id="sourceAvailHelp" style="font-size:.72rem;">Available in Shop A: —</small>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Destination Shop & Requested Item --}}
                    <div class="p-3 rounded-3 mb-4 border" style="background:var(--input-bg); border-color:var(--input-border) !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="badge bg-info text-dark px-2.5 py-1.5 fw-700" style="font-size:.78rem;">
                                <i class="bi bi-box-arrow-in-down-left me-1"></i> 2. Product Requested (Destination Shop B)
                            </span>

                            {{-- Filter Toggle --}}
                            <div class="btn-group btn-group-sm" role="group" id="filterTypeGroup">
                                <button type="button" class="btn btn-outline-custom active filter-btn" data-filter="same_category">
                                    <i class="bi bi-tags me-1"></i> Same Category
                                </button>
                                <button type="button" class="btn btn-outline-custom filter-btn" data-filter="same_product">
                                    <i class="bi bi-layers me-1"></i> Exact Same Product
                                </button>
                                <button type="button" class="btn btn-outline-custom filter-btn" data-filter="all">
                                    <i class="bi bi-grid me-1"></i> All Products
                                </button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-700">Select Destination Shop (Shop B) <span class="text-danger">*</span></label>
                                <select name="destination_shop_id" id="destShopSelect" class="form-select form-select-sm" required>
                                    <option value="">Choose destination branch...</option>
                                    @foreach($destinationShops as $ds)
                                    <option value="{{ $ds->id }}" {{ old('destination_shop_id') == $ds->id ? 'selected' : '' }}>{{ $ds->shop_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label small fw-700">Select Item to Receive <span class="text-danger">*</span></label>
                                <select name="destination_item_id" id="destItemSelect" class="form-select form-select-sm" required disabled>
                                    <option value="">First choose destination branch above...</option>
                                </select>
                                <div id="destItemMeta" class="mt-1 text-muted small d-none" style="font-size:.75rem;"></div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-700">Quantity to Receive <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="destination_quantity" id="destQtyInput" class="form-control text-center fw-700" value="{{ old('destination_quantity', 1) }}" min="1" required>
                                    <span class="input-group-text bg-light text-muted" id="destUnitSpan">pc</span>
                                </div>
                                <small class="text-muted d-block mt-1" id="destAvailHelp" style="font-size:.72rem;">Available in Shop B: —</small>
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: Swap Context & Reason --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-700">Swap Reason Category <span class="text-danger">*</span></label>
                            <select name="reason_type" class="form-select form-select-sm" required>
                                <option value="customer_preference" {{ old('reason_type') == 'customer_preference' ? 'selected' : '' }}>Customer Preference / Wants Different Model</option>
                                <option value="defective_replacement" {{ old('reason_type') == 'defective_replacement' ? 'selected' : '' }}>Defective / Problematic Unit Replacement</option>
                                <option value="spec_mismatch" {{ old('reason_type') == 'spec_mismatch' ? 'selected' : '' }}>Specification Mismatch</option>
                                <option value="stock_balancing" {{ old('reason_type') == 'stock_balancing' ? 'selected' : '' }}>Branch Stock Balancing</option>
                                <option value="other" {{ old('reason_type') == 'other' ? 'selected' : '' }}>Other Business Reason</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-700">Detailed Reason / Context (Optional)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Customer at Shop A requested Dell Latitude 3310 instead of Lenovo 11e; exchanging 1 unit with Kariakoo branch.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Live Visual Comparison Preview Card --}}
                    <div class="card border border-2 mb-4" style="border-style: dashed !important; background:rgba(88,166,255,0.03);">
                        <div class="card-body p-3 text-center">
                            <h6 class="fw-700 text-muted small text-uppercase mb-3">Live Product Exchange Preview</h6>
                            <div class="row align-items-center justify-content-center g-3">
                                <div class="col-5 text-end">
                                    <span class="badge bg-primary-subtle text-primary py-0.5 px-2 mb-1" style="font-size:.7rem;">{{ $sourceShop->shop_name }} Gives:</span>
                                    <div class="fw-800" id="previewSourceItemName" style="font-size:1rem; color:var(--text-color);">Select source item above</div>
                                    <div class="text-muted small" id="previewSourceQty">—</div>
                                </div>
                                <div class="col-2 text-center">
                                    <div class="rounded-circle bg-custom border d-inline-flex align-items-center justify-content-center shadow-sm" style="width:44px; height:44px;">
                                        <i class="bi bi-arrow-left-right fs-4 text-accent"></i>
                                    </div>
                                </div>
                                <div class="col-5 text-start">
                                    <span class="badge bg-info-subtle text-info py-0.5 px-2 mb-1" id="previewDestShopBadge" style="font-size:.7rem;">Shop B Gives:</span>
                                    <div class="fw-800" id="previewDestItemName" style="font-size:1rem; color:var(--text-color);">Select destination item above</div>
                                    <div class="text-muted small" id="previewDestQty">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <a href="{{ route('shop-swaps.index') }}" class="btn btn-outline-custom btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-accent btn-sm shadow-sm" id="btnSubmitSwap">
                            <i class="bi bi-send-check me-1"></i> Send Swap Proposal to Destination Shop
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    let currentFilterType = 'same_category';

    // Update Source Item Metadata
    function updateSourceMeta() {
        const opt = $('#sourceItemSelect').find('option:selected');
        const itemName = opt.data('name');
        const unit = opt.data('unit') || 'pc';
        const avail = parseInt(opt.data('avail')) || 0;
        const brand = opt.data('brand') || '';
        const model = opt.data('model') || '';

        $('#sourceUnitSpan').text(unit);
        $('#sourceAvailHelp').text(`Available in ${opt.val() ? '{{ $sourceShop->shop_name }}' : 'Shop A'}: ${avail} ${unit}`);
        $('#sourceQtyInput').attr('max', avail);

        if (itemName) {
            $('#previewSourceItemName').text(itemName);
            $('#previewSourceQty').text(`${$('#sourceQtyInput').val()} ${unit} (${brand} ${model})`);
        } else {
            $('#previewSourceItemName').text('Select source item above');
            $('#previewSourceQty').text('—');
        }

        // Reload destination items if destination shop is already chosen
        if ($('#destShopSelect').val()) {
            loadDestinationItems();
        }
    }

    $('#sourceItemSelect').on('change', updateSourceMeta);
    $('#sourceQtyInput').on('input change', function() {
        const unit = $('#sourceUnitSpan').text();
        $('#previewSourceQty').text(`${$(this).val()} ${unit}`);
    });

    // Destination shop change
    $('#destShopSelect').on('change', function() {
        const shopName = $(this).find('option:selected').text();
        if ($(this).val()) {
            $('#previewDestShopBadge').text(`${shopName} Gives:`);
            loadDestinationItems();
        } else {
            $('#previewDestShopBadge').text('Shop B Gives:');
            $('#destItemSelect').html('<option value="">First choose destination branch above...</option>').prop('disabled', true);
        }
    });

    // Filter type button click
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilterType = $(this).data('filter');
        if ($('#destShopSelect').val()) {
            loadDestinationItems();
        }
    });

    // AJAX load destination items
    function loadDestinationItems() {
        const destShopId = $('#destShopSelect').val();
        const sourceItemId = $('#sourceItemSelect').val();

        if (!destShopId) return;

        const select = $('#destItemSelect');
        select.prop('disabled', true).html('<option value="">Loading available products from destination shop...</option>');

        $.ajax({
            url: '{{ route("shop-swaps.destination-items") }}',
            type: 'GET',
            data: {
                destination_shop_id: destShopId,
                source_item_id: sourceItemId,
                filter_type: currentFilterType
            },
            success: function(res) {
                select.empty();

                if (!res.items || res.items.length === 0) {
                    select.append('<option value="">No matching items currently available in destination shop.</option>');
                    select.prop('disabled', true);
                    return;
                }

                select.append('<option value="">Choose item to receive from destination shop...</option>');
                res.items.forEach(function(it) {
                    select.append(`
                        <option value="${it.item_id}"
                            data-name="${it.item_name}"
                            data-unit="${it.unit_symbol}"
                            data-avail="${it.remaining_quantity}"
                            data-brand="${it.brand}"
                            data-model="${it.model}"
                            data-cat="${it.category_name}"
                            data-price="${it.selling_price}">
                            [${it.category_name}] ${it.item_name} (Avail: ${it.remaining_quantity} ${it.unit_symbol})
                        </option>
                    `);
                });

                select.prop('disabled', false);
            },
            error: function() {
                select.html('<option value="">Error loading items. Please try again.</option>');
            }
        });
    }

    // Destination item select change
    $('#destItemSelect').on('change', function() {
        const opt = $(this).find('option:selected');
        const itemName = opt.data('name');
        const unit = opt.data('unit') || 'pc';
        const avail = parseInt(opt.data('avail')) || 0;
        const brand = opt.data('brand') || '';
        const model = opt.data('model') || '';

        $('#destUnitSpan').text(unit);
        $('#destAvailHelp').text(`Available in destination shop: ${avail} ${unit}`);
        $('#destQtyInput').attr('max', avail);

        if (itemName) {
            $('#previewDestItemName').text(itemName);
            $('#previewDestQty').text(`${$('#destQtyInput').val()} ${unit} (${brand} ${model})`);
        } else {
            $('#previewDestItemName').text('Select destination item above');
            $('#previewDestQty').text('—');
        }
    });

    $('#destQtyInput').on('input change', function() {
        const unit = $('#destUnitSpan').text();
        $('#previewDestQty').text(`${$(this).val()} ${unit}`);
    });

    // Form submit validation
    $('#createSwapForm').on('submit', function(e) {
        const srcItem = $('#sourceItemSelect').val();
        const dstItem = $('#destItemSelect').val();
        const dstShop = $('#destShopSelect').val();

        if (!srcItem) {
            alert('Please select an item from your shop to swap out.');
            e.preventDefault();
            return false;
        }

        if (!dstShop) {
            alert('Please choose a destination shop.');
            e.preventDefault();
            return false;
        }

        if (!dstItem) {
            alert('Please choose the item you wish to receive from the destination shop.');
            e.preventDefault();
            return false;
        }
    });

    // Initialize initial state if preselected
    if ($('#sourceItemSelect').val()) {
        updateSourceMeta();
    }
});
</script>
@endpush
