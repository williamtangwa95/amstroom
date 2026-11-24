@extends('layouts.app')
@section('title', 'Assign Stock to Shop')
@section('page-title', 'Direct Stock Assignment')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
<li class="breadcrumb-item active">Assign Stock</li>
@endsection

@section('content')
<style>
    /* ── Select2 Customizations for Stock Assignment ── */
    .select2-container {
        width: 100% !important;
    }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 42px !important;
        display: flex !important;
        align-items: center !important;
        border-radius: 8px !important;
        border-color: var(--input-border, #d0d7de) !important;
        background-color: var(--input-bg, #ffffff) !important;
        color: var(--text-primary, #1f2328) !important;
        padding-left: 0.65rem !important;
        padding-right: 0.65rem !important;
        font-size: 0.88rem !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        color: var(--text-primary, #1f2328) !important;
        line-height: 1.4 !important;
        padding: 0 !important;
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        border-radius: 8px !important;
        border-color: var(--card-border, #d0d7de) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
        background-color: var(--card-bg, #ffffff) !important;
        z-index: 1060 !important;
    }
    .select2-container--bootstrap-5 .select2-search__field {
        border-radius: 6px !important;
        font-size: 0.84rem !important;
        border-color: var(--input-border, #d0d7de) !important;
        background-color: var(--input-bg, #ffffff) !important;
        color: var(--text-primary, #1f2328) !important;
        padding: 0.45rem 0.65rem !important;
    }
    .select2-container--bootstrap-5 .select2-results__option {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.84rem !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: var(--accent, #0088cc) !important;
        color: #ffffff !important;
    }

    /* ── Item Row Card Styling ── */
    .item-row-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--card-border, #e1e4e8);
        border-left: 4px solid var(--accent, #0088cc) !important;
        border-radius: 10px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .item-row-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }
    .available-pill {
        transition: all 0.2s ease;
    }

    @media (max-width: 767.98px) {
        .dispatch-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem !important;
        }
        .dispatch-header .btn {
            width: 100% !important;
            justify-content: center !important;
        }
        .item-row-card {
            padding: 0.85rem !important;
        }
        .btn-remove-item {
            font-size: 0.8rem !important;
        }
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-lg-11 col-xl-10">

        {{-- Page Header Banner --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-4" style="background: var(--card-bg); border: 1px solid var(--card-border) !important; border-radius: 14px;">
            <div class="card-header border-0 py-3 px-3 px-md-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3" style="background: linear-gradient(135deg, rgba(0, 136, 204, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%); border-bottom: 1px solid var(--card-border) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center shadow-xs" style="background: var(--accent, #0088cc); color: #ffffff; width: 44px; height: 44px; flex-shrink: 0; border-radius: 10px;">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-800" style="color: var(--text-primary); font-size: 1.15rem;">Direct Stock Assignment</h5>
                        <small style="color: var(--text-secondary); font-size: 0.78rem;">Allocate and dispatch inventory directly from Central Warehouse to a branch shop</small>
                    </div>
                </div>
                <div class="w-100 w-sm-auto text-end">
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-sm btn-outline-custom w-100 w-sm-auto shadow-xs">
                        <i class="bi bi-arrow-left me-1"></i> Transfers History
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-4 shadow-xs" style="font-size:.83rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-left: 4px solid #ef4444 !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>
                        <strong>Please resolve the following errors:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('stock-transfers.store') }}" id="assignForm">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

            {{-- Step 1: Target Destination Shop --}}
            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border: 1px solid var(--card-border) !important; border-radius: 12px;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label for="shop_id" class="form-label fw-700 mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem; color: var(--text-primary);">
                            <span class="badge bg-primary px-2.5 py-1 text-white fw-700" style="font-size:0.75rem; border-radius:6px;">Step 1</span>
                            <span>Target Destination Shop <span class="text-danger">*</span></span>
                        </label>
                        <span class="badge bg-light text-secondary border small d-none d-sm-inline-flex align-items-center gap-1" style="font-size:0.73rem;">
                            <i class="bi bi-search"></i> Searchable / Filterable
                        </span>
                    </div>
                    <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                        Select the branch shop that will receive this stock dispatch. You can type to instantly search by shop name or location.
                    </p>

                    <div class="position-relative">
                        <select name="shop_id" id="shop_id" class="form-select" required>
                            <option value="">— Type to search or select target shop —</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}"
                                    data-location="{{ $shop->location }}"
                                    {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->shop_name }} ({{ $shop->location }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="selectedShopDetails" class="mt-2.5 d-none">
                        <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size:0.8rem; background:rgba(0,136,204,0.06); border:1px solid rgba(0,136,204,0.18); color:var(--text-primary); border-radius:8px;">
                            <i class="bi bi-geo-alt-fill text-accent fs-6"></i>
                            <span>Selected destination: <strong id="selectedShopName" class="text-accent"></strong> &bull; Location: <span id="selectedShopLocation" class="text-muted"></span></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Items to Dispatch --}}
            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border: 1px solid var(--card-border) !important; border-radius: 12px;">
                <div class="card-header py-3 px-3 px-md-4 border-bottom d-flex align-items-center justify-content-between" style="background: var(--card-bg); border-color: var(--card-border) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary px-2.5 py-1 text-white fw-700" style="font-size:0.75rem; border-radius:6px;">Step 2</span>
                        <h6 class="mb-0 fw-700" style="color: var(--text-primary); font-size: 0.95rem;">
                            <i class="bi bi-box-seam me-1 text-accent"></i> Select Items & Quantities
                        </h6>
                    </div>
                    <button type="button" class="btn btn-sm btn-accent shadow-xs px-3" onclick="addItemRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="items-container" class="d-flex flex-column gap-3">
                        {{-- First Row --}}
                        <div class="cart-item-row item-row item-row-card p-3 p-md-3" id="item-row-0">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-secondary-subtle text-secondary border small fw-600 item-number-badge" style="font-size:0.72rem;">Item #1</span>
                                <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-item d-none" onclick="removeItemRow(this)" title="Remove Item">
                                    <i class="bi bi-trash"></i> <span class="small">Remove</span>
                                </button>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-600 mb-1">Product <span class="text-danger">*</span></label>
                                    <select name="items[0][id]" class="form-select item-select" required>
                                        <option value="">— Type to search or choose product —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                data-name="{{ $item->item_name }}"
                                                data-brand="{{ $item->brand }}"
                                                data-category="{{ $item->category?->category_name ?? 'General' }}"
                                                data-available="{{ $availableStock[$item->id] ?? 0 }}">
                                                [{{ $item->category?->category_name ?? 'General' }}] {{ $item->item_name }}{{ $item->brand ? ' (' . $item->brand . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-600 mb-1">Available in Store</label>
                                    <div class="available-badge-wrapper">
                                        <span class="available-pill badge bg-light text-secondary border px-2.5 py-2 fs-6 fw-700 w-100 text-center">—</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-600 mb-1">Dispatch Quantity <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <button type="button" class="btn btn-outline-secondary px-2.5" onclick="changeQty(this, -1)">-</button>
                                        <input type="number" name="items[0][qty]" class="form-control text-center fw-700 qty-input" min="1" value="1" required>
                                        <button type="button" class="btn btn-outline-secondary px-2.5" onclick="changeQty(this, 1)">+</button>
                                    </div>
                                    <div class="qty-warning text-danger small mt-1 d-none" style="font-size:0.72rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Exceeds available stock!
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Item Bottom Button --}}
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-custom w-100 py-2.5 d-flex align-items-center justify-content-center gap-2" onclick="addItemRow()" style="border-style: dashed !important; border-width: 1.5px !important; border-radius: 8px;">
                            <i class="bi bi-plus-circle text-accent fs-6"></i>
                            <span class="fw-600">Add Another Product</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 3: Real-Time Summary & Dispatch Bar --}}
            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border: 1px solid var(--card-border) !important; border-radius: 12px;">
                <div class="card-body p-3 p-md-4">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-12 col-md-auto d-flex flex-wrap align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-shop fs-6"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size:0.68rem; font-weight:700; letter-spacing:0.5px;">TARGET SHOP</div>
                                    <div class="fw-700" id="summaryTargetShop" style="font-size:0.88rem; color:var(--text-primary);">No shop selected</div>
                                </div>
                            </div>
                            <div class="vr d-none d-md-block" style="height:32px;"></div>
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <div class="text-muted" style="font-size:0.68rem; font-weight:700; letter-spacing:0.5px;">PRODUCTS</div>
                                    <div class="fw-700 fs-6 text-primary" id="summaryTotalProducts">1 product</div>
                                </div>
                                <div class="vr" style="height:26px;"></div>
                                <div>
                                    <div class="text-muted" style="font-size:0.68rem; font-weight:700; letter-spacing:0.5px;">TOTAL DISPATCH UNITS</div>
                                    <div class="fw-700 fs-6 text-success" id="summaryTotalUnits">1 unit</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-auto text-end">
                            <button type="submit" class="btn btn-accent px-4 py-2.5 fw-700 w-100 w-md-auto shadow-sm" id="btnSubmitDispatch">
                                <i class="bi bi-truck me-2"></i>Dispatch Stock Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let itemIndex = 1;

// ── Select2 Custom Formatters ──────────────────────────────────────────
function formatShopOption(item) {
    if (!item.id) return item.text;
    const location = $(item.element).data('location') || '';
    const name = item.text.replace(/\s*\([^)]*\)\s*$/, '').trim();
    return $(`
        <div class="d-flex align-items-center justify-content-between py-1">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <i class="bi bi-shop text-primary fs-6"></i>
                <span class="fw-600 text-truncate">${name}</span>
            </div>
            ${location ? `<span class="badge bg-secondary-subtle text-secondary small border ms-2 flex-shrink-0" style="font-size:0.7rem;"><i class="bi bi-geo-alt me-1"></i>${location}</span>` : ''}
        </div>
    `);
}

function formatShopSelection(item) {
    if (!item.id) return item.text;
    const location = $(item.element).data('location') || '';
    const name = item.text.replace(/\s*\([^)]*\)\s*$/, '').trim();
    return $(`
        <span class="d-flex align-items-center gap-2 text-truncate">
            <i class="bi bi-shop text-primary"></i>
            <span class="fw-600">${name}</span>
            ${location ? `<small class="text-muted">(${location})</small>` : ''}
        </span>
    `);
}

function formatItemOption(item) {
    if (!item.id) return item.text;
    const avail = $(item.element).data('available');
    const cat = $(item.element).data('category') || 'General';
    const brand = $(item.element).data('brand') || '';
    const name = $(item.element).data('name') || item.text;
    const isOutOfStock = avail <= 0;

    const availBadge = isOutOfStock
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.68rem;">Out of stock</span>'
        : `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">Avail: ${avail}</span>`;

    return $(`
        <div class="d-flex align-items-center justify-content-between py-1">
            <div class="min-w-0 pe-2">
                <div class="fw-600 text-truncate" style="font-size:0.83rem;">${name}</div>
                <div class="text-muted small" style="font-size:0.72rem;">${cat}${brand ? ' &bull; ' + brand : ''}</div>
            </div>
            <div class="flex-shrink-0">${availBadge}</div>
        </div>
    `);
}

function formatItemSelection(item) {
    if (!item.id) return item.text;
    const avail = $(item.element).data('available');
    const name = $(item.element).data('name') || item.text;
    return $(`
        <span class="d-flex align-items-center gap-2 text-truncate">
            <i class="bi bi-box-seam text-secondary"></i>
            <span class="fw-600">${name}</span>
            <span class="badge bg-secondary-subtle text-secondary border small" style="font-size:0.7rem;">Stock: ${avail ?? 0}</span>
        </span>
    `);
}

// ── Select2 Initializers ────────────────────────────────────────────────
function initShopSelect() {
    const $shopSelect = $('#shop_id');
    if ($shopSelect.hasClass('select2-hidden-accessible')) {
        $shopSelect.select2('destroy');
    }
    $shopSelect.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '— Type to search or select target shop —',
        allowClear: true,
        templateResult: formatShopOption,
        templateSelection: formatShopSelection
    });

    $shopSelect.on('change', function() {
        const val = $(this).val();
        const $opt = $(this).find('option:selected');
        if (val) {
            const loc = $opt.data('location') || '';
            const name = $opt.text().replace(/\s*\([^)]*\)\s*$/, '').trim();
            $('#selectedShopName').text(name);
            $('#selectedShopLocation').text(loc || 'N/A');
            $('#selectedShopDetails').removeClass('d-none');
            $('#summaryTargetShop').html(`<i class="bi bi-shop text-primary me-1"></i><strong>${name}</strong>`);
        } else {
            $('#selectedShopDetails').addClass('d-none');
            $('#summaryTargetShop').text('No shop selected');
        }
        validateForm();
    });

    if ($shopSelect.val()) {
        $shopSelect.trigger('change');
    }
}

