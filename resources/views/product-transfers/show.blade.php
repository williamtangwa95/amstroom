@extends('layouts.app')
@section('title', 'Product Transfer ' . $productTransfer->transfer_number)
@section('page-title', 'Product Transfer ' . $productTransfer->transfer_number)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('product-transfers.index') }}">Product Transfers</a></li>
<li class="breadcrumb-item active">{{ $productTransfer->transfer_number }}</li>
@endsection

@section('content')
@php
    $user = auth()->user();
    $isPending   = $productTransfer->status === 'pending';
    $isConfirmed = $productTransfer->status === 'confirmed';
    $canConfirm  = $productTransfer->canConfirm($user);
    $canReject   = $productTransfer->canReject($user);
    $canCancel   = $productTransfer->canCancel($user);
    $canRevert   = $productTransfer->canRevert($user);

    $statusMeta = match ($productTransfer->status) {
        'pending'   => ['bg' => '#fef3c7', 'color' => '#b45309', 'icon' => 'bi-hourglass-split', 'label' => 'Pending Supplying Shop Confirmation'],
        'confirmed' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'bi-check-circle-fill', 'label' => 'Confirmed & Stock Transferred'],
        'rejected'  => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'icon' => 'bi-x-circle-fill', 'label' => 'Rejected'],
        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-slash-circle', 'label' => 'Cancelled'],
        'reverted'  => ['bg' => '#e2e8f0', 'color' => '#1e293b', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Reverted by Owner'],
        default     => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-info-circle', 'label' => ucfirst($productTransfer->status)],
    };
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        {{-- Top Banner --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:14px; overflow:hidden;">
            <div class="p-3 p-md-4 text-white d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="mb-0 fw-800 text-white">{{ $productTransfer->transfer_number }}</h5>
                        <span class="badge px-2 py-1" style="background:{{ $statusMeta['bg'] }}; color:{{ $statusMeta['color'] }}; font-size:.72rem;">
                            <i class="bi {{ $statusMeta['icon'] }} me-1"></i>{{ $statusMeta['label'] }}
                        </span>
                    </div>
                    <div class="mt-1" style="font-size:.8rem; color:rgba(255,255,255,.85);">
                        Requested on {{ $productTransfer->created_at?->format('M d, Y · h:i A') }} by <strong>{{ $productTransfer->requester?->name ?? 'Shop Admin' }}</strong>
                    </div>
                </div>
                <a href="{{ route('product-transfers.index') }}" class="btn btn-sm text-white" style="background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:9px;">
                    <i class="bi bi-arrow-left me-1"></i>All Product Transfers
                </a>
            </div>

            {{-- From -> To Direction Strip --}}
            <div class="p-3 p-md-4 bg-white">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="p-3 rounded-3 border" style="background:#fff7ed; border-color:#fed7aa !important;">
                            <div class="text-uppercase fw-700" style="font-size:.66rem; color:#c2410c;">From Shop (Stock Decreases)</div>
                            <div class="fw-800 text-dark mt-1" style="font-size:1rem;">
                                <i class="bi bi-box-arrow-up text-danger me-1"></i>{{ $productTransfer->fromShop?->shop_name ?? '—' }}
                            </div>
                            <div class="small text-muted">{{ $productTransfer->fromShop?->location ?? '' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width:42px; height:42px;">
                            <i class="bi bi-arrow-right fs-5 d-none d-md-inline"></i>
                            <i class="bi bi-arrow-down fs-5 d-md-none"></i>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="p-3 rounded-3 border" style="background:#f0fdf4; border-color:#bbf7d0 !important;">
                            <div class="text-uppercase fw-700" style="font-size:.66rem; color:#15803d;">To Shop (Stock Increases)</div>
                            <div class="fw-800 text-dark mt-1" style="font-size:1rem;">
                                <i class="bi bi-box-arrow-in-down text-success me-1"></i>{{ $productTransfer->toShop?->shop_name ?? '—' }}
                            </div>
                            <div class="small text-muted">{{ $productTransfer->toShop?->location ?? '' }}</div>
                        </div>
                    </div>
                </div>

                @if($productTransfer->notes)
                    <div class="mt-3 p-3 rounded-3 bg-light border small">
                        <strong class="text-dark">Request Notes:</strong> {{ $productTransfer->notes }}
                    </div>
                @endif

                @if($productTransfer->status === 'rejected' && $productTransfer->rejection_reason)
                    <div class="alert alert-danger mt-3 mb-0 small">
                        <strong>Rejected by {{ $productTransfer->rejecter?->name ?? 'Admin' }}:</strong> {{ $productTransfer->rejection_reason }}
                    </div>
                @elseif($productTransfer->status === 'cancelled' && $productTransfer->cancellation_reason)
                    <div class="alert alert-secondary mt-3 mb-0 small">
                        <strong>Cancelled by {{ $productTransfer->canceller?->name ?? 'Admin' }}:</strong> {{ $productTransfer->cancellation_reason }}
                    </div>
                @elseif($productTransfer->status === 'reverted' && $productTransfer->revert_reason)
                    <div class="alert alert-dark mt-3 mb-0 small">
                        <strong>Reverted by {{ $productTransfer->reverter?->name ?? 'Owner' }}:</strong> {{ $productTransfer->revert_reason }}
                    </div>
                @elseif($isConfirmed)
                    <div class="alert alert-success mt-3 mb-0 small">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Confirmed on <strong>{{ $productTransfer->approved_at?->format('M d, Y · h:i A') }}</strong> by <strong>{{ $productTransfer->approver?->name ?? 'Shop Admin' }}</strong>. Stock has been updated in both branches.
                    </div>
                @endif
            </div>
        </div>

        {{-- Requested Items & Stock Stability Table --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-700 text-dark"><i class="bi bi-boxes text-primary me-2"></i>Transferred Products, Amount &amp; Stock Impact</h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">{{ number_format($productTransfer->total_quantity) }} total units</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Total Value: TZS {{ number_format($productTransfer->total_amount, 0) }}</span>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                {{-- Desktop Table --}}
                <div class="d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-center">Transfer Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Amount (TZS)</th>
                                <th class="text-center">{{ $productTransfer->fromShop?->shop_name }} Stock</th>
                                <th class="text-center">{{ $productTransfer->toShop?->shop_name }} Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productTransfer->items as $idx => $ti)
                                @php
                                    $unit = $ti->item?->unit_symbol ?? 'pc';
                                    $unitPrice = (float) ($ti->selling_price > 0 ? $ti->selling_price : $ti->buying_price);
                                    $lineAmount = $ti->quantity * $unitPrice;
                                    $fromCur = $fromStockMap[$ti->item_id] ?? 0;
                                    $toCur = $toStockMap[$ti->item_id] ?? 0;
                                    $fromAfter = $fromCur - $ti->quantity;
                                    $toAfter = $toCur + $ti->quantity;
                                @endphp
                                <tr>
                                    <td class="text-muted fw-600">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-700 text-dark">{{ $ti->item?->item_name ?? 'Item' }}</div>
                                        <div class="small text-muted">{{ $ti->item?->category?->category_name ?? 'General' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary px-3 py-2" style="font-size:.82rem;">{{ $ti->quantity }} {{ $unit }}</span>
                                    </td>
                                    <td class="text-end small text-muted">TZS {{ number_format($unitPrice, 0) }}</td>
                                    <td class="text-end fw-700 text-dark">TZS {{ number_format($lineAmount, 0) }}</td>
                                    <td class="text-center">
                                        @if($isPending)
                                            <span class="fw-700">{{ $fromCur }}</span> &rarr;
                                            <strong class="{{ $fromAfter < 0 ? 'text-danger' : 'text-dark' }}">{{ $fromAfter }} {{ $unit }}</strong>
                                            @if($fromAfter < 0)
                                                <div class="small text-danger fw-600">Insufficient stock!</div>
                                            @endif
                                        @else
                                            <span class="fw-700 text-dark">Current: {{ $fromCur }} {{ $unit }}</span>
                                            @if($isConfirmed)
                                                <div class="small text-danger">(-{{ $ti->quantity }} {{ $unit }} moved out)</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isPending)
                                            <span class="fw-700">{{ $toCur }}</span> &rarr;
                                            <strong class="text-success">{{ $toAfter }} {{ $unit }}</strong>
                                        @else
                                            <span class="fw-700 text-dark">Current: {{ $toCur }} {{ $unit }}</span>
                                            @if($isConfirmed)
                                                <div class="small text-success">(+{{ $ti->quantity }} {{ $unit }} received)</div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8fafc;">
                                <td colspan="2" class="fw-700 text-dark">Total Transfer Summary</td>
                                <td class="text-center fw-800 text-primary">{{ number_format($productTransfer->total_quantity) }} pc</td>
                                <td></td>
                                <td class="text-end fw-800 text-success">TZS {{ number_format($productTransfer->total_amount, 0) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-md-none d-flex flex-column gap-2">
                    @foreach($productTransfer->items as $ti)
                        @php
                            $unit = $ti->item?->unit_symbol ?? 'pc';
                            $unitPrice = (float) ($ti->selling_price > 0 ? $ti->selling_price : $ti->buying_price);
                            $lineAmount = $ti->quantity * $unitPrice;
                            $fromCur = $fromStockMap[$ti->item_id] ?? 0;
                            $toCur = $toStockMap[$ti->item_id] ?? 0;
                        @endphp
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-700 text-dark">{{ $ti->item?->item_name ?? 'Item' }}</div>
                                    <div class="small text-muted">{{ $ti->item?->category?->category_name ?? 'General' }}</div>
                                </div>
                                <span class="badge bg-primary">{{ $ti->quantity }} {{ $unit }}</span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2 pb-2 border-bottom">
                                <span class="text-muted">Unit: TZS {{ number_format($unitPrice, 0) }}</span>
                                <strong class="text-success">TZS {{ number_format($lineAmount, 0) }}</strong>
                            </div>
                            <div class="small">
                                @if($isPending)
                                    <div>{{ $productTransfer->fromShop?->shop_name }}: <strong>{{ $fromCur }}</strong> &rarr; <strong class="{{ ($fromCur - $ti->quantity) < 0 ? 'text-danger' : 'text-dark' }}">{{ $fromCur - $ti->quantity }} {{ $unit }}</strong></div>
                                    <div>{{ $productTransfer->toShop?->shop_name }}: <strong>{{ $toCur }}</strong> &rarr; <strong class="text-success">{{ $toCur + $ti->quantity }} {{ $unit }}</strong></div>
                                @else
                                    <div>{{ $productTransfer->fromShop?->shop_name }} Current: <strong>{{ $fromCur }} {{ $unit }}</strong></div>
                                    <div>{{ $productTransfer->toShop?->shop_name }} Current: <strong>{{ $toCur }} {{ $unit }}</strong></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($user->isOwner())
        <div class="alert alert-info border-0 shadow-sm small d-flex align-items-center gap-2 mb-3" style="border-radius:12px;">
            <i class="bi bi-eye-fill fs-5 text-primary flex-shrink-0"></i>
            <span><strong>Owner Transparency View (Read-Only):</strong> You have full visibility into the products, quantities, transfer value, source &amp; destination shops, and approval status. Shop Admins handle request and confirmation actions directly.</span>
        </div>
        @endif

        {{-- Action Footer --}}
        @if($canConfirm || $canReject || $canCancel || $canRevert)
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small text-muted">
                    @if($canConfirm)
                        Confirming will immediately deduct stock from <strong>{{ $productTransfer->fromShop?->shop_name }}</strong> and add it to <strong>{{ $productTransfer->toShop?->shop_name }}</strong>.
                    @elseif($canCancel)
                        You can cancel this request while it is still pending confirmation.
                    @elseif($canRevert)
                        Owner Action: Reverting moves stock back to {{ $productTransfer->fromShop?->shop_name }} (if unsold).
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($canReject)
                        <button type="button" class="btn btn-outline-danger fw-600" data-bs-toggle="modal" data-bs-target="#rejectTransferModal">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    @endif
                    @if($canConfirm)
                        <form method="POST" action="{{ route('product-transfers.confirm', $productTransfer) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success fw-700 px-4">
                                <i class="bi bi-check-circle-fill me-1"></i>Confirm &amp; Move Stock
                            </button>
                        </form>
                    @endif
                    @if($canCancel)
                        <button type="button" class="btn btn-outline-secondary fw-600" data-bs-toggle="modal" data-bs-target="#cancelTransferModal">
                            <i class="bi bi-slash-circle me-1"></i>Cancel Request
                        </button>
                    @endif
                    @if($canRevert)
                        <button type="button" class="btn btn-outline-dark fw-600" data-bs-toggle="modal" data-bs-target="#revertTransferModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Revert Transfer
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
@if($canReject)
<div class="modal fade" id="rejectTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('product-transfers.reject', $productTransfer) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-700"><i class="bi bi-x-circle me-2"></i>Reject Product Transfer</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-600">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why your branch cannot fulfill this request..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-600">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Cancel Modal --}}
@if($canCancel)
<div class="modal fade" id="cancelTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('product-transfers.cancel', $productTransfer) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-700">Cancel Product Transfer Request</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-600">Reason for Cancellation <span class="text-danger">*</span></label>
                    <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Why are you cancelling this transfer request?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-secondary fw-600">Cancel Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Owner Revert Modal --}}
@if($canRevert)
<div class="modal fade" id="revertTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('product-transfers.revert', $productTransfer) }}">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h6 class="modal-title fw-700"><i class="bi bi-arrow-counterclockwise me-2"></i>Revert Confirmed Transfer</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">This will move the transferred stock back from <strong>{{ $productTransfer->toShop?->shop_name }}</strong> to <strong>{{ $productTransfer->fromShop?->shop_name }}</strong> (only allowed if units remain unsold).</p>
                    <label class="form-label small fw-600">Reason for Reverting <span class="text-danger">*</span></label>
                    <textarea name="revert_reason" class="form-control" rows="3" required placeholder="Enter audit reason for reverting this transfer..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-dark fw-600">Confirm Revert</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
