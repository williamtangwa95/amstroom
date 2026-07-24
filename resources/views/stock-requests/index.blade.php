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
                    <td><span style="background:rgba(88,166,255,.12);color:#58a6ff;padding:.2rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;">{{ $req->items->count() }} item(s)</span>
                        <button type="button" class="btn btn-xs btn-outline-custom toggle-details" data-id="{{ $req->id }}" title="Toggle Details">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <a href="{{ route('stock-requests.show', $req) }}" class="btn btn-xs btn-outline-custom">View / Action</a>
                        </div>

                        <!-- Hidden details template container -->
                        <div id="details-{{ $req->id }}" class="d-none">
                            <div class="p-3 my-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
                                @if($req->notes)
                                <div class="mb-3">
                                    <span style="color:var(--text-secondary); font-size:.8rem; font-weight:600; display:block; margin-bottom:.2rem;">Notes:</span>
                                    <div class="p-2 rounded bg-white" style="font-size:.82rem; border:1px solid var(--card-border); color:var(--text-primary);">{{ $req->notes }}</div>
                                </div>
                                @endif
                                <h6 class="fw-700 mb-2" style="font-size:.9rem; color:var(--accent);"><i class="bi bi-list-task me-1"></i> Requested Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0" style="background: var(--card-bg); border-color: var(--card-border);">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th class="text-center">Qty Requested</th>
                                                <th class="text-center">Main Warehouse Available</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($req->items as $reqItem)
                                            @php $avail = $reqItem->item->getTotalMainStock(); @endphp
                                            <tr>
                                                <td style="font-weight:600;">{{ $reqItem->item->item_name }}</td>
                                                <td style="color:var(--text-secondary);font-size:.78rem;">{{ $reqItem->item->category->category_name }}</td>
                                                <td class="text-center"><strong style="color:var(--accent);">{{ $reqItem->quantity }}</strong></td>
                                                <td class="text-center">
                                                    <strong style="color:{{ $avail >= $reqItem->quantity ? '#10b981' : '#ef4444' }}">{{ $avail }}</strong>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
<script>
    $(() => {
        var table = $('#requestsTable').DataTable();

        $('#requestsTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var reqId = $(this).data('id');
            var targetDiv = $('#details-' + reqId);
            var icon = $(this).find('i');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                row.child(targetDiv.html()).show();
                tr.addClass('shown');
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
            }
        });
    });
</script>
@endpush