function initItemSelect($select) {
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '— Type to search or choose product —',
        allowClear: true,
        templateResult: formatItemOption,
        templateSelection: formatItemSelection
    });

    $select.on('change', function() {
        updateRowAvailable($(this));
        validateForm();
    });
}

// ── Row Logic & Dynamic Rows ───────────────────────────────────────────
function addItemRow() {
    const container = document.getElementById('items-container');
    const rowId = `item-row-${itemIndex}`;
    const row = document.createElement('div');
    row.id = rowId;
    row.className = 'cart-item-row item-row item-row-card p-3 p-md-3';
    row.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-secondary-subtle text-secondary border small fw-600 item-number-badge" style="font-size:0.72rem;">Item #${container.children.length + 1}</span>
            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none btn-remove-item" onclick="removeItemRow(this)" title="Remove Item">
                <i class="bi bi-trash"></i> <span class="small">Remove</span>
            </button>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small fw-600 mb-1">Product <span class="text-danger">*</span></label>
                <select name="items[${itemIndex}][id]" class="form-select item-select" required>
                    <option value="">— Type to search or choose product —</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-name="{{ $item->item_name }}"
                            data-brand="{{ $item->brand }}"
                            data-category="{{ $item->category?->category_name ?? 'General' }}"
                            data-available="{{ $availableStock[$item->id] ?? 0 }}">
                            [{{ $item->category?->category_name ?? 'General' }}] {{ $item->item_name }}{{ $item->brand ? ' (' . $item->brand . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-600 mb-1">Available in Store</label>
                <div class="available-badge-wrapper">
                    <span class="available-pill badge bg-light text-secondary border px-2.5 py-2 fs-6 fw-700 w-100 text-center">—</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-600 mb-1">Dispatch Quantity <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <button type="button" class="btn btn-outline-secondary px-2.5" onclick="changeQty(this, -1)">-</button>
                    <input type="number" name="items[${itemIndex}][qty]" class="form-control text-center fw-700 qty-input" min="1" value="1" required>
                    <button type="button" class="btn btn-outline-secondary px-2.5" onclick="changeQty(this, 1)">+</button>
                </div>
                <div class="qty-warning text-danger small mt-1 d-none" style="font-size:0.72rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Exceeds available stock!
                </div>
            </div>
        </div>
    `;
    container.appendChild(row);

    const $newSelect = $(row).find('.item-select');
    initItemSelect($newSelect);

    itemIndex++;
    renumberItems();
    validateForm();
}

function removeItemRow(btn) {
    const rows = document.querySelectorAll('#items-container .item-row');
    if (rows.length > 1) {
        const row = btn.closest('.item-row');
        $(row).fadeOut(150, function() {
            $(this).remove();
            renumberItems();
            validateForm();
        });
    }
}

function renumberItems() {
    const rows = document.querySelectorAll('#items-container .item-row');
    rows.forEach((row, idx) => {
        const badge = row.querySelector('.item-number-badge');
        if (badge) {
            badge.textContent = `Item #${idx + 1}`;
        }
        const removeBtn = row.querySelector('.btn-remove-item');
        if (removeBtn) {
            if (rows.length === 1) {
                removeBtn.classList.add('d-none');
            } else {
                removeBtn.classList.remove('d-none');
            }
        }
    });
}

