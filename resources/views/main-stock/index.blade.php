@extends('layouts.app')
@section('title', 'Main Store Stock')
@section('page-title', 'Main Warehouse')
@section('breadcrumb')
<li class="breadcrumb-item active">Main Store Stock</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       MAIN STORE STOCK — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .ms-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .ms-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .ms-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .ms-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .18rem .6rem;
        border-radius: 50px;
        background: rgba(0, 119, 182, .08);
        color: #0077b6;
        font-size: .72rem;
        font-weight: 600;
    }
    .ms-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
    }
    .ms-actions .btn {
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        font-size: .78rem;
        font-weight: 600;
        padding: 0 .8rem;
    }

    /* ── Executive 5-Card KPI Grid (Matches /stock-reorder & /diary) ── */
    .ms-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1.1rem;
    }
    .ms-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .9rem .95rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .ms-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .ms-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .ms-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .ms-kpi-label {
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ms-kpi-val {
        font-size: clamp(.84rem, 1.05vw, 1.05rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.015em;
        word-break: break-word;
    }
    .ms-kpi-sub {
        font-size: .68rem;
        color: #94a3b8;
        margin-top: .16rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Filter & Table Cards ── */
    .ms-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }
    .ms-filter-label {
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

    @media (max-width: 1199.98px) {
        .ms-kpi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 767.98px) {
        .ms-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: .45rem;
        }
        .ms-actions .btn {
            width: 100%;
        }
        .ms-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
        .ms-kpi-batches {
            grid-column: 1 / -1;
        }
        .ms-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .ms-kpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            font-size: .95rem;
        }
        .ms-kpi-val {
            font-size: .9rem;
        }
    }
    @media (max-width: 420px) {
        .ms-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Executive Header --}}
<div class="ms-header">
    <div class="ms-title-row">
        <h5 class="ms-title">
            <i class="bi bi-building text-primary me-1"></i>Main Store Inventory
        </h5>
        <span class="ms-pill">
            <i class="bi bi-layers"></i>Central Warehouse
        </span>
    </div>

    <div class="ms-actions main-stock-header-actions">
        <a href="{{ route('main-stock.history') }}" class="btn btn-outline-custom">
            <i class="bi bi-clock-history"></i>History
        </a>
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadMainStockModal">
            <i class="bi bi-file-earmark-excel"></i>Upload
        </button>
        <button type="button" class="btn btn-accent shadow-sm" data-bs-toggle="modal" data-bs-target="#addMainStockModal">
            <i class="bi bi-plus-circle"></i>Add Stock
        </button>
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-danger alert-dismissible fade show mb-3 rounded-3" role="alert" style="font-size:.83rem;">
    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Import Failed! Please correct the following errors:</h6>
    <ul class="mb-0 ps-3 small" style="max-height: 200px; overflow-y: auto;">
        @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Executive KPI Cards --}}
<div class="ms-kpi-grid">
    <div class="ms-kpi-card">
        <div class="ms-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="ms-kpi-body">
            <p class="ms-kpi-label">Total Cost</p>
            <div class="ms-kpi-val" style="color:#0077b6;">TZS {{ number_format($stats['totalInitialCost'], 0) }}</div>
            <div class="ms-kpi-sub">{{ number_format($stats['totalInitialQty']) }} stocked units</div>
        </div>
    </div>

    <div class="ms-kpi-card">
        <div class="ms-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div class="ms-kpi-body">
            <p class="ms-kpi-label">Total Sell Value</p>
            <div class="ms-kpi-val" style="color:#059669;">TZS {{ number_format($stats['totalInitialSell'], 0) }}</div>
            <div class="ms-kpi-sub">Initial retail value</div>
        </div>
    </div>

    <div class="ms-kpi-card">
        <div class="ms-kpi-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="ms-kpi-body">
            <p class="ms-kpi-label">Remain Cost</p>
            <div class="ms-kpi-val" style="color:#7c3aed;">TZS {{ number_format($stats['totalRemainingCost'], 0) }}</div>
            <div class="ms-kpi-sub">{{ number_format($stats['totalRemainingQty']) }} units left</div>
        </div>
    </div>

    <div class="ms-kpi-card">
        <div class="ms-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-piggy-bank"></i>
        </div>
        <div class="ms-kpi-body">
            <p class="ms-kpi-label">Remain Sell Value</p>
            <div class="ms-kpi-val" style="color:#d97706;">TZS {{ number_format($stats['totalRemainingSell'], 0) }}</div>
            <div class="ms-kpi-sub">Remaining retail</div>
        </div>
    </div>

    <div class="ms-kpi-card ms-kpi-batches">
        <div class="ms-kpi-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <i class="bi bi-layers"></i>
        </div>
        <div class="ms-kpi-body">
            <p class="ms-kpi-label">Stock Batches</p>
            <div class="ms-kpi-val" style="color:#dc2626;">{{ number_format($stats['stockBatchesCount']) }}</div>
            <div class="ms-kpi-sub">Recorded batches</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="ms-card mb-3">
    <div class="p-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-4">
                <label for="filterCategory" class="ms-filter-label">
                    <i class="bi bi-tag text-accent"></i>Category
                </label>
                <select id="filterCategory" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-5">
                <label for="filterProduct" class="ms-filter-label">
                    <i class="bi bi-box-seam text-accent"></i>Product
                </label>
                <select id="filterProduct" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" data-category-id="{{ $it->category_id }}">{{ $it->item_name }}{{ $it->brand ? ' (' . $it->brand . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-custom w-100" style="height:31px;" title="Reset Filters">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 px-3 py-2 rounded-3 border d-none" id="bulkActionsBar" style="background: var(--card-bg) !important; border-color: var(--card-border) !important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-square text-accent fs-5"></i>
        <span class="fw-600 small" id="selectedCountText" style="color:var(--text-primary);">0 items selected</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-xs btn-accent px-3 py-1" id="bulkEnableBtn" style="font-size: .75rem;">Enable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-secondary px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDeleteBtn" style="font-size: .75rem;"><i class="bi bi-trash me-1"></i>Delete Selected Stock</button>
    </div>
