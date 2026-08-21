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
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;font-size:.85rem;">{{ $u->name }}</td>
                    <td style="font-size:.82rem;">{{ $u->email }}</td>
                    <td style="font-size:.82rem;">{{ $u->phone ?: '—' }}</td>
                    <td>
                        <span class="role-pill role-{{ $u->role }}">
                            {{ str_replace('_', ' ', ucfirst($u->role)) }}
                        </span>
                    </td>
                    @if(auth()->user()->isOwner())
                    <td>
                        @if($u->shop)
                        <span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">
                            <i class="bi bi-shop me-1"></i>{{ $u->shop->shop_name }}
                        </span>
                        @else
                        <span style="font-size:.75rem;color:var(--text-secondary);">Unassigned</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        <span class="status-badge {{ $u->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                            {{ $u->status === 'active' ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('users.show', $u) }}" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                            @if(!$u->isOwner() && $u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle-status', $u) }}" id="toggle-user-{{ $u->id }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="{{ $u->status === 'active' ? 'Disable employee account?' : 'Enable employee account?' }}"
                                    data-text="{{ $u->status === 'active' ? 'They will not be able to log in or reset password.' : 'They will be able to log in and use the application.' }}"
                                    data-form="toggle-user-{{ $u->id }}"
                                    title="{{ $u->status === 'active' ? 'Disable' : 'Enable' }}">
                                    @if($u->status === 'active')
                                        <i class="bi bi-person-dash" style="color:var(--text-secondary);"></i>
                                    @else
                                        <i class="bi bi-person-check" style="color:#3fb950;"></i>
                                    @endif
                                </button>
                            </form>
                            @endif
                            @if(!$u->hasDependencies())
                            <form method="POST" action="{{ route('users.destroy', $u) }}" id="del-user-{{ $u->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="Delete employee account?"
                                    data-form="del-user-{{ $u->id }}"
                                    title="Delete">
                                    <i class="bi bi-trash" style="color:#e94560;"></i>
                                </button>
                            </form>
                            @else
                            <button type="button" class="btn btn-xs btn-outline-custom" disabled style="opacity:0.5; cursor:not-allowed;"
                                title="Cannot delete user with associated sales, stock requests, or other records.">
                                <i class="bi bi-trash" style="color:var(--text-secondary);"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#usersTable').DataTable())</script>
@endpush
