@php
    $isOwner = auth()->user()->isOwner();
@endphp
<div class="p-2 p-md-3 my-1 my-md-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
    @if($stockRequest->notes)
    <div class="mb-2 mb-md-3">
        <span style="color:var(--text-secondary); font-size:.76rem; font-weight:600; display:block; margin-bottom:.2rem;">Notes:</span>
        <div class="p-2 rounded bg-white" style="font-size:.8rem; border:1px solid var(--card-border); color:var(--text-primary);">{{ $stockRequest->notes }}</div>
    </div>
    @endif
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h6 class="fw-700 mb-0" style="font-size:.86rem; color:var(--accent);"><i class="bi bi-list-task me-1"></i> Requested Items</h6>
        @if($isOwner && $stockRequest->isPending())
            <a href="{{ route('stock-requests.show', $stockRequest) }}" class="btn btn-xs btn-success fw-600">
                <i class="bi bi-send-check me-1"></i>Review, Regulate &amp; Dispatch
            </a>
        @endif
    </div>

    {{-- Desktop / Tablet Table --}}
    <div class="d-none d-md-block table-responsive">
        <table class="table table-sm table-hover mb-0" style="background: var(--card-bg); border-color: var(--card-border);">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-center">Qty Requested</th>
                    @if($isOwner)
                        <th class="text-center">Main Warehouse Available</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($stockRequest->items as $reqItem)
                @php $avail = $isOwner && $reqItem->item ? $reqItem->item->getTotalMainStock() : 0; @endphp
                <tr>
                    <td style="font-weight:600;">{{ $reqItem->item?->item_name ?? 'Item' }}</td>
                    <td style="color:var(--text-secondary);font-size:.78rem;">{{ $reqItem->item?->category?->category_name ?? 'General' }}</td>
                    <td class="text-center"><strong style="color:var(--accent);">{{ $reqItem->quantity }}</strong></td>
                    @if($isOwner)
                    <td class="text-center">
                        <strong style="color:{{ $avail >= $reqItem->quantity ? '#10b981' : ($avail > 0 ? '#d97706' : '#ef4444') }}">{{ $avail }}</strong>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Stacked Cards (Zero Horizontal Scroll) --}}
    <div class="d-md-none d-flex flex-column gap-2">
        @foreach($stockRequest->items as $reqItem)
        @php $avail = $isOwner && $reqItem->item ? $reqItem->item->getTotalMainStock() : 0; @endphp
        <div class="rounded-3 p-2 bg-white border" style="border-color: var(--card-border) !important;">
            <div class="fw-600 text-dark" style="font-size:.82rem; line-height:1.3; word-break:break-word;">
                {{ $reqItem->item?->item_name ?? 'Item' }}
            </div>
            <div class="text-muted mb-1" style="font-size:.72rem;">{{ $reqItem->item?->category?->category_name ?? 'General' }}</div>
            <div class="d-flex justify-content-between align-items-center" style="font-size:.76rem;">
                <span>Requested: <strong style="color:var(--accent);">{{ $reqItem->quantity }}</strong></span>
                @if($isOwner)
                    <span>Warehouse: <strong style="color:{{ $avail >= $reqItem->quantity ? '#10b981' : ($avail > 0 ? '#d97706' : '#ef4444') }}">{{ $avail }}</strong></span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
