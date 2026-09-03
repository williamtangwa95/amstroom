<div class="p-3 my-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
    <h6 class="fw-700 mb-2" style="font-size:.9rem; color:var(--accent);"><i class="bi bi-list-task me-1"></i> Sold Items</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="background: var(--card-bg); border-color: var(--card-border);">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $isOwner = auth()->check() && auth()->user()->isOwner();
                    $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
                @endphp
                @foreach($sale->items->where('parent_id', null) as $item)
                    @if($isOwner && $item->is_admin_stock)
                        @continue
                    @endif
                    @php
                        $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                        $displaySubtotal = $displayPrice * $item->quantity;
                    @endphp
                    <tr>
                        <td style="font-weight:600; font-size:.82rem;">{{ $item->display_name }}</td>
                        <td class="text-center" style="font-size:.82rem;">{{ $item->quantity }}</td>
                        <td class="text-end" style="font-size:.82rem;">TZS {{ number_format($displayPrice, 0) }}</td>
                        <td class="text-end" style="font-size:.82rem;"><strong style="color:#3fb950;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                    </tr>
                    @if($item->components->isNotEmpty())
                        @foreach($item->components as $component)
                            <tr style="background-color: rgba(0,0,0,0.015);">
                                <td style="font-size:.78rem; padding-left: 1.5rem; color: var(--text-secondary);">
                                    <span class="text-muted">└─</span> {{ $component->display_name }}
                                </td>
                                <td class="text-center" style="font-size:.78rem; color: var(--text-secondary);">
                                    {{ $component->quantity }}
                                </td>
                                <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                    Included
                                </td>
                                <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                    Included
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-700" style="font-size:.82rem;">{{ $isOwner ? 'Total Revenue Realized:' : 'Total Amount Paid:' }}</td>
                    <td class="text-end" style="font-size:.82rem;"><strong style="color:#3fb950;font-size:1rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
