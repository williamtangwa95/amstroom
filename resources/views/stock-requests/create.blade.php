@extends('layouts.app')
@section('title', 'New Stock Request')
@section('page-title', 'Request Warehouse Stock')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Stock Requests</a></li>
<li class="breadcrumb-item active">New Request</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            {{-- Header Banner --}}
            <div class="card-header bg-gradient-custom py-3 px-4 d-flex align-items-center justify-content-between border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background:rgba(255,255,255,0.15);width:44px;height:44px;">
                        <i class="bi bi-box-arrow-in-down-right fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-700 text-white" style="font-size:1.15rem;">Request Warehouse Stock</h5>
                        <small class="text-white-50" style="font-size:0.78rem;">Submit stock replenishment request for <strong>{{ $shop->shop_name }}</strong></small>
                    </div>
                </div>
                <div class="d-none d-sm-block text-end">
                    <span class="badge rounded-pill bg-white text-dark px-3 py-2 fw-600 shadow-sm" style="font-size:0.75rem;">
                        <i class="bi bi-shop me-1 text-accent"></i> {{ $shop->shop_name }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('stock-requests.store') }}" id="stockRequestForm">
                    @csrf

                    {{-- Instruction Box --}}
                    <div class="alert alert-info border-0 py-2 px-3 mb-4 d-flex align-items-center gap-2" style="background:rgba(88,166,255,0.08);color:#58a6ff;border-left:3px solid #58a6ff !important;font-size:0.82rem;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 fs-5"></i>
                        <div>Select items from the catalog below. Requested quantities are automatically validated against available Main Warehouse stock in real-time.</div>
                    </div>

                    {{-- Items Container Table/Cards --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                            <h6 class="fw-700 mb-0" style="font-size:0.9rem;color:var(--text-color);">Requested Products List</h6>
                            <small class="text-muted" style="font-size:0.75rem;">Select products & quantities</small>
                        </div>

                        <div id="requestItemsContainer" class="d-flex flex-column gap-3">
                            {{-- First Row --}}
                            <div class="p-3 rounded-3 border request-item-row position-relative" style="background:var(--input-bg);border-color:var(--input-border) !important;transition:all 0.2s ease;">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6 col-lg-7">
                                        <label class="form-label fw-600 small mb-1">Select Product <span class="text-danger">*</span></label>
                                        <select name="items[0][item_id]" class="form-select item-select" required>
                                            <option value="">Choose product to request...</option>
                                            @foreach($items as $item)
                                            @php $whStock = $item->getTotalMainStock(); @endphp
                                            <option value="{{ $item->id }}" data-stock="{{ $whStock }}" {{ (isset($selectedItemId) && $selectedItemId == $item->id) || request('item_id') == $item->id ? 'selected' : '' }}>
                                                [{{ $item->category->category_name ?? 'General' }}] {{ $item->item_name }} (Stock: {{ $whStock }})
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="item-stock-badge mt-1" style="font-size:0.75rem;"></div>
                                    </div>
                                    <div class="col-md-4 col-lg-4">
                                        <label class="form-label fw-600 small mb-1">Request Quantity <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-minus" onclick="adjustQty(this, -1)">-</button>
                                            <input type="number" name="items[0][quantity]" class="form-control text-center quantity-input fw-600" min="1" value="1" required style="max-width:90px;">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-plus" onclick="adjustQty(this, 1)">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-lg-1 text-end">
                                        <label class="form-label d-none d-md-block small mb-1">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row" style="display:none;" title="Remove Item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Item Button --}}
                    <div class="mb-4">
                        <button type="button" class="btn btn-sm btn-outline-custom px-3 py-2 fw-600" id="addItemBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product
                        </button>
                    </div>

                    {{-- Real-time Request Summary Bar --}}
                    <div class="p-3 rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.2);">
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <small class="text-muted d-block" style="font-size:0.72rem;">TOTAL PRODUCTS</small>
                                <strong class="fs-6 text-dark" id="summaryTotalProducts">1 product(s)</strong>
                            </div>
                            <div class="border-start ps-4">
                                <small class="text-muted d-block" style="font-size:0.72rem;">TOTAL UNITS REQUESTED</small>
                                <strong class="fs-6 text-accent" id="summaryTotalUnits">1 unit(s)</strong>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2" style="font-size:0.75rem;">
                                <i class="bi bi-shield-check me-1"></i> Stock Limits Validated
                            </span>
                        </div>
                    </div>

                    {{-- Notes Section --}}
                    <div class="mb-4">
                        <label class="form-label fw-600 small">Additional Request Notes / Justification</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Provide any details or urgency notes for the warehouse manager (e.g., High weekend demand)..."></textarea>
                    </div>

                    {{-- Form Action Buttons --}}
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top gap-2">
                        <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-custom px-4 py-2 fw-600">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-accent px-4 py-2 fw-700 shadow-sm" id="submitRequestBtn">
                            <i class="bi bi-send-fill me-1"></i> Submit Request
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
let itemIndex = 1;

