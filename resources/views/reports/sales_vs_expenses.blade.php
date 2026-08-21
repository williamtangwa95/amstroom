@extends('layouts.app')
@section('title', 'Sales vs Expenses')
@section('page-title', 'Revenue vs Expenses Analysis')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales vs Expenses</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.sales-vs-expenses') }}" class="row g-2 align-items-end">
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
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Sales Revenue</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalSales, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Approved Expenses</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalExpenses, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @if($netProfit >= 0)
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #3498db !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Net Profit (Surplus)</h6>
                    <h3 class="mb-0 fw-800 text-success" style="font-size: 1.6rem;">TZS {{ number_format($netProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #3498db; opacity: 0.25;"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Net Loss (Deficit)</h6>
                    <h3 class="mb-0 fw-800 text-danger" style="font-size: 1.6rem;">TZS {{ number_format($netProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-dash-circle-fill"></i></div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-pie-chart-fill me-2" style="color:#bc8cff;"></i>Visual Summary</div>
    <div class="card-body py-4 text-center">
        @php
            $totalAmount = $totalSales + $totalExpenses;
            $salesPercentage = $totalAmount > 0 ? ($totalSales / $totalAmount) * 100 : 50;
            $expensesPercentage = $totalAmount > 0 ? ($totalExpenses / $totalAmount) * 100 : 50;
        @endphp
        
        <h6 class="mb-3 text-secondary">Ratio: Sales vs Approved Expenses</h6>
        <div class="progress" style="height: 25px; border-radius: 6px; overflow: hidden; background-color: var(--card-border);">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $salesPercentage }}%" aria-valuenow="{{ $salesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                Sales: {{ number_format($salesPercentage, 1) }}%
            </div>
            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $expensesPercentage }}%" aria-valuenow="{{ $expensesPercentage }}" aria-valuemin="0" aria-valuemax="100">
                Expenses: {{ number_format($expensesPercentage, 1) }}%
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-6 text-end">
                <span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#198754;"></span>
                <span class="text-secondary small">Total Sales: <strong>TZS {{ number_format($totalSales) }}</strong></span>
            </div>
            <div class="col-6 text-start">
                <span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#dc3545;"></span>
                <span class="text-secondary small">Total Expenses: <strong>TZS {{ number_format($totalExpenses) }}</strong></span>
            </div>
        </div>
    </div>
</div>
@endsection
