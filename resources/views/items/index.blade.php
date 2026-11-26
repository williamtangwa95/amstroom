@extends('layouts.app')
@section('title', 'Products')
@section('page-title', 'Product Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Products</li>
@endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="mb-0 fw-700">All Products</h5>
        <small style="color:var(--text-secondary);">Manage your product catalog</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-info d-none" id="btnOpenBulkAssignTop" data-bs-toggle="modal" data-bs-target="#bulkAssignUnitModal">
            <i class="bi bi-rulers me-1"></i> Assign Unit (<span class="selected-count-badge">0</span>)
        </button>
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadProductsModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Products
        </button>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addItemModal" id="btnNewItem">
            <i class="bi bi-plus-circle me-1"></i> Add Product
        </button>
    </div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <!-- Bulk Action Toolbar -->
        <div id="bulkActionBar" class="alert py-2 px-3 mb-3 d-none align-items-center justify-content-between flex-wrap gap-2" style="background: rgba(88, 166, 255, 0.1); border: 1px solid rgba(88, 166, 255, 0.25); color: var(--text-primary); border-radius: 8px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check2-square text-accent fs-5"></i>
                <span><strong id="selectedCountText">0</strong> product(s) selected</span>
                <button type="button" class="btn btn-xs btn-link text-decoration-none text-muted p-0 ms-2" id="btnClearSelection" style="font-size:0.75rem;">Clear selection</button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#bulkAssignUnitModal">
                    <i class="bi bi-rulers me-1"></i> Assign Unit to Selected
                </button>
            </div>
        </div>

        <table class="table table-hover align-middle w-100 dt-mobile-table dt-has-checkbox" id="itemsTable">
            <thead>
                <tr>
                    <th data-priority="1000" style="width: 38px;" class="min-tablet-l text-center no-sort">
                        <input type="checkbox" class="form-check-input" id="selectAllItems" title="Select all on this page">
                    </th>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Product</th>
                    <th data-priority="2" class="min-tablet-l">Category</th>
                    <th data-priority="3" class="min-tablet-l">Unit</th>
                    <th data-priority="3" class="min-tablet-l">Brand / Model</th>
                    <th data-priority="4" class="min-tablet-l">Warranty</th>
                    <th data-priority="2" class="min-tablet-l">Main Stock</th>
                    <th data-priority="2" class="min-tablet-l">Shop Stock</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Bulk Assign Unit Modal -->
<div class="modal fade" id="bulkAssignUnitModal" tabindex="-1" aria-labelledby="bulkAssignUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="bulkAssignUnitModalLabel">
                    <i class="bi bi-rulers text-accent me-2"></i>Assign Unit to Selected Products
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkAssignUnitForm">
                @csrf
                <div class="modal-body">
                    <div class="p-3 mb-3 rounded" style="background:rgba(88,166,255,0.08); border:1px solid rgba(88,166,255,0.2);">
                        <div class="small fw-600 mb-1" style="color:var(--text-secondary);">Target Products:</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary" id="modalSelectedCountBadge">0 selected</span>
                            <span class="small text-muted">All selected products will be updated with the chosen unit.</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk_unit_id" class="form-label fw-600 mb-1" style="font-size:0.85rem;">Select Measurement Unit *</label>
                        <select name="unit_id" id="bulk_unit_id" class="form-select form-select-sm" required style="background:var(--input-bg); border-color:var(--input-border); color:var(--text-primary);">
                            <option value="">-- Choose Unit to Assign --</option>
                            @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ $u->is_default ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->symbol }}) {{ $u->description ? '— ' . $u->description : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small" style="color:var(--text-secondary);">
                            This will update the measurement unit for all checked products and reflect across Stock, Main Store, POS, and Reports.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent" id="btnSubmitBulkAssign">
                        <i class="bi bi-check-lg me-1"></i> Apply Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add / Edit Product Modal -->
@php
    $itemModalErr = $errors->any() && in_array(old('_modal'), ['add_item', 'edit_item'], true);
    $itemModalIsEdit = $itemModalErr && old('_modal') === 'edit_item';
@endphp
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addItemModalLabel">
                    <i class="bi bi-box-seam me-2" style="color:var(--accent-blue);"></i>
                    <span id="itemModalTitleText">{{ $itemModalIsEdit ? 'Edit Product' : 'New Product' }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addItemForm" method="POST" action="{{ $itemModalIsEdit ? old('_action', route('items.store')) : route('items.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="itemFormMethod" value="{{ $itemModalIsEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_modal" id="itemFormModalTag" value="{{ $itemModalIsEdit ? 'edit_item' : 'add_item' }}">
                <input type="hidden" name="_action" id="itemFormActionMirror" value="{{ $itemModalIsEdit ? old('_action') : '' }}">
                <div class="modal-body">
                    @if($itemModalErr)
                    <div class="alert alert-danger border-0 rounded-3 mb-3 py-2 px-3" style="font-size:.82rem;">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="itemInputName" class="form-control" value="{{ $itemModalErr ? old('item_name') : '' }}" placeholder="e.g. HP EliteBook 840 G8" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="itemInputCategory" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $itemModalErr && old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Measurement Unit</label>
                            <select name="unit_id" id="itemInputUnit" class="form-select">
                                <option value="">-- Select Unit (Default: Piece / pc) --</option>
                                @foreach($units as $u)
                                <option value="{{ $u->id }}" data-is-default="{{ $u->is_default ? '1' : '0' }}" {{ ($itemModalErr ? old('unit_id') == $u->id : $u->is_default) ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->symbol }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Brand</label>
                            <input type="text" name="brand" id="itemInputBrand" class="form-control" value="{{ $itemModalErr ? old('brand') : '' }}" placeholder="e.g. HP, Dell, Logitech" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Model</label>
                            <input type="text" name="model" id="itemInputModel" class="form-control" value="{{ $itemModalErr ? old('model') : '' }}" placeholder="e.g. 840 G8" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Warranty Period</label>
                            <input type="text" name="warranty_period" id="itemInputWarranty" class="form-control" value="{{ $itemModalErr ? old('warranty_period') : '' }}" placeholder="e.g. 1 Year, 6 Months" maxlength="50">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Specifications</label>
                            <textarea name="specification" id="itemInputSpec" class="form-control" rows="3" placeholder="Core i7, 16GB RAM, 512GB SSD...">{{ $itemModalErr ? old('specification') : '' }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Product Image (Optional, Max {{ \App\Models\Setting::get('max_upload_size_mb', 5) }}MB)</label>
                            <div id="editItemCurrentImageWrap" class="mb-2 d-none">
                                <img id="editItemCurrentImage" src="" alt="Product Image" class="rounded img-thumbnail" style="max-height:85px;">
                            </div>
                            <input type="file" name="image" id="itemInputImage" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif">
                            <small class="text-muted" style="font-size:.72rem;">Allowed formats: JPG, JPEG, PNG, GIF, WebP.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">
                        <i class="bi bi-save me-1"></i> <span id="btnSaveItemText">{{ $itemModalIsEdit ? 'Update Product' : 'Save Product' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewItemModal" tabindex="-1" aria-labelledby="viewItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="viewItemModalLabel">
                    <i class="bi bi-box-seam me-2" style="color:var(--accent-blue);"></i>
                    <span id="viewItemTitle">Product Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewItemBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading product details...</div>
            </div>
            <div class="modal-footer justify-content-between" style="border-top:1px solid var(--card-border);">
                <a href="#" id="btnViewItemFullPage" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Details
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-accent" id="btnViewItemToEdit">
                        <i class="bi bi-pencil me-1"></i> Edit Product
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Products Modal -->
<div class="modal fade" id="uploadProductsModal" tabindex="-1" aria-labelledby="uploadProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="uploadProductsModalLabel"><i class="bi bi-file-earmark-excel text-accent me-2"></i>Upload Products</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('items.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label" style="font-size:0.8rem;">Excel/CSV File *</label>
                        <input type="file" name="excel_file" id="excel_file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
                        <p class="mt-2 small text-muted">Select an Excel or CSV file to import products.</p>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('items.import-template') }}" class="btn btn-xs btn-outline-success">
                            <i class="bi bi-download me-1"></i> Download Template (.xlsx)
                        </a>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const selectedItemIds = new Set();

    function updateSelectionUI() {
        const count = selectedItemIds.size;
        $('.selected-count-badge').text(count);
        $('#selectedCountText').text(count);
        $('#modalSelectedCountBadge').text(count + ' selected');

        if (count > 0) {
            $('#bulkActionBar').removeClass('d-none').addClass('d-flex');
            $('#btnOpenBulkAssignTop').removeClass('d-none');
        } else {
            $('#bulkActionBar').removeClass('d-flex').addClass('d-none');
            $('#btnOpenBulkAssignTop').addClass('d-none');
        }

        // Update "Select All" checkbox state for current page
        const totalOnPage = $('.item-select-cb').length;
        const checkedOnPage = $('.item-select-cb:checked').length;
        if (totalOnPage > 0 && totalOnPage === checkedOnPage) {
            $('#selectAllItems').prop('checked', true).prop('indeterminate', false);
        } else if (checkedOnPage > 0) {
            $('#selectAllItems').prop('checked', false).prop('indeterminate', true);
        } else {
            $('#selectAllItems').prop('checked', false).prop('indeterminate', false);
        }
    }

    const table = $('#itemsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("items.data") }}',
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, responsivePriority: 1000, className: 'min-tablet-l text-center' },
            { data: 'no', name: 'no', orderable: false, searchable: false, responsivePriority: 1, className: 'all iter-col text-center' },
            { data: 'product', name: 'product', responsivePriority: 1, className: 'all primary-col' },
            { data: 'category', name: 'category', responsivePriority: 2, className: 'min-tablet-l' },
            { data: 'unit', name: 'unit', orderable: false, searchable: false, responsivePriority: 3, className: 'min-tablet-l' },
            { data: 'brand_model', name: 'brand_model', responsivePriority: 3, className: 'min-tablet-l' },
            { data: 'warranty', name: 'warranty', responsivePriority: 4, className: 'min-tablet-l' },
            { data: 'main_stock', name: 'main_stock', orderable: false, searchable: false, responsivePriority: 2, className: 'min-tablet-l' },
            { data: 'shop_stock', name: 'shop_stock', orderable: false, searchable: false, responsivePriority: 2, className: 'min-tablet-l' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, responsivePriority: 3, className: 'min-tablet-l text-end' }
        ],
        order: [[2, 'asc']],
        drawCallback: function() {
            // Restore checked state for items in selectedItemIds
            $('.item-select-cb').each(function() {
                if (selectedItemIds.has(this.value)) {
                    $(this).prop('checked', true);
                }
            });
            updateSelectionUI();
        }
    });

    // Handle individual row checkbox change
    $('#itemsTable').on('change', '.item-select-cb', function() {
        if (this.checked) {
            selectedItemIds.add(this.value);
        } else {
            selectedItemIds.delete(this.value);
        }
        updateSelectionUI();
    });

    // Handle "Select All" checkbox on current page
    $('#selectAllItems').on('change', function() {
        const isChecked = this.checked;
        $('.item-select-cb').each(function() {
            $(this).prop('checked', isChecked);
            if (isChecked) {
                selectedItemIds.add(this.value);
            } else {
                selectedItemIds.delete(this.value);
            }
        });
        updateSelectionUI();
    });

    // Clear selection
    $('#btnClearSelection').on('click', function() {
        selectedItemIds.clear();
        $('.item-select-cb').prop('checked', false);
        updateSelectionUI();
    });

    // Submit bulk assign unit form
    $('#bulkAssignUnitForm').on('submit', function(e) {
        e.preventDefault();

        if (selectedItemIds.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Products Selected',
                text: 'Please select at least one product to assign a unit.',
                background: '#161b22',
                color: '#e6edf3'
            });
            return;
        }

        const unitId = $('#bulk_unit_id').val();
        if (!unitId) {
            Swal.fire({
                icon: 'warning',
                title: 'Select Unit',
                text: 'Please choose a measurement unit.',
                background: '#161b22',
                color: '#e6edf3'
            });
            return;
        }

        const btn = $('#btnSubmitBulkAssign');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Applying...');

        $.ajax({
            url: '{{ route("items.bulk-assign-unit") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                item_ids: Array.from(selectedItemIds),
                unit_id: unitId
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Apply Unit');
                $('#bulkAssignUnitModal').modal('hide');

                selectedItemIds.clear();
                updateSelectionUI();
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Unit Assigned!',
                    text: res.message || 'Products updated successfully.',
                    timer: 2000,
                    showConfirmButton: false,
                    background: '#161b22',
                    color: '#e6edf3'
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Apply Unit');
                const msg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Failed to assign unit. Please try again.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg,
                    background: '#161b22',
                    color: '#e6edf3'
                });
            }
        });
    });

    let lastViewedItem = null;

    function escItem(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function openItemEditModal(data) {
        $('#itemModalTitleText').text('Edit Product');
        $('#btnSaveItemText').text('Update Product');
        $('#itemFormMethod').val('PUT');
        $('#itemFormModalTag').val('edit_item');
        $('#itemFormActionMirror').val(data.updateUrl);
        $('#addItemForm').attr('action', data.updateUrl);

        $('#itemInputName').val(data.name || '');
        $('#itemInputCategory').val(data.categoryId || '').trigger('change');
        $('#itemInputUnit').val(data.unitId || '').trigger('change');
        $('#itemInputBrand').val(data.brand || '');
        $('#itemInputModel').val(data.model || '');
        $('#itemInputWarranty').val(data.warranty || '');
        $('#itemInputSpec').val(data.spec || '');
        $('#itemInputImage').val('');

        if (data.imageUrl) {
            $('#editItemCurrentImage').attr('src', data.imageUrl);
            $('#editItemCurrentImageWrap').removeClass('d-none');
        } else {
            $('#editItemCurrentImageWrap').addClass('d-none');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('addItemModal')).show();
    }

    $('#btnNewItem').on('click', function() {
        const form = document.getElementById('addItemForm');
        if (form) form.reset();
        $('#itemModalTitleText').text('New Product');
        $('#btnSaveItemText').text('Save Product');
        $('#itemFormMethod').val('POST');
        $('#itemFormModalTag').val('add_item');
        $('#itemFormActionMirror').val('');
        $('#addItemForm').attr('action', '{{ route("items.store") }}');
        $('#itemInputName, #itemInputBrand, #itemInputModel, #itemInputWarranty, #itemInputSpec, #itemInputImage').val('');
        $('#itemInputCategory').val('').trigger('change');
        const defaultUnitVal = $('#itemInputUnit option[data-is-default="1"]').val() || '';
        $('#itemInputUnit').val(defaultUnitVal).trigger('change');
        $('#editItemCurrentImageWrap').addClass('d-none');
    });

    $(document).on('click', '.btn-edit-item', function() {
        const btn = $(this);
        openItemEditModal({
            name: btn.data('name'),
            categoryId: btn.data('category-id'),
            unitId: btn.data('unit-id'),
            brand: btn.data('brand'),
            model: btn.data('model'),
            warranty: btn.data('warranty'),
            spec: btn.data('spec'),
            imageUrl: btn.data('image-url'),
            updateUrl: btn.data('update-url')
        });
    });

    $(document).on('click', '.btn-view-item', function() {
        const url = $(this).data('show-url');
        $('#viewItemTitle').text('Product Details');
        $('#viewItemBody').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading product details...</div>');
        $('#btnViewItemFullPage').attr('href', url);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewItemModal')).show();

        $.getJSON(url).done(function(res) {
            lastViewedItem = {
                name: res.item_name,
                categoryId: res.category_id,
                unitId: res.unit_id,
                brand: res.brand,
                model: res.model,
                warranty: res.warranty_period,
                spec: res.specification,
                imageUrl: res.image_url,
                updateUrl: res.update_url
            };
            $('#viewItemTitle').text(res.item_name);

            const imgHtml = res.image_url
                ? `<div class="text-center mb-3"><img src="${escItem(res.image_url)}" alt="${escItem(res.item_name)}" class="rounded border p-1" style="max-height:150px;object-fit:contain;cursor:pointer;" onclick="if(typeof zoomProductImage==='function') zoomProductImage('${escItem(res.image_url)}', '${escItem(res.item_name)}')"></div>`
                : `<div class="text-center mb-3 py-3 bg-light rounded border text-muted"><i class="bi bi-image fs-3 d-block"></i><small>No image</small></div>`;

            let mainRows = '';
            if (res.main_stocks && res.main_stocks.length) {
                res.main_stocks.forEach(function(ms) {
                    mainRows += `<tr>
                        <td>TZS ${Number(ms.buying_price).toLocaleString()}</td>
                        <td>TZS ${Number(ms.selling_price).toLocaleString()}</td>
                        <td>${ms.stocked_quantity}</td>
                        <td><strong style="color:${ms.remaining_quantity > 0 ? '#16a34a' : '#dc2626'}">${ms.remaining_quantity}</strong></td>
                        <td class="text-muted">${escItem(ms.date_received)}</td>
                    </tr>`;
                });
            } else {
                mainRows = '<tr><td colspan="5" class="text-center text-muted py-2">Not in main store</td></tr>';
            }

            let shopRows = '';
            if (res.shop_stocks && res.shop_stocks.length) {
                res.shop_stocks.forEach(function(ss) {
                    shopRows += `<tr>
                        <td>${escItem(ss.shop_name)}</td>
                        <td>${ss.quantity}</td>
                        <td><strong style="color:${ss.is_low ? '#dc2626' : '#16a34a'}">${ss.remaining_quantity}</strong></td>
                        <td>TZS ${Number(ss.selling_price).toLocaleString()}</td>
                    </tr>`;
                });
            } else {
                shopRows = '<tr><td colspan="4" class="text-center text-muted py-2">Not in any shop yet</td></tr>';
            }

            $('#viewItemBody').html(`
                <div class="row g-3">
                    <div class="col-md-5">
                        ${imgHtml}
                        <table class="table table-borderless table-sm mb-0" style="font-size:.82rem;">
                            <tr><th class="text-muted" style="width:40%;">Category</th><td class="fw-600">${escItem(res.category_name)}</td></tr>
                            <tr><th class="text-muted">Unit</th><td>${escItem(res.unit_name)}</td></tr>
                            <tr><th class="text-muted">Brand</th><td>${escItem(res.brand || '—')}</td></tr>
                            <tr><th class="text-muted">Model</th><td>${escItem(res.model || '—')}</td></tr>
                            <tr><th class="text-muted">Warranty</th><td>${escItem(res.warranty_period || '—')}</td></tr>
                            <tr><th class="text-muted">Specs</th><td style="white-space:pre-wrap;">${escItem(res.specification || '—')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-7">
                        <div class="fw-700 small text-uppercase text-muted mb-2"><i class="bi bi-building me-1 text-warning"></i>Main Store Stock</div>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm mb-0" style="font-size:.78rem;">
                                <thead class="table-light"><tr><th>Buy</th><th>Sell</th><th>Stocked</th><th>Rem</th><th>Date</th></tr></thead>
                                <tbody>${mainRows}</tbody>
                            </table>
                        </div>
                        <div class="fw-700 small text-uppercase text-muted mb-2"><i class="bi bi-shop me-1 text-success"></i>Shop Stock</div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm mb-0" style="font-size:.78rem;">
                                <thead class="table-light"><tr><th>Shop</th><th>Qty</th><th>Rem</th><th>Sell Price</th></tr></thead>
                                <tbody>${shopRows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `);
        }).fail(function() {
            $('#viewItemBody').html('<div class="alert alert-danger mb-0">Failed to load product details.</div>');
        });
    });

    $('#btnViewItemToEdit').on('click', function() {
        if (!lastViewedItem) return;
        bootstrap.Modal.getInstance(document.getElementById('viewItemModal'))?.hide();
        openItemEditModal(lastViewedItem);
    });

    @if($itemModalErr)
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addItemModal')).show();
    @endif
});
</script>
@endpush
