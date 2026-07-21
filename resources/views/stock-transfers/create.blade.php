@extends('layouts.app')
@section('title', 'Assign Stock to Shop')
@section('page-title', 'Direct Stock Assignment')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
<li class="breadcrumb-item active">Assign Stock</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700 text-primary">
                    <i class="bi bi-truck me-2"></i>Dispatch Stock to Shop
                </h6>
                <a href="{{ route('stock-transfers.index') }}" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 mb-4" style="font-size:.83rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('stock-transfers.store') }}" id="assignForm">
                    @csrf

                    {{-- Target Shop --}}
                    <div class="mb-4">
                        <label for="shop_id" class="form-label fw-600">Target Shop <span class="text-danger">*</span></label>
                        <select name="shop_id" id="shop_id" class="form-select" required>
                            <option value="">— Select Shop —</option>
                            @foreach($shops as $shop)
                                <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>
                                    {{ $shop->shop_name }} — {{ $shop->location }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Items Section --}}
                    <div class="border rounded-3 p-3 bg-light mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-700 mb-0"><i class="bi bi-box-seam-fill text-primary me-2"></i>Select Items</h6>
                            <button type="button" class="btn btn-sm btn-accent" onclick="addItemRow()">
                                <i class="bi bi-plus-lg me-1"></i> Add Item
                            </button>
                        </div>

                        <div id="items-container">
                            <div class="cart-item-row item-row d-flex align-items-start gap-3">
                                <div class="flex-fill">
                                    <label class="form-label small fw-600">Product</label>
                                    <select name="items[0][id]" class="form-select item-select" onchange="updateAvailable(this)" required>
                                        <option value="">— Select Product —</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                data-available="{{ $availableStock[$item->id] ?? 0 }}"
                                                data-category="{{ $item->category?->category_name ?? 'N/A' }}">
                                                {{ $item->item_name }} ({{ $item->brand }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width:120px;">
                                    <label class="form-label small fw-600">Available</label>
                                    <input type="text" class="form-control available-display" readonly value="—" style="background:#e2e8f0;">
                                </div>
                                <div style="width:120px;">
                                    <label class="form-label small fw-600">Quantity</label>
                                    <input type="number" name="items[0][qty]" class="form-control qty-input" min="1" value="1" required>
                                </div>
                                <div style="padding-top:28px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-accent px-4">
                            <i class="bi bi-truck me-1"></i> Dispatch Stock
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

function addItemRow() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'cart-item-row item-row d-flex align-items-start gap-3 mt-2';
    row.innerHTML = `
        <div class="flex-fill">
            <label class="form-label small fw-600">Product</label>
            <select name="items[${itemIndex}][id]" class="form-select item-select" onchange="updateAvailable(this)" required>
                <option value="">— Select Product —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}"
                        data-available="{{ $availableStock[$item->id] ?? 0 }}"
                        data-category="{{ $item->category?->category_name ?? 'N/A' }}">
                        {{ $item->item_name }} ({{ $item->brand }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="width:120px;">
            <label class="form-label small fw-600">Available</label>
            <input type="text" class="form-control available-display" readonly value="—" style="background:#e2e8f0;">
        </div>
        <div style="width:120px;">
            <label class="form-label small fw-600">Quantity</label>
            <input type="number" name="items[${itemIndex}][qty]" class="form-control qty-input" min="1" value="1" required>
        </div>
        <div style="padding-top:28px;">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    itemIndex++;
}

function removeItemRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        btn.closest('.item-row').remove();
    }
}

function updateAvailable(select) {
    const row = select.closest('.item-row');
    const display = row.querySelector('.available-display');
    const qtyInput = row.querySelector('.qty-input');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const avail = parseInt(option.getAttribute('data-available')) || 0;
        display.value = avail;
        qtyInput.max = avail;
        if (parseInt(qtyInput.value) > avail) qtyInput.value = avail;
    } else {
        display.value = '—';
        qtyInput.removeAttribute('max');
    }
}
</script>
@endpush
