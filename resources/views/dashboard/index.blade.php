@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
<li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
@php $user = auth()->user(); @endphp

{{-- ── OWNER DASHBOARD ── --}}
@if($user->isOwner())

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;">
                <i class="bi bi-shop"></i>
            </div>
            <div class="stat-value" style="color:#e94560;">{{ $totalShops }}</div>
            <div class="stat-label">Total Shops</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-value" style="color:#58a6ff;">{{ $totalEmployees }}</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;">
                <i class="bi bi-box-seam-fill"></i>
            </div>
            <div class="stat-value" style="color:#3fb950;">{{ $totalItems }}</div>
            <div class="stat-label">Products</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div class="stat-value" style="color:#bc8cff;">{{ $totalCategories }}</div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;">
                <i class="bi bi-building-fill"></i>
            </div>
            <div class="stat-value" style="color:#d29922;">TZS {{ number_format($mainStockValue, 0) }}</div>
            <div class="stat-label">Main Store Stock Value (Cost)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-value" style="color:#3fb950;">TZS {{ number_format($totalSales, 0) }}</div>
            <div class="stat-label">Total Sales Revenue</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-value" style="color:#58a6ff;">TZS {{ number_format(max(0,$profit), 0) }}</div>
            <div class="stat-label">Estimated Profit</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="stat-value" style="color:#d29922;">{{ $pendingRequests }}</div>
            <div class="stat-label">Pending Stock Requests</div>
            @if($pendingRequests > 0)
            <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-accent mt-2">Review Now</a>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="stat-value" style="color:#e94560;">{{ $totalDefects }}</div>
            <div class="stat-label">Defective Items Reported</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-value" style="color:#bc8cff;">{{ number_format($totalSalesCount) }}</div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up-arrow me-2" style="color:#3fb950;"></i>Sales — Last 6 Months</span>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shop me-2" style="color:#58a6ff;"></i>Shops Performance</div>
            <div class="card-body p-0">
                @foreach($shopsSummary as $shop)
                <div style="padding:.75rem 1rem; border-bottom:1px solid var(--card-border);">
                    <div style="font-size:.82rem;font-weight:600;">{{ $shop->shop_name }}</div>
                    <div style="font-size:.75rem;color:var(--text-secondary);">
                        {{ $shop->sales_count }} sales &bull;
                        TZS {{ number_format($shop->sales_sum_total_amount ?? 0, 0) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2" style="color:#3fb950;"></i>Recent Sales</span>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Shop</th><th>Seller</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td><span style="font-size:.78rem;">{{ $sale->shop->shop_name ?? 'Main Store (Owner)' }}</span></td>
                        <td><span style="font-size:.78rem;">{{ $sale->seller->name ?? 'System Owner' }}</span></td>
                        <td><strong style="color:#3fb950;font-size:.82rem;">TZS {{ number_format($sale->total_amount, 0) }}</strong></td>
                        <td><span style="font-size:.75rem;color:var(--text-secondary);">{{ $sale->sale_date->format('M d') }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-3" style="color:var(--text-secondary);font-size:.8rem;">No sales yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-fill me-2" style="color:#d29922;"></i>Pending Requests</span>
                <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-outline-custom">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Shop</th><th>Requester</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($recentRequests as $req)
                    <tr>
                        <td><span style="font-size:.78rem;">{{ $req->shop->shop_name }}</span></td>
                        <td><span style="font-size:.78rem;">{{ $req->requester->name }}</span></td>
                        <td><span style="font-size:.75rem;color:var(--text-secondary);">{{ $req->request_date->format('M d') }}</span></td>
                        <td>
                            <a href="{{ route('stock-requests.show', $req) }}" class="btn btn-xs btn-accent" style="font-size:.7rem;padding:.2rem .5rem;">Review</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-3" style="color:var(--text-secondary);font-size:.8rem;">No pending requests</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── SHOP ADMIN DASHBOARD ── --}}
@elseif($user->isShopAdmin())
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-layers-fill"></i></div>
            <div class="stat-value" style="color:#3fb950;">{{ number_format($shopStock) }}</div>
            <div class="stat-label">Units in Stock</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="color:#58a6ff;font-size:1.1rem;">TZS {{ number_format($shopSales, 0) }}</div>
            <div class="stat-label">Total Shop Sales</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-value" style="color:#d29922;">{{ $pendingRequests }}</div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value" style="color:#e94560;">{{ $lowStockCount }}</div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up-arrow me-2" style="color:#3fb950;"></i>Monthly Sales — {{ $shop->shop_name }}</div>
            <div class="card-body"><canvas id="salesChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-receipt me-2" style="color:#58a6ff;"></i>Recent Sales</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Seller</th><th>Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td style="font-size:.78rem;">{{ $sale->seller->name ?? '-' }}</td>
                        <td style="font-size:.78rem;color:#3fb950;font-weight:600;">TZS {{ number_format($sale->total_amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center py-3" style="color:var(--text-secondary);font-size:.8rem;">No sales yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── SELLER DASHBOARD ── --}}
@else
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-cart-check-fill"></i></div>
            <div class="stat-value" style="color:#3fb950;">{{ number_format($mySalesCount) }}</div>
            <div class="stat-label">My Transactions</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value" style="color:#58a6ff;font-size:1.1rem;">TZS {{ number_format($mySales, 0) }}</div>
            <div class="stat-label">My Total Sales</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-layers-fill"></i></div>
            <div class="stat-value" style="color:#3fb950;">{{ $availableStock }}</div>
            <div class="stat-label">Products Available</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-value" style="color:#e94560;">{{ $lowStockCount }}</div>
            <div class="stat-label">Low Stock Alerts</div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-receipt me-2" style="color:#3fb950;"></i>My Recent Sales</span>
                <a href="{{ route('sales.create') }}" class="btn btn-sm btn-accent"><i class="bi bi-plus-circle me-1"></i>New Sale</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Customer</th><th>Items</th><th>Amount</th><th>Payment</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td style="font-size:.82rem;">{{ $sale->customer_name ?: 'Walk-in' }}</td>
                        <td style="font-size:.78rem;color:var(--text-secondary);">{{ $sale->items->count() }} item(s)</td>
                        <td><strong style="color:#3fb950;">TZS {{ number_format($sale->total_amount, 0) }}</strong></td>
                        <td style="font-size:.75rem;">{{ str_replace('_',' ',ucfirst($sale->payment_method)) }}</td>
                        <td style="font-size:.75rem;color:var(--text-secondary);">{{ $sale->sale_date->format('M d, Y') }}</td>
                        <td><a href="{{ route('sales.receipt', $sale) }}" class="btn btn-xs btn-outline-custom" style="font-size:.7rem;">Receipt</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4" style="color:var(--text-secondary);">No sales recorded yet. <a href="{{ route('sales.create') }}" class="text-decoration-none" style="color:#e94560;">Make your first sale!</a></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
@if(isset($monthlySales) && count($monthlySales) > 0)
const salesCtx = document.getElementById('salesChart');
if (salesCtx) {
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlySales->pluck('month')) !!},
            datasets: [{
                label: 'Revenue (TZS)',
                data: {!! json_encode($monthlySales->pluck('total')) !!},
                backgroundColor: 'rgba(233,69,96,.7)',
                borderColor: '#e94560',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#8b949e', font: { family: 'Inter' } } }
            },
            scales: {
                x: { ticks: { color: '#8b949e' }, grid: { color: 'rgba(255,255,255,.04)' } },
                y: { ticks: { color: '#8b949e', callback: v => 'TZS ' + Number(v).toLocaleString() }, grid: { color: 'rgba(255,255,255,.04)' } }
            }
        }
    });
}
@endif
</script>
@endpush
