@extends('layouts.app')
@section('title', 'Request Inter-Shop Product Transfer')
@section('page-title', 'Request Product Transfer')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('product-transfers.index') }}">Product Transfers</a></li>
<li class="breadcrumb-item active">New Request</li>
@endsection

@section('content')
<style>
    .ptc-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px; padding: 1.05rem 1.35rem; color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .18); margin-bottom: 1.25rem;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
    }
    .ptc-step-card {
        background: #ffffff; border: 1px solid var(--card-border); border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15,23,42,.04); margin-bottom: 1.25rem; overflow: hidden;
    }
    .ptc-step-head {
        padding: .9rem 1.2rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
    }
    .ptc-step-num {
        width: 26px; height: 26px; border-radius: 8px; background: #0077b6; color: #ffffff;
        font-size: .76rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ptc-shop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .75rem; }
    .ptc-shop-card {
        border: 1.5px solid #e2e8f0; border-radius: 11px; padding: .8rem .9rem;
        background: #ffffff; display: flex; align-items: center; gap: .7rem;
        cursor: pointer; text-align: left; transition: all .15s ease; width: 100%;
    }
    .ptc-shop-card:hover { border-color: #7dd3fc; background: #f8fcff; }
    .ptc-shop-card.is-selected { border-color: #0077b6; background: #f0f9ff; box-shadow: 0 0 0 3px rgba(0,119,182,.12); }
    .ptc-shop-icon {
        width: 38px; height: 38px; border-radius: 9px; background: #f1f5f9; color: #64748b;
        display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
    }
    .ptc-shop-card.is-selected .ptc-shop-icon { background: #0077b6; color: #ffffff; }

    .ptc-item-card {
        background: #fafbfc; border: 1px solid #e2e8f0; border-left: 3.5px solid #0284c7 !important;
        border-radius: 10px; padding: .9rem 1rem;
    }
    .ptc-impact-bar {
        margin-top: .7rem; padding: .55rem .8rem; border-radius: 8px;
        background: #f0f9ff; border: 1px solid #bae6fd;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
        font-size: .76rem;
    }
    .select2-container { width: 100% !important; }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 40px !important; display: flex !important; align-items: center !important;
        border-radius: 8px !important; border-color: #cbd5e1 !important; font-size: .85rem !important;
    }
    .ptc-pill-box {
        height: 40px; display: flex; align-items: center; justify-content: center;
        border-radius: 8px; font-size: .78rem; font-weight: 700; padding: 0 .6rem;
    }
    .ptc-qty-group, .ptc-qty-group .btn, .ptc-qty-group .form-control { height: 40px; border-color: #cbd5e1; }
    .ptc-qty-group .btn { width: 36px; font-weight: 700; }

    @media (max-width: 767.98px) {
        .ptc-shop-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="ptc-hero">
            <div style="flex:1; min-width:220px;">
                <h1 class="mb-0 fw-700 text-white" style="font-size:1.1rem;">Request Inter-Shop Product Transfer</h1>
                <p class="mb-0 mt-1" style="font-size:.77rem; color:rgba(255,255,255,.84);">
                    Requesting branch: <strong>{{ $myShop->shop_name }}</strong>. Select another branch to request stock from — stock moves once they confirm.
                </p>
            </div>
            <a href="{{ route('product-transfers.index') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:9px;">
                <i class="bi bi-arrow-left me-1"></i>Back to Transfers
            </a>
        </div>

        @if($otherShops->isEmpty())
            <div class="alert alert-warning rounded-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>No other active shops are available to request stock from.
            </div>
        @else
        <form method="POST" action="{{ route('product-transfers.store') }}" id="productTransferForm">
            @csrf
            <input type="hidden" name="from_shop_id" id="fromShopIdInput" value="{{ old('from_shop_id') }}">

            {{-- Step 1: Select Supplying Shop (Shop A) --}}
            <div class="ptc-step-card">
                <div class="ptc-step-head">
                    <div class="d-flex align-items-center gap-3">
                        <span class="ptc-step-num">1</span>
                        <div>
                            <h6 class="mb-0 fw-700 text-dark" style="font-size:.94rem;">Select Supplying Shop (From Shop) <span class="text-danger">*</span></h6>
                            <p class="mb-0 text-muted" style="font-size:.74rem;">Choose which branch you are requesting products from.</p>
                        </div>
                    </div>
                </div>
                <div class="p-3 p-md-4">
                    <div class="ptc-shop-grid" id="supplyingShopGrid">
                        @foreach($otherShops as $shop)
                            <button type="button"
                                    class="ptc-shop-card {{ old('from_shop_id') == $shop->id ? 'is-selected' : '' }}"
                                    data-shop-id="{{ $shop->id }}"
                                    data-shop-name="{{ $shop->shop_name }}">
                                <div class="ptc-shop-icon"><i class="bi bi-shop"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-700 text-dark text-truncate" style="font-size:.84rem;">{{ $shop->shop_name }}</div>
                                    <div class="text-muted text-truncate" style="font-size:.72rem;"><i class="bi bi-geo-alt me-1"></i>{{ $shop->location ?: 'Branch' }}</div>
                                </div>
                                <i class="bi bi-check-circle-fill text-primary fs-5 ptc-check-icon {{ old('from_shop_id') == $shop->id ? '' : 'd-none' }}"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Step 2: Select Products & Quantities --}}
            <div class="ptc-step-card">
                <div class="ptc-step-head">
                    <div class="d-flex align-items-center gap-3">
                        <span class="ptc-step-num">2</span>
                        <div>
                            <h6 class="mb-0 fw-700 text-dark" style="font-size:.94rem;">Select Products &amp; Quantities <span class="text-danger">*</span></h6>
                            <p class="mb-0 text-muted" style="font-size:.74rem;" id="step2Subtitle">Pick a supplying shop above to load its available products.</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-accent d-none" id="btnAddTransferItem" onclick="addTransferItemRow()">
                        <i class="bi bi-plus-lg me-1"></i>Add Product
                    </button>
                </div>
                <div class="p-3 p-md-4">
                    <div id="itemsEmptyPlaceholder" class="text-center py-4 px-3 rounded-3 border" style="background:#f8fafc; border-style:dashed !important;">
                        <i class="bi bi-shop fs-2 text-primary d-block mb-2"></i>
                        <div class="fw-700 text-dark mb-1">Choose a supplying shop in Step 1</div>
                        <div class="small text-muted">Available stock from that shop will load automatically with live before/after stock projections.</div>
                    </div>

                    <div id="itemsLoadingState" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary spinner-border-sm me-2"></div>
                        <span class="text-muted small fw-600">Loading available products from selected shop...</span>
                    </div>

                    <div id="itemsNoStockState" class="alert alert-warning d-none mb-0">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>The selected shop currently has no available stock items to transfer.
                    </div>

                    <div id="transferItemsWrapper" class="d-none">
                        <div id="transferItemsList" class="d-flex flex-column gap-3"></div>
                        <button type="button" class="btn btn-sm btn-outline-custom w-100 mt-3 py-2 d-flex align-items-center justify-content-center gap-2" onclick="addTransferItemRow()" style="border-style:dashed !important; border-radius:9px;">
                            <i class="bi bi-plus-circle text-accent"></i>
                            <span class="fw-600">Add Another Product</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 3: Notes & Submit --}}
            <div class="ptc-step-card">
                <div class="p-3 p-md-4">
                    <div class="mb-3">
                        <label for="transferNotes" class="form-label small fw-700 text-dark mb-1">Notes / Reason (Optional)</label>
                        <textarea name="notes" id="transferNotes" class="form-control form-control-sm" rows="2" placeholder="e.g. Urgent customer order at {{ $myShop->shop_name }}...">{{ old('notes') }}</textarea>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-2 border-top">
                        <div class="small text-secondary" id="transferSummaryText">
                            Select a supplying shop and add products to continue.
                        </div>
                        <button type="submit" class="btn btn-accent px-4 py-2 fw-700 shadow-sm" id="btnSubmitProductTransfer" disabled style="border-radius:10px;">
                            <i class="bi bi-send-check-fill me-2"></i>Send Transfer Request
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const MY_SHOP_NAME = @json($myShop->shop_name);
let selectedFromShopName = '';
let sourceItemsCatalog = [];
let sourceItemsMap = {};
let ptRowIdx = 0;

