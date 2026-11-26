@extends('layouts.app')
@section('title', 'New Stock Request')
@section('page-title', 'Request Warehouse Stock')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Stock Requests</a></li>
<li class="breadcrumb-item active">New Request</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK REQUEST CREATE — Executive Responsive UI
       ══════════════════════════════════════════════ */
    .src-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 58%, #003a6c 100%);
        border-radius: 16px;
        padding: 1.2rem 1.45rem;
        color: #ffffff;
        box-shadow: 0 8px 24px rgba(0, 86, 145, .2);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .src-hero-title {
        font-size: 1.18rem;
        font-weight: 800;
        margin: 0;
        color: #ffffff;
        letter-spacing: -.015em;
    }
    .src-hero-sub {
        font-size: .8rem;
        color: rgba(255, 255, 255, .86);
        margin: .28rem 0 0;
    }
    .src-hero-pill {
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        padding: .24rem .7rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .26);
        color: #ffffff;
        font-size: .7rem;
        font-weight: 700;
    }
    .src-hero-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .46rem .9rem;
        border-radius: 10px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .25);
        color: #ffffff;
        text-decoration: none;
        font-size: .78rem;
        font-weight: 600;
        transition: all .15s ease;
    }
    .src-hero-back:hover {
        background: rgba(255, 255, 255, .24);
        color: #ffffff;
    }

    .src-card {
        background: #ffffff;
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .045);
        overflow: hidden;
    }
    .src-section-head {
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fafcff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .src-step-badge {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: rgba(0, 119, 182, .12);
        color: #0077b6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    /* Product Row Cards */
    .src-item-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #0077b6;
        border-radius: 13px;
        padding: 1rem 1.15rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .03);
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .src-item-row:hover {
        border-color: #cbd5e1;
        border-left-color: #0077b6;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .06);
    }
    .src-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .75rem;
    }
    .src-item-num {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 6px;
        background: #f1f5f9;
        color: #334155;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .src-remove-btn {
        display: inline-flex;
        align-items: center;
        gap: .28rem;
        border: none;
        background: transparent;
        color: #dc2626;
        font-size: .76rem;
        font-weight: 600;
        padding: .22rem .5rem;
        border-radius: 7px;
        transition: background .15s ease;
    }
    .src-remove-btn:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    /* Stepper */
    .src-qty-stepper {
        display: flex;
        align-items: stretch;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        overflow: hidden;
        background: #ffffff;
        height: 38px;
    }
    .src-qty-stepper button {
        width: 38px;
        border: none;
        background: #f8fafc;
        color: #334155;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }
    .src-qty-stepper button:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .src-qty-stepper input {
        flex: 1;
        min-width: 0;
        border: none;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        text-align: center;
        font-weight: 800;
        font-size: .92rem;
        color: #0f172a;
        background: #ffffff;
        outline: none;
    }
    .src-qty-stepper input:focus {
        background: #f0f9ff;
    }

    /* Add Product Dashed Trigger */
    .src-add-dashed {
        width: 100%;
        border: 1.5px dashed #94a3b8;
        border-radius: 12px;
        background: #f8fafc;
        color: #0077b6;
        padding: .72rem 1rem;
        font-size: .84rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        cursor: pointer;
        transition: all .16s ease;
    }
    .src-add-dashed:hover {
        border-color: #0077b6;
        background: #f0f9ff;
        color: #005691;
    }

    /* Summary Strip */
    .src-summary-bar {
        background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%);
        border: 1px solid #bae6fd;
        border-radius: 13px;
        padding: 1rem 1.2rem;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .85rem;
    }
    .src-summary-stat-k {
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
    }
    .src-summary-stat-v {
        font-size: 1.1rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: .12rem;
    }

    .src-branch-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .src-hero {
            padding: 1rem;
        }
        .src-hero-back {
            width: 100%;
            justify-content: center;
        }
        .src-summary-bar {
            grid-template-columns: 1fr;
            gap: .6rem;
        }
        .src-footer-actions {
            flex-direction: column-reverse;
        }
        .src-footer-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        {{-- ── Executive Hero Banner ── --}}
        <div class="src-hero">
            <div style="flex:1; min-width:220px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="src-hero-title">Request Stock for Your Shop</h1>
                    <span class="src-hero-pill">
                        <i class="bi bi-shop"></i> {{ $shop->shop_name }}
                    </span>
                </div>
                <p class="src-hero-sub">
                    Add every product your branch needs replenished. The Owner will review your list, regulate quantities based on Main Store availability, and dispatch directly to your shop.
                </p>
            </div>
            <a href="{{ route('stock-requests.index') }}" class="src-hero-back">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Requests</span>
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius:12px;">
                <div class="fw-700 mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Please fix the following:</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('stock-requests.store') }}" id="stockRequestForm">
            @csrf

            <div class="src-card mb-4">
                {{-- Card Header --}}
                <div class="src-section-head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="src-step-badge">1</span>
                        <div>
                            <h6 class="mb-0 fw-700 text-dark" style="font-size:.94rem;">Products to Add to {{ $shop->shop_name }}</h6>
                            <div class="small text-muted" style="font-size:.74rem;">Select one or more products and specify the quantity needed for your shop</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-accent fw-600 px-3" id="addItemTopBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add Product
                    </button>
                </div>

                <div class="p-3 p-md-4">
                    {{-- Helpful Tip --}}
                    <div class="alert alert-info border-0 py-2 px-3 mb-4 d-flex align-items-center gap-2 small" style="background:#f0f9ff; color:#0369a1; border-left:4px solid #0284c7 !important; border-radius:10px;">
                        <i class="bi bi-lightbulb-fill fs-5 flex-shrink-0"></i>
                        <div>Request all products your branch needs — even items that may need restocking. The Owner will check warehouse stock, adjust quantities if needed, and dispatch available items to your shop.</div>
                    </div>

                    {{-- Product Rows Container --}}
                    <div id="requestItemsContainer" class="d-flex flex-column gap-3">
                        <div class="src-item-row request-item-row">
                            <div class="src-item-header">
                                <span class="src-item-num"><i class="bi bi-box-seam text-primary"></i> <span class="row-label">Item #1</span></span>
                                <button type="button" class="src-remove-btn remove-row" style="display:none;" title="Remove Product">
                                    <i class="bi bi-trash3"></i><span>Remove</span>
                                </button>
                            </div>
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-7 col-lg-8">
                                    <label class="form-label fw-600 small text-secondary mb-1">Product <span class="text-danger">*</span></label>
                                    <select name="items[0][item_id]" class="form-select item-select" required>
                                        <option value="">Search &amp; choose a product...</option>
                                        @foreach($items as $item)
                                            @php
                                                $myBranchStock = (int) ($myShopStockMap[$item->id] ?? 0);
                                                $unitSym = $item->unit_symbol ?? 'pc';
                                            @endphp
                                            <option value="{{ $item->id }}"
                                                    data-category="{{ $item->category->category_name ?? 'General' }}"
                                                    data-unit="{{ $unitSym }}"
                                                    data-my-stock="{{ $myBranchStock }}"
                                                    {{ (isset($selectedItemId) && (int)$selectedItemId === (int)$item->id) || (int)request('item_id') === (int)$item->id ? 'selected' : '' }}>
                                                [{{ $item->category->category_name ?? 'General' }}] {{ $item->item_name }}{{ $item->brand ? ' — ' . $item->brand : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="item-meta-line mt-2"></div>
                                </div>
                                <div class="col-12 col-md-5 col-lg-4">
                                    <label class="form-label fw-600 small text-secondary mb-1">Quantity Needed <span class="text-danger">*</span></label>
                                    <div class="src-qty-stepper">
                                        <button type="button" class="btn-qty-minus" aria-label="Decrease quantity">&minus;</button>
                                        <input type="number" name="items[0][quantity]" class="quantity-input" min="1" value="1" required>
                                        <button type="button" class="btn-qty-plus" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Another Product Dashed Trigger --}}
                    <div class="mt-3 mb-4">
                        <button type="button" class="src-add-dashed" id="addItemBtn">
                            <i class="bi bi-plus-circle-fill fs-6"></i>
                            <span>Add Another Product to Request</span>
                        </button>
                    </div>

                    {{-- Live Executive Summary Bar --}}
                    <div class="src-summary-bar mb-4">
                        <div>
                            <div class="src-summary-stat-k">Products Selected</div>
                            <div class="src-summary-stat-v text-primary" id="summaryTotalProducts">0 products</div>
                        </div>
                        <div>
                            <div class="src-summary-stat-k">Total Units Requested</div>
                            <div class="src-summary-stat-v text-success" id="summaryTotalUnits">0 units</div>
                        </div>
                        <div>
                            <div class="src-summary-stat-k">Destination Branch</div>
                            <div class="src-summary-stat-v text-dark" style="font-size:.96rem;">
                                <i class="bi bi-shop text-primary me-1"></i>{{ $shop->shop_name }}
                            </div>
                        </div>
                    </div>

                    {{-- Notes Section --}}
                    <div class="mb-4">
                        <label class="form-label fw-600 small text-secondary mb-1">
                            <i class="bi bi-chat-left-text text-primary me-1"></i>Additional Request Notes / Urgency (Optional)
                        </label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add helpful context for the Owner (e.g., customer order waiting, fast-moving weekend items, urgent restock)...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Footer Action Buttons --}}
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top gap-2 src-footer-actions">
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-custom px-4 py-2 fw-600">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-accent px-4 py-2 fw-700 shadow-sm" id="submitRequestBtn">
                            <i class="bi bi-send-fill me-1"></i>Submit Stock Request
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    let itemIndex = 1;
    const container = document.getElementById('requestItemsContainer');

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function refreshRowNumbers() {
        const rows = container.querySelectorAll('.request-item-row');
        rows.forEach(function(row, idx) {
            const label = row.querySelector('.row-label');
            if (label) label.textContent = 'Item #' + (idx + 1);
            const removeBtn = row.querySelector('.remove-row');
            if (removeBtn) {
                removeBtn.style.display = rows.length > 1 ? 'inline-flex' : 'none';
            }
        });
    }

    function updateRowMeta(row) {
        const select = row.querySelector('.item-select');
        const metaLine = row.querySelector('.item-meta-line');
        const option = select ? select.options[select.selectedIndex] : null;

        if (!option || !option.value) {
            metaLine.innerHTML = '';
            updateSummary();
            return;
        }

        const category = option.getAttribute('data-category') || 'General';
        const unit = option.getAttribute('data-unit') || 'pc';
        const myStock = parseInt(option.getAttribute('data-my-stock') || '0', 10);

        const stockClass = myStock > 0
            ? 'bg-primary-subtle text-primary border border-primary-subtle'
            : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        const stockText = myStock > 0
            ? `Your current shop stock: ${myStock} ${escapeHtml(unit)}`
            : 'Currently 0 in your shop';

        metaLine.innerHTML = `
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="src-branch-pill bg-light text-secondary border">
                    <i class="bi bi-tag"></i> ${escapeHtml(category)}
                </span>
                <span class="src-branch-pill ${stockClass}">
                    <i class="bi bi-shop"></i> ${stockText}
                </span>
            </div>
        `;

        updateSummary();
    }

    function updateSummary() {
        const rows = container.querySelectorAll('.request-item-row');
        const uniqueItems = new Set();
        let totalUnits = 0;

        rows.forEach(function(row) {
            const select = row.querySelector('.item-select');
            const input = row.querySelector('.quantity-input');
            if (select && select.value) {
                uniqueItems.add(select.value);
                totalUnits += Math.max(0, parseInt(input.value || '0', 10));
            }
        });

        const count = uniqueItems.size;
        document.getElementById('summaryTotalProducts').textContent = count + (count === 1 ? ' product' : ' products');
        document.getElementById('summaryTotalUnits').textContent = totalUnits.toLocaleString() + (totalUnits === 1 ? ' unit' : ' units');
    }

    function addProductRow() {
        const firstRow = container.querySelector('.request-item-row');
        const $firstSelect = $(firstRow).find('.item-select');

        if ($firstSelect.hasClass('select2-hidden-accessible')) {
            $firstSelect.select2('destroy');
        }

        const newRow = firstRow.cloneNode(true);

        if ($firstSelect.find('option').length > 8 && window.initSearchableSelect) {
            window.initSearchableSelect($firstSelect);
        }

        const select = newRow.querySelector('.item-select');
        select.classList.remove('select2-hidden-accessible');
        const oldS2 = newRow.querySelector('.select2-container');
        if (oldS2) oldS2.remove();

        select.name = `items[${itemIndex}][item_id]`;
        select.value = '';

        const input = newRow.querySelector('.quantity-input');
        input.name = `items[${itemIndex}][quantity]`;
        input.value = '1';

        newRow.querySelector('.item-meta-line').innerHTML = '';

        container.appendChild(newRow);

        if (select.options.length > 8 && window.initSearchableSelect) {
            window.initSearchableSelect(select);
        }

        itemIndex++;
        refreshRowNumbers();
        updateSummary();
    }

    document.getElementById('addItemBtn').addEventListener('click', addProductRow);
    document.getElementById('addItemTopBtn').addEventListener('click', addProductRow);

    // Event delegation for stepper, remove, select change & quantity input
    $(container).on('click', '.btn-qty-minus', function() {
        const input = $(this).closest('.src-qty-stepper').find('.quantity-input')[0];
        const cur = parseInt(input.value, 10) || 1;
        if (cur > 1) {
            input.value = cur - 1;
            updateSummary();
        }
    });

    $(container).on('click', '.btn-qty-plus', function() {
        const input = $(this).closest('.src-qty-stepper').find('.quantity-input')[0];
        const cur = parseInt(input.value, 10) || 0;
        input.value = cur + 1;
        updateSummary();
    });

    $(container).on('click', '.remove-row', function() {
        const rows = container.querySelectorAll('.request-item-row');
        if (rows.length <= 1) return;
        $(this).closest('.request-item-row').remove();
        refreshRowNumbers();
        updateSummary();
    });

    $(container).on('change', '.item-select', function() {
        updateRowMeta(this.closest('.request-item-row'));
    });

    $(container).on('input', '.quantity-input', function() {
        const val = parseInt(this.value, 10);
        if (val < 1 && this.value !== '') {
            this.value = 1;
        }
        updateSummary();
    });

    document.addEventListener('DOMContentLoaded', function() {
        container.querySelectorAll('.request-item-row').forEach(updateRowMeta);
        refreshRowNumbers();
    });
})();
</script>
@endpush
