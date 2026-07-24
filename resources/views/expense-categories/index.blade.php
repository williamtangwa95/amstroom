@extends('layouts.app')
@section('title', 'Expense Categories')
@section('page-title', 'Expense Categories')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
<li class="breadcrumb-item active">Categories</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-tag-fill me-2" style="color:#bc8cff;"></i>Add Category</div>
            <div class="card-body">
                <form method="POST" action="{{ route('expense-categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g. Rent, Utilities, Transport">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-circle me-1"></i>Save Category</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-tags-fill me-2" style="color:#d29922;"></i>All Expense Categories</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Category Name</th>
                            <th>Created By</th>
                            <th>Date Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>{{ $category->creator->name ?? 'System' }}</td>
                            <td class="text-secondary small">{{ $category->created_at->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-custom btn-edit-category" data-id="{{ $category->id }}" data-name="{{ $category->name }}" title="Edit Category">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('expense-categories.destroy', $category) }}" onsubmit="return confirm('Are you sure you want to delete this category? All expenses under it will be deleted!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-custom text-danger" title="Delete Category"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">No categories created yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card-bg); border: 1px solid var(--card-border);">
            <div class="modal-header" style="border-bottom: 1px solid var(--card-border);">
                <h5 class="modal-title" style="color:var(--text-primary);"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600" style="color:var(--text-primary);">Category Name *</label>
                        <input type="text" name="name" id="editCategoryName" class="form-control" required placeholder="e.g. Rent, Utilities">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.btn-edit-category').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        // Update form action and input value
        $('#editCategoryForm').attr('action', `/expense-categories/${id}`);
        $('#editCategoryName').val(name);
        
        // Show modal
        const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        editModal.show();
    });
});
</script>
@endpush
@endsection
