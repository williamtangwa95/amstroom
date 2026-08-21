@extends('layouts.app')
@section('title', $user->name)
@section('page-title', 'Employee Profile')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('users.index') }}">Employees</a></li>
<li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-badge-fill me-2" style="color:#58a6ff;"></i>{{ $user->name }}</span>
                {{-- Show edit only if owner, or if shop_admin owns this seller --}}
                @if(auth()->user()->isOwner() || (auth()->user()->isShopAdmin() && $user->shop_id === auth()->user()->shop_id && $user->role === 'seller'))
                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-accent">Edit</a>
                @endif
            </div>
            <div class="card-body">
                <table class="table table-borderless" style="font-size:.85rem;">
                    <tr><th style="color:var(--text-secondary);width:35%;">Email</th><td>{{ $user->email }}</td></tr>
                    <tr><th style="color:var(--text-secondary);">Phone</th><td>{{ $user->phone ?: '—' }}</td></tr>
                    <tr>
                        <th style="color:var(--text-secondary);">Role</th>
                        <td><span class="role-pill role-{{ $user->role }}">{{ str_replace('_',' ',ucfirst($user->role)) }}</span></td>
                    </tr>
                    <tr>
                        <th style="color:var(--text-secondary);">Assigned Shop</th>
                        <td>{{ $user->shop ? $user->shop->shop_name : 'Unassigned' }}</td>
                    </tr>
                    <tr>
                        <th style="color:var(--text-secondary);">Total Sales Made</th>
                        <td><strong style="color:#3fb950;">{{ $user->sales->count() }} transaction(s)</strong></td>
                    </tr>
                    <tr>
                        <th style="color:var(--text-secondary);">Defects Reported</th>
                        <td>{{ $user->defects->count() }} item(s)</td>
                    </tr>
                </table>
                <a href="{{ route('users.index') }}" class="btn btn-outline-custom">Back to Employees</a>
            </div>
        </div>
    </div>
</div>
@endsection