function escPt(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildSourceOptionsHtml() {
    let html = '<option value="">— Search or choose product —</option>';
    sourceItemsCatalog.forEach(function(it) {
        const meta = it.brand ? ' (' + it.brand + ')' : '';
        html += `<option value="${it.item_id}">[${escPt(it.category_name)}] ${escPt(it.item_name)}${escPt(meta)} — Avail: ${it.source_available} ${escPt(it.unit_symbol)}</option>`;
    });
    return html;
}

function initPtSelect($sel) {
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '— Search or choose product —',
        allowClear: true
    });
    $sel.on('change', function() {
        validateTransferForm();
    });
}

function addTransferItemRow() {
    const idx = ptRowIdx++;
    const $list = $('#transferItemsList');
    const $row = $(`
        <div class="ptc-item-card pt-item-row" data-idx="${idx}">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-secondary-subtle text-secondary border small fw-600 pt-row-num" style="font-size:.7rem;">Product #${$list.children().length + 1}</span>
                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-pt-row" style="font-size:.78rem;">
                    <i class="bi bi-trash me-1"></i>Remove
                </button>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-600 text-secondary mb-1">Product <span class="text-danger">*</span></label>
                    <select name="items[${idx}][id]" class="form-select pt-item-select" required>
                        ${buildSourceOptionsHtml()}
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-600 text-secondary mb-1">Available in ${escPt(selectedFromShopName)}</label>
                    <div class="ptc-pill-box bg-light text-secondary border pt-avail-pill">—</div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-600 text-secondary mb-1">Request Qty <span class="text-danger">*</span></label>
                    <div class="input-group ptc-qty-group">
                        <button type="button" class="btn btn-outline-secondary btn-pt-step" data-delta="-1">−</button>
                        <input type="number" name="items[${idx}][qty]" class="form-control text-center fw-700 pt-qty-input" min="1" value="1" required>
                        <button type="button" class="btn btn-outline-secondary btn-pt-step" data-delta="1">+</button>
                    </div>
                </div>
            </div>
            <div class="ptc-impact-bar d-none pt-impact-preview"></div>
            <div class="text-danger small mt-2 d-none pt-row-warn" style="font-size:.74rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><span class="pt-row-warn-text"></span>
            </div>
        </div>
    `);

    $list.append($row);
    initPtSelect($row.find('.pt-item-select'));
    renumberPtRows();
    validateTransferForm();
}

