@extends('layouts.app')
@section('title', 'Shops')
@section('page-title', 'Shop Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Shops</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">@if(auth()->user()->isOwner()) All Shops @else My Shop Profile @endif</h5>
        <small style="color:var(--text-secondary);">@if(auth()->user()->isOwner()) Manage your retail branches @else View and manage your shop profile details @endif</small>
    </div>
    @if(auth()->user()->isOwner())
    <a href="{{ route('shops.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-circle me-1"></i> Add Shop
    </a>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="shopsTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop Name</th>
                    <th>Location</th>
                    <th>Phone</th>
                    <th>Employees</th>
                    <th>Sales</th>
                    <th>Status</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#shopsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("shops.data") }}',
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'shop_name', name: 'shop_name' },
            { data: 'location', name: 'location' },
            { data: 'phone', name: 'phone' },
            { data: 'employees', name: 'employees', orderable: false, searchable: false },
            { data: 'sales', name: 'sales', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });
});
</script>
@endpush
