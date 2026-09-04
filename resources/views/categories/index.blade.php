@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Product Categories')
@section('breadcrumb')
<li class="breadcrumb-item active">Categories</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Categories</h5>
        <small style="color:var(--text-secondary);">Manage product categories and subcategories</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadCategoriesModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Categories
        </button>
        <a href="{{ route('categories.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Add Category</a>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="catTable">
            <thead>
                <tr><th>No</th><th>Category Name</th><th>Description</th><th>Products</th><th>Created</th><th class="no-sort">Actions</th></tr>
            </thead>
            <tbody></tbody>
        </table>
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
});
</script>
@endpush
