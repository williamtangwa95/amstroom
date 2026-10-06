@extends('layouts.app')
@section('title', 'Point of Sale')
@section('page-title', 'New Sale (POS)')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">New Sale</li>
@endsection
@section('content')
<style>
    .pos-product-card {
        background: var(--card-bg, #161b22);
        border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .pos-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
        border-color: rgba(63, 185, 80, 0.35) !important;
    }
    .pos-product-img {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
        border: 1px solid var(--card-border, rgba(255, 255, 255, 0.1));
        transition: transform 0.2s ease;
    }
    .pos-product-img:hover {
        transform: scale(1.05);
    }
    .cart-item-row {
        background: var(--input-bg, rgba(255, 255, 255, 0.02));
        border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
        border-radius: 10px;
        padding: 0.75rem;
        transition: background 0.2s ease;
    }
    .cart-item-row:hover {
        background: rgba(255, 255, 255, 0.04);
    }
    .cart-qty-btn {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .cart-qty-input {
        width: 44px;
        height: 30px;
        text-align: center;
        font-weight: 600;
        font-size: 0.82rem;
        border-radius: 6px;
        padding: 0;
    }
    .pos-item-card {
        margin-bottom: 0.75rem;
    }
    .category-badge-pill {
        background: linear-gradient(135deg, #0088cc, #006699) !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.68rem !important;
        padding: 2.5px 8px !important;
        border-radius: 12px !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: 0 2px 5px rgba(0, 136, 204, 0.3) !important;
        white-space: nowrap !important;
        line-height: 1.2 !important;
        letter-spacing: 0.3px !important;
    }
    .category-filter-btn {
        font-size: 0.74rem !important;
        font-weight: 600 !important;
        padding: 3.5px 12px !important;
        border-radius: 20px !important;
        border: 1px solid var(--card-border) !important;
        background: var(--input-bg, rgba(255, 255, 255, 0.05)) !important;
        color: var(--text-secondary) !important;
        white-space: nowrap !important;
        transition: all 0.2s ease !important;
    }
    .category-filter-btn:hover {
        color: #0088cc !important;
        border-color: #0088cc !important;
        background: rgba(0, 136, 204, 0.1) !important;
    }
    .category-filter-btn.active {
        background: #0088cc !important;
        color: #ffffff !important;
        border-color: #0088cc !important;
        box-shadow: 0 2px 6px rgba(0, 136, 204, 0.35) !important;
    }
    @media (max-width: 575.98px) {
        .pos-header-actions {
            width: 100%;
        }
        .pos-search-input {
            max-width: 100% !important;
            width: 100% !important;
        }
        .cart-item-controls {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }
        .cart-item-price-group {
            width: 100% !important;
            justify-content: space-between;
        }
    }
</style>
<div class="row g-3 mb-5 pb-5">
    {{-- Left: Available Shop Products --}}
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm border-0" style="background:var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="card-header py-2.5 px-3" style="border-bottom: 1px solid var(--card-border); background: rgba(255,255,255,0.01);">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="fw-700 text-uppercase tracking-wider" style="font-size:.85rem; color:var(--text-primary);">
                        <i class="bi bi-box-seam-fill me-2" style="color:#3fb950;"></i>Available Inventory
                    </span>
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1 justify-content-end pos-header-actions" style="min-width:0;">
                        <div class="form-check form-switch mb-0 flex-shrink-0">
                            <input class="form-check-input" type="checkbox" id="showAllProductsToggle" style="cursor:pointer;">
                            <label class="form-check-label small fw-600 text-nowrap" for="showAllProductsToggle" style="color:var(--text-secondary);cursor:pointer;user-select:none;">Show Out of Stock</label>
                        </div>
                        <div class="position-relative flex-grow-1 pos-search-input" style="max-width:220px;">
                            <input type="text" id="posSearch" class="form-control form-control-sm ps-4" placeholder="Search name/brand..." style="border-radius: 8px;">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Category Quick Filter Pills Bar --}}
            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-1.5 overflow-x-auto pos-category-bar" style="border-color:var(--card-border)!important; background:rgba(0,0,0,0.03); scrollbar-width:thin;">
                <button type="button" class="btn btn-xs category-filter-btn active" data-category="all"><i class="bi bi-grid-fill me-1"></i>All</button>
                @php
                    $uniqueCategories = $shopStocks->map(fn($s) => $s->item?->category?->category_name)->filter()->unique()->values();
                @endphp
                @foreach($uniqueCategories as $catName)
                    <button type="button" class="btn btn-xs category-filter-btn" data-category="{{ strtolower($catName) }}">{{ $catName }}</button>
                @endforeach
            </div>

            <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
                <div class="row g-3 p-3 pb-5" id="posProductGrid">
                    @forelse($shopStocks as $stock)
                    @php
                    $pendingPrice = $stock->is_price_pending ? $stock->pending_selling_price : null;
                    $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
                    $isLocked = !auth()->user()->isOwner() && $isIndependent && !$stock->is_sellable;
                    $hasStock = $stock->remaining_quantity > 0;
                    $isMock = str_starts_with($stock->id, 'item_');
                    $categoryName = $stock->item?->category?->category_name ?? 'General';
                    @endphp
                    <div class="col-12 col-sm-6 pos-item-card"
                        data-name="{{ strtolower($stock->item->item_name) }}"
                        data-brand="{{ strtolower($stock->item->brand) }}"
                        data-category="{{ strtolower($categoryName) }}"
                        data-available="{{ ($hasStock && !$isLocked && !$isMock) ? 'true' : 'false' }}">
                        <div class="pos-product-card p-3 h-100 d-flex flex-column justify-content-between" style="opacity: {{ ($hasStock && !$isLocked && !$isMock) ? '1' : '.65' }};">
                            <div class="d-flex align-items-start gap-2.5">
                                @if($stock->item->image_path)
                                <img src="{{ asset('media/' . $stock->item->image_path) }}"
                                    alt="{{ $stock->item->item_name }}"
                                    class="pos-product-img img-lightbox"
                                    onclick="openLightbox(this.src, '{{ addslashes($stock->item->item_name) }}')"
                                    title="Click to enlarge">
                                @else
                                <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted border pos-product-img">
                                    <i class="bi bi-image" style="font-size: 1.2rem;"></i>
                                </div>
                                @endif
                                <div style="min-width:0;" class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                        <span class="category-badge-pill"><i class="bi bi-tag-fill me-1" style="font-size:0.6rem;"></i>{{ $categoryName }}</span>
                                        @if(isset($stock->is_admin_stock) && $stock->is_admin_stock)
                                        <span class="badge bg-info text-dark" style="font-size:.62rem;font-weight:600;"><i class="bi bi-person-fill-lock"></i> Admin</span>
                                        @endif
                                        @if($isLocked)
                                        <span class="badge bg-danger" style="font-size:.62rem;"><i class="bi bi-lock-fill"></i> Locked</span>
                                        @elseif($isMock)
                                        <span class="badge bg-secondary" style="font-size:.62rem;">Catalog</span>
                                        @elseif(!$hasStock)
                                        <span class="badge bg-warning text-dark" style="font-size:.62rem;">Out of Stock</span>
                                        @endif
                                    </div>
                                    <div class="fw-700 text-truncate" style="font-size:.88rem;color:var(--text-primary);" title="{{ $stock->item->item_name }}">{{ $stock->item->item_name }}</div>
                                    <div class="text-truncate" style="font-size:.74rem;color:var(--text-secondary);" title="{{ $stock->item->specification }}">{{ $stock->item->specification ?: 'No specification' }}</div>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between" style="border-color:var(--card-border) !important;">
                                <div>
                                    <div class="fw-800" style="color:#3fb950;font-size:.95rem;">TZS {{ number_format($stock->selling_price, 0) }}</div>
                                    <div style="font-size:.7rem;color:{{ ($stock->isLowStock() && !$isMock) ? '#e94560' : 'var(--text-secondary)' }};">
                                        In Stock: <strong>{{ $stock->remaining_quantity }}</strong>
                                    </div>
                                </div>
                                @if($isLocked)
                                <button type="button" class="btn btn-sm btn-secondary add-to-cart-btn px-2.5 py-1" disabled data-is-sellable="false" style="font-size:.78rem;">
                                    <i class="bi bi-lock-fill"></i> Locked
                                </button>
                                @else
                                <button type="button" class="btn btn-sm {{ ($hasStock && !$isMock) ? 'btn-accent' : 'btn-outline-secondary' }} add-to-cart-btn px-2.5 py-1"
                                    data-id="{{ $stock->id }}"
                                    data-name="{{ $stock->item->item_name }}"
                                    data-price="{{ $stock->selling_price }}"
                                    data-buying-price="{{ $stock->buying_price }}"
                                    data-stock="{{ $stock->remaining_quantity }}"
                                    data-price-pending="{{ $pendingPrice ? 'true' : 'false' }}"
                                    data-pending-price="{{ $pendingPrice ?? 0 }}"
                                    data-is-sellable="true"
                                    data-is-admin-stock="{{ (isset($stock->is_admin_stock) && $stock->is_admin_stock) ? 'true' : 'false' }}"
                                    data-allow-components="{{ $stock->allow_components ? 'true' : 'false' }}"
                                    data-components="{{ json_encode($stock->item->components->map(fn($c) => ['item_id' => $c->component_item_id, 'item_name' => $c->childItem->item_name, 'quantity' => $c->quantity])) }}" style="font-size:.78rem; border-radius: 6px;">
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
        <div class="card h-100 shadow-sm border-0" style="background:var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="card-header py-2.5 px-3 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid var(--card-border); background: rgba(255,255,255,0.01);">
                <span class="fw-700 text-uppercase tracking-wider" style="font-size:.85rem; color:var(--text-primary);"><i class="bi bi-cart-check-fill me-2" style="color:#e94560;"></i>Shopping Cart</span>
                <input type="date" name="sale_date" class="form-control form-control-sm" style="width:auto; border-radius: 6px;" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" form="checkoutForm">
            </div>
            <div class="card-body p-3 d-flex flex-column">
                <form method="POST" action="{{ route('sales.store') }}" id="checkoutForm" class="flex-grow-1 d-flex flex-column">
                    @csrf
                    <input type="hidden" name="idempotency_key" id="idempotencyKeyInput" value="{{ \Illuminate\Support\Str::uuid() }}">

                    <div id="cartItemsList" class="flex-grow-1 mb-3" style="max-height:360px;overflow-y:auto;">
                        <div class="text-center py-5 text-muted" id="emptyCartMsg">
                            <i class="bi bi-cart-x fs-2 d-block mb-1" style="opacity:.6;"></i>
                            Cart is empty. Select products from the left.
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-auto" style="border-color:var(--card-border) !important;">
                        {{-- Total Amount Summary Card --}}
                        <div class="p-3 rounded mb-3" style="background: linear-gradient(135deg, rgba(63,185,80,0.12), rgba(46,160,67,0.04)); border: 1px solid rgba(63,185,80,0.25);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-700 text-uppercase tracking-wide" style="font-size:.82rem; color:var(--text-secondary);">Total Amount:</span>
                                    <div class="small text-muted" style="font-size:.7rem;" id="cartItemCountDisplay">0 items</div>
                                </div>
                                <span class="fw-800" style="font-size:1.4rem;color:#3fb950;" id="cartTotalDisplay">TZS 0</span>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label mb-1 small fw-600" style="font-size:.78rem;">Customer Name (Optional)</label>
                                <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Walk-in Customer" style="border-radius:6px;">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label mb-1 small fw-600" style="font-size:.78rem;">Payment Method *</label>
                                <select name="payment_method" class="form-select form-select-sm" required style="border-radius:6px;">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="mobile_money">Mobile Money (M-Pesa / Tigo Pesa)</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        {{-- Billing & Delivery Details Collapsible --}}
                        <div class="mb-2">
                            <a class="d-flex align-items-center gap-2 text-decoration-none fw-600 py-1.5 px-2 rounded" style="font-size:.8rem;color:var(--accent); background: rgba(57,178,255,0.06); border: 1px solid rgba(57,178,255,0.15);" data-bs-toggle="collapse" href="#billingDetailsPanel" role="button">
                                <i class="bi bi-file-earmark-text"></i> + Add Billing & Delivery Details (for Invoice/Proforma)
                            </a>
                            <div class="collapse mt-2" id="billingDetailsPanel">
                                <div class="rounded p-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Customer ID</label>
                                            <input type="text" name="customer_id" class="form-control form-control-sm" placeholder="e.g. AD-0025">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Customer P.O. Box</label>
                                            <input type="text" name="customer_po_box" class="form-control form-control-sm" placeholder="e.g. 6858 Morogoro">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Deliver To</label>
                                            <input type="text" name="deliver_to" class="form-control form-control-sm" placeholder="e.g. CHAMWINO STUDENT CENTER">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Delivery Date</label>
                                            <input type="date" name="delivery_date" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Delivery Time</label>
                                            <input type="time" name="delivery_time" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Validity Date</label>
                                            <input type="date" name="validity_date" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Terms of Payment</label>
                                            <input type="text" name="terms_of_payment" class="form-control form-control-sm" placeholder="e.g. 30 Days Net">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Custom Off-Catalog Item Entry --}}
                        <div class="mb-3">
                            <a class="d-flex align-items-center gap-2 text-decoration-none fw-600 py-1.5 px-2 rounded" style="font-size:.8rem;color:#e3b341; background: rgba(227,179,65,0.06); border: 1px solid rgba(227,179,65,0.2);" data-bs-toggle="collapse" href="#customItemPanel" role="button">
                                <i class="bi bi-plus-circle-dotted"></i> + Add Custom Item (Proforma Only)
                            </a>
                            <div class="collapse mt-2" id="customItemPanel">
                                <div class="rounded p-3" style="background:var(--input-bg);border:1px dashed #e3b341;">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Product / Service Name *</label>
                                            <input type="text" id="customItemName" class="form-control form-control-sm" placeholder="e.g. Laptop HP Elitebook 840">
                                        </div>
                                        <div class="col-12 col-sm-5">
                                            <label class="form-label mb-0" style="font-size:.75rem;">Qty *</label>
                                            <input type="number" id="customItemQty" class="form-control form-control-sm" value="1" min="1">
                                        </div>
                                        <div class="col-12 col-sm-7">
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
                            <button type="submit" class="btn btn-accent w-100 py-2.5 fw-700 shadow-sm" id="checkoutBtn" disabled
                                onclick="document.getElementById('saleStatusInput').value='completed'" style="border-radius: 8px; font-size: 0.92rem;">
                                <i class="bi bi-check2-circle me-1"></i> Complete Sale
                            </button>
                            <button type="submit" class="btn btn-outline-custom w-100 py-2 fw-600" id="proformaBtn" disabled
                                onclick="document.getElementById('saleStatusInput').value='draft_proforma'" style="border-radius: 8px; font-size: 0.85rem;">
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

    let selectedCategory = 'all';

    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedCategory = this.dataset.category || 'all';
            filterProducts();
        });
    });

    // Product Filter (Combined search, category & toggle)
    function filterProducts() {
        const term = document.getElementById('posSearch').value.toLowerCase();
        const showAll = document.getElementById('showAllProductsToggle').checked;

        document.querySelectorAll('.pos-item-card').forEach(card => {
            const name = card.dataset.name || '';
            const brand = card.dataset.brand || '';
            const category = card.dataset.category || '';
            const available = card.dataset.available === 'true';

            const matchesSearch = name.includes(term) || brand.includes(term) || category.includes(term);
            const matchesAvailability = showAll || available;
            const matchesCategory = (selectedCategory === 'all' || category === selectedCategory);

            if (matchesSearch && matchesAvailability && matchesCategory) {
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
            const buyingPrice = parseFloat(this.dataset.buyingPrice) || 0;
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

            const isAdminStock = this.dataset.isAdminStock === 'true';

            let components = [];
            if (this.dataset.components) {
                try {
                    components = JSON.parse(this.dataset.components);
                } catch (e) {
                    console.error("Error parsing components", e);
                }
            }

            const allowComponents = this.dataset.allowComponents === 'true';

            if (cart[id]) {
                if (cart[id].qty >= cart[id].maxStock && !cart[id].isCustom && !cart[id].isMock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Stock Reached',
                        text: `Only ${cart[id].maxStock} units available in stock for ${cart[id].name}.`,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    return;
                }
                cart[id].qty++;
            } else {
                const isMock = String(id).startsWith('item_');
                cart[id] = {
                    id,
                    name,
                    price,
                    buyingPrice,
                    qty: 1,
                    maxStock,
                    isMock: isMock,
                    isCustom: false,
                    negotiatedPrice: price,
                    isAdminStock: isAdminStock,
                    components: components,
                    allowComponents: allowComponents
                };
            }
            renderCart();
        });
    });

    function renderCart() {
        const list = document.getElementById('cartItemsList');
        const keys = Object.keys(cart);
        const isOwnerOrAdmin = {{ (auth()->user()->isOwner() || auth()->user()->isShopAdmin()) ? 'true' : 'false' }};
        const countDisplay = document.getElementById('cartItemCountDisplay');

        if (keys.length === 0) {
            list.innerHTML = `<div class="text-center py-5 text-muted" id="emptyCartMsg"><i class="bi bi-cart-x fs-2 d-block mb-1" style="opacity:.6;"></i>Cart is empty. Select products from the left.</div>`;
            document.getElementById('cartTotalDisplay').textContent = 'TZS 0';
            if (countDisplay) countDisplay.textContent = '0 items';
            document.getElementById('checkoutBtn').disabled = true;
            document.getElementById('proformaBtn').disabled = true;
            return;
        }

        let html = '';
        let total = 0;
        let totalItemsCount = 0;
        let index = 0;

        keys.forEach(id => {
            const item = cart[id];
            const subtotal = item.qty * item.negotiatedPrice;
            total += subtotal;
            totalItemsCount += item.qty;

            let compHtml = '';
            if (item.components && item.components.length > 0) {
                compHtml += `<div class="mt-2 pt-1 border-top" style="font-size:.72rem; border-color:var(--card-border) !important; padding-left:10px;">`;
                item.components.forEach((comp, cIdx) => {
                    compHtml += `
                    <div class="d-flex align-items-center justify-content-between mb-1 text-muted" style="font-size:.72rem;">
                        <input type="hidden" name="items[${index}][components][${cIdx}][item_id]" value="${comp.item_id}">
                        <span class="text-truncate" style="max-width:180px;"><span class="text-muted">└─</span> ${comp.item_name}</span>
                        <div class="d-flex align-items-center gap-1">
                            ${item.allowComponents ? `
                                <button type="button" class="btn btn-xs py-0 px-1 btn-outline-secondary" onclick="changeComponentQty('${id}', ${cIdx}, -1)">-</button>
                            ` : ''}
                            <input type="number" name="items[${index}][components][${cIdx}][quantity]" value="${comp.quantity}" readonly class="form-control form-control-sm py-0 px-1" style="width:30px; text-align:center; font-size:.65rem; height:18px;">
                            ${item.allowComponents ? `
                                <button type="button" class="btn btn-xs py-0 px-1 btn-outline-secondary" onclick="changeComponentQty('${id}', ${cIdx}, 1)">+</button>
                                <button type="button" class="btn btn-xs text-danger py-0 px-1 ms-1" onclick="removeComponentFromCartItem('${id}', ${cIdx})"><i class="bi bi-trash" style="font-size:.7rem;"></i></button>
                            ` : ''}
                        </div>
                    </div>
                    `;
                });
                compHtml += `</div>`;
            }

            if (!item.isCustom && item.allowComponents) {
                compHtml += `
                <div class="mt-2" style="padding-left:10px; max-width:400px;">
                    <button type="button" class="btn btn-link text-decoration-none p-0 text-accent fw-600" style="font-size:.72rem;" onclick="showAddComponentDropdown('${id}')">
                        <i class="bi bi-plus-circle-fill"></i> Add component
                    </button>
                    <div id="comp-select-container-${id}" class="d-none mt-2 p-2 rounded" style="background: var(--body-bg); border: 1px solid var(--card-border);">
                        <label class="form-label mb-1" style="font-size:.7rem; font-weight:600; color:var(--text-secondary);">Select Component to Add</label>
                        <div class="mb-2">
                            <select id="comp-select-${id}" class="form-select form-select-sm" style="width:100%;">
                                <option value="" disabled selected>Search / select item...</option>
                                ${window.availableComponentsOptions || ''}
                            </select>
                        </div>
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-1" onclick="hideAddComponentDropdown('${id}')" style="font-size:.7rem;">Cancel</button>
                            <button type="button" class="btn btn-xs btn-accent px-2 py-1" onclick="addComponentToCartItem('${id}')" style="font-size:.7rem;">Add to Bundle</button>
                        </div>
                    </div>
                </div>
                `;
            }

            const displayMinPrice = isOwnerOrAdmin ? (item.buyingPrice || 0) : (item.price || 0);

            html += `
            <div class="cart-item-row mb-2">
                <input type="hidden" name="items[${index}][shop_stock_id]" value="${item.id}">
                ${item.isCustom ? `<input type="hidden" name="items[${index}][custom_name]" value="${item.name}">` : ''}
                
                <div class="d-flex align-items-start justify-content-between gap-2 mb-1.5">
                    <div style="flex:1;min-width:0;">
                        <div class="fw-700 text-truncate" style="font-size:.84rem; color:var(--text-primary);">${item.name} ${item.isCustom ? '<span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">CUSTOM</span>' : ''}</div>
                        <div style="font-size:.68rem;color:var(--text-secondary);">Min Price: <span class="fw-600 text-muted">TZS ${displayMinPrice.toLocaleString()}</span></div>
                    </div>
                    <button type="button" class="btn btn-xs text-danger p-0 ms-1 flex-shrink-0" onclick="removeItem('${id}')" title="Remove item" style="font-size:.85rem; background:transparent; border:none;">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 pt-1 border-top cart-item-controls" style="border-color:var(--card-border) !important;">
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-xs btn-outline-secondary cart-qty-btn" onclick="changeQty('${id}', -1)">-</button>
                        <input type="number" name="items[${index}][quantity]" value="${item.qty}" min="1" ${(!item.isCustom && !item.isMock) ? `max="${item.maxStock}"` : ''} onchange="updateItemQty('${id}', this.value)" class="form-control form-control-sm cart-qty-input">
                        <button type="button" class="btn btn-xs btn-outline-secondary cart-qty-btn" onclick="changeQty('${id}', 1)">+</button>
                    </div>

                    <div class="d-flex align-items-center gap-1 cart-item-price-group">
                        <span style="font-size:.7rem; color:var(--text-secondary);" class="fw-600">Price:</span>
                        <div class="input-group input-group-sm" style="width:115px;">
                            <input type="text" name="items[${index}][price]" value="${window.formatCurrencyValue ? window.formatCurrencyValue(String(item.negotiatedPrice)) : item.negotiatedPrice}" 
                                   class="form-control form-control-sm py-0 px-1 currency-input fw-600" min="0" 
                                   onchange="updateItemPrice('${id}', this.value)" required style="text-align:right; border-radius: 6px;">
                        </div>
                    </div>
                </div>
                ${compHtml}
            </div>
            `;
            index++;
        });

        list.innerHTML = html;
        document.getElementById('cartTotalDisplay').textContent = 'TZS ' + total.toLocaleString();
        if (countDisplay) countDisplay.textContent = totalItemsCount + ' item' + (totalItemsCount === 1 ? '' : 's');
        
        const isOffline = !navigator.onLine;
        const checkoutBtn = document.getElementById('checkoutBtn');
        const proformaBtn = document.getElementById('proformaBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = isOffline;
            if (isOffline) checkoutBtn.dataset.disabledByOffline = "true";
        }
        if (proformaBtn) {
            proformaBtn.disabled = isOffline;
            if (isOffline) proformaBtn.dataset.disabledByOffline = "true";
        }
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

    function updateItemQty(id, val) {
        if (cart[id]) {
            let intVal = parseInt(val);
            if (isNaN(intVal) || intVal <= 0) {
                delete cart[id];
            } else if (intVal > cart[id].maxStock && !cart[id].isCustom && !cart[id].isMock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Maximum Stock Reached',
                    text: `Cannot exceed available stock. Only ${cart[id].maxStock} units available for ${cart[id].name}.`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
                cart[id].qty = cart[id].maxStock;
            } else {
                cart[id].qty = intVal;
            }
            renderCart();
        }
    }

    function changeQty(id, delta) {
        if (cart[id]) {
            const newQty = cart[id].qty + delta;
            if (newQty <= 0) {
                delete cart[id];
            } else if (delta > 0 && newQty > cart[id].maxStock && !cart[id].isCustom && !cart[id].isMock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Maximum Stock Reached',
                    text: `Cannot add more. Only ${cart[id].maxStock} units available in stock for ${cart[id].name}.`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
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
        const nameEl = document.getElementById('customItemName');
        const qtyEl = document.getElementById('customItemQty');
        const priceEl = document.getElementById('customItemPrice');

        const name = nameEl.value.trim();
        const qty = parseInt(qtyEl.value) || 1;
        const price = parseFloat(priceEl.value) || 0;

        if (!name) {
            Swal.fire({
                icon: 'warning',
                title: 'Name Required',
                text: 'Please enter a product/service name.',
                background: '#161b22',
                color: '#e6edf3'
            });
            return;
        }
        if (price <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Price Required',
                text: 'Please enter a unit price greater than 0.',
                background: '#161b22',
                color: '#e6edf3'
            });
            return;
        }

        // Use a unique key based on name to allow multiple entries
        const customId = 'custom_' + Date.now();
        cart[customId] = {
            id: customId,
            name,
            price: 0, // no floor price for custom items
            qty,
            maxStock: 99999, // unlimited stock
            negotiatedPrice: price,
            isCustom: true,
        };

        // Reset fields
        nameEl.value = '';
        qtyEl.value = 1;
        priceEl.value = '';

        renderCart();
    }

    let _clickedSubmitBtn = null;
    document.getElementById('checkoutBtn').addEventListener('click', function() {
        _clickedSubmitBtn = 'checkout';
    });
    document.getElementById('proformaBtn').addEventListener('click', function() {
        _clickedSubmitBtn = 'proforma';
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const checkoutBtn = document.getElementById('checkoutBtn');
        const proformaBtn = document.getElementById('proformaBtn');

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
                const isOwnerOrAdmin = {{ (auth()->user()->isOwner() || auth()->user()->isShopAdmin()) ? 'true' : 'false' }};
                const minAllowedPrice = isOwnerOrAdmin ? (cart[id].buyingPrice || 0) : (cart[id].price || 0);
                if (cart[id].negotiatedPrice < minAllowedPrice) {
                    e.preventDefault();
                    const priceType = isOwnerOrAdmin ? 'buying' : 'dedicated selling';
                    Swal.fire({
                        icon: 'error',
                        title: 'Price Floor Violation',
                        text: `Price for ${cart[id].name} cannot be less than ${priceType} price TZS ${minAllowedPrice.toLocaleString()}.`,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    return;
                }
            }
        }

        if (_clickedSubmitBtn === 'proforma') {
            proformaBtn.disabled = true;
            checkoutBtn.disabled = true;
            proformaBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving Proforma...`;
        } else {
            checkoutBtn.disabled = true;
            proformaBtn.disabled = true;
            checkoutBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Completing Sale...`;
        }
    });

    const availableComponentsData = [
        @foreach($shopStocks as $ss)
            @if(!str_starts_with($ss->id, 'item_') && $ss->remaining_quantity > 0)
            {
                item_id: {{ $ss->item_id }},
                item_name: {!! json_encode($ss->item->item_name) !!},
                brand: {!! json_encode($ss->item->brand ?: '—') !!},
                stock: {{ $ss->remaining_quantity }},
                is_admin_stock: {{ (isset($ss->is_admin_stock) && $ss->is_admin_stock) ? 'true' : 'false' }}
            },
            @endif
        @endforeach
    ];

    function addComponentToCartItem(cartItemId) {
        const select = document.getElementById('comp-select-' + cartItemId);
        const selectedOpt = select.options[select.selectedIndex];
        if (!selectedOpt || select.value === '') return;

        const itemId = parseInt(select.value);
        const itemName = selectedOpt.dataset.name;

        if (!cart[cartItemId].components) {
            cart[cartItemId].components = [];
        }

        const existsIdx = cart[cartItemId].components.findIndex(c => c.item_id === itemId);
        if (existsIdx !== -1) {
            cart[cartItemId].components[existsIdx].quantity++;
        } else {
            cart[cartItemId].components.push({
                item_id: itemId,
                item_name: itemName,
                quantity: 1
            });
        }

        hideAddComponentDropdown(cartItemId);
        renderCart();
    }

    function showAddComponentDropdown(cartItemId) {
        const item = cart[cartItemId];
        const targetIsAdminStock = item ? !!item.isAdminStock : false;

        const select = document.getElementById('comp-select-' + cartItemId);
        if (select) {
            let optionsHtml = '<option value="" disabled selected>Search / select item...</option>';
            const filteredComponents = availableComponentsData.filter(c => c.is_admin_stock === targetIsAdminStock);

            if (filteredComponents.length === 0) {
                const stockLabel = targetIsAdminStock ? 'Admin Shop Stock' : 'Shop Stock (Main Store)';
                optionsHtml += `<option value="" disabled>No available components in ${stockLabel}</option>`;
            } else {
                filteredComponents.forEach(c => {
                    const labelType = c.is_admin_stock ? '(Admin Stock)' : '(Shop Stock)';
                    const escapedName = (c.item_name || '').replace(/"/g, '&quot;');
                    optionsHtml += `<option value="${c.item_id}" data-name="${escapedName}">${c.item_name} (${c.brand}) [Stock: ${c.stock}] ${labelType}</option>`;
                });
            }
            select.innerHTML = optionsHtml;
        }

        const container = document.getElementById('comp-select-container-' + cartItemId);
        container.classList.remove('d-none');

        // Initialize Select2 search select on the dropdown
        const $select = $('#comp-select-' + cartItemId);
        $select.select2({
            theme: 'bootstrap-5',
            dropdownParent: $(container),
            width: '100%'
        });
    }

    function hideAddComponentDropdown(cartItemId) {
        const container = document.getElementById('comp-select-container-' + cartItemId);
        container.classList.add('d-none');

        const $select = $('#comp-select-' + cartItemId);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.val('');
    }

    function changeComponentQty(cartItemId, compIndex, delta) {
        if (cart[cartItemId] && cart[cartItemId].components && cart[cartItemId].components[compIndex]) {
            const newQty = cart[cartItemId].components[compIndex].quantity + delta;
            if (newQty <= 0) {
                cart[cartItemId].components.splice(compIndex, 1);
            } else {
                cart[cartItemId].components[compIndex].quantity = newQty;
            }
            renderCart();
        }
    }

    function removeComponentFromCartItem(cartItemId, compIndex) {
        if (cart[cartItemId] && cart[cartItemId].components) {
            cart[cartItemId].components.splice(compIndex, 1);
            renderCart();
        }
    }
</script>
@endpush