</div>

<div class="ms-card">
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table dt-has-checkbox" id="mainStockTable">
            <thead>
                <tr>
                    <th data-priority="1000" class="min-tablet-l no-sort text-center" style="width: 30px;"><input type="checkbox" id="checkAllStocks" style="cursor:pointer;"></th>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Product Details</th>
                    <th data-priority="3" class="min-tablet-l">Category</th>
                    <th data-priority="3" class="min-tablet-l">Buy Price</th>
                    <th data-priority="3" class="min-tablet-l">Sell Price</th>
                    <th data-priority="3" class="min-tablet-l">Stocked Qty</th>
                    <th data-priority="3" class="min-tablet-l">Remaining Qty</th>
                    <th data-priority="4" class="min-tablet-l">Date</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Excel Modal -->
<div class="modal fade" id="uploadMainStockModal" tabindex="-1" aria-labelledby="uploadMainStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="uploadMainStockModalLabel"><i class="bi bi-file-earmark-excel text-accent me-2"></i>Upload Stock Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form action="{{ route('main-stock.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-4 text-center py-3 border border-dashed rounded bg-light" style="border-style: dashed !important; border-width: 2px !important; border-color: var(--accent) !important; background: rgba(0, 136, 204, 0.03) !important;">
                        <i class="bi bi-cloud-arrow-up text-accent" style="font-size: 3rem;"></i>
                        <p class="mt-2 small text-muted">Select an Excel or CSV file to import stock batches.</p>
                        <div class="d-grid gap-2 px-4 mt-3">
                            <input type="file" name="excel_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.8rem; background: rgba(0, 136, 204, 0.08); border-color: rgba(0, 136, 204, 0.15); color: #005f9e;">
                        <i class="bi bi-info-circle-fill me-2"></i><strong>Tip:</strong> Need a starting point?
                        <a href="{{ route('main-stock.import-template') }}" class="fw-bold text-accent text-decoration-none ms-1"><i class="bi bi-download me-1"></i>Download Template (.xlsx)</a>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent"><i class="bi bi-upload me-1"></i>Import Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Main Store Stock Modal -->
<div class="modal fade" id="addMainStockModal" tabindex="-1" aria-labelledby="addMainStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(0, 136, 204, 0.1); width: 34px; height: 34px;">
                        <i class="bi bi-building-fill text-accent fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-700 mb-0" id="addMainStockModalLabel" style="font-size: 1.05rem;">Receive Stock into Main Warehouse</h5>
                        <small class="text-muted" style="font-size: 0.75rem;">Add existing catalog products or add brand new unlisted products directly</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form action="{{ route('main-stock.store') }}" method="POST" id="modalMainStockForm">
                @csrf
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    <div id="modalMainStockProductsContainer">
                        <!-- Product block 0 -->
                        <div class="product-block-modal-main border rounded p-3 mb-3 bg-white" data-index="0" style="border-left: 4px solid var(--accent) !important; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary fw-700 me-2 px-2.5 py-1.5 modal-product-title text-white" style="font-size: 0.75rem;">Product #1</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input modal-main-create-new-product-toggle" type="checkbox" name="products[0][create_new_product]" value="1" id="modalMainCreateNewProductToggle_0" style="cursor: pointer;">
                                        <label class="form-check-label small fw-600 text-muted mb-0" for="modalMainCreateNewProductToggle_0" style="cursor: pointer; font-size: 0.75rem;">
                                            <i class="bi bi-plus-circle me-1 text-accent"></i>New Product instead
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-modal-main-product d-none" style="padding: 2px 8px !important; border-radius: 4px;">
                                        <i class="bi bi-trash3 me-1"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-12">
                                    <div class="modal-main-existing-product-group">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Select Product *</label>
                                        <select name="products[0][item_id]" class="form-select form-select-sm modal-main-item-id-select" required>
                                            <option value="">-- Search & Choose Existing Product --</option>
                                            @foreach($items as $item)
                                            @php
                                                $hasMainStock = $item->mainStock && $item->mainStock->selling_price > 0;
                                                $buyingPrice = $hasMainStock ? number_format($item->mainStock->buying_price, 0, '', '') : '';
                                                $sellingPrice = $hasMainStock ? number_format($item->mainStock->selling_price, 0, '', '') : '';
                                            @endphp
                                            <option value="{{ $item->id }}"
                                                    data-buying-price="{{ $buyingPrice }}"
                                                    data-selling-price="{{ $sellingPrice }}"
                                                    data-unit-id="{{ $item->unit_id }}"
                                                    data-unit-symbol="{{ $item->unit_symbol }}">
                                                [{{ $item->category->category_name ?? 'Uncategorized' }}] {{ $item->item_name }} {{ $item->brand ? "($item->brand)" : '' }}
                                                @if($hasMainStock)
                                                    (Current: BP {{ number_format($item->mainStock->buying_price, 0) }} / SP {{ number_format($item->mainStock->selling_price, 0) }})
                                                @endif
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="modal-main-new-product-group" style="display: none;">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">New Product Name *</label>
                                        <input type="text" name="products[0][new_item_name]" class="form-control form-control-sm modal-main-new-item-name-input" placeholder="e.g. Dell Latitude 5420 Laptop">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-main-new-product-group border rounded p-3 mb-2" style="display: none; background: rgba(0, 136, 204, 0.02);">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-600 mb-0" style="font-size:0.8rem;">Category *</label>
                                            <div class="form-check form-switch mb-0" style="padding-left: 2.2em;">
                                                <input class="form-check-input modal-main-create-new-category-toggle" type="checkbox" name="products[0][create_new_category]" value="1" id="modalMainCreateNewCategoryToggle_0" style="cursor: pointer;">
                                                <label class="form-check-label text-muted" for="modalMainCreateNewCategoryToggle_0" style="cursor: pointer; font-size:0.65rem;">New Category</label>
                                            </div>
                                        </div>
                                        <div class="modal-main-existing-category-group">
                                            <select name="products[0][category_id]" class="form-select form-select-sm modal-main-category-id-select">
                                                <option value="">-- Choose Category --</option>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="modal-main-new-category-group" style="display: none;">
                                            <input type="text" name="products[0][new_category_name]" class="form-control form-control-sm modal-main-new-category-name-input" placeholder="e.g. Laptops">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Unit *</label>
                                        <select name="products[0][unit_id]" class="form-select form-select-sm modal-main-new-unit-id-select" disabled>
                                            <option value="">-- Choose Unit --</option>
                                            @foreach($units as $u)
                                            <option value="{{ $u->id }}" data-symbol="{{ $u->symbol }}" {{ $u->is_default ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Brand</label>
                                        <input type="text" name="products[0][brand]" class="form-control form-control-sm modal-main-brand-input" placeholder="e.g. Dell">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Model</label>
                                        <input type="text" name="products[0][model]" class="form-control form-control-sm modal-main-model-input" placeholder="e.g. 5420">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Specification</label>
                                        <input type="text" name="products[0][specification]" class="form-control form-control-sm modal-main-specification-input" placeholder="e.g. Core i5, 16GB RAM">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Quantity *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control modal-main-quantity-display-input" required autocomplete="off" placeholder="e.g. 10">
                                        <span class="input-group-text modal-main-unit-symbol-display" style="min-width: 48px; justify-content: center; font-weight: 600; font-size: 0.78rem; background: var(--input-bg, #21262d); color: var(--text-secondary, #8b949e);">pc</span>
                                    </div>
                                    <input type="hidden" name="products[0][quantity]" class="modal-main-quantity-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">TZS</span>
                                        <input type="text" class="form-control modal-main-buying-price-display-input" required autocomplete="off" placeholder="e.g. 1,000,000">
                                    </div>
                                    <input type="hidden" name="products[0][buying_price]" class="modal-main-buying-price-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">TZS</span>
                                        <input type="text" class="form-control modal-main-selling-price-display-input" required autocomplete="off" placeholder="e.g. 1,250,000">
                                    </div>
                                    <input type="hidden" name="products[0][selling_price]" class="modal-main-selling-price-hidden-input">
                                    <div class="text-danger small mt-1 modal-main-price-warning" style="display: none; font-size: 0.72rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 fw-600" id="btnModalMainAddProduct" style="border-style: dashed; border-width: 1.5px;">
                            <i class="bi bi-plus-circle me-1"></i> Add Another Product to Batch
                        </button>
                    </div>

                    <div class="mb-2 border-top pt-3">
                        <label for="modal_date_received" class="form-label fw-600" style="font-size:0.8rem;">Date Received *</label>
                        <input type="date" name="date_received" id="modal_date_received" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent px-3 fw-600"><i class="bi bi-check2-circle me-1"></i>Receive into Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Swap Product Modal -->
<div class="modal fade" id="swapProductModal" tabindex="-1" aria-labelledby="swapProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.12); width: 36px; height: 36px;">
                        <i class="bi bi-arrow-left-right text-warning fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-700 mb-0" id="swapProductModalLabel" style="font-size: 1.05rem;">Swap Product Stock</h5>
                        <small class="text-muted" style="font-size: 0.75rem;">Reassign this stock to a different catalog product</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter, none);"></button>
            </div>
            <form id="swapProductForm">
                @csrf
                <input type="hidden" id="swapStockId" name="stock_id" value="">
                <div class="modal-body p-4">
                    <!-- Current Product Card -->
                    <div class="p-3 mb-3 rounded border" style="background: rgba(255, 255, 255, 0.03); border-color: var(--card-border) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary-subtle text-secondary fw-700" style="font-size: 0.72rem;">CURRENT PRODUCT</span>
                            <span class="badge bg-info-subtle text-info fw-600" id="swapCurrentCategory"></span>
                        </div>
                        <h6 class="fw-bold mb-2" id="swapCurrentProductName" style="font-size: 1rem; color: var(--text-primary);">-</h6>
                        <div class="row g-2 text-muted small" style="font-size: 0.78rem;">
                            <div class="col-6 col-sm-3">
                                <div>Remaining Qty:</div>
                                <strong class="text-success fs-6" id="swapCurrentRemainingQty">0</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>Total Stocked:</div>
                                <strong class="fs-6" id="swapCurrentStockedQty">0</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>Buy Price:</div>
                                <strong class="fs-6" id="swapCurrentBuyPrice">TZS 0</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div>Sell Price:</div>
                                <strong class="fs-6" id="swapCurrentSellPrice">TZS 0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Swap Icon Divider -->
                    <div class="text-center my-3">
                        <span class="badge rounded-pill px-3 py-2 text-warning border border-warning" style="background: rgba(245, 158, 11, 0.08);">
                            <i class="bi bi-arrow-down-up me-1"></i> Swap With
                        </span>
                    </div>

                    <!-- Target Product Selection -->
                    <div class="mb-3">
                        <label for="swapTargetItemId" class="form-label fw-600" style="font-size: 0.85rem;">Select New Product to Swap to *</label>
                        <select name="target_item_id" id="swapTargetItemId" class="form-select form-select-sm" required style="width: 100%;">
                            <option value="">-- Choose New Product from List --</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" data-category="{{ $item->category->category_name ?? 'Uncategorized' }}" data-name="{{ $item->item_name }}">
                                [{{ $item->category->category_name ?? 'Uncategorized' }}] {{ $item->item_name }} {{ $item->brand ? "($item->brand)" : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small" style="font-size: 0.72rem;">Choose the correct catalog product to replace the current stock with.</div>
                    </div>

                    <!-- Propagation Option -->
                    <div class="form-check form-switch mb-3 p-3 rounded border" style="background: rgba(0, 136, 204, 0.03); border-color: rgba(0, 136, 204, 0.15) !important;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="affect_shops" value="1" id="swapAffectShops" checked style="cursor: pointer;">
                        <label class="form-check-label fw-600 mb-0" for="swapAffectShops" style="cursor: pointer; font-size: 0.82rem;">
                            <i class="bi bi-shop me-1 text-accent"></i> Also propagate swap to all Shop Branches
                        </label>
                        <div class="text-muted small mt-1 ms-4 ps-1" style="font-size: 0.72rem;">
                            If enabled, any stock of the old product currently held in shop branches and pending stock transfers will also be reassigned to the new product.
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-warning py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size: 0.76rem; background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.2); color: #d97706;">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 fs-6"></i>
                        <div>
                            <strong>Note:</strong> If the target product already has existing stock in the warehouse, the quantities will be consolidated automatically.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning px-3 fw-600" id="btnConfirmSwap">
                        <i class="bi bi-arrow-left-right me-1"></i> Update & Swap Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
    $(() => {
        function formatNumber(n) {
            if (!n) return '';
            let parts = n.toString().split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        function initModalSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#addMainStockModal'),
                    placeholder: '-- Search & Choose Existing Product --',
                    allowClear: true
                });
            }
        }

        $('#addMainStockModal').on('shown.bs.modal', function () {
            $('.modal-main-item-id-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    initModalSelect2(this);
                }
            });
        });

        // Quantity Formatting
        $('#modalMainStockProductsContainer').on('input', '.modal-main-quantity-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9]/g, '');
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-modal-main').find('.modal-main-quantity-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        });

        // Buying Price Formatting
        $('#modalMainStockProductsContainer').on('input', '.modal-main-buying-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-modal-main').find('.modal-main-buying-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateModalMainBlockPrices($(this).closest('.product-block-modal-main'));
        });

        // Selling Price Formatting
        $('#modalMainStockProductsContainer').on('input', '.modal-main-selling-price-display-input', function() {
            let selectionStart = this.selectionStart;
            let origLength = this.value.length;
            let cleanVal = this.value.replace(/[^0-9.]/g, '');
            let dotCount = (cleanVal.match(/\./g) || []).length;
            if (dotCount > 1) {
                cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
            }
            this.value = formatNumber(cleanVal);
            $(this).closest('.product-block-modal-main').find('.modal-main-selling-price-hidden-input').val(cleanVal);
            let newLength = this.value.length;
            this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
            validateModalMainBlockPrices($(this).closest('.product-block-modal-main'));
        });

        function validateModalMainBlockPrices(block) {
            const buying = parseFloat(block.find('.modal-main-buying-price-hidden-input').val() || 0);
            const selling = parseFloat(block.find('.modal-main-selling-price-hidden-input').val() || 0);
            const warning = block.find('.modal-main-price-warning');

            if (selling > 0 && buying > 0 && selling < buying) {
                warning.show();
                block.find('.modal-main-selling-price-display-input').addClass('is-invalid');
            } else {
                warning.hide();
                block.find('.modal-main-selling-price-display-input').removeClass('is-invalid');
            }
            validateAllModalMainPrices();
        }

        function validateAllModalMainPrices() {
            let isValid = true;
            $('#modalMainStockProductsContainer .product-block-modal-main').each(function() {
                const buying = parseFloat($(this).find('.modal-main-buying-price-hidden-input').val() || 0);
                const selling = parseFloat($(this).find('.modal-main-selling-price-hidden-input').val() || 0);
                if (selling > 0 && buying > 0 && selling < buying) {
                    isValid = false;
                }
            });
            $('#modalMainStockForm').find('button[type="submit"]').prop('disabled', !isValid);
        }

        function updateModalMainBlockCategoryFields(block) {
            const isNewProduct = block.find('.modal-main-create-new-product-toggle').is(':checked');
            const isNewCategory = block.find('.modal-main-create-new-category-toggle').is(':checked');

            if (isNewProduct) {
                if (isNewCategory) {
                    block.find('.modal-main-existing-category-group').hide();
                    block.find('.modal-main-category-id-select').val('').prop('required', false);
                    block.find('.modal-main-new-category-group').show();
                    block.find('.modal-main-new-category-name-input').prop('required', true);
                } else {
                    block.find('.modal-main-new-category-group').hide();
                    block.find('.modal-main-new-category-name-input').val('').prop('required', false);
                    block.find('.modal-main-existing-category-group').show();
                    block.find('.modal-main-category-id-select').prop('required', true);
                }
            } else {
                block.find('.modal-main-category-id-select').prop('required', false);
                block.find('.modal-main-new-category-name-input').prop('required', false);
            }
        }

        $('#modalMainStockProductsContainer').on('change', '.modal-main-create-new-product-toggle', function() {
            const block = $(this).closest('.product-block-modal-main');
            if (this.checked) {
                block.find('.modal-main-existing-product-group').hide();
                block.find('.modal-main-item-id-select').val('').prop('required', false);
                block.find('.modal-main-new-product-group').show();
                block.find('.modal-main-new-item-name-input').prop('required', true);
                block.find('.modal-main-new-unit-id-select').prop('required', true).prop('disabled', false);
                const newUnitSym = block.find('.modal-main-new-unit-id-select option:selected').data('symbol') || 'pc';
                block.find('.modal-main-unit-symbol-display').text(newUnitSym);
                updateModalMainBlockCategoryFields(block);
            } else {
                block.find('.modal-main-new-product-group').hide();
                block.find('.modal-main-new-item-name-input').val('').prop('required', false);
                block.find('.modal-main-new-unit-id-select').prop('required', false).prop('disabled', true);
                block.find('.modal-main-existing-product-group').show();
                block.find('.modal-main-item-id-select').prop('required', true);
                const itemUnitSym = block.find('.modal-main-item-id-select option:selected').data('unit-symbol') || 'pc';
                block.find('.modal-main-unit-symbol-display').text(itemUnitSym);
                updateModalMainBlockCategoryFields(block);
            }
        });

        $('#modalMainStockProductsContainer').on('change', '.modal-main-new-unit-id-select', function() {
            const sym = $(this).find('option:selected').data('symbol') || 'pc';
            $(this).closest('.product-block-modal-main').find('.modal-main-unit-symbol-display').text(sym);
        });

        $('#modalMainStockProductsContainer').on('change', '.modal-main-create-new-category-toggle', function() {
            const block = $(this).closest('.product-block-modal-main');
            updateModalMainBlockCategoryFields(block);
        });

        $('#modalMainStockProductsContainer').on('change', '.modal-main-item-id-select', function() {
            const block = $(this).closest('.product-block-modal-main');
            const selectedOpt = $(this).find('option:selected');
            if (!selectedOpt.val()) {
                block.find('.modal-main-buying-price-display-input').val('');
                block.find('.modal-main-buying-price-hidden-input').val('');
                block.find('.modal-main-selling-price-display-input').val('');
                block.find('.modal-main-selling-price-hidden-input').val('');
                block.find('.modal-main-unit-symbol-display').text('pc');
                return;
            }

            const mainBuying = selectedOpt.data('buying-price');
            const mainSelling = selectedOpt.data('selling-price');
            const unitSymbol = selectedOpt.data('unit-symbol') || 'pc';

            block.find('.modal-main-unit-symbol-display').text(unitSymbol);

            if (mainBuying && mainSelling) {
                block.find('.modal-main-buying-price-hidden-input').val(mainBuying);
                block.find('.modal-main-buying-price-display-input').val(formatNumber(mainBuying));
                block.find('.modal-main-selling-price-hidden-input').val(mainSelling);
                block.find('.modal-main-selling-price-display-input').val(formatNumber(mainSelling));
            } else if (mainSelling) {
                block.find('.modal-main-buying-price-hidden-input').val('');
                block.find('.modal-main-buying-price-display-input').val('');
                block.find('.modal-main-selling-price-hidden-input').val(mainSelling);
                block.find('.modal-main-selling-price-display-input').val(formatNumber(mainSelling));
            } else {
                block.find('.modal-main-buying-price-display-input').val('');
                block.find('.modal-main-buying-price-hidden-input').val('');
                block.find('.modal-main-selling-price-display-input').val('');
                block.find('.modal-main-selling-price-hidden-input').val('');
            }
            validateModalMainBlockPrices(block);
        });

        let modalMainProductIndex = 0;
        $('#btnModalMainAddProduct').on('click', function() {
            modalMainProductIndex++;
            const originalBlock = $('#modalMainStockProductsContainer .product-block-modal-main').first();

            originalBlock.find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            const newBlock = originalBlock.clone();

            initModalSelect2(originalBlock.find('.modal-main-item-id-select'));

            newBlock.attr('data-index', modalMainProductIndex);
            newBlock.find('.modal-product-title').text('Product #' + (modalMainProductIndex + 1));

            newBlock.find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/products\[\d+\]/, 'products[' + modalMainProductIndex + ']'));
                }
                const id = $(this).attr('id');
                if (id) {
                    $(this).attr('id', id.replace(/_\d+$/, '_' + modalMainProductIndex));
                }
            });

            newBlock.find('label[for]').each(function() {
                const forAttr = $(this).attr('for');
                if (forAttr) {
                    $(this).attr('for', forAttr.replace(/_\d+$/, '_' + modalMainProductIndex));
                }
            });

            newBlock.find('input[type="text"], input[type="number"], input[type="hidden"]').val('');
            newBlock.find('input[type="checkbox"]').prop('checked', false);
            newBlock.find('select').val('');
            newBlock.find('.modal-main-new-product-group').hide();
            newBlock.find('.modal-main-existing-product-group').show();
            newBlock.find('.modal-main-item-id-select').prop('required', true);
            newBlock.find('.modal-main-new-item-name-input').prop('required', false);
            newBlock.find('.modal-main-new-unit-id-select').prop('required', false).prop('disabled', true);
            newBlock.find('.modal-main-unit-symbol-display').text('pc');
            newBlock.find('.modal-main-new-category-group').hide();
            newBlock.find('.modal-main-existing-category-group').show();
            newBlock.find('.modal-main-price-warning').hide();
            newBlock.find('.btn-remove-modal-main-product').removeClass('d-none');

            $('#modalMainStockProductsContainer').append(newBlock);

            initModalSelect2(newBlock.find('.modal-main-item-id-select'));

            const blocks = $('#modalMainStockProductsContainer .product-block-modal-main');
            if (blocks.length > 1) {
                blocks.find('.btn-remove-modal-main-product').removeClass('d-none');
            }
        });

        $('#modalMainStockProductsContainer').on('click', '.btn-remove-modal-main-product', function() {
            $(this).closest('.product-block-modal-main').remove();
            $('#modalMainStockProductsContainer .product-block-modal-main').each(function(idx) {
                $(this).attr('data-index', idx);
                $(this).find('.modal-product-title').text('Product #' + (idx + 1));
                $(this).find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/products\[\d+\]/, 'products[' + idx + ']'));
                    }
                    const id = $(this).attr('id');
                    if (id) {
                        $(this).attr('id', id.replace(/_\d+$/, '_' + idx));
                    }
                });
                $(this).find('label[for]').each(function() {
                    const forAttr = $(this).attr('for');
                    if (forAttr) {
                        $(this).attr('for', forAttr.replace(/_\d+$/, '_' + idx));
                    }
                });
            });
            modalMainProductIndex = $('#modalMainStockProductsContainer .product-block-modal-main').length - 1;
            const blocks = $('#modalMainStockProductsContainer .product-block-modal-main');
            if (blocks.length <= 1) {
                blocks.find('.btn-remove-modal-main-product').addClass('d-none');
            }
            validateAllModalMainPrices();
        });

        const table = $('#mainStockTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("main-stock.data") }}',
                data: function(d) {
                    d.category_id = $('#filterCategory').val();
                    d.item_id = $('#filterProduct').val();
                }
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, responsivePriority: 1000, className: 'min-tablet-l text-center' },
                { data: 'no', name: 'no', orderable: false, searchable: false, responsivePriority: 1, className: 'all iter-col text-center' },
                { data: 'product', name: 'product', responsivePriority: 1, className: 'all primary-col' },
                { data: 'category', name: 'category', responsivePriority: 3, className: 'min-tablet-l' },
                { data: 'buying_price', name: 'buying_price', responsivePriority: 3, className: 'min-tablet-l' },
                { data: 'selling_price', name: 'selling_price', responsivePriority: 3, className: 'min-tablet-l' },
                { data: 'stocked_quantity', name: 'stocked_quantity', responsivePriority: 3, className: 'min-tablet-l' },
                { data: 'remaining_quantity', name: 'remaining_quantity', responsivePriority: 3, className: 'min-tablet-l' },
                { data: 'date_received', name: 'date_received', responsivePriority: 4, className: 'min-tablet-l' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, responsivePriority: 3, className: 'min-tablet-l text-end' }
            ],
            order: [[8, 'desc']]
        });

        // ── Category & Product Filter Cascading ──────────────────────────
        const allMainProductOptions = $('#filterProduct option').clone();

        function refreshMainProductDropdown(categoryId, preserveSelectedId = null) {
            const $prod = $('#filterProduct');
            const wasSelect2 = $prod.hasClass('select2-hidden-accessible');
            if (wasSelect2) {
                $prod.select2('destroy');
            }

            $prod.empty();
            allMainProductOptions.each(function() {
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
            refreshMainProductDropdown(selectedCatId, currentProdId);
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
                    refreshMainProductDropdown(catId, selectedProdId);
                }
            }
            table.ajax.reload();
        });

        $('#btnResetFilters').on('click', function() {
            $('#filterCategory').val('');
            if ($('#filterCategory').hasClass('select2-hidden-accessible')) {
                $('#filterCategory').trigger('change.select2');
            }
            refreshMainProductDropdown('');
            table.ajax.reload();
        });

        $('.toggle-components-btn').on('change', function() {
            const isChecked = $(this).is(':checked');
            const mainStockId = $(this).data('id');
            const self = $(this);
            
            $.post("{{ route('settings.toggle-components') }}", {
                _token: "{{ csrf_token() }}",
                main_stock_id: mainStockId,
                enabled: isChecked ? 1 : 0
            })
            .done(function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: isChecked ? 'Manual components enabled for this product batch.' : 'Manual components disabled for this product batch.',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                }
            })
            .fail(function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update setting. Please try again.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                self.prop('checked', !isChecked);
            });
        });

        function updateBulkActionsBar() {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });
            
            const count = checkedIds.length;
            if (count > 0) {
                $('#selectedCountText').html(`<strong>${count}</strong> item(s) selected`);
                $('#bulkActionsBar').removeClass('d-none');
            } else {
                $('#bulkActionsBar').addClass('d-none');
            }
        }

        $('#checkAllStocks').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.stock-checkbox').prop('checked', isChecked);
            updateBulkActionsBar();
        });

        $(document).on('change', '.stock-checkbox', function() {
            updateBulkActionsBar();
            if (!$(this).is(':checked')) {
                $('#checkAllStocks').prop('checked', false);
            }
        });

        function sendBulkUpdate(enabled) {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });
            
            if (checkedIds.length === 0) return;
            
            Swal.fire({
                title: 'Please wait...',
                html: 'Updating selected products components capability...',
                allowOutsideClick: false,
                background: '#161b22',
                color: '#e6edf3',
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.post("{{ route('settings.toggle-components') }}", {
                _token: "{{ csrf_token() }}",
                main_stock_ids: checkedIds,
                enabled: enabled ? 1 : 0
            })
            .done(function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: 'Products updated successfully!',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#161b22',
                    color: '#e6edf3'
                }).then(() => {
                    location.reload();
                });
            })
            .fail(function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to perform bulk update. Please try again.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
            });
        }

        $('#bulkEnableBtn').on('click', () => sendBulkUpdate(true));
        $('#bulkDisableBtn').on('click', () => sendBulkUpdate(false));

        $('#bulkDeleteBtn').on('click', function() {
            const checkedIds = [];
            $('.stock-checkbox:checked').each(function() {
                checkedIds.push($(this).data('id'));
            });

            if (checkedIds.length === 0) return;

            Swal.fire({
                title: 'Delete ' + checkedIds.length + ' Stock Batch(es)?',
                html: 'Are you sure you want to delete the selected stock batch(es) from the Main Store?<br><small class="text-warning">Batches with partial sales/transfers will be skipped.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete selected!',
                cancelButtonText: 'Cancel',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting stock...',
                        allowOutsideClick: false,
                        background: '#161b22',
                        color: '#e6edf3',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('main-stock.bulk-destroy') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: "DELETE",
                            ids: checkedIds
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: res.message,
                                    background: '#161b22',
                                    color: '#e6edf3'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message || 'Failed to delete selected stock.',
                                    background: '#161b22',
                                    color: '#e6edf3'
                                });
                            }
                        },
                        error: function(err) {
                            const errRes = err.responseJSON;
                            let errMsg = 'Failed to delete selected stock.';
                            if (errRes && errRes.errors) {
                                errMsg = errRes.errors.join('<br>');
                            } else if (errRes && errRes.message) {
                                errMsg = errRes.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Bulk Delete Blocked',
                                html: errMsg,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.confirm-delete-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Delete Stock Batch?',
                text: "Are you sure you want to delete this stock batch? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // ════════════════════════════════════════════════════════════════
        // PRODUCT SWAP HANDLERS (OWNER ONLY)
        // ════════════════════════════════════════════════════════════════
        $('#swapProductModal').on('shown.bs.modal', function () {
            if ($.fn.select2) {
                if ($('#swapTargetItemId').hasClass('select2-hidden-accessible')) {
                    $('#swapTargetItemId').select2('destroy');
                }
                $('#swapTargetItemId').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#swapProductModal'),
                    placeholder: '-- Choose New Product from List --',
                    allowClear: true
                });
            }
        });

        $(document).on('click', '.btn-swap-product', function() {
            const btn = $(this);
            const stockId = btn.data('id');
            const itemId = btn.data('item-id');
            const itemName = btn.data('item-name');
            const category = btn.data('category');
            const remainingQty = btn.data('remaining-qty');
            const stockedQty = btn.data('stocked-qty');
            const buyPrice = btn.data('buying-price');
            const sellPrice = btn.data('selling-price');

            $('#swapStockId').val(stockId);
            $('#swapCurrentProductName').text(itemName);
            $('#swapCurrentCategory').text(category || 'General');
            $('#swapCurrentRemainingQty').text(remainingQty);
            $('#swapCurrentStockedQty').text(stockedQty);
            $('#swapCurrentBuyPrice').text('TZS ' + buyPrice);
            $('#swapCurrentSellPrice').text('TZS ' + sellPrice);

            // Hide/disable current product in target selection
            $('#swapTargetItemId option').each(function() {
                if ($(this).val() == itemId) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });

            $('#swapTargetItemId').val('');
            $('#swapAffectShops').prop('checked', true);

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('swapProductModal'));
            modal.show();
        });

        $('#swapProductForm').on('submit', function(e) {
            e.preventDefault();
            const stockId = $('#swapStockId').val();
            const targetItemId = $('#swapTargetItemId').val();
            const targetItemName = $('#swapTargetItemId option:selected').data('name') || 'Selected Product';
            const currentItemName = $('#swapCurrentProductName').text();
            const affectShops = $('#swapAffectShops').is(':checked') ? 1 : 0;

            if (!targetItemId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Product',
                    text: 'Please select a new product to swap to.',
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Product Swap',
                html: `Are you sure you want to swap <b>${currentItemName}</b> to <b>${targetItemName}</b>?<br><br><small class="text-warning">This will update warehouse stock and ${affectShops ? 'propagate to all shop branches' : 'keep shop stocks unchanged'}.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, swap product!',
                cancelButtonText: 'Cancel',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Swapping product...',
                        allowOutsideClick: false,
                        background: '#161b22',
                        color: '#e6edf3',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const swapUrl = "{{ url('main-stock') }}/" + stockId + "/swap";

                    $.ajax({
                        url: swapUrl,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            target_item_id: targetItemId,
                            affect_shops: affectShops
                        },
                        success: function(res) {
                            bootstrap.Modal.getInstance(document.getElementById('swapProductModal'))?.hide();
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Product Swapped!',
                                    text: res.message,
                                    background: '#161b22',
                                    color: '#e6edf3'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message || 'Failed to swap product.',
                                    background: '#161b22',
                                    color: '#e6edf3'
                                });
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Failed to swap product.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Swap Failed',
                                text: msg,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
