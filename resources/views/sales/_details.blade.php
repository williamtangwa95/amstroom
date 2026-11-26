@php
    $isOwner = auth()->check() && auth()->user()->isOwner();
    $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
    $canEditComponents = auth()->check() && $sale->canMutateUnderHandover(auth()->user(), 'edit');
    $visibleItems = $sale->items->where('parent_id', null)->filter(function ($item) use ($isOwner) {
        return !($isOwner && $item->is_admin_stock);
    });
@endphp

<div class="my-2 rounded-3 text-start overflow-hidden" style="background:#f8fafc; border:1px solid #dbe4ef; box-shadow:0 3px 12px rgba(15,23,42,.05); color:var(--text-primary);">
    {{-- Executive Header Strip --}}
    <div class="d-flex justify-content-between align-items-center gap-2 px-3 py-2" style="background:linear-gradient(135deg,#f0f9ff 0%,#ffffff 100%); border-bottom:1px solid #e2e8f0;">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-2" style="width:26px;height:26px;background:rgba(0,119,182,.12);color:#0077b6;font-size:.82rem;">
                <i class="bi bi-bag-check-fill"></i>
            </span>
            <div>
                <div class="fw-700 text-dark" style="font-size:.84rem; line-height:1.15;">Sold Items</div>
                <div class="text-muted" style="font-size:.68rem;">{{ $visibleItems->count() }} {{ $visibleItems->count() === 1 ? 'product' : 'products' }}</div>
            </div>
        </div>
        <span class="badge rounded-pill px-2 py-1" style="background:rgba(0,119,182,.1); color:#005691; border:1px solid rgba(0,119,182,.2); font-size:.7rem; font-weight:700;">
            Sale #SL-{{ $sale->id }}
        </span>
    </div>

    <div class="p-3">
        {{-- Desktop / Tablet Table --}}
        <div class="d-none d-md-block table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 bg-white rounded-3 overflow-hidden" style="border:1px solid #e2e8f0;">
                <thead style="background:#f1f5f9;">
                    <tr>
                        <th class="ps-3 py-2">Product</th>
                        <th class="text-center py-2">Qty</th>
                        <th class="text-end py-2">Selling Price</th>
                        <th class="text-end py-2">Subtotal</th>
                        <th class="text-center pe-3 py-2" style="width: 85px;">Components</th>
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
                            <td class="ps-3 py-2" style="font-weight:600; font-size:.82rem;">
                                {{ $item->display_name }}
                                @if($canAddComponents)
                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.64rem; border:1px solid #cbd5e1;">Bundle</span>
                                @endif
                            </td>
                            <td class="text-center py-2" style="font-size:.82rem;">{{ $item->quantity }} <small class="text-muted">{{ $item->unit_symbol }}</small></td>
                            <td class="text-end py-2" style="font-size:.82rem;">TZS {{ number_format($displayPrice, 0) }}</td>
                            <td class="text-end py-2" style="font-size:.82rem;"><strong style="color:#059669;">TZS {{ number_format($displaySubtotal, 0) }}</strong></td>
                            <td class="text-center pe-3 py-2">
                                @if($canAddComponents && $canEditComponents)
                                    <button type="button" class="btn btn-xs btn-outline-success open-add-component-modal-btn py-0 px-1"
                                        data-sale-id="{{ $sale->id }}"
                                        data-item-id="{{ $item->id }}"
                                        data-item-name="{{ $item->display_name }}"
                                        title="Add Component">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                @elseif($canAddComponents)
                                    <span class="text-muted" title="Locked by submitted Cash Handover"><i class="bi bi-lock-fill"></i></span>
                                @else
                                    <span class="text-muted" style="font-size:.72rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @if($item->components->isNotEmpty())
                            @foreach($item->components as $component)
                                <tr style="background-color:#fafcff;">
                                    <td class="py-2" style="font-size:.78rem; padding-left: 1.75rem; color: var(--text-secondary);">
                                        <span class="text-muted">└─</span> {{ $component->display_name }}
                                    </td>
                                    <td class="text-center py-2" style="font-size:.78rem; color: var(--text-secondary);">
                                        {{ $component->quantity }} <small>{{ $component->unit_symbol }}</small>
                                    </td>
                                    <td class="text-end py-2" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                        Included
                                    </td>
                                    <td class="text-end py-2" style="font-size:.78rem; color: var(--text-secondary); font-style: italic;">
                                        Included
                                    </td>
                                    <td class="text-center pe-3 py-2">
                                        @if($canAddComponents && $canEditComponents)
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
                <tfoot style="background:#f0fdf4;">
                    <tr>
                        <td colspan="3" class="text-end fw-700 py-2" style="font-size:.82rem; color:#065f46;">{{ $isOwner ? 'Total Revenue Realized:' : 'Total Amount Paid:' }}</td>
                        <td class="text-end py-2" style="font-size:.86rem;"><strong style="color:#059669; font-size:.98rem;">TZS {{ number_format($sale->report_revenue, 0) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Mobile Executive Stacked Cards (Zero Horizontal Scroll) --}}
        <div class="d-md-none d-flex flex-column gap-2">
            @foreach($visibleItems as $item)
                @php
                    $displayPrice = ($isOwner && $isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                    $displaySubtotal = $displayPrice * $item->quantity;
                    $canAddComponents = $item->allowsComponents();
                @endphp
                <div class="rounded-3 bg-white" style="padding: .75rem .85rem; border:1px solid #e2e8f0; border-left:3px solid #0077b6; box-shadow:0 1px 4px rgba(15,23,42,.04);">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-700 text-dark" style="font-size:.83rem; line-height:1.3; word-break:break-word;">
                                {{ $item->display_name }}
                            </div>
                            @if($canAddComponents)
                                <span class="badge rounded-pill mt-1" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:.62rem; font-weight:600;">
                                    <i class="bi bi-boxes me-1"></i>Bundle Item
                                </span>
                            @endif
                        </div>
                        @if($canAddComponents && $canEditComponents)
                            <button type="button" class="btn btn-sm btn-outline-success open-add-component-modal-btn flex-shrink-0 d-inline-flex align-items-center justify-content-center"
                                style="width:28px; height:28px; padding:0; border-radius:8px;"
                                data-sale-id="{{ $sale->id }}"
                                data-item-id="{{ $item->id }}"
                                data-item-name="{{ $item->display_name }}"
                                title="Add Component">
                                <i class="bi bi-plus-lg" style="font-size:.8rem;"></i>
                            </button>
                        @elseif($canAddComponents)
                            <span class="d-inline-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                style="width:26px; height:26px; border-radius:7px; background:#f1f5f9;"
                                title="Locked by submitted Cash Handover">
                                <i class="bi bi-lock-fill" style="font-size:.72rem;"></i>
                            </span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 pt-2" style="border-top:1px dashed #e2e8f0; font-size:.76rem;">
                        <span class="text-muted fw-500">
                            <span class="badge bg-light text-dark border me-1" style="font-size:.68rem; font-weight:700;">{{ $item->quantity }} {{ $item->unit_symbol }}</span>
                            &times; TZS {{ number_format($displayPrice, 0) }}
                        </span>
                        <strong style="color:#059669; font-size:.84rem; font-variant-numeric:tabular-nums;">TZS {{ number_format($displaySubtotal, 0) }}</strong>
                    </div>

                    @if($item->components->isNotEmpty())
                        <div class="mt-2 pt-2 d-flex flex-column gap-2" style="border-top:1px solid #f1f5f9;">
                            @foreach($item->components as $component)
                                <div class="d-flex justify-content-between align-items-center gap-2 rounded-2 px-2 py-1" style="background:#f8fafc; border:1px solid #edf2f7; font-size:.73rem; color: var(--text-secondary);">
                                    <div style="word-break:break-word;">
                                        <span class="text-muted">└─</span> <strong class="text-dark">{{ $component->display_name }}</strong>
                                        <span class="text-muted ms-1">({{ $component->quantity }} {{ $component->unit_symbol }} &middot; Included)</span>
                                    </div>
                                    @if($canAddComponents && $canEditComponents)
                                        <button type="button" class="btn btn-xs btn-outline-danger remove-component-btn flex-shrink-0 d-inline-flex align-items-center justify-content-center"
                                            style="width:24px; height:24px; padding:0; border-radius:6px;"
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

            {{-- Mobile Summary Footer --}}
            <div class="d-flex justify-content-between align-items-center rounded-3 mt-1" style="padding:.72rem .9rem; background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%); border:1px solid #bbf7d0;">
                <span class="fw-700 text-uppercase" style="font-size:.7rem; letter-spacing:.04em; color:#065f46;">
                    {{ $isOwner ? 'Total Revenue' : 'Total Paid' }}
                </span>
                <strong style="color:#059669; font-size:.96rem; font-variant-numeric:tabular-nums;">TZS {{ number_format($sale->report_revenue, 0) }}</strong>
            </div>
        </div>
    </div>
</div>
