@extends('layouts.app')
@section('title', 'Add Stock to Main Store')
@section('page-title', 'Add to Main Store')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('main-stock.index') }}">Main Store</a></li>
<li class="breadcrumb-item active">Add Stock</li>
@endsection
@section('content')
<style>
    .product-block-main {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 4px solid var(--accent, #0088cc) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        padding: 1.25rem 1.25rem;
    }
    .product-block-main:hover {
        border-left-color: #0077b5 !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }
    .input-group-text-custom {
        background-color: var(--body-bg, #f4f6f9);
        border-color: var(--input-border, #cbd5e1);
        color: var(--text-secondary, #64748b);
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(0, 136, 204, 0.1); width: 38px; height: 38px;">
                        <i class="bi bi-building-fill text-accent fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-700">Receive Stock into Central Warehouse</h6>
                        <small class="text-muted" style="font-size: 0.78rem;">Add existing catalog products or create brand new unlisted products directly</small>
                    </div>
                </div>
                <a href="{{ route('main-stock.index') }}" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-arrow-left me-1"></i> Back to Main Store
                </a>
            </div>

            <div class="card-body p-4">
                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background: rgba(233, 69, 96, 0.1); border-color: rgba(233, 69, 96, 0.2); color: #e94560;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <h6 class="fw-bold mb-0">Please resolve the following errors:</h6>
                    </div>
                    <ul class="mb-0 ps-4 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('main-stock.store') }}" id="mainStockCreateForm">
                    @csrf

                    <div id="mainStockProductsContainer">
                        <!-- Product block 0 -->
                        <div class="product-block-main border rounded p-3 p-md-4 mb-4 bg-white" data-index="0">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary fw-700 me-2 px-2.5 py-1.5 product-title text-white" style="font-size: 0.76rem;">Product #1</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input main-create-new-product-toggle" type="checkbox" name="products[0][create_new_product]" value="1" id="mainCreateNewProductToggle_0" style="cursor: pointer;">
                                        <label class="form-check-label small fw-600 text-muted mb-0" for="mainCreateNewProductToggle_0" style="cursor: pointer; font-size: 0.78rem;">
                                            <i class="bi bi-plus-circle me-1 text-accent"></i>New Product instead
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-main-product d-none" style="padding: 2px 8px !important; border-radius: 4px;">
                                        <i class="bi bi-trash3 me-1"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Product selection / name -->
                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-12">
                                    <!-- Existing Product Dropdown -->
                                    <div class="main-existing-product-group">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.82rem;">Select Catalog Product *</label>
                                        <select name="products[0][item_id]" class="form-select form-select-sm main-item-id-select" required>
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
                                        <div class="price-suggestion-notice text-info small mt-1" style="display: none; font-size: 0.75rem;">
                                            <i class="bi bi-info-circle-fill me-1"></i> Current suggested prices auto-filled below. You can keep or modify them.
                                        </div>
                                    </div>

                                    <!-- New Product Input -->
                                    <div class="main-new-product-group" style="display: none;">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.82rem;">New Product Name *</label>
                                        <input type="text" name="products[0][new_item_name]" class="form-control form-control-sm main-new-item-name-input" placeholder="e.g. Dell Latitude 5420 Laptop / HP LaserJet Pro M404dn">
                                    </div>
                                </div>
                            </div>

                            <!-- New Product Metadata (Category, Brand, Model, Spec) -->
                            <div class="main-new-product-group border rounded p-3 mb-3" style="display: none; background: rgba(0, 136, 204, 0.02); border-color: var(--card-border) !important;">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-600 mb-0" style="font-size:0.8rem;">Category *</label>
                                            <div class="form-check form-switch mb-0" style="padding-left: 2.2em;">
                                                <input class="form-check-input main-create-new-category-toggle" type="checkbox" name="products[0][create_new_category]" value="1" id="mainCreateNewCategoryToggle_0" style="cursor: pointer;">
                                                <label class="form-check-label text-muted" for="mainCreateNewCategoryToggle_0" style="cursor: pointer; font-size:0.68rem;">New Category</label>
                                            </div>
                                        </div>
                                        <div class="main-existing-category-group">
                                            <select name="products[0][category_id]" class="form-select form-select-sm main-category-id-select">
                                                <option value="">-- Choose Category --</option>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="main-new-category-group" style="display: none;">
                                            <input type="text" name="products[0][new_category_name]" class="form-control form-control-sm main-new-category-name-input" placeholder="e.g. Laptops & Notebooks">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Brand</label>
                                        <input type="text" name="products[0][brand]" class="form-control form-control-sm main-brand-input" placeholder="e.g. Dell, HP, Apple">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Model</label>
                                        <input type="text" name="products[0][model]" class="form-control form-control-sm main-model-input" placeholder="e.g. Latitude 5420">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Specification</label>
                                        <input type="text" name="products[0][specification]" class="form-control form-control-sm main-specification-input" placeholder="e.g. Core i5, 16GB, 512GB SSD">
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Quantity, Buying & Selling Price -->
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Quantity to Receive *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom"><i class="bi bi-hash"></i></span>
                                        <input type="text" class="form-control main-quantity-display-input" required autocomplete="off" placeholder="e.g. 10">
                                    </div>
                                    <input type="hidden" name="products[0][quantity]" class="main-quantity-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Buying Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control main-buying-price-display-input" required autocomplete="off" placeholder="e.g. 1,000,000">
                                    </div>
                                    <input type="hidden" name="products[0][buying_price]" class="main-buying-price-hidden-input">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600 mb-1" style="font-size:0.8rem;">Selling Price (TZS) *</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text input-group-text-custom">TZS</span>
                                        <input type="text" class="form-control main-selling-price-display-input" required autocomplete="off" placeholder="e.g. 1,250,000">
                                    </div>
                                    <input type="hidden" name="products[0][selling_price]" class="main-selling-price-hidden-input">
                                    <div class="text-danger small mt-1 main-price-warning" style="display: none; font-size: 0.75rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Selling price is less than buying price!
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Another Product Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 py-2 fw-600" id="btnMainAddProduct" style="border-style: dashed; border-width: 1.5px;">
                            <i class="bi bi-plus-circle-dotted me-1.5 fs-6"></i> Add Another Product to Batch
                        </button>
                    </div>

                    <!-- Date Received & Action Buttons -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <label for="date_received" class="form-label fw-600 mb-1" style="font-size:0.82rem;">Date Received *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="date_received" id="date_received" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-7 text-md-end mt-3 mt-md-0">
                                <span class="text-muted small me-3"><i class="bi bi-shield-check text-success me-1"></i>MIN selling price rule applied</span>
                                <a href="{{ route('main-stock.index') }}" class="btn btn-outline-secondary me-2 px-3">Cancel</a>
                                <button type="submit" class="btn btn-accent px-4 fw-600">
                                    <i class="bi bi-check2-circle me-1.5"></i> Receive into Main Warehouse
                                </button>
                            </div>
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
$(document).ready(function() {
    function formatNumber(n) {
        if (!n) return '';
        let parts = n.toString().split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    // Initialize Select2 if available
    function initSelect2(el) {
        if ($.fn.select2) {
            $(el).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Search & Choose Existing Product --',
                allowClear: true
            });
        }
    }

    $('.main-item-id-select').each(function() {
        initSelect2(this);
    });

    // Formatting for Quantity input
    $('#mainStockProductsContainer').on('input', '.main-quantity-display-input', function() {
        let selectionStart = this.selectionStart;
        let origLength = this.value.length;
        let cleanVal = this.value.replace(/[^0-9]/g, '');
        this.value = formatNumber(cleanVal);
        $(this).closest('.product-block-main').find('.main-quantity-hidden-input').val(cleanVal);
        let newLength = this.value.length;
        this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
    });

    // Formatting for Buying Price
    $('#mainStockProductsContainer').on('input', '.main-buying-price-display-input', function() {
        let selectionStart = this.selectionStart;
        let origLength = this.value.length;
        let cleanVal = this.value.replace(/[^0-9.]/g, '');
        let dotCount = (cleanVal.match(/\./g) || []).length;
        if (dotCount > 1) {
            cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
        }
        this.value = formatNumber(cleanVal);
        $(this).closest('.product-block-main').find('.main-buying-price-hidden-input').val(cleanVal);
        let newLength = this.value.length;
        this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        validateMainBlockPrices($(this).closest('.product-block-main'));
    });

    // Formatting for Selling Price
    $('#mainStockProductsContainer').on('input', '.main-selling-price-display-input', function() {
        let selectionStart = this.selectionStart;
        let origLength = this.value.length;
        let cleanVal = this.value.replace(/[^0-9.]/g, '');
        let dotCount = (cleanVal.match(/\./g) || []).length;
        if (dotCount > 1) {
            cleanVal = cleanVal.substr(0, cleanVal.lastIndexOf('.'));
        }
        this.value = formatNumber(cleanVal);
        $(this).closest('.product-block-main').find('.main-selling-price-hidden-input').val(cleanVal);
        let newLength = this.value.length;
        this.setSelectionRange(selectionStart + (newLength - origLength), selectionStart + (newLength - origLength));
        validateMainBlockPrices($(this).closest('.product-block-main'));
    });

    function validateMainBlockPrices(block) {
        const buying = parseFloat(block.find('.main-buying-price-hidden-input').val() || 0);
        const selling = parseFloat(block.find('.main-selling-price-hidden-input').val() || 0);
        const warning = block.find('.main-price-warning');

        if (selling > 0 && buying > 0 && selling < buying) {
            warning.show();
            block.find('.main-selling-price-display-input').addClass('is-invalid');
        } else {
            warning.hide();
            block.find('.main-selling-price-display-input').removeClass('is-invalid');
        }
        validateAllMainPrices();
    }

    function validateAllMainPrices() {
        let isValid = true;
        $('#mainStockProductsContainer .product-block-main').each(function() {
            const buying = parseFloat($(this).find('.main-buying-price-hidden-input').val() || 0);
            const selling = parseFloat($(this).find('.main-selling-price-hidden-input').val() || 0);
            if (selling > 0 && buying > 0 && selling < buying) {
                isValid = false;
            }
        });
        $('#mainStockCreateForm').find('button[type="submit"]').prop('disabled', !isValid);
    }

    function updateMainBlockCategoryFields(block) {
        const isNewProduct = block.find('.main-create-new-product-toggle').is(':checked');
        const isNewCategory = block.find('.main-create-new-category-toggle').is(':checked');

        if (isNewProduct) {
            if (isNewCategory) {
                block.find('.main-existing-category-group').hide();
                block.find('.main-category-id-select').val('').prop('required', false);
                block.find('.main-new-category-group').show();
                block.find('.main-new-category-name-input').prop('required', true);
            } else {
                block.find('.main-new-category-group').hide();
                block.find('.main-new-category-name-input').val('').prop('required', false);
                block.find('.main-existing-category-group').show();
                block.find('.main-category-id-select').prop('required', true);
            }
        } else {
            block.find('.main-category-id-select').prop('required', false);
            block.find('.main-new-category-name-input').prop('required', false);
        }
    }

    // Toggle new product inputs vs select product dropdown
    $('#mainStockProductsContainer').on('change', '.main-create-new-product-toggle', function() {
        const block = $(this).closest('.product-block-main');
        if (this.checked) {
            block.find('.main-existing-product-group').hide();
            block.find('.main-item-id-select').val('').prop('required', false);
            block.find('.main-new-product-group').show();
            block.find('.main-new-item-name-input').prop('required', true);
            block.find('.price-suggestion-notice').hide();
            updateMainBlockCategoryFields(block);
        } else {
            block.find('.main-new-product-group').hide();
            block.find('.main-new-item-name-input').val('').prop('required', false);
            block.find('.main-existing-product-group').show();
            block.find('.main-item-id-select').prop('required', true);
            updateMainBlockCategoryFields(block);
        }
    });

    // Toggle new category input vs category select dropdown
    $('#mainStockProductsContainer').on('change', '.main-create-new-category-toggle', function() {
        const block = $(this).closest('.product-block-main');
        updateMainBlockCategoryFields(block);
    });

    // Auto-suggest values when selecting a product
    $('#mainStockProductsContainer').on('change', '.main-item-id-select', function() {
        const block = $(this).closest('.product-block-main');
        const selectedOpt = $(this).find('option:selected');
        if (!selectedOpt.val()) {
            block.find('.main-buying-price-display-input').val('');
            block.find('.main-buying-price-hidden-input').val('');
            block.find('.main-selling-price-display-input').val('');
            block.find('.main-selling-price-hidden-input').val('');
            block.find('.price-suggestion-notice').slideUp(180);
            return;
        }

        const mainBuying = selectedOpt.data('buying-price');
        const mainSelling = selectedOpt.data('selling-price');

        if (mainBuying && mainSelling) {
            block.find('.main-buying-price-hidden-input').val(mainBuying);
            block.find('.main-buying-price-display-input').val(formatNumber(mainBuying));
            block.find('.main-selling-price-hidden-input').val(mainSelling);
            block.find('.main-selling-price-display-input').val(formatNumber(mainSelling));
            block.find('.price-suggestion-notice').slideDown(180);
        } else if (mainSelling) {
            block.find('.main-buying-price-hidden-input').val('');
            block.find('.main-buying-price-display-input').val('');
            block.find('.main-selling-price-hidden-input').val(mainSelling);
            block.find('.main-selling-price-display-input').val(formatNumber(mainSelling));
            block.find('.price-suggestion-notice').slideDown(180);
        } else {
            block.find('.main-buying-price-display-input').val('');
            block.find('.main-buying-price-hidden-input').val('');
            block.find('.main-selling-price-display-input').val('');
            block.find('.main-selling-price-hidden-input').val('');
            block.find('.price-suggestion-notice').slideUp(180);
        }
        validateMainBlockPrices(block);
    });

    // Dynamic clone/reindex/remove handler for repeatable Main Stock rows
    let mainProductIndex = 0;
    $('#btnMainAddProduct').on('click', function() {
        mainProductIndex++;
        const originalBlock = $('#mainStockProductsContainer .product-block-main').first();

        // Destroy Select2 on original before cloning
        originalBlock.find('select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        const newBlock = originalBlock.clone();

        // Re-initialize Select2 on original
        initSelect2(originalBlock.find('.main-item-id-select'));

        newBlock.attr('data-index', mainProductIndex);
        newBlock.find('.product-title').text('Product #' + (mainProductIndex + 1));

        newBlock.find('input, select').each(function() {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/products\[\d+\]/, 'products[' + mainProductIndex + ']'));
            }
            const id = $(this).attr('id');
            if (id) {
                $(this).attr('id', id.replace(/_\d+$/, '_' + mainProductIndex));
            }
            const forAttr = $(this).attr('for');
            if (forAttr) {
                $(this).attr('for', forAttr.replace(/_\d+$/, '_' + mainProductIndex));
            }
        });

        newBlock.find('label[for]').each(function() {
            const forAttr = $(this).attr('for');
            if (forAttr) {
                $(this).attr('for', forAttr.replace(/_\d+$/, '_' + mainProductIndex));
            }
        });

        // Reset values in new block
        newBlock.find('input[type="text"], input[type="number"], input[type="hidden"]').val('');
        newBlock.find('input[type="checkbox"]').prop('checked', false);
        newBlock.find('select').val('');
        newBlock.find('.main-new-product-group').hide();
        newBlock.find('.main-existing-product-group').show();
        newBlock.find('.main-item-id-select').prop('required', true);
        newBlock.find('.main-new-item-name-input').prop('required', false);
        newBlock.find('.main-new-category-group').hide();
        newBlock.find('.main-existing-category-group').show();
        newBlock.find('.main-price-warning').hide();
        newBlock.find('.price-suggestion-notice').hide();
        newBlock.find('.btn-remove-main-product').removeClass('d-none');

        $('#mainStockProductsContainer').append(newBlock);

        // Initialize Select2 on new block
        initSelect2(newBlock.find('.main-item-id-select'));

        updateRemoveButtons();
    });

    // Remove block
    $('#mainStockProductsContainer').on('click', '.btn-remove-main-product', function() {
        $(this).closest('.product-block-main').remove();
        reindexMainBlocks();
        updateRemoveButtons();
        validateAllMainPrices();
    });

    function reindexMainBlocks() {
        $('#mainStockProductsContainer .product-block-main').each(function(idx) {
            $(this).attr('data-index', idx);
            $(this).find('.product-title').text('Product #' + (idx + 1));
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
        mainProductIndex = $('#mainStockProductsContainer .product-block-main').length - 1;
    }

    function updateRemoveButtons() {
        const blocks = $('#mainStockProductsContainer .product-block-main');
        if (blocks.length > 1) {
            blocks.find('.btn-remove-main-product').removeClass('d-none');
        } else {
            blocks.find('.btn-remove-main-product').addClass('d-none');
        }
    }
});
</script>
@endpush
