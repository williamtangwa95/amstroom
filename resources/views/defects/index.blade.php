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
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(() => {
        $('#defectsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: "{{ route('defects.data') }}",
            columns: [
                { data: 'iteration', name: 'iteration' },
                { data: 'date', name: 'date' },
                { data: 'location', name: 'location' },
                { data: 'product', name: 'product' },
                { data: 'category', name: 'category' },
                { data: 'quantity', name: 'quantity' },
                { data: 'reason', name: 'reason' },
                { data: 'reporter', name: 'reporter' },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']]
        });
    });
</script>
@endpush