function changeQty(btn, delta) {
    const row = btn.closest('.item-row');
    const input = row.querySelector('.qty-input');
    let current = parseInt(input.value) || 0;
    let next = current + delta;
    if (next < 1) next = 1;
    input.value = next;
    validateRow(row);
    validateForm();
}

function updateRowAvailable($select) {
    const row = $select.closest('.item-row')[0];
    if (!row) return;
    const pill = row.querySelector('.available-pill');
    const qtyInput = row.querySelector('.qty-input');
    const opt = $select.find('option:selected')[0];

    if (opt && opt.value) {
        const avail = parseInt(opt.getAttribute('data-available')) || 0;
        pill.textContent = `${avail} units`;
        if (avail <= 0) {
            pill.className = 'available-pill badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-2 fs-6 fw-700 w-100 text-center';
        } else if (avail <= 5) {
            pill.className = 'available-pill badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-2 fs-6 fw-700 w-100 text-center';
        } else {
            pill.className = 'available-pill badge bg-success-subtle text-success border border-success-subtle px-2.5 py-2 fs-6 fw-700 w-100 text-center';
        }
        qtyInput.max = avail;
    } else {
        pill.textContent = '—';
        pill.className = 'available-pill badge bg-light text-secondary border px-2.5 py-2 fs-6 fw-700 w-100 text-center';
        qtyInput.removeAttribute('max');
    }
    validateRow(row);
}

