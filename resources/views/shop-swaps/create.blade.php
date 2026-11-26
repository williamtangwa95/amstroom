@extends('layouts.app')
@section('title', 'Propose Product Swap')
@section('page-title', 'Propose Product Swap')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-swaps.index') }}">Product Swaps</a></li>
<li class="breadcrumb-item active">Propose Swap</li>
@endsection

@section('content')
<style>
    .swap-form-card {
        overflow: hidden;
    }
    .swap-step-box {
        min-width: 0;
        overflow: hidden;
    }
    .swap-step-badge {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.35rem;
        white-space: normal !important;
        text-align: left;
        line-height: 1.35;
        max-width: 100%;
        overflow-wrap: anywhere;
    }
    .swap-step-badge i {
        margin-top: 0.1rem;
        flex-shrink: 0;
    }
    .swap-form-card .form-select {
        max-width: 100%;
        text-overflow: ellipsis;
    }
    .swap-form-card .select2-container {
        width: 100% !important;
        max-width: 100% !important;
    }
    .swap-form-card .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 36px;
        border-radius: 8px;
        border-color: var(--input-border, #cbd5e1);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
    }
    .swap-form-card .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding-right: 0.5rem;
        line-height: 1.35;
    }
    .select2-dropdown.swap-select2-dropdown {
        max-width: calc(100vw - 24px) !important;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
        overflow: hidden;
    }
    .select2-dropdown.swap-select2-dropdown .select2-search__field {
        font-size: 0.84rem !important;
        border-radius: 6px !important;
    }
    .select2-dropdown.swap-select2-dropdown .select2-results__option {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        line-height: 1.35;
        font-size: 0.83rem;
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    }
    .select2-dropdown.swap-select2-dropdown .select2-results__option:last-child {
        border-bottom: none;
    }
    .swap-select-opt-name {
        font-weight: 600;
        line-height: 1.3;
    }
    .swap-select-opt-meta {
        font-size: 0.73rem;
        opacity: 0.82;
        margin-top: 0.15rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }
    .swap-mode-option {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 0.9rem;
        border: 1.5px solid #cbd5e1;
        border-radius: 0.65rem;
        background: var(--card-bg, #ffffff);
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        min-width: 0;
        overflow-wrap: break-word;
    }
    .swap-mode-option:hover {
        border-color: var(--accent, #0d6efd);
        background: rgba(13, 110, 253, 0.03);
    }
    .swap-mode-option:hover .swap-mode-radio:not(:checked) {
        border-color: var(--accent, #0d6efd) !important;
    }
    .swap-mode-option.is-selected {
        border: 2px solid var(--accent, #0d6efd);
        background: rgba(13, 110, 253, 0.07);
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }
    .swap-mode-option .swap-mode-radio {
        width: 1.2rem;
        height: 1.2rem;
        margin-top: 0.1rem;
        margin-left: 0;
        flex-shrink: 0;
        cursor: pointer;
        border: 2px solid #64748b !important;
        background-color: var(--card-bg, #ffffff);
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }
    .swap-mode-option .swap-mode-radio:checked {
        border-color: var(--accent, #0d6efd) !important;
        background-color: var(--accent, #0d6efd) !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
    }
    .swap-mode-body {
        flex: 1 1 auto;
        min-width: 0;
    }
    .swap-mode-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .swap-mode-title {
        font-size: 0.86rem;
        font-weight: 700;
        color: var(--text-color);
        line-height: 1.25;
    }
    .swap-mode-selected-pill {
        display: none;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: var(--accent, #0d6efd);
        color: #ffffff;
        line-height: 1.2;
        letter-spacing: 0.01em;
    }
    .swap-mode-option.is-selected .swap-mode-selected-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .swap-mode-desc {
        font-size: 0.74rem;
        color: var(--text-muted, #6c757d);
        line-height: 1.35;
        margin-top: 0.25rem;
    }
</style>
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-sm mb-4 swap-form-card">
            <div class="card-header bg-custom p-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1 1 220px;">
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="bi bi-arrow-left-right fs-5"></i>
                    </div>
                    <div style="min-width: 0;">
                        <h6 class="mb-0 fw-700">Propose Inter-Shop Product Swap</h6>
                        <small class="text-muted d-block lh-sm mt-1" style="font-size:.75rem;">
                            Exchange an item from <strong>{{ $sourceShop->shop_name }}</strong> with an item from another branch.
                        </small>
                    </div>
                </div>
                <a href="{{ route('shop-swaps.index') }}" class="btn btn-sm btn-outline-custom text-nowrap flex-shrink-0">
                    <i class="bi bi-arrow-left me-1"></i> Back to Swaps
                </a>
            </div>

            <div class="card-body p-3 p-md-4">
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
                    <div class="p-3 rounded-3 mb-4 border swap-step-box" style="background:var(--input-bg); border-color:var(--input-border) !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="badge bg-primary px-2 py-1 fw-700 swap-step-badge" style="font-size:.78rem;">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>1. Product Offered (Source Shop: {{ $sourceShop->shop_name }})</span>
                            </span>
                            <input type="hidden" name="source_shop_id" id="sourceShopSelect" value="{{ $sourceShop->id }}">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-700">Select Item to Swap Out <span class="text-danger">*</span></label>
                                <select name="source_item_id" id="sourceItemSelect" class="form-select form-select-sm" required>
                                    <option value="">Choose item in stock...</option>
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
                    @php
                        $selectedSwapMode = old('swap_mode', 'same_category');
                        if (!in_array($selectedSwapMode, ['same_category', 'same_product'], true)) {
                            $selectedSwapMode = 'same_category';
                        }
                    @endphp
                    <div class="p-3 rounded-3 mb-4 border swap-step-box" style="background:var(--input-bg); border-color:var(--input-border) !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="badge bg-info text-dark px-2 py-1 fw-700 swap-step-badge" style="font-size:.78rem;">
                                <i class="bi bi-box-arrow-in-down-left"></i>
                                <span>2. Product Requested (Destination Shop B)</span>
                            </span>
                            <small class="text-muted lh-sm" style="font-size:.74rem;">
                                <i class="bi bi-shield-check me-1"></i>Only products in the exact same category or the exact same product can be swapped
                            </small>
                        </div>

                        {{-- Swap Eligibility Radio Selector (Same Category or Exact Same Product) --}}
                        <div class="mb-3">
                            <label class="form-label small fw-700 mb-2 d-flex align-items-center flex-wrap gap-2">
                                <span>Swap Match Type <span class="text-danger">*</span></span>
                                <span class="text-muted fw-normal" style="font-size:.73rem;">(Choose one option)</span>
                            </label>
                            <div class="row g-2" id="swapModeRadioGroup" role="radiogroup" aria-label="Swap Match Type">
                                <div class="col-sm-6">
                                    <label for="swapModeSameCategory" class="swap-mode-option {{ $selectedSwapMode === 'same_category' ? 'is-selected' : '' }}">
                                        <input class="form-check-input swap-mode-radio"
                                               type="radio"
                                               name="swap_mode"
                                               id="swapModeSameCategory"
                                               value="same_category"
                                               {{ $selectedSwapMode === 'same_category' ? 'checked' : '' }}>
                                        <div class="swap-mode-body">
                                            <div class="swap-mode-header">
                                                <div class="swap-mode-title">
                                                    <i class="bi bi-tags me-1 text-primary"></i> Same Category
                                                </div>
                                                <span class="swap-mode-selected-pill">
                                                    <i class="bi bi-check-circle-fill"></i> Selected
                                                </span>
                                            </div>
                                            <div class="swap-mode-desc">
                                                Swap different variants or models within the same category (e.g., Touchscreen Laptop for Non-Touchscreen Laptop).
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label for="swapModeSameProduct" class="swap-mode-option {{ $selectedSwapMode === 'same_product' ? 'is-selected' : '' }}">
                                        <input class="form-check-input swap-mode-radio"
                                               type="radio"
                                               name="swap_mode"
                                               id="swapModeSameProduct"
                                               value="same_product"
                                               {{ $selectedSwapMode === 'same_product' ? 'checked' : '' }}>
                                        <div class="swap-mode-body">
                                            <div class="swap-mode-header">
                                                <div class="swap-mode-title">
                                                    <i class="bi bi-layers me-1 text-info"></i> Exact Same Product
                                                </div>
                                                <span class="swap-mode-selected-pill">
                                                    <i class="bi bi-check-circle-fill"></i> Selected
                                                </span>
                                            </div>
                                            <div class="swap-mode-desc">
                                                Swap the exact same product item with another branch (e.g., defective unit replacement).
                                            </div>
                                        </div>
                                    </label>
                                </div>
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
                                <label class="form-label small fw-700">
                                    Quantity to Receive <span class="text-danger">*</span>
                                    <i class="bi bi-lock-fill text-muted ms-1" title="Auto-matched to Quantity to Swap Out" style="font-size:.7rem;"></i>
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="destination_quantity" id="destQtyInput" class="form-control text-center fw-700 bg-light" value="{{ old('source_quantity', 1) }}" min="1" readonly required>
                                    <span class="input-group-text bg-light text-muted" id="destUnitSpan">pc</span>
                                </div>
                                <small class="text-muted d-block mt-1" id="destAvailHelp" style="font-size:.72rem;">Available in Shop B: — · Matched 1:1</small>
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
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Customer at Shop A requested Dell Latitude 3190 without Touchscreen instead of Touchscreen model; exchanging 1 unit with Kariakoo branch.">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Live Visual Comparison Preview Card --}}
                    <div class="card border border-2 mb-4 swap-step-box" style="border-style: dashed !important; background:rgba(88,166,255,0.03);">
                        <div class="card-body p-3 text-center">
                            <h6 class="fw-700 text-muted small text-uppercase mb-3">Live Product Exchange Preview</h6>
                            <div class="row align-items-center justify-content-center g-3">
                                <div class="col-12 col-sm-5 text-center text-sm-end">
                                    <span class="badge bg-primary-subtle text-primary py-1 px-2 mb-1 text-wrap lh-sm" style="font-size:.7rem; max-width:100%;">{{ $sourceShop->shop_name }} Gives:</span>
                                    <div class="fw-800 text-break" id="previewSourceItemName" style="font-size:.95rem; color:var(--text-color);">Select source item above</div>
                                    <div class="text-muted small text-break" id="previewSourceQty">—</div>
                                </div>
                                <div class="col-12 col-sm-2 text-center">
                                    <div class="rounded-circle bg-custom border d-inline-flex align-items-center justify-content-center shadow-sm" style="width:40px; height:40px;">
                                        <i class="bi bi-arrow-left-right fs-5 text-accent"></i>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-5 text-center text-sm-start">
                                    <span class="badge bg-info-subtle text-info py-1 px-2 mb-1 text-wrap lh-sm" id="previewDestShopBadge" style="font-size:.7rem; max-width:100%;">Shop B Gives:</span>
                                    <div class="fw-800 text-break" id="previewDestItemName" style="font-size:.95rem; color:var(--text-color);">Select destination item above</div>
                                    <div class="text-muted small text-break" id="previewDestQty">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.8rem;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Once you submit this request, the <strong>Destination Shop Admin</strong> will review and approve it to immediately confirm and exchange stock across both branches. The Shop Owner will see the completed swap once done.
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                        <a href="{{ route('shop-swaps.index') }}" class="btn btn-outline-custom btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-accent btn-sm shadow-sm" id="btnSubmitSwap">
                            <i class="bi bi-send-check me-1"></i> Send Swap Request to Destination Shop
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
    let currentFilterType = $('input[name="swap_mode"]:checked').val() || 'same_category';
    let initialDestItemId = @json(old('destination_item_id'));

    function formatProductOption(state) {
        if (!state.id || !state.element) {
            return state.text;
        }
        const el = $(state.element);
        const name = el.data('name');
        if (!name) {
            return state.text;
        }
        const cat = el.data('cat-name') || el.data('cat') || 'General';
        const avail = el.data('avail');
        const unit = el.data('unit') || 'pc';

        const wrap = $('<div class="swap-select-opt">');
        wrap.append($('<div class="swap-select-opt-name">').text(name));
        const meta = $('<div class="swap-select-opt-meta">');
        meta.append($('<span>').text(cat));
        if (avail !== undefined && avail !== '') {
            meta.append($('<span>').text('•'));
            meta.append($('<span>').text(`Avail: ${avail} ${unit}`));
        }
        wrap.append(meta);
        return wrap;
    }

    function initSwapSelect2() {
        if (!$.fn.select2) return;

        $('#sourceItemSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownCssClass: 'swap-select2-dropdown',
            templateResult: formatProductOption,
            placeholder: 'Choose item in stock...'
        });

        $('#destShopSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: 8,
            dropdownCssClass: 'swap-select2-dropdown',
            placeholder: 'Choose destination branch...'
        });

        $('#destItemSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownCssClass: 'swap-select2-dropdown',
            templateResult: formatProductOption,
            placeholder: 'First choose destination branch...'
        });

        $('select[name="reason_type"]').select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: Infinity,
            dropdownCssClass: 'swap-select2-dropdown'
        });
    }

    function refreshDestItemSelect2() {
        if ($.fn.select2 && $('#destItemSelect').hasClass('select2-hidden-accessible')) {
            $('#destItemSelect').trigger('change.select2');
        }
    }

    initSwapSelect2();

    function updateMaxSwapQuantity() {
        const srcOpt = $('#sourceItemSelect').find('option:selected');
        const dstOpt = $('#destItemSelect').find('option:selected');
        const srcAvail = srcOpt.val() ? (parseInt(srcOpt.data('avail')) || 0) : null;
        const dstAvail = dstOpt.val() ? (parseInt(dstOpt.data('avail')) || 0) : null;

        let maxQty = null;
        if (srcAvail !== null && dstAvail !== null) {
            maxQty = Math.min(srcAvail, dstAvail);
        } else if (srcAvail !== null) {
            maxQty = srcAvail;
        }

        if (maxQty !== null && maxQty > 0) {
            $('#sourceQtyInput').attr('max', maxQty);
            $('#destQtyInput').attr('max', maxQty);
        } else {
            $('#sourceQtyInput').removeAttr('max');
            $('#destQtyInput').removeAttr('max');
        }
    }

    function syncSwapQuantities() {
        const qty = $('#sourceQtyInput').val();
        $('#destQtyInput').val(qty);

        const srcOpt = $('#sourceItemSelect').find('option:selected');
        if (srcOpt.data('name')) {
            const srcUnit = $('#sourceUnitSpan').text();
            const srcBrand = srcOpt.data('brand') || '';
            const srcModel = srcOpt.data('model') || '';
            const srcMeta = `${srcBrand} ${srcModel}`.trim();
            $('#previewSourceQty').text(srcMeta ? `${qty} ${srcUnit} (${srcMeta})` : `${qty} ${srcUnit}`);
        }

        const dstOpt = $('#destItemSelect').find('option:selected');
        if (dstOpt.data('name')) {
            const dstUnit = $('#destUnitSpan').text();
            const dstBrand = dstOpt.data('brand') || '';
            const dstModel = dstOpt.data('model') || '';
            const dstMeta = `${dstBrand} ${dstModel}`.trim();
            $('#previewDestQty').text(dstMeta ? `${qty} ${dstUnit} (${dstMeta})` : `${qty} ${dstUnit}`);
        }
    }

    function resetDestinationSelection() {
        $('#destUnitSpan').text('pc');
        $('#destAvailHelp').text('Available in Shop B: — · Matched 1:1');
        $('#previewDestItemName').text('Select destination item above');
        $('#previewDestQty').text('—');
        updateMaxSwapQuantity();
    }

    // Update Source Item Metadata
    function updateSourceMeta() {
        const opt = $('#sourceItemSelect').find('option:selected');
        const itemName = opt.data('name');
        const unit = opt.data('unit') || 'pc';
        const avail = parseInt(opt.data('avail')) || 0;

        $('#sourceUnitSpan').text(unit);
        $('#sourceAvailHelp').text(`Available in ${opt.val() ? '{{ $sourceShop->shop_name }}' : 'Shop A'}: ${avail} ${unit}`);
        updateMaxSwapQuantity();

        if (itemName) {
            $('#previewSourceItemName').text(itemName);
            syncSwapQuantities();
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
    $('#sourceQtyInput').on('input change', syncSwapQuantities);

    // Destination shop change
    $('#destShopSelect').on('change', function() {
        const shopName = $(this).find('option:selected').text();
        resetDestinationSelection();
        if ($(this).val()) {
            $('#previewDestShopBadge').text(`${shopName} Gives:`);
            loadDestinationItems();
        } else {
            $('#previewDestShopBadge').text('Shop B Gives:');
            $('#destItemSelect').html('<option value="">First choose destination branch above...</option>').prop('disabled', true);
            refreshDestItemSelect2();
        }
    });

    // Swap mode radio change (Same Category vs Exact Same Product)
    $('.swap-mode-radio').on('change', function() {
        $('.swap-mode-option').removeClass('is-selected');
        $(this).closest('.swap-mode-option').addClass('is-selected');
        currentFilterType = $(this).val();
        resetDestinationSelection();
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
        resetDestinationSelection();

        if (!sourceItemId) {
            select.prop('disabled', true).html('<option value="">First select the product you are offering in Step 1...</option>');
            refreshDestItemSelect2();
            return;
        }

        select.prop('disabled', true).html('<option value="">Loading matching products from destination shop...</option>');
        refreshDestItemSelect2();

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
                    const emptyMsg = currentFilterType === 'same_product'
                        ? 'This branch does not currently have the exact same product in stock.'
                        : `No matching ${res.source_category || 'same-category'} products available in this branch.`;
                    select.append($('<option>', { value: '', text: emptyMsg }));
                    select.prop('disabled', true);
                    refreshDestItemSelect2();
                    return;
                }

                select.append($('<option>', { value: '', text: 'Choose matching item to receive...' }));
                res.items.forEach(function(it) {
                    const option = $('<option>', {
                        value: it.item_id,
                        text: `[${it.category_name}] ${it.item_name} (Avail: ${it.remaining_quantity} ${it.unit_symbol})`
                    }).attr({
                        'data-name': it.item_name,
                        'data-unit': it.unit_symbol,
                        'data-avail': it.remaining_quantity,
                        'data-brand': it.brand,
                        'data-model': it.model,
                        'data-cat': it.category_name,
                        'data-price': it.selling_price
                    });

                    if (initialDestItemId && String(initialDestItemId) === String(it.item_id)) {
                        option.prop('selected', true);
                    }

                    select.append(option);
                });

                select.prop('disabled', false);

                if (initialDestItemId) {
                    initialDestItemId = null;
                    select.trigger('change');
                } else {
                    refreshDestItemSelect2();
                }
            },
            error: function() {
                select.html('<option value="">Error loading items. Please try again.</option>');
                refreshDestItemSelect2();
            }
        });
    }

    // Destination item select change
    $('#destItemSelect').on('change', function() {
        const opt = $(this).find('option:selected');
        const itemName = opt.data('name');
        const unit = opt.data('unit') || 'pc';
        const avail = parseInt(opt.data('avail')) || 0;

        $('#destUnitSpan').text(unit);
        $('#destAvailHelp').text(`Available in destination shop: ${avail} ${unit} · Matched 1:1`);
        updateMaxSwapQuantity();

        if (itemName) {
            $('#previewDestItemName').text(itemName);
            syncSwapQuantities();
        } else {
            $('#previewDestItemName').text('Select destination item above');
            $('#previewDestQty').text('—');
        }
    });

    // Form submit validation
    $('#createSwapForm').on('submit', function(e) {
        const srcItem = $('#sourceItemSelect').val();
        const dstItem = $('#destItemSelect').val();
        const dstShop = $('#destShopSelect').val();
        const srcQty = parseInt($('#sourceQtyInput').val(), 10);
        $('#destQtyInput').val($('#sourceQtyInput').val());
        const dstQty = parseInt($('#destQtyInput').val(), 10);

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

        if (!srcQty || srcQty < 1 || srcQty !== dstQty) {
            alert('Quantity to Swap Out and Quantity to Receive must be equal (at least 1).');
            e.preventDefault();
            return false;
        }
    });

    syncSwapQuantities();

    // Initialize initial state if preselected or returning with old input
    if ($('#destShopSelect').val()) {
        const shopName = $('#destShopSelect').find('option:selected').text();
        $('#previewDestShopBadge').text(`${shopName} Gives:`);
    }
    if ($('#sourceItemSelect').val()) {
        updateSourceMeta();
    } else if ($('#destShopSelect').val()) {
        loadDestinationItems();
    }
});
</script>
@endpush
