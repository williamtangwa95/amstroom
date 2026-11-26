@extends('layouts.app')
@section('title', 'Return Sale #SL-' . $sale->id)
@section('page-title', 'Request Sales Return')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
<li class="breadcrumb-item"><a href="{{ route('sales.show', $sale) }}">#SL-{{ $sale->id }}</a></li>
<li class="breadcrumb-item active">Return</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-700 text-danger">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Return items from Sale #SL-{{ $sale->id }}
                </h6>
                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-custom">
                    <i class="bi bi-arrow-left me-1"></i> Cancel
                </a>
            </div>
            <div class="card-body p-4">
                <div class="p-3 bg-light rounded-3 mb-4 small border">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <span class="text-muted">Customer Name:</span>
                            <strong class="text-dark d-block">{{ $sale->customer_name ?: 'Walk-in Customer' }}</strong>
                        </div>
                        <div class="col-6 mb-2 text-end">
                            <span class="text-muted">Sale Date:</span>
                            <strong class="text-dark d-block">{{ $sale->sale_date->format('M d, Y') }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Shop Branch:</span>
                            <strong class="text-dark d-block">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</strong>
                        </div>
                        <div class="col-6 text-end">
                            <span class="text-muted">Total Paid:</span>
                            <strong class="text-success d-block" style="font-size:1.1rem;">TZS {{ number_format($sale->total_amount, 0) }}</strong>
                        </div>
                    </div>
                </div>

                @php
                    $parentItems = $sale->items->whereNull('parent_id');
                    $parentIds = $parentItems->pluck('id')->all();
                    $orderedRows = collect();

                    foreach ($parentItems as $parentItem) {
                        $children = $sale->items->where('parent_id', $parentItem->id)->values();
                        $orderedRows->push([
                            'item'             => $parentItem,
                            'is_attachment'    => false,
                            'parent_item'      => null,
                            'attachment_count' => $children->count(),
                        ]);

                        foreach ($children as $childItem) {
                            $orderedRows->push([
                                'item'             => $childItem,
                                'is_attachment'    => true,
                                'parent_item'      => $parentItem,
                                'attachment_count' => 0,
                            ]);
                        }
                    }

                    // Fallback for any orphaned rows whose parent_id is not in parentIds
                    foreach ($sale->items->whereNotNull('parent_id') as $maybeOrphan) {
                        if (!in_array($maybeOrphan->parent_id, $parentIds, true)) {
                            $orderedRows->push([
                                'item'             => $maybeOrphan,
                                'is_attachment'    => false,
                                'parent_item'      => null,
                                'attachment_count' => 0,
                            ]);
                        }
                    }

                    $hasAnyAttachments = $orderedRows->contains(fn ($r) => $r['is_attachment']);
                @endphp

                <form method="POST" action="{{ route('sales-returns.store', $sale) }}" id="returnForm">
                    @csrf

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 mt-4">
                        <h6 class="fw-700 mb-0 text-dark">
                            <i class="bi bi-list-check text-primary me-2"></i>Select Items to Return
                        </h6>
                        @if($hasAnyAttachments)
                            <span class="small text-muted">
                                <i class="bi bi-info-circle text-primary me-1"></i>Selecting a product automatically selects its attachment(s).
                            </span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 48px;" class="text-center">Select</th>
                                    <th>Product</th>
                                    <th class="text-center">Sold Qty</th>
                                    <th style="width: 145px;" class="text-center">Return Qty</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orderedRows as $idx => $row)
                                    @php
                                        $item = $row['item'];
                                        $isAttachment = $row['is_attachment'];
                                        $parentItem = $row['parent_item'];
                                        $attachmentCount = $row['attachment_count'];
                                    @endphp
                                    <tr class="{{ $isAttachment ? 'attachment-row' : 'parent-item-row' }}"
                                        data-sale-item-id="{{ $item->id }}"
                                        data-parent-id="{{ $isAttachment ? $item->parent_id : '' }}"
                                        style="{{ $isAttachment ? 'background-color: rgba(13, 110, 253, 0.03);' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input item-select-cb"
                                                   id="item_cb_{{ $idx }}"
                                                   name="items[{{ $idx }}][selected]"
                                                   value="1"
                                                   data-idx="{{ $idx }}"
                                                   data-sale-item-id="{{ $item->id }}"
                                                   data-parent-id="{{ $isAttachment ? $item->parent_id : '' }}"
                                                   data-sold-qty="{{ $item->quantity }}"
                                                   data-parent-sold-qty="{{ $parentItem ? $parentItem->quantity : '' }}"
                                                   onchange="handleItemToggle(this)">
                                            <input type="hidden"
                                                   name="items[{{ $idx }}][sale_item_id]"
                                                   value="{{ $item->id }}"
                                                   disabled
                                                   id="sale_item_id_{{ $idx }}">
                                        </td>
                                        <td style="{{ $isAttachment ? 'padding-left: 1.75rem;' : '' }}">
                                            <div class="d-flex align-items-center flex-wrap gap-1">
                                                @if($isAttachment)
                                                    <span class="text-muted fw-semibold me-1">└─</span>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.66rem;">
                                                        <i class="bi bi-paperclip"></i> Attachment
                                                    </span>
                                                @endif
                                                <span class="fw-600 text-dark">{{ $item->display_name }}</span>
                                                @if($attachmentCount > 0)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size:.66rem;">
                                                        <i class="bi bi-paperclip me-1"></i>{{ $attachmentCount }} {{ \Illuminate\Support\Str::plural('Attachment', $attachmentCount) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($item->item && ($item->item->brand || $item->item->model))
                                                <small class="text-muted d-block {{ $isAttachment ? 'ms-4' : '' }}">
                                                    {{ trim(($item->item->brand ?? '') . ' ' . ($item->item->model ?? '')) }}
                                                </small>
                                            @endif
                                            @if($isAttachment)
                                                <small class="text-primary d-none attachment-auto-note ms-4" id="auto_note_{{ $idx }}" style="font-size:.72rem;">
                                                    <i class="bi bi-link-45deg"></i> Auto-selected with parent item
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center fw-700 text-muted">{{ $item->quantity }}</td>
                                        <td>
                                            <input type="number"
                                                   name="items[{{ $idx }}][qty]"
                                                   id="qty_input_{{ $idx }}"
                                                   class="form-control form-control-sm text-center return-qty-input"
                                                   data-idx="{{ $idx }}"
                                                   data-sale-item-id="{{ $item->id }}"
                                                   data-parent-id="{{ $isAttachment ? $item->parent_id : '' }}"
                                                   data-sold-qty="{{ $item->quantity }}"
                                                   min="1"
                                                   max="{{ $item->quantity }}"
                                                   value="{{ $item->quantity }}"
                                                   oninput="handleQtyChange(this)"
                                                   disabled
                                                   required>
                                        </td>
                                        <td class="text-end fw-600 text-dark">
                                            @if($isAttachment && (float) $item->selling_price <= 0)
                                                <span class="text-muted fst-italic small">Included</span>
                                            @else
                                                TZS {{ number_format($item->selling_price, 0) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-4 mt-4">
                        <label for="reason" class="form-label fw-600">Reason for Return <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Explain why this item is being returned (e.g. Defective, Wrong item, customer changed mind)..." required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-danger px-4" id="submitBtn" disabled>
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Submit Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setRowSelectedState(cb, isSelected, lockedByParent = false) {
    const idx = cb.dataset.idx;
    const saleItemId = document.getElementById('sale_item_id_' + idx);
    const qtyInput = document.getElementById('qty_input_' + idx);
    const autoNote = document.getElementById('auto_note_' + idx);
    const row = cb.closest('tr');

    cb.checked = isSelected;
    saleItemId.disabled = !isSelected;
    qtyInput.disabled = !isSelected;

    if (row) {
        row.classList.toggle('table-warning', isSelected);
    }

    if (autoNote) {
        autoNote.classList.toggle('d-none', !lockedByParent);
    }

    cb.dataset.lockedByParent = lockedByParent ? 'true' : 'false';
    cb.title = lockedByParent
        ? 'Automatically selected with parent item. Uncheck the parent item to deselect.'
        : '';
}

function syncAttachmentsForParent(parentCb) {
    const parentSaleItemId = parentCb.dataset.saleItemId;
    const parentIdx = parentCb.dataset.idx;
    const parentQtyInput = document.getElementById('qty_input_' + parentIdx);
    const parentSoldQty = Math.max(1, parseInt(parentCb.dataset.soldQty || '1', 10));
    const parentReturnQty = Math.max(1, Math.min(parentSoldQty, parseInt(parentQtyInput ? parentQtyInput.value : parentSoldQty, 10) || parentSoldQty));

    const childCheckboxes = document.querySelectorAll('.item-select-cb[data-parent-id="' + parentSaleItemId + '"]');
    childCheckboxes.forEach(function (childCb) {
        const childIdx = childCb.dataset.idx;
        const childQtyInput = document.getElementById('qty_input_' + childIdx);
        const childSoldQty = Math.max(1, parseInt(childCb.dataset.soldQty || '1', 10));

        if (parentCb.checked) {
            if (childQtyInput) {
                if (parentReturnQty >= parentSoldQty) {
                    childQtyInput.value = childSoldQty;
                } else {
                    const proportional = Math.round((childSoldQty / parentSoldQty) * parentReturnQty);
                    childQtyInput.value = Math.max(1, Math.min(childSoldQty, proportional));
                }
                childQtyInput.readOnly = (parentReturnQty >= parentSoldQty);
            }
            setRowSelectedState(childCb, true, true);
        } else {
            if (childQtyInput) {
                childQtyInput.value = childSoldQty;
                childQtyInput.readOnly = false;
            }
            setRowSelectedState(childCb, false, false);
        }
    });
}

function updateSubmitButtonState() {
    const btn = document.getElementById('submitBtn');
    if (btn && btn.dataset.submitting !== 'true') {
        const anyChecked = document.querySelectorAll('.item-select-cb:checked').length > 0;
        btn.disabled = !anyChecked;
    }
}

function handleItemToggle(cb) {
    const parentId = cb.dataset.parentId;

    // If this is an attachment whose parent is currently checked, keep it selected with the parent
    if (parentId) {
        const parentCb = document.querySelector('.item-select-cb[data-sale-item-id="' + parentId + '"]');
        if (parentCb && parentCb.checked && !cb.checked) {
            setRowSelectedState(cb, true, true);
            updateSubmitButtonState();
            return;
        }
        setRowSelectedState(cb, cb.checked, false);
    } else {
        setRowSelectedState(cb, cb.checked, false);
        syncAttachmentsForParent(cb);
    }

    updateSubmitButtonState();
}

function handleQtyChange(qtyInput) {
    const parentId = qtyInput.dataset.parentId;
    if (!parentId) {
        const saleItemId = qtyInput.dataset.saleItemId;
        const parentCb = document.querySelector('.item-select-cb[data-sale-item-id="' + saleItemId + '"]');
        if (parentCb && parentCb.checked) {
            syncAttachmentsForParent(parentCb);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateSubmitButtonState();

    const returnForm = document.getElementById('returnForm');
    if (returnForm) {
        returnForm.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            if (btn.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }
            btn.dataset.submitting = 'true';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting Return...';
        });
    }
});
</script>
@endpush
