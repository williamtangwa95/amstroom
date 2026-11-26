@extends('layouts.app')
@section('title', 'Finished Stocks')
@section('page-title', 'Finished Stocks')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shop-stock.index') }}">Shop Stock</a></li>
<li class="breadcrumb-item active">Finished Stocks</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       FINISHED STOCKS — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .fs-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .fs-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .fs-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .fs-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(220, 38, 38, .08);
        color: #dc2626;
        font-size: .72rem;
        font-weight: 600;
    }
    .fs-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    /* ── Executive KPI Grid (Matches /stock-reorder) ── */
    .fs-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .fs-kpi-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .fs-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .fs-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .fs-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .fs-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .fs-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fs-kpi-val {
        font-size: clamp(1rem, 1.4vw, 1.28rem);
        font-weight: 800;
        line-height: 1.18;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fs-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Filter & Table Cards ── */
    .fs-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }
    .fs-filter-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary, #64748b);
        margin-bottom: .3rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }

    #finishedStockTable .bi {
        font-size: 0.78rem !important;
    }
    #finishedStockTable .btn .bi {
        font-size: 0.72rem !important;
    }
    #finishedStockTable .btn,
    #finishedStockTable .btn-xs {
        padding: 4px 8px !important;
        font-size: 0.76rem !important;
        line-height: 1.3 !important;
    }

    @media (max-width: 991.98px) {
        .fs-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .fs-kpi-action {
            grid-column: 1 / -1;
        }
    }
    @media (max-width: 575.98px) {
        .fs-controls {
            width: 100%;
        }
        .fs-controls .btn {
            flex: 1;
            justify-content: center;
        }
        .fs-kpi-grid {
            grid-template-columns: 1fr;
        }
        .fs-kpi-card {
            padding: .85rem .9rem;
            gap: .7rem;
            border-radius: 12px;
        }
        .fs-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $activeShopLabel = auth()->user()->isOwner()
        ? ($shopId ? ($shops->firstWhere('id', $shopId)?->shop_name ?? 'Selected Shop') : 'All Shops')
        : (auth()->user()->shop?->shop_name ?? 'My Branch');
@endphp

{{-- Compact Header --}}
<div class="fs-header">
    <div class="fs-title-row">
        <h5 class="fs-title">
            <i class="bi bi-archive-fill text-danger me-1"></i>Finished Stocks
        </h5>
        <span class="fs-pill">
            <i class="bi bi-exclamation-circle"></i>Out of Stock
        </span>
    </div>

    <div class="fs-controls">
        <a href="{{ route('reorder.index', $shopId ? ['shop_id' => $shopId] : []) }}" class="btn btn-sm btn-accent shadow-sm" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-magic"></i> Reorder
        </a>
        <a href="{{ route('shop-stock.index') }}" class="btn btn-sm btn-outline-custom" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
            <i class="bi bi-arrow-left"></i> Shop Stocks
        </a>
    </div>
</div>

{{-- Executive KPI Cards --}}
<div class="fs-kpi-grid">
    <div class="fs-kpi-card">
        <div class="fs-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-archive-fill"></i>
        </div>
        <div class="fs-kpi-body">
            <p class="fs-kpi-label">Finished Products</p>
            <div class="fs-kpi-val" style="color:#dc2626;">{{ number_format($finishedCount) }}</div>
            <div class="fs-kpi-sub">0 units remaining</div>
        </div>
    </div>

    <div class="fs-kpi-card">
        <div class="fs-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-shop"></i>
        </div>
        <div class="fs-kpi-body">
            <p class="fs-kpi-label">Branch Scope</p>
            <div class="fs-kpi-val" style="color:#0077b6;">{{ $activeShopLabel }}</div>
            <div class="fs-kpi-sub">Currently filtered view</div>
        </div>
    </div>

    <a href="{{ route('reorder.index', $shopId ? ['shop_id' => $shopId] : []) }}" class="fs-kpi-link fs-kpi-action">
        <div class="fs-kpi-card">
            <div class="fs-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="fs-kpi-body">
                <p class="fs-kpi-label">Replenishment</p>
                <div class="fs-kpi-val" style="color:#059669;">Smart Reorder</div>
                <div class="fs-kpi-sub">Order or transfer stock &rarr;</div>
            </div>
        </div>
    </a>
</div>

{{-- Filter Bar --}}
<div class="fs-card mb-3">
    <div class="p-3">
        <div class="row g-2 align-items-end">
            @if(auth()->user()->isOwner())
            <div class="col-12 col-sm-6 col-md-3">
                <label for="filterShop" class="fs-filter-label">
                    <i class="bi bi-shop text-accent"></i>Shop
                </label>
                <form method="GET" action="{{ route('shop-stock.finished') }}" id="shopFilterForm">
                    <select name="shop_id" id="filterShop" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Shops</option>
                        @foreach($shops as $s)
                        <option value="{{ $s->id }}" {{ $shopId == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif
            <div class="col-12 col-sm-6 {{ auth()->user()->isOwner() ? 'col-md-3' : 'col-md-4' }}">
                <label for="filterCategory" class="fs-filter-label">
                    <i class="bi bi-tag text-accent"></i>Category
                </label>
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 {{ auth()->user()->isOwner() ? 'col-md-4' : 'col-md-5' }}">
                <label for="filterProduct" class="fs-filter-label">
                    <i class="bi bi-box-seam text-accent"></i>Product
                </label>
                <select id="filterProduct" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" data-category-id="{{ $it->category_id }}">{{ $it->item_name }}{{ $it->brand ? ' (' . $it->brand . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 {{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-3' }}">
                <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-custom w-100" style="height:31px;" title="Reset Filters">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<div class="fs-card">
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="finishedStockTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="3" class="min-tablet-l">Shop</th>
                    <th data-priority="1" class="all primary-col">Product</th>
                    <th data-priority="3" class="min-tablet-l">Category</th>
                    <th data-priority="4" class="min-tablet-l">Initial Qty</th>
                    <th data-priority="2" class="min-tablet-l">Remaining Qty</th>
                    <th data-priority="3" class="min-tablet-l">Average Sales</th>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <th data-priority="4" class="min-tablet-l">Buying Price</th>
                    @endif
                    <th data-priority="2" class="min-tablet-l">Selling Price</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Restock Modal -->
<div class="modal fade" id="quickRestockModal" tabindex="-1" aria-labelledby="quickRestockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="quickRestockModalLabel"><i class="bi bi-plus-circle text-success me-2"></i>Quick Restock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('shop-stock.quick-restock') }}" method="POST" id="quickRestockForm">
                @csrf
                <input type="hidden" name="shop_id" id="restockShopId">
                <input type="hidden" name="item_id" id="restockItemId">
                <input type="hidden" name="buying_price" id="restockBuyingPrice">
                <input type="hidden" name="selling_price" id="restockSellingPrice">
                <input type="hidden" name="low_stock_alert" id="restockLowStockAlert">
                <input type="hidden" name="is_admin_stock" id="restockIsAdminStock">

                <div class="modal-body">
                    <div class="p-3 mb-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0;">
                        <div class="fw-bold fs-6" style="color:var(--text-primary);" id="restockItemName">Product Name</div>
                        <small class="text-secondary" id="restockDetailsHelp">Add stock back into active inventory</small>
                    </div>

                    @if(auth()->user()->isOwner())
                    <div class="mb-3 p-2 rounded" style="background:rgba(2, 132, 199, 0.08); border:1px solid rgba(2, 132, 199, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-building me-1"></i> Available in Main Warehouse:</span>
                            <span class="fw-bold text-accent" id="restockWarehouseAvailable">Loading...</span>
                        </div>
                        <div id="restockWarehouseWarning" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Quantity exceeds available stock in Main Warehouse!
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="restock_quantity" class="form-label">Quantity to Add *</label>
                        <input type="number" name="quantity" id="restock_quantity" class="form-control" min="1" required autocomplete="off" placeholder="e.g. 10">
                    </div>

                    <div class="mb-3">
                        <label for="restock_date_received" class="form-label">Date Received *</label>
                        <input type="date" name="date_received" id="restock_date_received" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="restockSubmitBtn"><i class="bi bi-plus-lg me-1"></i> Add Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        const columns = [
            { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
            { data: 'shop', name: 'shop' },
            { data: 'product', name: 'product' },
            { data: 'category', name: 'category' },
            { data: 'initial_qty', name: 'initial_qty' },
            { data: 'remaining_qty', name: 'remaining_qty' },
            { data: 'average_sales', name: 'average_sales', orderable: false, searchable: false },
            @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
            { data: 'buying_price', name: 'buying_price' },
            @endif
            { data: 'selling_price', name: 'selling_price' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ];

        const table = $('#finishedStockTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('shop-stock.finished-data') }}",
                data: function(d) {
                    d.shop_id = $('select[name="shop_id"]').val();
                    d.category_id = $('#filterCategory').val();
                    d.item_id = $('#filterProduct').val();
                }
            },
            columns: columns,
            order: [[2, 'asc']]
        });

        // ── Category & Product Filter Cascading ──────────────────────────
        const allFinishedProductOptions = $('#filterProduct option').clone();

        function refreshFinishedProductDropdown(categoryId, preserveSelectedId = null) {
            const $prod = $('#filterProduct');
            const wasSelect2 = $prod.hasClass('select2-hidden-accessible');
            if (wasSelect2) {
                $prod.select2('destroy');
            }

            $prod.empty();
            allFinishedProductOptions.each(function() {
                const optCatId = $(this).data('category-id');
                const optVal = $(this).val();
                if (!categoryId || optVal === '' || optCatId == categoryId) {
                    $prod.append($(this).clone());
                }
            });

            if (preserveSelectedId && $prod.find('option[value="' + preserveSelectedId + '"]').length > 0) {
                $prod.val(preserveSelectedId);
            } else {
                $prod.val('');
            }

            if (wasSelect2 && window.initSearchableSelect) {
                window.initSearchableSelect($prod);
            }
        }

        $('#filterCategory').on('change', function() {
            const selectedCatId = $(this).val();
            const currentProdId = $('#filterProduct').val();
            refreshFinishedProductDropdown(selectedCatId, currentProdId);
            table.ajax.reload();
        });

        $('#filterProduct').on('change', function() {
            const selectedProdId = $(this).val();
            if (selectedProdId) {
                const catId = $(this).find('option:selected').data('category-id');
                if (catId && !$('#filterCategory').val()) {
                    $('#filterCategory').val(catId);
                    if ($('#filterCategory').hasClass('select2-hidden-accessible')) {
                        $('#filterCategory').trigger('change.select2');
                    }
                    refreshFinishedProductDropdown(catId, selectedProdId);
                }
            }
            table.ajax.reload();
        });

        $('#btnResetFilters').on('click', function() {
            $('#filterCategory').val('');
            if ($('#filterCategory').hasClass('select2-hidden-accessible')) {
                $('#filterCategory').trigger('change.select2');
            }
            refreshFinishedProductDropdown('');
            table.ajax.reload();
        });

        // Quick Restock Modal Handler
        $('#finishedStockTable tbody').on('click', '.btn-quick-restock', function() {
            const shopId = $(this).data('shop-id');
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');
            const buyingPrice = $(this).data('buying-price');
            const sellingPrice = $(this).data('selling-price');
            const lowStockAlert = $(this).data('low-stock-alert');
            const isAdminStock = $(this).data('is-admin-stock');

            $('#restockShopId').val(shopId);
            $('#restockItemId').val(itemId);
            $('#restockItemName').text(itemName);
            $('#restockBuyingPrice').val(buyingPrice);
            $('#restockSellingPrice').val(sellingPrice);
            $('#restockLowStockAlert').val(lowStockAlert);
            $('#restockIsAdminStock').val(isAdminStock);

            $('#restock_quantity').val('').removeClass('is-invalid');
            $('#restockWarehouseWarning').addClass('d-none');
            $('#restockSubmitBtn').prop('disabled', false);

            @if(auth()->user()->isOwner())
            $('#restockWarehouseAvailable').text('Loading...');
            $.get('{{ route("shop-stock.warehouse-available") }}', { item_id: itemId })
                .done(function(res) {
                    const available = parseInt(res.available || 0);
                    $('#restockWarehouseAvailable').text(available.toLocaleString());
                    $('#restockWarehouseAvailable').data('available', available);
                })
                .fail(function() {
                    $('#restockWarehouseAvailable').text('N/A');
                });
            @endif

            new bootstrap.Modal(document.getElementById('quickRestockModal')).show();
        });
    });
</script>
@endpush
@endsection
