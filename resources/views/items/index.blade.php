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
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#uploadProductsModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Products
        </button>
        <a href="{{ route('items.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Add Product</a>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="itemsTable">
            <thead>
                <tr><th>No</th><th>Product</th><th>Category</th><th>Brand/Model</th><th>Warranty</th><th>Main Stock</th><th class="no-sort">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
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
<script>$(()=>$('#itemsTable').DataTable())</script>
@endpush
