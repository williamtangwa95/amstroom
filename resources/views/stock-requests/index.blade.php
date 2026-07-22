@extends('layouts.app')
@section('title', 'Stock Requests')
@section('page-title', 'Stock Requests')
@section('breadcrumb')
<li class="breadcrumb-item active">Stock Requests</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Stock Requests</h5>
        <small style="color:var(--text-secondary);">Request inventory from central warehouse</small>
    </div>
    @if(!auth()->user()->isOwner())
    <a href="{{ route('stock-requests.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> New Request</a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="requestsTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Requester</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $req->shop->shop_name }}</td>
                    <td style="font-size:.82rem;">{{ $req->requester->name }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $req->request_date->format('M d, Y') }}</td>
                    <td>
                        <span class="status-badge badge-{{ $req->status }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td><span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">{{ $req->items->count() }} item(s)</span></td>
                    <td>
                        <a href="{{ route('stock-requests.show', $req) }}" class="btn btn-xs btn-outline-custom">View / Action</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#requestsTable').DataTable())</script>
@endpush
