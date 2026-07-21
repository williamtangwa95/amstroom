@extends('layouts.app')
@section('title', 'Point of Sale')
@section('page-title', 'New Sale (POS)')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">New Sale</li>
@endsection
@section('content')
<div class="row g-3">
    {{-- Left: Available Shop Products --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam-fill me-2" style="color:#3fb950;"></i>Available Shop Inventory</span>
                <input type="text" id="posSearch" class="form-control form-control-sm w-50" placeholder="Search product name/brand...">
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                <div class="row g-2 p-3" id="posProductGrid">
                    @forelse($shopStocks as $stock)
                    <div class="col-md-6 pos-item-card" data-name="{{ strtolower($stock->item->item_name) }}" data-brand="{{ strtolower($stock->item->brand) }}">
                        <div class="p-3 rounded border h-100 d-flex flex-column justify-content-between" style="background:var(--input-bg);border-color:var(--input-border) !important;">
                            <div>
                                <div class="badge badge-approved mb-1" style="font-size:.65rem;">{{ $stock->item->category->category_name }}</div>
                                <div class="fw-700" style="font-size:.88rem;color:var(--text-primary);">{{ $stock->item->item_name }}</div>
                                <div style="font-size:.75rem;color:var(--text-secondary);">{{ $stock->item->specification }}</div>
                            </div>
                            <div class="mt-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-800" style="color:#3fb950;font-size:.95rem;">TZS {{ number_format($stock->selling_price, 0) }}</div>
                                    <div style="font-size:.7rem;color:{{ $stock->isLowStock() ? '#e94560' : 'var(--text-secondary)' }};">
                                        In Stock: <strong>{{ $stock->remaining_quantity }}</strong>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-accent add-to-cart-btn"
                                    data-id="{{ $stock->id }}"
                                    data-name="{{ $stock->item->item_name }}"
                                    data-price="{{ $stock->selling_price }}"
                                    data-stock="{{ $stock->remaining_quantity }}">
                                    <i class="bi bi-cart-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5" style="color:var(--text-secondary);">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No stock available in this shop. Request stock from Main Store first.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Cart & Checkout --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-cart-check-fill me-2" style="color:#e94560;"></i>Shopping Cart</div>
            <div class="card-body d-flex flex-column">
                <form method="POST" action="{{ route('sales.store') }}" id="checkoutForm" class="flex-grow-1 d-flex flex-column">
                    @csrf
                    
                    <div id="cartItemsList" class="flex-grow-1 mb-3" style="max-height:350px;overflow-y:auto;">
                        <div class="text-center py-5 text-muted" id="emptyCartMsg">
                            <i class="bi bi-cart-x fs-2 d-block mb-1"></i>
                            Cart is empty. Select products from the left.
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-auto" style="border-color:var(--card-border) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-700" style="font-size:1.1rem;">Total Amount:</span>
                            <span class="fw-800" style="font-size:1.4rem;color:#3fb950;" id="cartTotalDisplay">TZS 0</span>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Customer Name (Optional)</label>
                            <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Walk-in Customer">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select form-select-sm" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_money">Mobile Money (M-Pesa / Tigo Pesa)</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 py-2 fw-700" id="checkoutBtn" disabled>
                            <i class="bi bi-check2-circle me-1"></i> Complete Sale
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
const cart = {};

// Product Filter
document.getElementById('posSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.pos-item-card').forEach(card => {
        const name = card.dataset.name;
        const brand = card.dataset.brand;
        if (name.includes(term) || brand.includes(term)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Add to Cart
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const maxStock = parseInt(this.dataset.stock);

        if (cart[id]) {
            if (cart[id].qty < maxStock) {
                cart[id].qty++;
            } else {
                Swal.fire({icon:'warning', title:'Stock Limit Reached', text:`Only ${maxStock} units available in shop.`, background:'#161b22', color:'#e6edf3'});
            }
        } else {
            cart[id] = { id, name, price, qty: 1, maxStock };
        }
        renderCart();
    });
});

function renderCart() {
    const list = document.getElementById('cartItemsList');
    const keys = Object.keys(cart);
    
    if (keys.length === 0) {
        list.innerHTML = `<div class="text-center py-5 text-muted" id="emptyCartMsg"><i class="bi bi-cart-x fs-2 d-block mb-1"></i>Cart is empty. Select products from the left.</div>`;
        document.getElementById('cartTotalDisplay').textContent = 'TZS 0';
        document.getElementById('checkoutBtn').disabled = true;
        return;
    }

    let html = '';
    let total = 0;
    let index = 0;

    keys.forEach(id => {
        const item = cart[id];
        const subtotal = item.qty * item.price;
        total += subtotal;

        html += `
            <div class="cart-item-row d-flex align-items-center justify-content-between">
                <input type="hidden" name="items[${index}][shop_stock_id]" value="${item.id}">
                <div style="flex:1;min-width:0;" class="pe-2">
                    <div class="fw-600 text-truncate" style="font-size:.83rem;">${item.name}</div>
                    <div style="font-size:.72rem;color:var(--text-secondary);">TZS ${item.price.toLocaleString()} × ${item.qty}</div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-custom px-2" onclick="changeQty('${id}', -1)">-</button>
                    <input type="number" name="items[${index}][quantity]" value="${item.qty}" readonly style="width:40px;text-align:center;" class="form-control form-control-sm py-0 px-1">
                    <button type="button" class="btn btn-xs btn-outline-custom px-2" onclick="changeQty('${id}', 1)">+</button>
                    <button type="button" class="btn btn-xs text-danger ms-1" onclick="removeItem('${id}')"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        `;
        index++;
    });

    list.innerHTML = html;
    document.getElementById('cartTotalDisplay').textContent = 'TZS ' + total.toLocaleString();
    document.getElementById('checkoutBtn').disabled = false;
}

function changeQty(id, delta) {
    if (cart[id]) {
        const newQty = cart[id].qty + delta;
        if (newQty <= 0) {
            delete cart[id];
        } else if (newQty > cart[id].maxStock) {
            Swal.fire({icon:'warning', title:'Stock Limit Reached', text:`Only ${cart[id].maxStock} units available.`, background:'#161b22', color:'#e6edf3'});
        } else {
            cart[id].qty = newQty;
        }
        renderCart();
    }
}

function removeItem(id) {
    delete cart[id];
    renderCart();
}
</script>
@endpush
