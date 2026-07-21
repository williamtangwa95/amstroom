@extends('layouts.app')
@section('title', 'Stock Request #' . $stockRequest->id)
@section('page-title', 'Stock Request Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stock-requests.index') }}">Stock Requests</a></li>
<li class="breadcrumb-item active">#{{ $stockRequest->id }}</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-text me-2" style="color:#58a6ff;"></i>Request #{{ $stockRequest->id }}</span>
                <span class="status-badge badge-{{ $stockRequest->status }}">{{ ucfirst($stockRequest->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3" style="font-size:.85rem;">
                    <div class="col-md-6">
                        <p class="mb-1" style="color:var(--text-secondary);">Shop: <strong style="color:var(--text-primary);">{{ $stockRequest->shop->shop_name }}</strong></p>
                        <p class="mb-1" style="color:var(--text-secondary);">Requested By: <strong style="color:var(--text-primary);">{{ $stockRequest->requester->name }}</strong></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1" style="color:var(--text-secondary);">Date: <strong style="color:var(--text-primary);">{{ $stockRequest->request_date->format('F d, Y') }}</strong></p>
                        @if($stockRequest->notes)
                        <p class="mb-1" style="color:var(--text-secondary);">Notes: <span style="color:var(--text-primary);">{{ $stockRequest->notes }}</span></p>
                        @endif
                    </div>
                </div>

                <h6 class="fw-700 mb-2 mt-4">Requested Items</h6>
                <table class="table mb-4">
                    <thead><tr><th>Product</th><th>Category</th><th>Qty Requested</th><th>Main Warehouse Available</th></tr></thead>
                    <tbody>
                    @foreach($stockRequest->items as $reqItem)
                    @php $avail = $reqItem->item->getTotalMainStock(); @endphp
                    <tr>
                        <td style="font-weight:600;">{{ $reqItem->item->item_name }}</td>
                        <td style="color:var(--text-secondary);font-size:.78rem;">{{ $reqItem->item->category->category_name }}</td>
                        <td><strong style="color:#58a6ff;">{{ $reqItem->quantity }}</strong></td>
                        <td><strong style="color:{{ $avail >= $reqItem->quantity ? '#3fb950' : '#e94560' }}">{{ $avail }}</strong></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>

                @if(auth()->user()->isOwner() && $stockRequest->isPending())
                <div class="p-3 rounded mb-3" style="background:var(--input-bg);border:1px solid var(--input-border);">
                    <h6 class="fw-700 mb-2" style="font-size:.85rem;">Owner Actions</h6>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('stock-requests.approve', $stockRequest) }}" id="approve-form">
                            @csrf
                            <button type="button" class="btn btn-accent" data-confirm="Approve and transfer stock?" data-form="approve-form">
                                <i class="bi bi-check-circle me-1"></i> Approve & Transfer
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectCollapse">
                            <i class="bi bi-x-circle me-1"></i> Reject Request
                        </button>
                    </div>

                    <div class="collapse mt-3" id="rejectCollapse">
                        <form method="POST" action="{{ route('stock-requests.reject', $stockRequest) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Reason for Rejection</label>
                                <textarea name="reject_reason" class="form-control" rows="2" placeholder="Out of stock, invalid request..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                        </form>
                    </div>
                </div>
                @endif

                <a href="{{ route('stock-requests.index') }}" class="btn btn-outline-custom">Back to Requests</a>
            </div>
        </div>
    </div>
</div>
@endsection
