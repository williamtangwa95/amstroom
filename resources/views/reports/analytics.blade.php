@extends('layouts.app')
@section('title', 'Analytics & Insights')
@section('page-title', 'Analytics & Insights')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reports.sales') }}">Reports</a></li>
<li class="breadcrumb-item active">Analytics</li>
@endsection

@push('styles')
<style>
:root {
    --an-blue:   #0088cc;
    --an-green:  #10b981;
    --an-amber:  #f59e0b;
    --an-red:    #ef4444;
    --an-purple: #8b5cf6;
    --an-teal:   #14b8a6;
}
.an-filter-bar {
    background:#fff;border:1px solid var(--card-border);
    border-radius:14px;padding:.85rem 1.2rem;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.kpi-card {
    background:#fff;border-radius:16px;border:1px solid var(--card-border);
    padding:1.2rem 1.4rem;box-shadow:0 2px 10px rgba(0,0,0,.05);
    transition:transform .2s,box-shadow .2s;position:relative;overflow:hidden;
}
.kpi-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08);}
.kpi-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:4px;
    background:var(--kpi-color,var(--an-blue));border-radius:16px 16px 0 0;
}
.kpi-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.kpi-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-secondary);}
.kpi-value{font-size:1.3rem;font-weight:800;color:var(--text-primary);line-height:1.2;}
.kpi-sub{font-size:.72rem;color:var(--text-secondary);margin-top:.15rem;}
.an-section-title{
    font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
    color:var(--text-secondary);padding-bottom:.4rem;border-bottom:2px solid var(--card-border);
    margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;
}
.an-section-title i{font-size:1rem;}
.badge-fast    {background:rgba(16,185,129,.12);color:#059669;border:1px solid rgba(16,185,129,.25);}
.badge-slow    {background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.25);}
.badge-stop    {background:rgba(239,68,68,.12);color:#dc2626;border:1px solid rgba(239,68,68,.25);}
.badge-moderate{background:rgba(14,165,233,.12);color:#0284c7;border:1px solid rgba(14,165,233,.25);}
.margin-pill{display:inline-block;padding:.15rem .6rem;border-radius:20px;font-size:.7rem;font-weight:700;}
.margin-high    {background:#d1fae5;color:#065f46;}
.margin-moderate{background:#fef9c3;color:#92400e;}
.margin-low     {background:#fee2e2;color:#991b1b;}
.suggestion-card{
    border-radius:12px;padding:.85rem 1rem;background:#fff;
    border:1px solid var(--card-border);border-left-width:4px;
    transition:transform .15s;
}
.suggestion-card:hover{transform:translateX(3px);}
.suggestion-card.critical{border-left-color:var(--an-red);}
.suggestion-card.warning {border-left-color:var(--an-amber);}
.suggestion-card.ok      {border-left-color:#94a3b8;}
.staff-bar-wrap{background:#f1f5f9;border-radius:20px;height:8px;overflow:hidden;}
.staff-bar-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,var(--an-blue),var(--an-teal));}
.chart-card{background:#fff;border:1px solid var(--card-border);border-radius:16px;padding:1.2rem 1.4rem;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.chart-card canvas{max-height:300px;}
.an-card{background:#fff;border:1px solid var(--card-border);border-radius:16px;padding:1.3rem;box-shadow:0 2px 10px rgba(0,0,0,.05);}
.an-card .table{font-size:.78rem;}
.an-card .table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-secondary);font-weight:700;background:#f8fafc;}
.velocity-bar{height:6px;border-radius:4px;background:#e2e8f0;overflow:hidden;}
.velocity-bar-fill{height:100%;border-radius:4px;}
</style>
@endpush

@section('content')
@php $user = auth()->user(); @endphp

{{-- FILTER BAR --}}
<div class="an-filter-bar mb-4">
    <form method="GET" action="{{ route('reports.analytics') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:.73rem;">Period</label>
            <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="daily"   {{ $period==='daily'   ?'selected':'' }}>Today</option>
                <option value="monthly" {{ $period==='monthly' ?'selected':'' }}>This Month</option>
                <option value="yearly"  {{ $period==='yearly'  ?'selected':'' }}>This Year</option>
                <option value="custom"  {{ $period==='custom'  ?'selected':'' }}>Custom Range</option>
            </select>
        </div>
        @if($user->isOwner())
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:.73rem;">Shop</label>
            <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Shops</option>
                <option value="owner" {{ $shopId==='owner'?'selected':'' }}>Main Store (Owner)</option>
                @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ $shopId==$s->id&&$shopId!=='owner'?'selected':'' }}>{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($period==='custom')
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:.73rem;">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from',$dateFrom) }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:.73rem;">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to',$dateTo) }}">
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
        </div>
        @endif
        <div class="col-auto ms-auto d-flex align-items-center">
            <span class="badge" style="background:rgba(0,136,204,.1);color:var(--an-blue);font-size:.7rem;padding:.35rem .75rem;border-radius:20px;font-weight:600;">
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </span>
        </div>
    </form>
</div>

{{-- KPI ROW --}}
<div class="row g-3 mb-4">
    @php
    $kpis = [
        ['label'=>'Total Revenue',   'value'=>'TZS '.number_format($totalRevenue,0),    'icon'=>'bi-cash-stack',      'color'=>'var(--an-blue)',   'sub'=>number_format($totalTransactions).' transactions'],
        ['label'=>'Total Profit',    'value'=>'TZS '.number_format($totalProfit,0),     'icon'=>'bi-graph-up-arrow',  'color'=>'var(--an-green)',  'sub'=>($totalRevenue>0?number_format(($totalProfit/$totalRevenue)*100,1).'% margin':'—')],
        ['label'=>'Total Cost',      'value'=>'TZS '.number_format($totalCost,0),       'icon'=>'bi-box-seam',        'color'=>'var(--an-amber)',  'sub'=>'Cost of goods sold'],
        ['label'=>'Avg Order Value', 'value'=>'TZS '.number_format($avgOrderValue,0),   'icon'=>'bi-receipt',         'color'=>'var(--an-purple)', 'sub'=>'Per transaction'],
        ['label'=>'Products Sold',   'value'=>number_format($byItem->count()),           'icon'=>'bi-tags-fill',       'color'=>'var(--an-teal)',   'sub'=>$fastItems->count().' fast-moving'],
        ['label'=>'Active Staff',    'value'=>number_format($staffPerformance->count()), 'icon'=>'bi-people-fill',     'color'=>'var(--an-red)',    'sub'=>'Contributing sellers'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-6 col-md-4 col-lg-2">
        <div class="kpi-card" style="--kpi-color:{{ $kpi['color'] }}">
            <div class="d-flex align-items-start gap-2 mb-2">
                <div class="kpi-icon" style="background:{{ $kpi['color'] }}1a;color:{{ $kpi['color'] }};justify-content:center;">
                    <i class="bi {{ $kpi['icon'] }}"></i>
                </div>
            </div>
            <div class="kpi-label">{{ $kpi['label'] }}</div>
            <div class="kpi-value">{{ $kpi['value'] }}</div>
            <div class="kpi-sub">{{ $kpi['sub'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- CHARTS ROW --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="an-section-title">
                <i class="bi bi-activity" style="color:var(--an-blue);"></i>
                Daily Revenue Trend — Last 60 Days + 30-Day Forecast
            </div>
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <div class="an-section-title">
                <i class="bi bi-pie-chart-fill" style="color:var(--an-purple);"></i> Revenue by Category
            </div>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

{{-- PRODUCT VELOCITY --}}
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-lightning-charge-fill" style="color:var(--an-green);"></i> Fast-Moving Products
            </div>
            @forelse($fastItems->take(10) as $item)
            @php $maxQ = $fastItems->max('qty_sold') ?: 1; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:.78rem;font-weight:600;">{{ Str::limit($item->item_name,28) }}</span>
                    <span class="badge badge-fast" style="font-size:.65rem;border-radius:8px;">{{ number_format($item->qty_sold) }} units</span>
                </div>
                <div class="velocity-bar">
                    <div class="velocity-bar-fill" style="width:{{ ($item->qty_sold/$maxQ)*100 }}%;background:var(--an-green);"></div>
                </div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.15rem;">TZS {{ number_format($item->revenue,0) }} &bull; {{ $item->category }}</div>
            </div>
            @empty
            <p class="text-muted text-center" style="font-size:.8rem;padding:2rem 0;">No sales data for this period.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-hourglass-split" style="color:var(--an-amber);"></i> Slow-Moving Products
            </div>
            @forelse($slowItems->take(10) as $item)
            @php $maxQ2 = $slowItems->max('qty_sold') ?: 1; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:.78rem;font-weight:600;">{{ Str::limit($item->item_name,28) }}</span>
                    <span class="badge badge-slow" style="font-size:.65rem;border-radius:8px;">{{ number_format($item->qty_sold) }} units</span>
                </div>
                <div class="velocity-bar">
                    <div class="velocity-bar-fill" style="width:{{ max(5,($item->qty_sold/$maxQ2)*100) }}%;background:var(--an-amber);"></div>
                </div>
                <div style="font-size:.65rem;color:var(--text-secondary);margin-top:.15rem;">TZS {{ number_format($item->revenue,0) }} &bull; {{ $item->category }}</div>
            </div>
            @empty
            <p class="text-muted text-center" style="font-size:.8rem;padding:2rem 0;">No data.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-slash-circle-fill" style="color:var(--an-red);"></i> Stop Ordering (Zero Sales)
            </div>
            @forelse($stopItems as $item)
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f1f5f9;">
                <div>
                    <div style="font-size:.78rem;font-weight:600;">{{ Str::limit($item->item_name,30) }}</div>
                    <div style="font-size:.65rem;color:var(--text-secondary);">{{ $item->category }}</div>
                </div>
                <span class="badge badge-stop" style="font-size:.65rem;border-radius:8px;">{{ number_format($item->qty_in_stock) }} in stock</span>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size:2rem;"></i>
                <p class="text-muted mt-2" style="font-size:.8rem;">All stocked items are selling!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- PROFIT MARGIN + STOCK SUGGESTIONS --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-currency-exchange" style="color:var(--an-teal);"></i> Profit Margin Analysis
                <div class="ms-auto d-flex gap-2 flex-wrap" style="font-size:.68rem;">
                    <span class="margin-pill margin-high">High ≥25%: {{ $marginSummary['high'] }}</span>
                    <span class="margin-pill margin-moderate">Mid 10-25%: {{ $marginSummary['moderate'] }}</span>
                    <span class="margin-pill margin-low">Low &lt;10%: {{ $marginSummary['low'] }}</span>
                </div>
            </div>
            <div class="mb-3">
                <canvas id="marginChart" style="max-height:160px;"></canvas>
            </div>
            <div style="max-height:280px;overflow-y:auto;">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Revenue</th><th>Profit</th><th>Margin</th></tr></thead>
                    <tbody>
                    @forelse($marginItems->take(25) as $idx => $item)
                    <tr>
                        <td class="text-muted">{{ $idx+1 }}</td>
                        <td style="font-weight:600;">{{ Str::limit($item->item_name,24) }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->category }}</td>
                        <td>{{ number_format($item->revenue,0) }}</td>
                        <td>{{ number_format($item->profit,0) }}</td>
                        <td><span class="margin-pill margin-{{ $item->margin_tier }}">{{ number_format($item->margin_pct,1) }}%</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No sales data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-boxes" style="color:var(--an-purple);"></i> Stock Reorder Suggestions
                <div class="ms-auto d-flex gap-1">
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.65rem;border-radius:4px;line-height:1.5;" onclick="printStockSuggestions()" title="Print suggestions">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:.65rem;border-radius:4px;line-height:1.5;" onclick="shareWhatsAppSuggestions()" title="Share on WhatsApp">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:.65rem;border-radius:4px;line-height:1.5;" onclick="shareEmailSuggestions()" title="Share via Email">
                        <i class="bi bi-envelope"></i> Email
                    </button>
                </div>
            </div>
            @forelse($stockSuggestions as $s)
            <div class="suggestion-card {{ $s->urgency }} mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.8rem;font-weight:700;">{{ Str::limit($s->item_name,30) }}</div>
                        <div style="font-size:.67rem;color:var(--text-secondary);">{{ $s->category }} &bull; {{ $s->daily_rate }}/day sold</div>
                    </div>
                    @if($s->urgency==='critical')
                    <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:.65rem;border-radius:8px;">🔴 Critical</span>
                    @else
                    <span class="badge" style="background:#fef9c3;color:#92400e;font-size:.65rem;border-radius:8px;">🟡 Low Stock</span>
                    @endif
                </div>
                <div class="d-flex gap-3 mt-1 flex-wrap" style="font-size:.68rem;color:var(--text-secondary);">
                    <span>📦 {{ number_format($s->current_stock) }} in stock</span>
                    <span>⏳ ~{{ $s->days_left!==null ? $s->days_left.' days left' : 'Unknown' }}</span>
                    <span>➕ Order: <strong>{{ number_format($s->suggest_qty) }} units</strong></span>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="bi bi-check2-all text-success" style="font-size:2.5rem;"></i>
                <p class="text-muted mt-2" style="font-size:.82rem;">All products have sufficient stock for 7+ days.</p>
            </div>
            @endforelse
        </div>
    </div>
{{-- EXPENSES & SALES VS EXPENSES ANALYSIS --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="an-card h-100">
            <div class="an-section-title">
                <i class="bi bi-wallet2" style="color:var(--an-red);"></i> Financial Summary & Sales vs Expenses
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size:.7rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;">Gross Profit</div>
                            <div class="fw-800 text-success" style="font-size:1.15rem;">TZS {{ number_format($totalProfit,0) }}</div>
                        </div>
                        <div class="rounded-circle p-2 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded border bg-light d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size:.7rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;">Total Expenses</div>
                            <div class="fw-800 text-danger" style="font-size:1.15rem;">TZS {{ number_format($totalExpenses,0) }}</div>
                        </div>
                        <div class="rounded-circle p-2 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-wallet-fill"></i></div>
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="p-3 rounded border {{ $netProfitValue >= 0 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-danger-subtle border-danger-subtle text-danger' }} d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color: inherit;">Net Profit / Loss (Sales vs Expenses)</div>
                            <div class="fw-900" style="font-size:1.35rem; color: inherit;">TZS {{ number_format($netProfitValue,0) }}</div>
                            <div style="font-size:.68rem;opacity:.85; color: inherit;">
                                @if($netProfitValue >= 0)
                                    🎉 Net margin after expenses: {{ $totalRevenue > 0 ? number_format(($netProfitValue / $totalRevenue) * 100, 1) : 0 }}%
                                @else
                                    ⚠️ Operating expenses exceed sales profit. Negative net margin.
                                @endif
                            </div>
                        </div>
                        <div class="rounded-circle p-2 bg-white d-flex align-items-center justify-content-center text-dark" style="width:40px;height:40px;box-shadow:0 2px 4px rgba(0,0,0,.05);"><i class="bi {{ $netProfitValue >= 0 ? 'bi-shield-check text-success' : 'bi-shield-exclamation text-danger' }}"></i></div>
                    </div>
                </div>
            </div>

            <div style="max-height:220px;overflow-y:auto;">
                <h6 style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text-secondary);margin-bottom:.5rem;">Operating Expenses by Category</h6>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expensesByCategory as $exp)
                        <tr>
                            <td style="font-weight:600;">{{ $exp->category_name }}</td>
                            <td>{{ $exp->count }} records</td>
                            <td class="text-end fw-700 text-danger">TZS {{ number_format($exp->total_amount,0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">No expenses recorded for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="chart-card h-100" style="min-height:350px;">
            <div class="an-section-title">
                <i class="bi bi-pie-chart-fill" style="color:var(--an-red);"></i> Operating Expenses Breakdown
            </div>
            <canvas id="expensesChart" style="min-height:260px;"></canvas>
        </div>
    </div>
</div>

{{-- STAFF PERFORMANCE --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="an-card">
            <div class="an-section-title">
                <i class="bi bi-person-badge-fill" style="color:var(--an-blue);"></i> Staff Performance Summary
            </div>
            @php $maxStaffRev = $staffPerformance->max('revenue') ?: 1; @endphp
            @forelse($staffPerformance as $staff)
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--an-blue),var(--an-teal));color:#fff;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;">
                            {{ strtoupper(substr($staff->seller_name,0,1)) }}
                        </div>
                        <div>
                            <div style="font-size:.82rem;font-weight:700;">{{ $staff->seller_name }}</div>
                            <div style="font-size:.66rem;color:var(--text-secondary);">{{ $staff->txn_count }} sales &bull; Avg TZS {{ number_format($staff->avg_sale,0) }}/sale</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:.82rem;font-weight:700;color:var(--an-blue);">TZS {{ number_format($staff->revenue,0) }}</div>
                        <div style="font-size:.67rem;color:var(--an-green);">+TZS {{ number_format($staff->profit,0) }} profit</div>
                    </div>
                </div>
                <div class="staff-bar-wrap">
                    <div class="staff-bar-fill" style="width:{{ ($staff->revenue/$maxStaffRev)*100 }}%;"></div>
                </div>
            </div>
            @empty
            <p class="text-muted text-center py-3" style="font-size:.8rem;">No staff sales data for this period.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card" style="min-height:300px;">
            <div class="an-section-title">
                <i class="bi bi-bar-chart-fill" style="color:var(--an-blue);"></i> Staff Revenue vs Profit
            </div>
            <canvas id="staffChart" style="min-height:230px;"></canvas>
        </div>
    </div>
</div>

{{-- FULL PRODUCT TABLE --}}
<div class="an-card mb-4">
    <div class="an-section-title">
        <i class="bi bi-table" style="color:var(--text-secondary);"></i> All Products — Velocity & Margin Detail
    </div>
    <div style="overflow-x:auto;">
        <table class="table table-sm" id="allProductsTable">
            <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Units Sold</th><th>Revenue (TZS)</th><th>Profit (TZS)</th><th>Margin</th><th>Velocity</th></tr></thead>
            <tbody>
            @php
            $fastIds = $fastItems->pluck('item_id')->toArray();
            $slowIds = $slowItems->pluck('item_id')->toArray();
            @endphp
            @forelse($marginItems as $idx => $item)
            @php $vel = in_array($item->item_id,$fastIds)?'fast':(in_array($item->item_id,$slowIds)?'slow':'moderate'); @endphp
            <tr>
                <td class="text-muted">{{ $idx+1 }}</td>
                <td style="font-weight:600;">{{ $item->item_name }}</td>
                <td style="color:var(--text-secondary);">{{ $item->category }}</td>
                <td>{{ number_format($item->qty_sold) }}</td>
                <td>{{ number_format($item->revenue,0) }}</td>
                <td>{{ number_format($item->profit,0) }}</td>
                <td><span class="margin-pill margin-{{ $item->margin_tier }}">{{ number_format($item->margin_pct,1) }}%</span></td>
                <td>
                    @if($vel==='fast')
                    <span class="badge badge-fast" style="border-radius:8px;font-size:.65rem;">⚡ Fast</span>
                    @elseif($vel==='slow')
                    <span class="badge badge-slow" style="border-radius:8px;font-size:.65rem;">🐢 Slow</span>
                    @else
                    <span class="badge badge-moderate" style="border-radius:8px;font-size:.65rem;">📊 Moderate</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-3">No sales data for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#64748b';

const PALETTE = ['#0088cc','#10b981','#f59e0b','#8b5cf6','#14b8a6','#ef4444','#f97316','#06b6d4','#84cc16','#ec4899'];
const fmt = v => 'TZS ' + Math.round(v||0).toLocaleString();

/* ── 1. Trend + Prediction ─────────────────────────────────────────── */
(function(){
    const actualLabels = @json($dailyRevenue->pluck('date'));
    const actualData   = @json($dailyRevenue->pluck('revenue'));
    const predLabels   = @json($prediction->pluck('date'));
    const predData     = @json($prediction->pluck('predicted'));

    const allLabels  = [...actualLabels, ...predLabels];
    const actualFull = [...actualData, ...Array(predLabels.length).fill(null)];
    const predFull   = [...Array(actualLabels.length).fill(null), ...predData];
    if (actualData.length && predData.length) {
        predFull[actualLabels.length - 1] = actualData[actualData.length - 1];
    }

    const ctx  = document.getElementById('trendChart').getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,280);
    grad.addColorStop(0,'rgba(0,136,204,.28)');
    grad.addColorStop(1,'rgba(0,136,204,0)');

    new Chart(ctx, {
        type: 'line',
        data: { labels: allLabels, datasets: [
            { label:'Actual Revenue', data:actualFull, borderColor:'#0088cc', backgroundColor:grad, fill:true, tension:.4, pointRadius:0, pointHoverRadius:5, borderWidth:2.5, spanGaps:false },
            { label:'Predicted Revenue', data:predFull, borderColor:'#8b5cf6', backgroundColor:'transparent', borderDash:[6,4], fill:false, tension:.4, pointRadius:0, pointHoverRadius:5, borderWidth:2, spanGaps:false }
        ]},
        options: {
            responsive:true, maintainAspectRatio:true,
            interaction:{mode:'index',intersect:false},
            plugins:{
                legend:{position:'top',labels:{boxWidth:12,padding:12}},
                tooltip:{callbacks:{label:c=>c.dataset.label+': '+fmt(c.raw)}}
            },
            scales:{
                x:{grid:{display:false},ticks:{maxTicksLimit:12,callback:function(v){
                    const d=new Date(this.getLabelForValue(v));
                    return d.toLocaleDateString('en-GB',{day:'numeric',month:'short'});
                }}},
                y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>'TZS '+(v>=1000?(v/1000).toFixed(0)+'K':v)}}
            }
        }
    });
})();

/* ── 2. Category Donut ─────────────────────────────────────────────── */
(function(){
    const cats = @json($categoryRevenue->take(8)->pluck('category'));
    const revs = @json($categoryRevenue->take(8)->pluck('revenue'));
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type:'doughnut',
        data:{labels:cats,datasets:[{data:revs,backgroundColor:PALETTE,borderWidth:2,borderColor:'#fff',hoverOffset:8}]},
        options:{responsive:true,maintainAspectRatio:true,cutout:'65%',
            plugins:{
                legend:{position:'bottom',labels:{boxWidth:10,padding:10,font:{size:10}}},
                tooltip:{callbacks:{label:c=>c.label+': '+fmt(c.raw)}}
            }
        }
    });
})();

/* ── 2.5. Expenses Donut ───────────────────────────────────────────── */
(function(){
    const cats = @json($expensesByCategory->pluck('category_name'));
    const amts = @json($expensesByCategory->pluck('total_amount'));
    new Chart(document.getElementById('expensesChart').getContext('2d'), {
        type:'doughnut',
        data:{labels:cats,datasets:[{data:amts,backgroundColor:PALETTE,borderWidth:2,borderColor:'#fff',hoverOffset:8}]},
        options:{responsive:true,maintainAspectRatio:true,cutout:'65%',
            plugins:{
                legend:{position:'bottom',labels:{boxWidth:10,padding:10,font:{size:10}}},
                tooltip:{callbacks:{label:c=>c.label+': '+fmt(c.raw)}}
            }
        }
    });
})();

/* ── 3. Margin Bar ─────────────────────────────────────────────────── */
(function(){
    const items  = @json($marginItems->take(15)->pluck('item_name'));
    const revs   = @json($marginItems->take(15)->pluck('revenue'));
    const profs  = @json($marginItems->take(15)->pluck('profit'));
    const colors = @json($marginItems->take(15)->map(fn($i)=>match($i->margin_tier){'high'=>'#10b981','moderate'=>'#f59e0b',default=>'#ef4444'})->values());
    new Chart(document.getElementById('marginChart').getContext('2d'), {
        type:'bar',
        data:{labels:items.map(n=>n.length>16?n.slice(0,16)+'…':n),datasets:[
            {label:'Revenue',data:revs,backgroundColor:'rgba(0,136,204,.2)',borderColor:'#0088cc',borderWidth:1.5,borderRadius:4},
            {label:'Profit', data:profs,backgroundColor:colors.map(c=>c+'33'),borderColor:colors,borderWidth:1.5,borderRadius:4}
        ]},
        options:{responsive:true,maintainAspectRatio:true,
            plugins:{legend:{position:'top',labels:{boxWidth:10}},tooltip:{callbacks:{label:c=>c.dataset.label+': '+fmt(c.raw)}}},
            scales:{x:{grid:{display:false},ticks:{font:{size:9}}},y:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>v>=1000?(v/1000).toFixed(0)+'K':v}}}
        }
    });
})();

/* ── 4. Staff Horizontal Bar ───────────────────────────────────────── */
(function(){
    const names = @json($staffPerformance->take(10)->pluck('seller_name'));
    const revs  = @json($staffPerformance->take(10)->pluck('revenue'));
    const profs = @json($staffPerformance->take(10)->pluck('profit'));
    new Chart(document.getElementById('staffChart').getContext('2d'), {
        type:'bar',
        data:{labels:names,datasets:[
            {label:'Revenue',data:revs,backgroundColor:'rgba(0,136,204,.8)',borderRadius:5,borderSkipped:false},
            {label:'Profit', data:profs,backgroundColor:'rgba(16,185,129,.8)',borderRadius:5,borderSkipped:false}
        ]},
        options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'top',labels:{boxWidth:10}},tooltip:{callbacks:{label:c=>c.dataset.label+': '+fmt(c.raw)}}},
            scales:{x:{grid:{color:'rgba(0,0,0,.05)'},ticks:{callback:v=>v>=1000?(v/1000).toFixed(0)+'K':v}},y:{grid:{display:false}}}
        }
    });
})();

/* ── 5. Share/Print Stock Suggestions ─────────────────────────────── */
function getStockSuggestionsText() {
    let text = "Stock Reorder Suggestions (" + new Date().toLocaleDateString() + "):\n\n";
    let index = 1;
    @foreach($stockSuggestions as $s)
        text += index + ". {{ $s->item_name }} ({{ $s->category }})\n";
        text += "   Current Stock: {{ $s->current_stock }} | Sells: {{ $s->daily_rate }}/day\n";
        text += "   Urgency: {{ strtoupper($s->urgency) }} | Suggested order: {{ $s->suggest_qty }} units\n\n";
        index++;
    @endforeach
    if (index === 1) {
        text += "All products have sufficient stock.";
    }
    return text;
}

function printStockSuggestions() {
    let printWindow = window.open('', '_blank', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Stock Reorder Suggestions<\/title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body { font-family: sans-serif; padding: 20px; } table { width: 100%; border-collapse: collapse; } th, td { padding: 8px; border: 1px solid #ddd; }<\/style>');
    printWindow.document.write('<\/head><body>');
    printWindow.document.write('<h2 class="mb-1">Stock Reorder Suggestions<\/h2>');
    printWindow.document.write('<p class="text-muted small">Generated on ' + new Date().toLocaleDateString() + '<\/p>');
    printWindow.document.write('<hr>');
    printWindow.document.write('<table class="table table-bordered">');
    printWindow.document.write('<thead><tr><th>Product Name<\/th><th>Category<\/th><th>Current Stock<\/th><th>Daily sales rate<\/th><th>Urgency<\/th><th>Suggested Order<\/th><\/tr><\/thead>');
    printWindow.document.write('<tbody>');
    @forelse($stockSuggestions as $s)
        let urgencyBadge{{ $loop->index }} = '<span class="badge {{ $s->urgency === "critical" ? "bg-danger" : "bg-warning text-dark" }}">{{ strtoupper($s->urgency) }}<\/span>';
        printWindow.document.write('<tr>');
        printWindow.document.write('<td><strong>{{ addslashes($s->item_name) }}<\/strong><\/td>');
        printWindow.document.write('<td>{{ addslashes($s->category) }}<\/td>');
        printWindow.document.write('<td>{{ number_format($s->current_stock) }}<\/td>');
        printWindow.document.write('<td>{{ $s->daily_rate }}/day<\/td>');
        printWindow.document.write('<td>' + urgencyBadge{{ $loop->index }} + '<\/td>');
        printWindow.document.write('<td><strong>{{ number_format($s->suggest_qty) }} units<\/strong><\/td>');
        printWindow.document.write('<\/tr>');
    @empty
        printWindow.document.write('<tr><td colspan="6" class="text-center">All products have sufficient stock.<\/td><\/tr>');
    @endforelse
    printWindow.document.write('<\/tbody><\/table>');
    printWindow.document.write('<\/body><\/html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function shareWhatsAppSuggestions() {
    let text = getStockSuggestionsText();
    let url = "https://api.whatsapp.com/send?text=" + encodeURIComponent(text);
    window.open(url, '_blank');
}

function shareEmailSuggestions() {
    let text = getStockSuggestionsText();
    let subject = "Stock Reorder Suggestions - " + new Date().toLocaleDateString();
    let url = "mailto:?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(text);
    window.location.href = url;
}
</script>
@endpush
