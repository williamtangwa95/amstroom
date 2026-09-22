@extends('layouts.app')
@section('title', 'Main Store Stock')
@section('page-title', 'Main Warehouse')
@section('breadcrumb')
<li class="breadcrumb-item active">Main Store Stock</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Main Store Inventory</h5>
        <small style="color:var(--text-secondary);">Central warehouse stock management</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('main-stock.history') }}" class="btn btn-outline-custom"><i class="bi bi-clock-history me-1"></i>History</a>
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadMainStockModal">
            <i class="bi bi-file-earmark-excel me-1"></i>Upload Stock
        </button>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addMainStockModal">
            <i class="bi bi-plus-circle me-1"></i>Add Stock
        </button>
        <a href="{{ route('main-stock.create') }}" class="btn btn-outline-custom" title="Full Page Form">
            <i class="bi bi-box-arrow-up-right me-1"></i>Full Form
        </a>
    </div>
</div>

@if(session('import_errors'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background: rgba(233, 69, 96, 0.1); border-color: rgba(233, 69, 96, 0.2); color: #e94560;">
    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Import Failed! Please correct the following errors and try again:</h6>
    <ul class="mb-0 ps-3 small" style="max-height: 200px; overflow-y: auto;">
        @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1);"></button>
</div>
@endif

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 mb-4">
    <!-- Total Cost Value Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(2, 132, 199, 0.1); color: var(--accent-blue); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($stats['totalInitialCost'], 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Total Cost Value ({{ number_format($stats['totalInitialQty']) }} units)">Total Cost <span class="small">({{ number_format($stats['totalInitialQty']) }})</span></div>
            </div>
        </div>
    </div>

    <!-- Total Sell Value Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0 text-success" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($stats['totalInitialSell'], 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Total Sell Value">Total Sell Value</div>
            </div>
        </div>
    </div>

    <!-- Remain Stock Value Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($stats['totalRemainingCost'], 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Remain Stock Value ({{ number_format($stats['totalRemainingQty']) }} units)">Remain Value <span class="small">({{ number_format($stats['totalRemainingQty']) }})</span></div>
            </div>
        </div>
    </div>

    <!-- Remain Sell Value Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-yellow); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-piggy-bank"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="color: var(--accent-yellow) !important; font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($stats['totalRemainingSell'], 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Remain Sell Value">Remain Sell Value</div>
            </div>
        </div>
    </div>

    <!-- Stock Batches Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-layers"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">{{ number_format($stats['stockBatchesCount']) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Stock Batches">Stock Batches</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-3 px-3 py-2 rounded border d-none" id="bulkActionsBar" style="background: var(--card-bg) !important; border-color: var(--card-border) !important;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check2-square text-accent fs-5"></i>
        <span class="fw-600 small" id="selectedCountText" style="color:var(--text-primary);">0 items selected</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-xs btn-accent px-3 py-1" id="bulkEnableBtn" style="font-size: .75rem;">Enable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-secondary px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDeleteBtn" style="font-size: .75rem;"><i class="bi bi-trash me-1"></i>Delete Selected Stock</button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="mainStockTable">
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="checkAllStocks" style="cursor:pointer;"></th>
                    <th>No</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Buy Price</th>
                    <th>Sell Price</th>
                    <th>Stocked</th>
                    <th>Remaining</th>
                    <th>Date</th>
                    <th class="no-sort">Actions</th>
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
                                                    data-selling-price="{{ $sellingPrice }}">
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
                                    <div class="col-md-4">
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
                                    <div class="col-md-3">
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
                updateModalMainBlockCategoryFields(block);
            } else {
                block.find('.modal-main-new-product-group').hide();
                block.find('.modal-main-new-item-name-input').val('').prop('required', false);
                block.find('.modal-main-existing-product-group').show();
                block.find('.modal-main-item-id-select').prop('required', true);
                updateModalMainBlockCategoryFields(block);
            }
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
                return;
            }

            const mainBuying = selectedOpt.data('buying-price');
            const mainSelling = selectedOpt.data('selling-price');

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

        $('#mainStockTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("main-stock.data") }}',
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                { data: 'no', name: 'no', orderable: false, searchable: false },
                { data: 'product', name: 'product' },
                { data: 'category', name: 'category' },
                { data: 'buying_price', name: 'buying_price' },
                { data: 'selling_price', name: 'selling_price' },
                { data: 'stocked_quantity', name: 'stocked_quantity' },
                { data: 'remaining_quantity', name: 'remaining_quantity' },
                { data: 'date_received', name: 'date_received' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[8, 'desc']]
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
    });
</script>
@endpush
