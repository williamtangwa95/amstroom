@php
    $isOwner = auth()->check() && auth()->user()->isOwner();
    $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
    $visibleItems = $sale->items->where('parent_id', null)->filter(function ($item) use ($isOwner) {
        return !($isOwner && $item->is_admin_stock);
    });
@endphp
<div class="p-2 p-md-3 my-1 my-md-2 rounded text-start" style="background: var(--body-bg); border: 1px solid var(--card-border); color: var(--text-primary);">
    <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
        <h6 class="fw-700 mb-0" style="font-size:.86rem; color:var(--accent);"><i class="bi bi-list-task me-1"></i> Sold Items</h6>
        <span class="badge" style="background: rgba(88,166,255,.12); color: #0284c7; font-size:.7rem;">Sale #SL-{{ $sale->id }}</span>
    </div>

    {{-- Desktop / Tablet Table --}}
    <div class="d-none d-md-block table-responsive">
        <table class="table table-sm table-hover mb-0" style="background: var(--card-bg); border-color: var(--card-border);">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-center" style="width: 70px;">Components</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visibleItems as $item)
                    @php
                        $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                        $displaySubtotal = $displayPrice * $item->quantity;
                        $canAddComponents = $item->allowsComponents();
                    @endphp
                    <tr>
                        <td style="font-weight:600; font-size:.82rem;">
                            {{ $item->display_name }}
                            @if($canAddComponents)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem; border:1px solid var(--card-border);">Bundle Item</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-size:.82rem;">{{ $item->quantity }} <small class="text-muted">{{ $item->unit_symbol }}</small></td>
                        <td class="text-end" style="font-size:.82rem;">TZS {{ number_format($displayPrice, 0) }}</td>
                        <td class="text-end" style="font-size:.82rem;"><strong style="color:#3fb950;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                        <td class="text-center">
                            @if($canAddComponents)
                                <button type="button" class="btn btn-xs btn-outline-success open-add-component-modal-btn py-0 px-1"
                                    data-sale-id="{{ $sale->id }}"
                                    data-item-id="{{ $item->id }}"
                                    data-item-name="{{ $item->display_name }}"
                                    title="Add Component">
                                    <i class="bi bi-plus-circle"></i>
                                </button>
                            @else
                                <span class="text-muted" style="font-size:.72rem;">—</span>
                            @endif
                        </td>
                    </tr>
                    @if($item->components->isNotEmpty())
                        @foreach($item->components as $component)
                            <tr style="background-color: rgba(0,0,0,0.015);">
                                <td style="font-size:.78rem; padding-left: 1.5rem; color: var(--text-secondary);">
                                    <span class="text-muted">└─</span> {{ $component->display_name }}
                                </td>
                                <td class="text-center" style="font-size:.78rem; color: var(--text-secondary);">
                                    {{ $component->quantity }} <small>{{ $component->unit_symbol }}</small>
                                </td>
                                <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                    Included
                                </td>
                                <td class="text-end" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                    Included
                                </td>
                                <td class="text-center">
                                    @if($canAddComponents)
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 remove-component-btn"
                                            data-sale-id="{{ $sale->id }}"
                                            data-component-id="{{ $component->id }}"
                                            data-component-name="{{ $component->display_name }}"
                                            title="Remove this component">
                                            <i class="bi bi-trash" style="font-size:.7rem;"></i>
                                        </button>
                                    @endif
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
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Mobile Stacked Cards (Zero Horizontal Scroll) --}}
    <div class="d-md-none d-flex flex-column gap-2">
        @foreach($visibleItems as $item)
            @php
                $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                $displaySubtotal = $displayPrice * $item->quantity;
                $canAddComponents = $item->allowsComponents();
            @endphp
            <div class="rounded-3 p-2.5 bg-white border" style="border-color: var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <div class="fw-600 text-dark" style="font-size:.82rem; line-height:1.3; word-break:break-word;">
                        {{ $item->display_name }}
                        @if($canAddComponents)
                            <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.62rem; border:1px solid var(--card-border);">Bundle</span>
                        @endif
                    </div>
                    @if($canAddComponents)
                        <button type="button" class="btn btn-xs btn-outline-success open-add-component-modal-btn py-0 px-1 flex-shrink-0"
                            data-sale-id="{{ $sale->id }}"
                            data-item-id="{{ $item->id }}"
                            data-item-name="{{ $item->display_name }}"
                            title="Add Component">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    @endif
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1" style="font-size:.76rem;">
                    <span class="text-muted">{{ $item->quantity }} {{ $item->unit_symbol }} &times; TZS {{ number_format($displayPrice, 0) }}</span>
                    <strong style="color:#16a34a;">TZS {{ number_format($displaySubtotal, 0) }}</strong>
                </div>

                @if($item->components->isNotEmpty())
                    <div class="mt-2 pt-2 border-top d-flex flex-column gap-1" style="border-color: #f1f5f9 !important;">
                        @foreach($item->components as $component)
                            <div class="d-flex justify-content-between align-items-center gap-2" style="font-size:.74rem; color: var(--text-secondary);">
                                <div style="word-break:break-word;">
                                    <span class="text-muted">└─</span> {{ $component->display_name }}
                                    <span class="badge bg-light text-secondary border ms-1" style="font-size:.64rem;">{{ $component->quantity }} {{ $component->unit_symbol }} · Included</span>
                                </div>
                                @if($canAddComponents)
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 remove-component-btn flex-shrink-0"
                                        data-sale-id="{{ $sale->id }}"
                                        data-component-id="{{ $component->id }}"
                                        data-component-name="{{ $component->display_name }}"
                                        title="Remove this component">
                                        <i class="bi bi-trash" style="font-size:.68rem;"></i>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="d-flex justify-content-between align-items-center px-2.5 py-2 rounded-3 bg-white border" style="border-color: var(--card-border) !important; font-size:.8rem;">
            <span class="fw-700 text-dark">{{ $isOwner ? 'Total Revenue:' : 'Total Paid:' }}</span>
            <strong style="color:#16a34a; font-size:.92rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong>
        </div>
    </div>
</div>
