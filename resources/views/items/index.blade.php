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
        <a href="{{ route('items.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Add Product</a>
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
});
</script>
@endpush
