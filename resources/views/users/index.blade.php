@extends('layouts.app')
@section('title', 'Employee Management')
@section('page-title', 'Employee Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Employees</li>
@endsection
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
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

<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100" id="usersTable">
            <thead>
                <tr>
                    <th data-priority="1" class="text-center" style="width: 45px;">#</th>
                    <th data-priority="1">Name</th>
                    <th data-priority="3">Email</th>
                    <th data-priority="3">Phone</th>
                    <th data-priority="2">Role</th>
                    @if(auth()->user()->isOwner())
                    <th data-priority="2">Assigned Shop</th>
                    @endif
                    <th data-priority="2">Status</th>
                    <th data-priority="3" class="no-sort text-end" style="min-width: 90px;">Actions</th>
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
