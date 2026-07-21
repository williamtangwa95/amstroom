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
        <small style="color:var(--text-secondary);">Organize products into categories</small>
    </div>
    <a href="{{ route('categories.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Add Category</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="catTable">
            <thead>
                <tr><th>#</th><th>Category Name</th><th>Description</th><th>Products</th><th>Created</th><th class="no-sort">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td style="color:var(--text-secondary);font-size:.75rem;">{{ $cat->id }}</td>
                    <td><strong style="font-size:.85rem;">{{ $cat->category_name }}</strong></td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ Str::limit($cat->description, 60) ?: '—' }}</td>
                    <td><span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">{{ $cat->items_count }}</span></td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $cat->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('categories.edit', $cat) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" id="del-cat-{{ $cat->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="Delete category?"
                                    data-text="This will fail if the category has items."
                                    data-form="del-cat-{{ $cat->id }}">
                                    <i class="bi bi-trash" style="color:#e94560;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#catTable').DataTable())</script>
@endpush
