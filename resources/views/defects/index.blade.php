@extends('layouts.app')
@section('title', 'Defective Items')
@section('page-title', 'Defective Product Reports')
@section('breadcrumb')
<li class="breadcrumb-item active">Defects</li>
@endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-700">Defective Items</h5>
        <small style="color:var(--text-secondary);">Track damaged, broken, or returned items</small>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isOwner())
        <a href="{{ route('defects.create') }}?main_store=1" class="btn btn-outline-custom"><i class="bi bi-building-fill me-1"></i> Main Store Defect</a>
        @endif
        <a href="{{ route('defects.create') }}" class="btn btn-accent"><i class="bi bi-plus-circle me-1"></i> Report Defect</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="defectsTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Reason</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th class="no-sort">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($defects as $def)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $def->date->format('M d, Y') }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $def->shop ? $def->shop->shop_name : 'Main Warehouse' }}</td>
                    <td style="font-size:.82rem;font-weight:600;">{{ $def->item->item_name }}</td>
                    <td style="font-size:.78rem;color:var(--text-secondary);">{{ $def->item->category->category_name }}</td>
                    <td><strong style="color:#e94560;">{{ $def->quantity }}</strong></td>
                    <td style="font-size:.8rem;color:var(--text-secondary);">{{ Str::limit($def->reason, 40) }}</td>
                    <td style="font-size:.78rem;">{{ $def->reporter->name }}</td>
                    <td>
                        <span class="status-badge badge-{{ $def->status === 'resolved' ? 'approved' : ($def->status === 'reviewed' ? 'pending' : 'rejected') }}">
                            {{ ucfirst($def->status) }}
                        </span>
                    </td>
                    <td>
                        @if(auth()->user()->isOwner() && $def->status !== 'resolved')
                        <form method="POST" action="{{ route('defects.update-status', $def) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="resolved">
                            <button type="submit" class="btn btn-xs btn-outline-custom text-success" title="Mark Resolved"><i class="bi bi-check-lg"></i> Resolve</button>
                        </form>
                        @else
                        <span style="font-size:.75rem;color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$(()=>$('#defectsTable').DataTable())</script>
@endpush
