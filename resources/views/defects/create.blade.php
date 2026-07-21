@extends('layouts.app')
@section('title', 'Report Defect')
@section('page-title', $isMainStore ? 'Report Main Store Defect' : 'Report Shop Defect')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('defects.index') }}">Defects</a></li>
<li class="breadcrumb-item active">Report Defect</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-exclamation-triangle-fill me-2" style="color:#e94560;"></i>Defective Product Entry</div>
            <div class="card-body">
                <form method="POST" action="{{ route('defects.store') }}">
                    @csrf
                    @if($isMainStore)
                    <input type="hidden" name="is_main_store" value="1">
                    <div class="alert alert-info py-2" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i> Reporting defect directly from <strong>Main Warehouse stock</strong>.
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Product *</label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select defective item...</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ old('item_id')==$item->id ? 'selected' : '' }}>
                                    [{{ $item->category->category_name }}] {{ $item->item_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Defective Quantity *</label>
                            <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason / Defect Description *</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Screen cracked, power supply failure, damaged during transport..." required>{{ old('reason') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Submit Defect Report</button>
                        <a href="{{ route('defects.index') }}" class="btn btn-outline-custom">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
