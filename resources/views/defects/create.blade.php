@extends('layouts.app')
@section('title', 'Report Defect')
@section('page-title', $isMainStore ? 'Report Main Store Defect' : 'Report Shop Defect')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('defects.index') }}">Defects</a></li>
<li class="breadcrumb-item active">Report Defect</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
                <span class="fw-700 text-primary" style="font-size: .95rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="color:#e94560;"></i>
                    {{ $isMainStore ? 'Main Warehouse Defective Product Entry' : 'Shop Defective Product Entry' }}
                </span>
                @if($isMainStore)
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:.7rem;">
                    <i class="bi bi-building me-1"></i> Main Store
                </span>
                @endif
            </div>

            <div class="card-body p-4">
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <form method="POST" action="{{ route('defects.store') }}" id="defectForm">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    @if($isMainStore)
                    <input type="hidden" name="is_main_store" value="1">
                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i> Reporting defect directly from <strong>Main Warehouse stock</strong>.
                    </div>
                    @endif

                    <div class="row g-3">
                        {{-- Select Item --}}
                        <div class="col-12">
                            <label class="form-label fw-600">Product / Item *</label>
                            <select name="item_id" id="defectItemId" class="form-select select2 @error('item_id') is-invalid @enderror" required>
                                <option value="" data-stock="0">-- Select defective product --</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}"
                                    data-stock="{{ $item->available_stock }}"
                                    {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                    [{{ $item->category->category_name }}] {{ $item->item_name }} {{ $item->brand ? '('.$item->brand.')' : '' }} — (Available Stock: {{ $item->available_stock }})
                                </option>
                                @endforeach
                            </select>
                            <div id="stockBadgeContainer" class="mt-2">
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1 px-2" style="font-size:.75rem;" id="stockBadgeText">
                                    <i class="bi bi-info-circle me-1"></i> Select a product above to inspect available inventory.
                                </span>
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="col-md-6">
                            <label class="form-label fw-600">Defective Quantity *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                <input type="number" name="quantity" id="defectQuantity"
                                    class="form-control @error('quantity') is-invalid @enderror"
                                    value="{{ old('quantity', 1) }}" min="1" required disabled>
                            </div>
                            <div class="invalid-feedback d-block mt-1" id="qtyErrorMsg" style="display:none; font-size:.78rem;"></div>
                            <div class="form-text text-muted" id="qtyMaxInfo" style="font-size:.75rem;">
                                Maximum reportable quantity: <strong id="maxStockSpan">0 units</strong>
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="col-12">
                            <label class="form-label fw-600">Reason / Defect Description *</label>
                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                                placeholder="Describe the damage (e.g., Cracked screen, power unit fail, scratched casing, corrupted firmware)..." required>{{ old('reason') }}</textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-2 border-top">
                        <button type="submit" class="btn btn-accent py-2 px-3 fw-600" id="submitDefectBtn" disabled>
                            <i class="bi bi-check2-circle me-1"></i> Submit Defect Report
                        </button>
                        <a href="{{ route('defects.index') }}" class="btn btn-outline-custom py-2 px-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const $itemSelect = $('#defectItemId');
        const $qtyInput = $('#defectQuantity');
        const $submitBtn = $('#submitDefectBtn');
        const $stockBadgeText = $('#stockBadgeText');
        const $maxStockSpan = $('#maxStockSpan');
        const $qtyErrorMsg = $('#qtyErrorMsg');

        // Initialize Select2 if available
        if ($.fn.select2) {
            $itemSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Select defective product --'
            });
        }

        function validateAndSyncStock() {
            const selectedOpt = $itemSelect.find('option:selected');
            const itemId = $itemSelect.val();

            if (!itemId) {
                $qtyInput.prop('disabled', true).val(1);
                $submitBtn.prop('disabled', true);
                $stockBadgeText.attr('class', 'badge bg-secondary-subtle text-secondary border border-secondary-subtle py-1 px-2')
                    .html('<i class="bi bi-info-circle me-1"></i> Select a product above to inspect available inventory.');
                $maxStockSpan.text('0 units');
                $qtyErrorMsg.hide().text('');
                $qtyInput.removeClass('is-invalid');
                return;
            }

            const maxStock = parseInt(selectedOpt.data('stock')) || 0;
            $maxStockSpan.text(maxStock + (maxStock === 1 ? ' unit' : ' units'));
            $qtyInput.attr('max', maxStock);

            if (maxStock <= 0) {
                $qtyInput.prop('disabled', true).val(0);
                $submitBtn.prop('disabled', true);
                $stockBadgeText.attr('class', 'badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2')
                    .html('<i class="bi bi-x-circle me-1"></i> <strong>Out of Stock</strong> (0 units available to report)');
                $qtyErrorMsg.show().text('This product has no available stock to report as defective.');
                $qtyInput.addClass('is-invalid');
                return;
            }

            $qtyInput.prop('disabled', false);
            $stockBadgeText.attr('class', 'badge bg-success-subtle text-success border border-success-subtle py-1 px-2')
                .html(`<i class="bi bi-box-seam me-1"></i> <strong>Available In Stock:</strong> ${maxStock} units`);

            let currentQty = parseInt($qtyInput.val()) || 0;
            if (currentQty <= 0) {
                currentQty = 1;
                $qtyInput.val(1);
            }

            if (currentQty > maxStock) {
                $qtyInput.addClass('is-invalid');
                $qtyErrorMsg.show().html(`<i class="bi bi-exclamation-circle me-1"></i> Quantity exceeds available stock! Maximum allowed is <strong>${maxStock}</strong>.`);
                $submitBtn.prop('disabled', true);
            } else {
                $qtyInput.removeClass('is-invalid');
                $qtyErrorMsg.hide().text('');
                $submitBtn.prop('disabled', false);
            }
        }

        $itemSelect.on('change', validateAndSyncStock);
        $qtyInput.on('input change keyup', validateAndSyncStock);

        // Run initial check on page load if item was pre-selected (e.g. after validation redirect)
        validateAndSyncStock();

        // Form Submit Guard
        $('#defectForm').on('submit', function(e) {
            const selectedOpt = $itemSelect.find('option:selected');
            const maxStock = parseInt(selectedOpt.data('stock')) || 0;
            const currentQty = parseInt($qtyInput.val()) || 0;

            if (currentQty > maxStock || maxStock <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Bound Exceeded',
                    text: `Defective quantity (${currentQty}) cannot exceed available stock (${maxStock} units).`,
                    background: '#161b22',
                    color: '#e6edf3'
                });
                return false;
            }
        });
    });
</script>
@endpush
