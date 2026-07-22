@extends('layouts.app')
@section('title', 'Employee Management')
@section('page-title', 'Employee Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Employees</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Employees</h5>
        <small style="color:var(--text-secondary);">Manage Shop Admins and Sellers</small>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-accent"><i class="bi bi-person-plus-fill me-1"></i> Register Employee</a>
</div>

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
                    <th>Assigned Shop</th>
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
                    <td>
                        @if($u->shop)
                        <span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">
                            <i class="bi bi-shop me-1"></i>{{ $u->shop->shop_name }}
                        </span>
                        @else
                        <span style="font-size:.75rem;color:var(--text-secondary);">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('users.show', $u) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-xs btn-outline-custom"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('users.destroy', $u) }}" id="del-user-{{ $u->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="Delete employee account?"
                                    data-form="del-user-{{ $u->id }}">
                                    <i class="bi bi-trash" style="color:#e94560;"></i>
                                </button>
                            </form>
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
