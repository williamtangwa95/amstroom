@extends('layouts.app')
@section('title', 'Shops')
@section('page-title', 'Shop Management')
@section('breadcrumb')
<li class="breadcrumb-item active">Shops</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">All Shops</h5>
        <small style="color:var(--text-secondary);">Manage your retail branches</small>
    </div>
    <a href="{{ route('shops.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-circle me-1"></i> Add Shop
    </a>
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
            <tbody>
                @foreach($shops as $shop)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;">{{ $shop->shop_name }}</div>
                        <div style="font-size:.73rem;color:var(--text-secondary);">{{ $shop->email }}</div>
                    </td>
                    <td style="font-size:.82rem;">{{ $shop->location }}</td>
                    <td style="font-size:.82rem;">{{ $shop->phone ?: '—' }}</td>
                    <td>
                        <span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">
                            {{ $shop->users_count }}
                        </span>
                    </td>
                    <td>
                        <span style="background:rgba(63,185,80,.12);color:#3fb950;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">
                            {{ $shop->sales_count }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge {{ $shop->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                            {{ ucfirst($shop->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('shops.show', $shop) }}" class="btn btn-xs btn-outline-custom" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('shops.edit', $shop) }}" class="btn btn-xs btn-outline-custom" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('shops.destroy', $shop) }}" id="del-shop-{{ $shop->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-outline-custom"
                                    data-confirm="Delete this shop?"
                                    data-text="All associated data may be affected."
                                    data-form="del-shop-{{ $shop->id }}">
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
<script>$(()=>$('#shopsTable').DataTable())</script>
@endpush
