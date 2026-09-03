<div class="p-3 my-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
    @if($stockRequest->notes)
    <div class="mb-3">
        <span style="color:var(--text-secondary); font-size:.8rem; font-weight:600; display:block; margin-bottom:.2rem;">Notes:</span>
        <div class="p-2 rounded bg-white" style="font-size:.82rem; border:1px solid var(--card-border); color:var(--text-primary);">{{ $stockRequest->notes }}</div>
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
                @foreach($stockRequest->items as $reqItem)
                @php $avail = $reqItem->item ? $reqItem->item->getTotalMainStock() : 0; @endphp
                <tr>
                    <td style="font-weight:600;">{{ $reqItem->item?->item_name ?? 'Item' }}</td>
                    <td style="color:var(--text-secondary);font-size:.78rem;">{{ $reqItem->item?->category?->category_name ?? 'General' }}</td>
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
