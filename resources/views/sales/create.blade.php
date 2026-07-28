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
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                <span class="fw-700" style="font-size:.9rem;"><i class="bi bi-box-seam-fill me-2" style="color:#3fb950;"></i>Available Inventory</span>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="showAllProductsToggle">
                        <label class="form-check-label small fw-600 text-nowrap" for="showAllProductsToggle" style="color:var(--text-secondary);cursor:pointer;user-select:none;">Show Out of Stock</label>
                    </div>
                    <input type="text" id="posSearch" class="form-control form-control-sm" placeholder="Search name/brand..." style="width:160px;">
                </div>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                <div class="row g-2 p-3" id="posProductGrid">
                    @forelse($shopStocks as $stock)
                    @php
                        $pendingPrice = $stock->is_price_pending ? $stock->pending_selling_price : null;
                        $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';
                        $isLocked = !auth()->user()->isOwner() && $isIndependent && !$stock->is_sellable;
                        $hasStock = $stock->remaining_quantity > 0;
                        $isMock = str_starts_with($stock->id, 'item_');
                    @endphp
                    <div class="col-md-6 pos-item-card" 
                         data-name="{{ strtolower($stock->item->item_name) }}" 
                         data-brand="{{ strtolower($stock->item->brand) }}"
                         data-available="{{ ($hasStock && !$isLocked && !$isMock) ? 'true' : 'false' }}">
                        <div class="p-3 rounded border h-100 d-flex flex-column justify-content-between" style="background:var(--input-bg);border-color:var(--input-border) !important; opacity: {{ ($hasStock && !$isLocked && !$isMock) ? '1' : '.65' }};">
                            <div class="d-flex gap-2">
                                @if($stock->item->image_path)
                                <img src="{{ asset('storage/' . $stock->item->image_path) }}" alt="{{ $stock->item->item_name }}" class="rounded border" style="width: 55px; height: 55px; object-fit: cover; flex-shrink: 0;">
                                @else
                                <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted border" style="width: 55px; height: 55px; flex-shrink: 0;">
                                    <i class="bi bi-image" style="font-size: 1.2rem;"></i>
                                </div>
                                @endif
                                <div style="min-width:0;">
                                    <div class="badge badge-approved mb-1" style="font-size:.65rem;">{{ $stock->item->category->category_name }}</div>
                                    @if($isLocked)
                                        <div class="badge bg-danger mb-1" style="font-size:.65rem;"><i class="bi bi-lock-fill"></i> Locked</div>
                                    @elseif($isMock)
                                        <div class="badge bg-secondary mb-1" style="font-size:.65rem;">Catalog Only</div>
                                    @elseif(!$hasStock)
                                        <div class="badge bg-warning text-dark mb-1" style="font-size:.65rem;">Out of Stock</div>
                                    @endif
                                    <div class="fw-700 text-truncate" style="font-size:.88rem;color:var(--text-primary);" title="{{ $stock->item->item_name }}">{{ $stock->item->item_name }}</div>
                                    <div class="text-truncate" style="font-size:.75rem;color:var(--text-secondary);" title="{{ $stock->item->specification }}">{{ $stock->item->specification }}</div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-800" style="color:#3fb950;font-size:.95rem;">TZS {{ number_format($stock->selling_price, 0) }}</div>
                                    <div style="font-size:.7rem;color:{{ ($stock->isLowStock() && !$isMock) ? '#e94560' : 'var(--text-secondary)' }};">
                                        In Stock: <strong>{{ $stock->remaining_quantity }}</strong>
                                    </div>
                                </div>
                                @if($isLocked)
                                <button type="button" class="btn btn-sm btn-secondary add-to-cart-btn" disabled
                                    data-is-sellable="false">
                                    <i class="bi bi-lock-fill"></i> Locked
                                </button>
                                @else
                                <button type="button" class="btn btn-sm {{ ($hasStock && !$isMock) ? 'btn-accent' : 'btn-outline-secondary' }} add-to-cart-btn"
                                    data-id="{{ $stock->id }}"
                                    data-name="{{ $stock->item->item_name }}"
                                    data-price="{{ $stock->selling_price }}"
                                    data-stock="{{ $stock->remaining_quantity }}"
                                    data-price-pending="{{ $pendingPrice ? 'true' : 'false' }}"
                                    data-pending-price="{{ $pendingPrice ?? 0 }}"
                                    data-is-sellable="true">
                                    <i class="bi bi-cart-plus me-1"></i> Add
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5 text-muted" id="noProductsMsg">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No available products in this shop. Check 'Show Out of Stock' to create a proforma.
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

                        {{-- Billing & Delivery Details Collapsible --}}
                        <div class="mb-3">
                            <a class="d-flex align-items-center gap-2 text-decoration-none fw-600" style="font-size:.82rem;color:var(--accent);" data-bs-toggle="collapse" href="#billingDetailsPanel" role="button">
                                <i class="bi bi-file-earmark-text"></i> + Add Billing & Delivery Details (for Invoice/Proforma)
                            </a>
                            <div class="collapse mt-2" id="billingDetailsPanel">
                                <div class="rounded p-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Customer ID</label>
                                            <input type="text" name="customer_id" class="form-control form-control-sm" placeholder="e.g. AD-0025">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Customer P.O. Box</label>
                                            <input type="text" name="customer_po_box" class="form-control form-control-sm" placeholder="e.g. 6858 Morogoro">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Deliver To</label>
                                            <input type="text" name="deliver_to" class="form-control form-control-sm" placeholder="e.g. CHAMWINO STUDENT CENTER">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Delivery Date</label>
                                            <input type="date" name="delivery_date" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Delivery Time</label>
                                            <input type="time" name="delivery_time" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Validity Date</label>
                                            <input type="date" name="validity_date" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Terms of Payment</label>
                                            <input type="text" name="terms_of_payment" class="form-control form-control-sm" placeholder="e.g. 30 Days Net">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Custom Off-Catalog Item Entry --}}
                        <div class="mb-3">
                            <a class="d-flex align-items-center gap-2 text-decoration-none fw-600" style="font-size:.82rem;color:#e3b341;" data-bs-toggle="collapse" href="#customItemPanel" role="button">
                                <i class="bi bi-plus-circle-dotted"></i> + Add Custom Item (Proforma Only)
                            </a>
                            <div class="collapse mt-2" id="customItemPanel">
                                <div class="rounded p-3" style="background:var(--input-bg);border:1px dashed #e3b341;">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Product / Service Name *</label>
                                            <input type="text" id="customItemName" class="form-control form-control-sm" placeholder="e.g. Laptop HP Elitebook 840">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Qty *</label>
                                            <input type="number" id="customItemQty" class="form-control form-control-sm" value="1" min="1">
                                        </div>
                                        <div class="col-7">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Unit Price (TZS) *</label>
                                            <input type="number" id="customItemPrice" class="form-control form-control-sm" placeholder="0" min="0">
                                        </div>
                                        <div class="col-12">
                                            <button type="button" class="btn btn-sm w-100 fw-600" onclick="addCustomItem()" style="background:#e3b341;color:#000;">
                                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="sale_status" id="saleStatusInput" value="completed">

                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-accent w-100 py-2 fw-700" id="checkoutBtn" disabled
                                onclick="document.getElementById('saleStatusInput').value='completed'">
                                <i class="bi bi-check2-circle me-1"></i> Complete Sale
                            </button>
                            <button type="submit" class="btn btn-outline-custom w-100 py-2 fw-600" id="proformaBtn" disabled
                                onclick="document.getElementById('saleStatusInput').value='draft_proforma'">
                                <i class="bi bi-file-earmark-text me-1"></i> Save as Proforma Quote
                            </button>
                        </div>
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

    // Product Filter (Combined search & toggle)
    function filterProducts() {
        const term = document.getElementById('posSearch').value.toLowerCase();
        const showAll = document.getElementById('showAllProductsToggle').checked;

        document.querySelectorAll('.pos-item-card').forEach(card => {
            const name = card.dataset.name || '';
            const brand = card.dataset.brand || '';
            const available = card.dataset.available === 'true';

            const matchesSearch = name.includes(term) || brand.includes(term);
            const matchesAvailability = showAll || available;

            if (matchesSearch && matchesAvailability) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.getElementById('posSearch').addEventListener('input', filterProducts);
    document.getElementById('showAllProductsToggle').addEventListener('change', filterProducts);

    // Initial run to hide out-of-stock / unstocked items by default
    filterProducts();

    // Add to Cart
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const maxStock = parseInt(this.dataset.stock);
            const isPending = this.dataset.pricePending === 'true';
            const pendingPrice = parseFloat(this.dataset.pendingPrice);

            if (isPending) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Price Changed',
                    text: 'Please wait for admin approval to complete the transaction!',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
            }

            const isSellable = this.dataset.isSellable !== 'false';
            if (!isSellable) {
                Swal.fire({
                    icon: 'error',
                    title: 'Item Locked',
                    text: 'Main Store updated transfer price for this item. Please review and update Selling Price to restore sales eligibility.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
            }

            if (cart[id]) {
                cart[id].qty++;
            } else {
                cart[id] = {
                    id,
                    name,
                    price,
                    qty: 1,
                    maxStock,
                    negotiatedPrice: price
                };
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
            document.getElementById('proformaBtn').disabled = true;
            return;
        }

        let html = '';
        let total = 0;
        let index = 0;

        keys.forEach(id => {
            const item = cart[id];
            const subtotal = item.qty * item.negotiatedPrice;
            total += subtotal;

            html += `
            <div class="cart-item-row d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2 mb-2 border-bottom" style="border-color:var(--card-border) !important;">
                <input type="hidden" name="items[${index}][shop_stock_id]" value="${item.id}">
                ${item.isCustom ? `<input type="hidden" name="items[${index}][custom_name]" value="${item.name}">` : ''}
                <div style="flex:1;min-width:0;" class="pe-2">
                    <div class="fw-600 text-truncate" style="font-size:.83rem;">${item.name} ${item.isCustom ? '<span style="font-size:.6rem;background:#e3b341;color:#000;padding:1px 5px;border-radius:3px;margin-left:3px;">CUSTOM</span>' : ''}</div>
                    <div style="font-size:.7rem;color:var(--text-secondary);">Min Price: TZS ${item.price.toLocaleString()}</div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-custom px-2" onclick="changeQty('${id}', -1)">-</button>
                    <input type="number" name="items[${index}][quantity]" value="${item.qty}" readonly style="width:40px;text-align:center;" class="form-control form-control-sm py-0 px-1">
                    <button type="button" class="btn btn-xs btn-outline-custom px-2" onclick="changeQty('${id}', 1)">+</button>
                    
                    <div class="input-group input-group-sm ms-2" style="width:120px;">
                        <input type="text" name="items[${index}][price]" value="${window.formatCurrencyValue ? window.formatCurrencyValue(String(item.negotiatedPrice)) : item.negotiatedPrice}" 
                               class="form-control form-control-sm py-0 px-1 currency-input" min="0" 
                               onchange="updateItemPrice('${id}', this.value)" required>
                    </div>

                    <button type="button" class="btn btn-xs text-danger ms-1" onclick="removeItem('${id}')"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        `;
            index++;
        });

        list.innerHTML = html;
        document.getElementById('cartTotalDisplay').textContent = 'TZS ' + total.toLocaleString();
        document.getElementById('checkoutBtn').disabled = false;
        document.getElementById('proformaBtn').disabled = false;
    }

    function updateItemPrice(id, val) {
        if (cart[id]) {
            const cleanVal = String(val).replace(/,/g, '');
            const floatVal = parseFloat(cleanVal);
            if (isNaN(floatVal) || floatVal < 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Price',
                    text: `Negotiated price cannot be less than 0.`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
                cart[id].negotiatedPrice = 0;
            } else {
                cart[id].negotiatedPrice = floatVal;
            }
            renderCart();
        }
    }

    function changeQty(id, delta) {
        if (cart[id]) {
            const newQty = cart[id].qty + delta;
            if (newQty <= 0) {
                delete cart[id];
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

    // Add a completely off-catalog custom item to the proforma cart
    function addCustomItem() {
        const nameEl  = document.getElementById('customItemName');
        const qtyEl   = document.getElementById('customItemQty');
        const priceEl = document.getElementById('customItemPrice');

        const name  = nameEl.value.trim();
        const qty   = parseInt(qtyEl.value) || 1;
        const price = parseFloat(priceEl.value) || 0;

        if (!name) {
            Swal.fire({ icon: 'warning', title: 'Name Required', text: 'Please enter a product/service name.', background: '#161b22', color: '#e6edf3' });
            return;
        }
        if (price <= 0) {
            Swal.fire({ icon: 'warning', title: 'Price Required', text: 'Please enter a unit price greater than 0.', background: '#161b22', color: '#e6edf3' });
            return;
        }

        // Use a unique key based on name to allow multiple entries
        const customId = 'custom_' + Date.now();
        cart[customId] = {
            id: customId,
            name,
            price: 0,          // no floor price for custom items
            qty,
            maxStock: 99999,   // unlimited stock
            negotiatedPrice: price,
            isCustom: true,
        };

        // Reset fields
        nameEl.value  = '';
        qtyEl.value   = 1;
        priceEl.value = '';

        renderCart();
    }

    let _clickedSubmitBtn = null;
    document.getElementById('checkoutBtn').addEventListener('click', function() { _clickedSubmitBtn = 'checkout'; });
    document.getElementById('proformaBtn').addEventListener('click', function() { _clickedSubmitBtn = 'proforma'; });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const checkoutBtn  = document.getElementById('checkoutBtn');
        const proformaBtn  = document.getElementById('proformaBtn');

        // 1. Validate prices are greater than 0
        for (const id of Object.keys(cart)) {
            if (cart[id].negotiatedPrice <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Price',
                    text: `Please enter a valid price greater than 0 for ${cart[id].name}.`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
            }
        }

        // 2. Validate stock for completed sales
        if (_clickedSubmitBtn === 'checkout') {
            for (const id of Object.keys(cart)) {
                // If it is a mock item or if qty exceeds available stock
                if (String(id).startsWith('item_') || cart[id].qty > cart[id].maxStock) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Insufficient Stock',
                        text: `Not enough stock to complete the sale for ${cart[id].name}. Available: ${cart[id].maxStock}`,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    return;
                }
                
                // Validate negotiable price floor for completed sales
                if (cart[id].negotiatedPrice < cart[id].price) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Price Floor Violation',
                        text: `Price for ${cart[id].name} cannot be less than dedicated selling price TZS ${cart[id].price.toLocaleString()}.`,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    return;
                }
            }
        }

        if (_clickedSubmitBtn === 'proforma') {
            proformaBtn.disabled  = true;
            checkoutBtn.disabled  = true;
            proformaBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving Proforma...`;
        } else {
            checkoutBtn.disabled  = true;
            proformaBtn.disabled  = true;
            checkoutBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Completing Sale...`;
        }
    });
</script>
@endpush