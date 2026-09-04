@extends('layouts.app')
@section('title', 'Employee Management')
@section('page-title', 'Employee Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Employees</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">
            @if(auth()->user()->isShopAdmin() && $shopName)
                {{ $shopName }} — Sellers
            @else
                Employees
            @endif
        </h5>
        <small style="color:var(--text-secondary);">
            @if(auth()->user()->isShopAdmin())
                Manage sellers in your shop
            @else
                Manage Shop Admins and Sellers
            @endif
        </small>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-accent">
        <i class="bi bi-person-plus-fill me-1"></i>
        @if(auth()->user()->isShopAdmin()) Add Seller @else Register Employee @endif
    </a>
</div>

@if(session('success'))
<div class="alert alert-success border-0 rounded-3 mb-3" style="font-size:.83rem;">
    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger border-0 rounded-3 mb-3" style="font-size:.83rem;">
    <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="usersTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    @if(auth()->user()->isOwner())
                    <th>Assigned Shop</th>
                    @endif
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
    $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("users.data") }}',
        columns: [
            { data: 'no', name: 'no', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'role', name: 'role' },
            @if(auth()->user()->isOwner())
            { data: 'shop', name: 'shop', orderable: false, searchable: false },
            @endif
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });
});
</script>
@endpush