function renumberPtRows() {
    const $rows = $('#transferItemsList .pt-item-row');
    $rows.each(function(i) {
        $(this).find('.pt-row-num').text('Product #' + (i + 1));
        $(this).find('.btn-remove-pt-row').toggleClass('d-none', $rows.length === 1);
    });
}

function validateTransferForm() {
    const fromShopId = $('#fromShopIdInput').val();
    let valid = !!fromShopId && sourceItemsCatalog.length > 0;
    let totalItems = 0;
    let totalUnits = 0;
    const aggregateByItem = {};

    $('#transferItemsList .pt-item-row').each(function() {
        const itemId = $(this).find('.pt-item-select').val();
        const qty = Math.max(0, parseInt($(this).find('.pt-qty-input').val(), 10) || 0);
        if (itemId) aggregateByItem[itemId] = (aggregateByItem[itemId] || 0) + qty;
    });

    $('#transferItemsList .pt-item-row').each(function() {
        const $row = $(this);
        const itemId = $row.find('.pt-item-select').val();
        const $qtyInp = $row.find('.pt-qty-input');
        const qty = Math.max(0, parseInt($qtyInp.val(), 10) || 0);
        const $availPill = $row.find('.pt-avail-pill');
        const $impact = $row.find('.pt-impact-preview');
        const $warn = $row.find('.pt-row-warn');
        const $warnText = $row.find('.pt-row-warn-text');

        if (!itemId || !sourceItemsMap[itemId]) {
            valid = false;
            $availPill.text('—').attr('class', 'ptc-pill-box bg-light text-secondary border pt-avail-pill');
            $impact.addClass('d-none').empty();
            $warn.addClass('d-none');
            $qtyInp.removeClass('is-invalid');
            return;
        }

        const meta = sourceItemsMap[itemId];
        const avail = meta.source_available;
        const myCur = meta.my_current_stock;
        const agg = aggregateByItem[itemId] || 0;
        const unit = meta.unit_symbol || 'pc';

        totalItems++;
        totalUnits += qty;

        const afterSource = avail - agg;
        const afterDest = myCur + agg;

        if (afterSource < 0) {
            $availPill.text(`${avail} ${unit} (${Math.abs(afterSource)} over)`).attr('class', 'ptc-pill-box bg-danger-subtle text-danger border border-danger-subtle pt-avail-pill');
        } else {
            $availPill.text(`${avail} ${unit} avail`).attr('class', 'ptc-pill-box bg-success-subtle text-success border border-success-subtle pt-avail-pill');
        }

        $impact.removeClass('d-none').html(`
            <span><i class="bi bi-arrow-left-right text-primary me-1"></i><strong>After Confirmation:</strong></span>
            <span>${escPt(selectedFromShopName)}: <strong>${avail}</strong> &rarr; <strong class="${afterSource < 0 ? 'text-danger' : 'text-dark'}">${afterSource} ${escPt(unit)}</strong></span>
            <span>${escPt(MY_SHOP_NAME)}: <strong>${myCur}</strong> &rarr; <strong class="text-success">${afterDest} ${escPt(unit)}</strong></span>
        `);

        if (qty <= 0) {
            valid = false;
            $warnText.text('Requested quantity must be at least 1.');
            $warn.removeClass('d-none');
            $qtyInp.addClass('is-invalid');
        } else if (agg > avail) {
            valid = false;
            $warnText.text(`Requested quantity (${agg} ${unit}) exceeds ${selectedFromShopName} available stock (${avail} ${unit}).`);
            $warn.removeClass('d-none');
            $qtyInp.addClass('is-invalid');
        } else {
            $warn.addClass('d-none');
            $qtyInp.removeClass('is-invalid');
        }
    });

    if (totalItems === 0 || totalUnits <= 0) valid = false;

    if (fromShopId && totalItems > 0) {
        $('#transferSummaryText').html(`Requesting <strong>${totalItems} product${totalItems === 1 ? '' : 's'}</strong> (<strong>${totalUnits} total unit${totalUnits === 1 ? '' : 's'}</strong>) from <strong>${escPt(selectedFromShopName)}</strong> &rarr; <strong>${escPt(MY_SHOP_NAME)}</strong>.`);
    } else if (fromShopId) {
        $('#transferSummaryText').text('Add at least one product to send your request.');
    }

    $('#btnSubmitProductTransfer').prop('disabled', !valid);
    return valid;
}