function validateRow(row) {
    const select = row.querySelector('.item-select');
    const qtyInput = row.querySelector('.qty-input');
    const warning = row.querySelector('.qty-warning');
    const opt = select ? select.options[select.selectedIndex] : null;

    if (opt && opt.value) {
        const avail = parseInt(opt.getAttribute('data-available')) || 0;
        const qty = parseInt(qtyInput.value) || 0;
        if (qty > avail) {
            qtyInput.classList.add('is-invalid');
            if (warning) warning.classList.remove('d-none');
            return false;
        } else {
            qtyInput.classList.remove('is-invalid');
            if (warning) warning.classList.add('d-none');
            return true;
        }
    }
    qtyInput.classList.remove('is-invalid');
    if (warning) warning.classList.add('d-none');
    return true;
}

function validateForm() {
    let allValid = true;
    let totalItems = 0;
    let totalUnits = 0;
    const shopVal = $('#shop_id').val();

    if (!shopVal) {
        allValid = false;
    }

    const rows = document.querySelectorAll('#items-container .item-row');
    rows.forEach(row => {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.qty-input');
        if (!select || !select.value) {
            allValid = false;
        } else {
            totalItems++;
            const qty = parseInt(qtyInput.value) || 0;
            totalUnits += qty;
            if (!validateRow(row)) {
                allValid = false;
            }
        }
    });

    if (totalItems === 0) {
        allValid = false;
    }

    $('#summaryTotalProducts').text(`${totalItems} product${totalItems === 1 ? '' : 's'}`);
    $('#summaryTotalUnits').text(`${totalUnits} unit${totalUnits === 1 ? '' : 's'}`);
    $('#btnSubmitDispatch').prop('disabled', !allValid);

    return allValid;
}

// ── On Document Ready ──────────────────────────────────────────────────
$(document).ready(function() {
    initShopSelect();
    initItemSelect($('.item-select'));

    $(document).on('input change', '.qty-input', function() {
        const row = $(this).closest('.item-row')[0];
        if (row) validateRow(row);
        validateForm();
    });

    $('#assignForm').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Please check your inputs',
                text: 'Ensure a target shop is chosen and no dispatch quantity exceeds warehouse stock.',
                background: 'var(--card-bg)',
                color: 'var(--text-primary)'
            });
            return false;
        }
        $('#btnSubmitDispatch').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Dispatching stock...');
    });

    $(window).on('resize', function() {
        $('.select2-container').css('width', '100%');
    });

    renumberItems();
    validateForm();
});
</script>
@endpush
