@extends('layouts.app')
@section('title', 'Sales Report')
@section('page-title', 'Sales Performance Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales Report</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" disabled>
                    <option value="{{ auth()->user()->shop_id }}" selected>{{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</option>
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Item</label>
                <select name="item_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Items</option>
                    @foreach($items as $i)
                    <option value="{{ $i->id }}" {{ request('item_id') == $i->id ? 'selected' : '' }}>{{ $i->item_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($period === 'custom')
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.3rem;">TZS {{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-label">Total Revenue ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,.12);color:#ffc107;"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value" style="color:#ffc107;font-size:1.3rem;">TZS {{ number_format($totalProfit, 0) }}</div>
            <div class="stat-label">Total Profit ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-receipt"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ $sales->count() }}</div>
            <div class="stat-label">Total Sales Transactions</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shop me-2" style="color:#bc8cff;"></i>Revenue & Profit Breakdown by Shop</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>No</th><th>Shop Name</th><th>Total Transactions</th><th>Revenue Generated</th><th>Profit Generated</th></tr></thead>
            <tbody>
            @foreach($salesByShop as $sbs)
            <tr>
                <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $sbs->shop ? $sbs->shop->shop_name : ($sbs->shop_id === null ? 'Main Store (Owner)' : 'Deleted Shop') }}</td>
                <td>{{ number_format($sbs->count) }}</td>
                <td><strong style="color:#3fb950;">TZS {{ number_format($sbs->revenue, 0) }}</strong></td>
                <td><strong style="color:#ffc107;">TZS {{ number_format($sbs->profit, 0) }}</strong></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-check me-2" style="color:#58a6ff;"></i>Sales Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="salesReportLogTable">
            <thead><tr><th>No</th><th>Date</th><th>Shop</th><th>Seller</th><th>Customer</th><th>Items Sold</th><th>Method</th><th>Revenue</th><th>Profit</th></tr></thead>
            <tbody>
            @foreach($sales as $sl)
            <tr>
                <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                <td style="font-size:.75rem;color:var(--text-secondary);">{{ $sl->sale_date->format('M d, Y') }}</td>
                <td style="font-size:.82rem;">{{ $sl->shop?->shop_name ?? 'Main Store (Owner)' }}</td>
                <td style="font-size:.82rem;">{{ $sl->seller->name }}</td>
                <td style="font-size:.82rem;">{{ $sl->customer_name ?: 'Walk-in' }}</td>
                <td style="font-size:.78rem;">
                    @php
                        $displayItems = $itemId ? $sl->items->where('item_id', $itemId) : $sl->items;
                    @endphp
                    @foreach($displayItems as $item)
                        <div style="font-size:.78rem;line-height:1.2;">
                            {{ $item->item?->item_name ?? 'Unknown Item' }} (x{{ $item->quantity }})
                        </div>
                    @endforeach
                </td>
                <td style="font-size:.78rem;">{{ str_replace('_',' ',ucfirst($sl->payment_method)) }}</td>
                <td><strong style="color:#3fb950;">TZS {{ number_format($sl->filtered_revenue, 0) }}</strong></td>
                <td><strong style="color:#ffc107;">TZS {{ number_format($sl->filtered_profit, 0) }}</strong></td>
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
    $('#salesReportLogTable').DataTable({
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'btn btn-sm btn-accent me-2',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                title: 'Sales Report'
            },
            {
                extend: 'pdfHtml5',
                className: 'btn btn-sm btn-outline-custom',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                title: 'Sales Report'
            }
        ]
    });
});
</script>
@endpush
