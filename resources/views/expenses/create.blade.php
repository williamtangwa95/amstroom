@extends('layouts.app')
@section('title', 'Record Expense')
@section('page-title', 'Record Expense')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
<li class="breadcrumb-item active">Record</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-wallet2 me-2" style="color:#e94560;"></i>Record Expense</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600">Category *</label>
                            <select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-600">Activity / Title *</label>
                            <input type="text" name="activity" class="form-control @error('activity') is-invalid @enderror" value="{{ old('activity') }}" placeholder="e.g. Paid Office internet bills" required>
                            @error('activity')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-600">Amount (TZS) *</label>
                            <input type="text" name="amount" class="form-control currency-input @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="e.g. 50,000" required>
                            @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-600">Activity Date *</label>
                            <input type="date" name="activity_date" class="form-control @error('activity_date') is-invalid @enderror" value="{{ old('activity_date', date('Y-m-d')) }}" required>
                            @error('activity_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-600">Description / Details</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Add any details, notes or explanations regarding the expense...">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Record Expense</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
