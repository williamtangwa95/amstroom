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
        <a href="{{ route('main-stock.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i>Add Stock</a>
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

@php
    $totalInitialCost = $stocks->sum(fn($st) => $st->stocked_quantity * $st->buying_price);
    $totalInitialSell = $stocks->sum(fn($st) => $st->stocked_quantity * $st->selling_price);
    $totalRemainingCost = $stocks->sum(fn($st) => $st->remaining_quantity * $st->buying_price);
    $totalRemainingSell = $stocks->sum(fn($st) => $st->remaining_quantity * $st->selling_price);
    $totalInitialQty = $stocks->sum('stocked_quantity');
    $totalRemainingQty = $stocks->sum('remaining_quantity');
@endphp

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 mb-4">
    <!-- Total Cost Value Card -->
    <div class="col">
        <div class="stat-card premium-stat-card p-2 d-flex align-items-center gap-2 h-100">
            <div class="stat-icon mb-0 d-flex align-items-center justify-content-center" style="background: rgba(2, 132, 199, 0.1); color: var(--accent-blue); width: 32px; height: 32px; font-size: 0.95rem; border-radius: 8px; flex-shrink: 0;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="overflow-hidden">
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalInitialCost, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Total Cost Value ({{ number_format($totalInitialQty) }} units)">Total Cost <span class="small">({{ number_format($totalInitialQty) }})</span></div>
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
                <div class="stat-value mb-0 text-success" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalInitialSell, 0) }}</div>
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
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalRemainingCost, 0) }}</div>
                <div class="stat-label text-muted text-truncate" style="font-size: 0.68rem; font-weight: 600;" title="Remain Stock Value ({{ number_format($totalRemainingQty) }} units)">Remain Value <span class="small">({{ number_format($totalRemainingQty) }})</span></div>
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
                <div class="stat-value mb-0" style="color: var(--accent-yellow) !important; font-size: 1.02rem; font-weight: 800; line-height: 1.2;">TZS {{ number_format($totalRemainingSell, 0) }}</div>
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
                <div class="stat-value mb-0" style="font-size: 1.02rem; font-weight: 800; line-height: 1.2;">{{ number_format($stocks->count()) }}</div>
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
        <button type="button" class="btn btn-xs btn-outline-danger px-3 py-1" id="bulkDisableBtn" style="font-size: .75rem;">Disable Custom Components</button>
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
                @foreach($stocks as $stock)
                <tr>
                    <td><input type="checkbox" class="stock-checkbox" data-id="{{ $stock->id }}" style="cursor:pointer;"></td>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($stock->item->image_path)
                                <img src="{{ asset('media/' . $stock->item->image_path) }}" alt="{{ $stock->item->item_name }}" class="rounded" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);">
                            @else
                                <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width: 32px; height: 32px; border: 1px solid var(--card-border);">
                                    <i class="bi bi-image" style="font-size: 0.8rem;"></i>
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:.83rem;">{{ $stock->item->item_name }}</div>
                                <div style="font-size:.7rem;color:var(--text-secondary);">{{ $stock->item->brand }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $stock->item->category->category_name }}</span></td>
                    <td style="font-size:.82rem;">TZS {{ number_format($stock->buying_price, 0) }}</td>
                    <td style="font-size:.82rem;">TZS {{ number_format($stock->selling_price, 0) }}</td>
                    <td style="font-size:.82rem;">{{ $stock->stocked_quantity }}</td>
                    <td>
                        <strong style="color:{{ $stock->remaining_quantity > 0 ? '#3fb950' : '#e94560' }};">
                            {{ $stock->remaining_quantity }}
                        </strong>
                    </td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $stock->date_received->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('main-stock.show', $stock) }}" class="btn btn-xs btn-outline-custom" title="View details"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('main-stock.edit', $stock) }}" class="btn btn-xs btn-outline-custom" title="Edit batch"><i class="bi bi-pencil"></i></a>
                            @if($stock->stocked_quantity == $stock->remaining_quantity)
                            <form action="{{ route('main-stock.destroy', $stock) }}" method="POST" class="d-inline delete-stock-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-danger confirm-delete-btn" title="Delete stock batch">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                            <div class="form-check form-switch ms-1 mb-0 d-flex align-items-center">
                                <input class="form-check-input toggle-components-btn" type="checkbox" data-id="{{ $stock->id }}" style="cursor:pointer; width: 30px; height: 16px;" 
                                    {{ $stock->allow_components ? 'checked' : '' }} title="Toggle custom components capability">
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
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
@endsection
@push('scripts')
<script>
    $(() => {
        $('#mainStockTable').DataTable();

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
