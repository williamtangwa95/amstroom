@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Product Categories')
@section('breadcrumb')
<li class="breadcrumb-item active">Categories</li>
@endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h5 class="mb-0 fw-700">Categories</h5>
        <small style="color:var(--text-secondary);">Manage product categories and subcategories</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadCategoriesModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Categories
        </button>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addCategoryModal" id="btnNewCategory">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
    </div>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="catTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Category Name</th>
                    <th data-priority="3" class="min-tablet-l">Description</th>
                    <th data-priority="2" class="min-tablet-l">Products</th>
                    <th data-priority="4" class="min-tablet-l">Created</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Category Modal -->
@php
    $catModalErr = $errors->any() && in_array(old('_modal'), ['add_category', 'edit_category'], true);
    $catModalIsEdit = $catModalErr && old('_modal') === 'edit_category';
@endphp
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="addCategoryModalLabel">
                    <i class="bi bi-tags me-2" style="color:var(--accent-blue);"></i>
                    <span id="catModalTitleText">{{ $catModalIsEdit ? 'Edit Category' : 'New Category' }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCategoryForm" method="POST" action="{{ $catModalIsEdit ? old('_action', route('categories.store')) : route('categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="catFormMethod" value="{{ $catModalIsEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_modal" id="catFormModalTag" value="{{ $catModalIsEdit ? 'edit_category' : 'add_category' }}">
                <input type="hidden" name="_action" id="catFormActionMirror" value="{{ $catModalIsEdit ? old('_action') : '' }}">
                <div class="modal-body">
                    @if($catModalErr)
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
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" id="catInputName" class="form-control" value="{{ $catModalErr ? old('category_name') : '' }}" placeholder="e.g. Laptop, Accessories, Printers" required maxlength="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Description</label>
                            <textarea name="description" id="catInputDescription" class="form-control" rows="3" placeholder="Brief description of this category...">{{ $catModalErr ? old('description') : '' }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent" id="btnSaveCategory">
                        <i class="bi bi-save me-1"></i> <span id="btnSaveCategoryText">{{ $catModalIsEdit ? 'Update Category' : 'Save Category' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Category Modal -->
<div class="modal fade" id="viewCategoryModal" tabindex="-1" aria-labelledby="viewCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="viewCategoryModalLabel">
                    <i class="bi bi-tags me-2" style="color:var(--accent-blue);"></i>
                    <span id="viewCatTitle">Category Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                    <div>
                        <h6 class="mb-1 fw-700" id="viewCatName">—</h6>
                        <small class="text-muted">Created: <span id="viewCatCreated">—</span></small>
                    </div>
                    <span class="badge px-2 py-1" id="viewCatProducts" style="background:rgba(88,166,255,.15);color:#0077b6;font-size:.78rem;">0 available / 0 total</span>
                </div>
                <div>
                    <div class="small fw-600 text-muted mb-1">Description</div>
                    <div id="viewCatDescription" style="font-size:.85rem;white-space:pre-wrap;">—</div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-accent" id="btnViewCatToEdit">
                    <i class="bi bi-pencil me-1"></i> Edit Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Categories Modal -->
<div class="modal fade" id="uploadCategoriesModal" tabindex="-1" aria-labelledby="uploadCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg); border:1px solid var(--card-border); color:var(--text-primary);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="uploadCategoriesModalLabel"><i class="bi bi-file-earmark-excel text-accent me-2"></i>Upload Categories</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label" style="font-size:0.8rem;">Excel/CSV File *</label>
                        <input type="file" name="excel_file" id="excel_file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
                        <p class="mt-2 small text-muted">Select an Excel or CSV file to import product categories.</p>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('categories.import-template') }}" class="btn btn-xs btn-outline-success">
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
    $('#catTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("categories.data") }}',
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'category_name', name: 'category_name' },
            { data: 'description', name: 'description' },
            { data: 'products', name: 'products', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });

    let lastViewedCategory = null;

    function openCategoryEditModal(data) {
        $('#catModalTitleText').text('Edit Category');
        $('#btnSaveCategoryText').text('Update Category');
        $('#catFormMethod').val('PUT');
        $('#catFormModalTag').val('edit_category');
        $('#catFormActionMirror').val(data.updateUrl);
        $('#addCategoryForm').attr('action', data.updateUrl);
        $('#catInputName').val(data.name || '');
        $('#catInputDescription').val(data.description || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal')).show();
    }

    $('#btnNewCategory').on('click', function() {
        const form = document.getElementById('addCategoryForm');
        if (form) form.reset();
        $('#catModalTitleText').text('New Category');
        $('#btnSaveCategoryText').text('Save Category');
        $('#catFormMethod').val('POST');
        $('#catFormModalTag').val('add_category');
        $('#catFormActionMirror').val('');
        $('#addCategoryForm').attr('action', '{{ route("categories.store") }}');
        $('#catInputName').val('');
        $('#catInputDescription').val('');
    });

    $(document).on('click', '.btn-edit-category', function() {
        const btn = $(this);
        openCategoryEditModal({
            id: btn.data('id'),
            name: btn.data('name'),
            description: btn.data('description'),
            updateUrl: btn.data('update-url')
        });
    });

    $(document).on('click', '.btn-view-category', function() {
        const btn = $(this);
        lastViewedCategory = {
            id: btn.data('id'),
            name: btn.data('name'),
            description: btn.data('description') || '',
            available: btn.data('available') || 0,
            total: btn.data('total') || 0,
            created: btn.data('created') || '—',
            updateUrl: btn.data('update-url')
        };

        $('#viewCatTitle').text(lastViewedCategory.name);
        $('#viewCatName').text(lastViewedCategory.name);
        $('#viewCatCreated').text(lastViewedCategory.created);
        $('#viewCatProducts').text(`${lastViewedCategory.available} available / ${lastViewedCategory.total} total`);
        $('#viewCatDescription').text(lastViewedCategory.description || 'No description provided.');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewCategoryModal')).show();
    });

    $('#btnViewCatToEdit').on('click', function() {
        if (!lastViewedCategory) return;
        bootstrap.Modal.getInstance(document.getElementById('viewCategoryModal'))?.hide();
        openCategoryEditModal(lastViewedCategory);
    });

    @if($catModalErr)
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addCategoryModal')).show();
    @endif
});
</script>
@endpush