function loadSourceShopItems(shopId, shopName) {
    selectedFromShopName = shopName;
    $('#fromShopIdInput').val(shopId);
    $('#step2Subtitle').text('Showing available stock in ' + shopName + '.');
    $('#itemsEmptyPlaceholder, #itemsNoStockState, #transferItemsWrapper, #btnAddTransferItem').addClass('d-none');
    $('#itemsLoadingState').removeClass('d-none');
    $('#transferItemsList').empty();
    sourceItemsCatalog = [];
    sourceItemsMap = {};
    validateTransferForm();

    $.ajax({
        url: "{{ route('product-transfers.source-items') }}",
        type: 'GET',
        data: { from_shop_id: shopId },
        success: function(res) {
            $('#itemsLoadingState').addClass('d-none');
            sourceItemsCatalog = res.items || [];
            sourceItemsCatalog.forEach(it => { sourceItemsMap[String(it.item_id)] = it; });

            if (sourceItemsCatalog.length === 0) {
                $('#itemsNoStockState').removeClass('d-none');
                validateTransferForm();
                return;
            }

            $('#transferItemsWrapper, #btnAddTransferItem').removeClass('d-none');
            addTransferItemRow();
        },
        error: function() {
            $('#itemsLoadingState').addClass('d-none');
            $('#itemsNoStockState').removeClass('d-none').text('Failed to load available products from this branch. Please try again.');
        }
    });
}

$(document).ready(function() {
    $(document).on('click', '#supplyingShopGrid .ptc-shop-card', function() {
        const shopId = $(this).data('shop-id');
        const shopName = $(this).data('shop-name');

        $('#supplyingShopGrid .ptc-shop-card').removeClass('is-selected').find('.ptc-check-icon').addClass('d-none');
        $(this).addClass('is-selected').find('.ptc-check-icon').removeClass('d-none');

        loadSourceShopItems(shopId, shopName);
    });

    $(document).on('click', '.btn-remove-pt-row', function() {
        if ($('#transferItemsList .pt-item-row').length > 1) {
            $(this).closest('.pt-item-row').remove();
            renumberPtRows();
            validateTransferForm();
        }
    });

    $(document).on('click', '.btn-pt-step', function() {
        const delta = parseInt($(this).data('delta'), 10) || 0;
        const $inp = $(this).siblings('.pt-qty-input');
        let next = (parseInt($inp.val(), 10) || 0) + delta;
        if (next < 1) next = 1;
        $inp.val(next);
        validateTransferForm();
    });

    $(document).on('input change', '.pt-qty-input', function() {
        validateTransferForm();
    });

    // If old('from_shop_id') exists after validation redirect, reload its items
    const initialShopId = $('#fromShopIdInput').val();
    if (initialShopId) {
        const $btn = $(`#supplyingShopGrid .ptc-shop-card[data-shop-id="${initialShopId}"]`);
        if ($btn.length) loadSourceShopItems(initialShopId, $btn.data('shop-name'));
    }
});
</script>
@endpush
