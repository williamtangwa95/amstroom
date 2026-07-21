@extends('layouts.app')
@section('title', $shop->shop_name)
@section('page-title', $shop->shop_name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Shops</a></li>
<li class="breadcrumb-item active">{{ $shop->shop_name }}</li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.1rem;">TZS {{ number_format($sales_total,0) }}</div>
            <div class="stat-label">Total Sales Revenue</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-layers-fill"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ number_format($stock_count) }}</div>
            <div class="stat-label">Units in Stock</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value" style="color:#bc8cff;">{{ $shop->users->count() }}</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba({{ $shop->status==='active' ? '63,185,80' : '139,148,158' }},.12);color:{{ $shop->status==='active' ? '#3fb950' : '#8b949e' }};"><i class="bi bi-circle-fill"></i></div>
            <div class="stat-value" style="font-size:1rem;color:{{ $shop->status==='active' ? '#3fb950' : '#8b949e' }};">{{ ucfirst($shop->status) }}</div>
            <div class="stat-label">Shop Status</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-people-fill me-2" style="color:#58a6ff;"></i>Employees</div>
            <div class="card-body p-0">
                @forelse($shop->users as $employee)
                <div style="padding:.75rem 1rem;border-bottom:1px solid var(--card-border);display:flex;align-items:center;gap:.65rem;">
                    <div style="width:32px;height:32px;background:var(--input-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-person-fill" style="color:var(--text-secondary);font-size:.85rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:600;">{{ $employee->name }}</div>
                        <div style="font-size:.72rem;color:var(--text-secondary);">{{ str_replace('_',' ',ucfirst($employee->role)) }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="color:var(--text-secondary);font-size:.82rem;">No employees assigned</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-layers-fill me-2" style="color:#3fb950;"></i>Shop Stock</span>
                <a href="{{ route('shop-stock.index') }}?shop_id={{ $shop->id }}" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Category</th><th>Qty</th><th>Selling Price</th></tr></thead>
                    <tbody>
                    @forelse($shop->shopStocks->take(8) as $stock)
                    <tr class="{{ $stock->isLowStock() ? 'low-stock-row' : '' }}">
                        <td style="font-size:.82rem;font-weight:500;">{{ $stock->item->item_name }}</td>
                        <td style="font-size:.75rem;color:var(--text-secondary);">{{ $stock->item->category->category_name }}</td>
                        <td>
                            <span style="font-weight:700;color:{{ $stock->isLowStock() ? '#e94560' : '#3fb950' }};">
                                {{ $stock->remaining_quantity }}
                            </span>
                            @if($stock->isLowStock())
                            <i class="bi bi-exclamation-triangle-fill" style="color:#e94560;font-size:.7rem;"></i>
                            @endif
                        </td>
                        <td style="font-size:.82rem;">TZS {{ number_format($stock->selling_price, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-3" style="color:var(--text-secondary);font-size:.8rem;">No stock available</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('shops.edit', $shop) }}" class="btn btn-accent"><i class="bi bi-pencil me-1"></i>Edit Shop</a>
    <a href="{{ route('shops.index') }}" class="btn btn-outline-custom ms-2">Back</a>
</div>
@endsection
