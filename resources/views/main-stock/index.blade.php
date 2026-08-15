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
        <a href="{{ route('main-stock.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i>Add Stock</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="color:#d29922;font-size:1.1rem;">TZS {{ number_format($totalValue, 0) }}</div>
            <div class="stat-label">Total Cost Value</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.1rem;">TZS {{ number_format($totalSellValue, 0) }}</div>
            <div class="stat-label">Total Sell Value</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ $stocks->count() }}</div>
            <div class="stat-label">Stock Batches</div>
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
                                <img src="{{ asset('storage/' . $stock->item->image_path) }}" alt="{{ $stock->item->item_name }}" class="rounded" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid var(--card-border);">
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