function adjustQty(btn, delta) {
    const row = btn.closest('.request-item-row');
    const input = row.querySelector('.quantity-input');
    const current = parseInt(input.value, 10) || 1;
    const max = parseInt(input.getAttribute('max') || 999999, 10);
    const nextVal = current + delta;
    if (nextVal >= 1 && nextVal <= max) {
        input.value = nextVal;
        updateSummary();
    }
}

function updateStockBadge(row) {
    const select = row.querySelector('.item-select');
    const badgeContainer = row.querySelector('.item-stock-badge');
    const input = row.querySelector('.quantity-input');
    const option = select.options[select.selectedIndex];

    if (!option || option.value === '') {
        badgeContainer.innerHTML = '';
        input.removeAttribute('max');
        updateSummary();
        return;
    }

    const stock = parseInt(option.getAttribute('data-stock') || 0, 10);
    input.setAttribute('max', stock);

    if (stock > 5) {
        badgeContainer.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size:0.7rem;"><i class="bi bi-check-circle me-1"></i>Warehouse Stock Available: ${stock} units</span>`;
    } else if (stock > 0) {
        badgeContainer.innerHTML = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size:0.7rem;"><i class="bi bi-exclamation-triangle me-1"></i>Low Warehouse Stock: ${stock} units remaining</span>`;
    } else {
        badgeContainer.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:0.7rem;"><i class="bi bi-x-circle me-1"></i>Out of Stock in Main Warehouse</span>`;
    }

    if (parseInt(input.value, 10) > stock && stock > 0) {
        input.value = stock;
    }
    updateSummary();
}

function updateSummary() {
    const container = document.getElementById('requestItemsContainer');
    const rows = container.querySelectorAll('.request-item-row');
    let totalProducts = 0;
    let totalUnits = 0;

    rows.forEach(row => {
        const select = row.querySelector('.item-select');
        const input = row.querySelector('.quantity-input');
        if (select && select.value !== '') {
            totalProducts++;
            totalUnits += parseInt(input.value || 0, 10);
        }
    });

    document.getElementById('summaryTotalProducts').textContent = `${totalProducts} product(s)`;
    document.getElementById('summaryTotalUnits').textContent = `${totalUnits} unit(s)`;
}

document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('requestItemsContainer');
    const firstRow = container.querySelector('.request-item-row');
    
    // Destroy select2 on first row's select before cloning
    const $firstSelect = $(firstRow).find('select');
    if ($firstSelect.hasClass('select2-hidden-accessible')) {
        $firstSelect.select2('destroy');
    }
    
    const newRow = firstRow.cloneNode(true);
    
    // Re-initialize first row's select
    if ($firstSelect.find('option').length > 8 && window.initSearchableSelect) {
        window.initSearchableSelect($firstSelect);
    }

    const select = newRow.querySelector('select');
    
    // Clean up Select2 artifacts from the cloned select
    select.classList.remove('select2-hidden-accessible');
    const oldContainer = newRow.querySelector('.select2-container');
    if (oldContainer) {
        oldContainer.remove();
    }

    select.name = `items[${itemIndex}][item_id]`;
    select.value = '';
    const input = newRow.querySelector('.quantity-input');
    input.name = `items[${itemIndex}][quantity]`;
    input.value = '1';
    input.removeAttribute('max');
    
    newRow.querySelector('.item-stock-badge').innerHTML = '';
    
    const removeBtn = newRow.querySelector('.remove-row');
    removeBtn.style.display = 'block';
    removeBtn.addEventListener('click', () => {
        newRow.remove();
        updateSummary();
    });

    container.appendChild(newRow);
    
    // Initialize select2 on the new row's select
    if (select.options.length > 8 && window.initSearchableSelect) {
        window.initSearchableSelect(select);
    }
    
    itemIndex++;
    updateSummary();
});

// Event delegation for validation & badge update
const container = document.getElementById('requestItemsContainer');

container.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-select')) {
        updateStockBadge(e.target.closest('.request-item-row'));
    }
});

container.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity-input')) {
        const row = e.target.closest('.request-item-row');
        const select = row.querySelector('.item-select');
        const option = select.options[select.selectedIndex];
        if (option && option.value !== '') {
            const stock = parseInt(option.getAttribute('data-stock') || 0, 10);
            let val = parseInt(e.target.value, 10);
            if (val > stock && stock > 0) {
                e.target.value = stock;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stock Limit Exceeded',
                        text: `The requested quantity cannot exceed the warehouse stock of ${stock} units.`,
                        confirmButtonColor: '#0088cc',
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                }
            }
        }
        updateSummary();
    }
});

// Initial validation on load
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.request-item-row');
    rows.forEach(row => updateStockBadge(row));
});
</script>
@endpush
