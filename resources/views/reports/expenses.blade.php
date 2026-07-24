@extends('layouts.app')
@section('title', 'Expenses Report')
@section('page-title', 'Expenses Breakdown Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses Report</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.expenses') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Category</label>
                <select name="expense_category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
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
            @if($period === 'custom')
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-wallet2"></i></div>
            <div class="stat-value" style="color:#e94560;font-size:1.3rem;">TZS {{ number_format($totalAmount, 0) }}</div>
            <div class="stat-label">Total Expenses ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="stat-value" style="color:#bc8cff;">{{ $expenses->count() }}</div>
            <div class="stat-label">Expense Transactions</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-tags-fill me-2" style="color:#d29922;"></i>Expenses by Category</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Category Name</th>
                    <th>Transactions</th>
                    <th>Total Expenses</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expensesByCategory as $ebc)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ebc->category->name ?? 'Deleted Category' }}</td>
                    <td>{{ number_format($ebc->count) }}</td>
                    <td><strong class="text-danger">TZS {{ number_format($ebc->total_amount, 0) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-secondary py-3">No category data found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-check me-2" style="color:#58a6ff;"></i>Expenses Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Activity</th>
                    <th>Recorded By</th>
                    <th>Approved By</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $exp->activity_date->format('M d, Y') }}</td>
                    <td><span class="badge" style="background:rgba(188,140,255,.12);color:#bc8cff;">{{ $exp->category->name }}</span></td>
                    <td style="font-size:.82rem;"><strong>{{ $exp->activity }}</strong></td>
                    <td style="font-size:.82rem;">{{ $exp->recorder->name ?? '—' }}</td>
                    <td style="font-size:.82rem;">{{ $exp->approver->name ?? '—' }}</td>
                    <td><strong class="text-danger">TZS {{ number_format($exp->amount, 0) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-secondary py-3">No expenses found for the selected criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
