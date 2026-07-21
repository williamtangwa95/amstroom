@extends('layouts.app')
@section('title', 'Products')
@section('page-title', 'Product Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Products</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">All Products</h5>
        <small style="color:var(--text-secondary);">Manage your product catalog</small>
    </div>
    <a href="{{ route('items.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Add Product</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="itemsTable">
            <thead>
                <tr><th>#</th><th>Product</th><th>Category</th><th>Brand/Model</th><th>Warranty</th><th>Main Stock</th><th class="no-sort">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $item->id }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;">{{ $item->item_name }}</div>
                        @if($item->specification)
                        <div style="font-size:.7rem;color:var(--text-secondary);">{{ Str::limit($item->specification, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        <span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;font-weight:600;">
                            {{ $item->category->category_name }}
                        </span>
                    </td>
                    <td style="font-size:.8rem;">{{ $item->brand }} {{ $item->model ? '/ '.$item->model : '' }}</td>
                    <td style="font-size:.78rem;color:var(--text-secondary);">{{ $item->warranty_period ?: '—' }}</td>
                    <td>
                        @php $stock = $item->mainStocks->sum('remaining_quantity'); @endphp
                        <span style="font-weight:700;color:{{ $stock > 0 ? '#3fb950' : '#e94560' }};">{{ $stock }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('items.show', $item) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('items.edit', $item) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('items.destroy', $item) }}" id="del-item-{{ $item->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="Delete this product?"
                                    data-form="del-item-{{ $item->id }}">
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
<script>$(()=>$('#itemsTable').DataTable())</script>
@endpush
