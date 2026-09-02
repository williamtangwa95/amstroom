@extends('layouts.app')
@section('title', 'Generate Handover')
@section('page-title', 'Generate Sales Cash Handover')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('handovers.index') }}">Handovers</a></li>
<li class="breadcrumb-item active">New Report</li>
@endsection

@section('content')
@if($overlapExists)
<div class="alert alert-danger py-2 px-3 mb-4" style="background:rgba(233, 69, 96, 0.1); border-color: rgba(233, 69, 96, 0.2); color: #e94560;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Overlap Alert:</strong> A handover report already covers some or all of the selected dates. Please adjust the range to prevent duplicates.
</div>
@endif

<div class="row g-4">
    <!-- Calculation & Input Column -->
    <div class="col-md-5">
        <!-- Date Selector -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><i class="bi bi-calendar-event me-2" style="color:var(--accent);"></i>Handover Period</div>
            <div class="card-body">
                <form method="GET" action="{{ route('handovers.create') }}">
                    @if(auth()->user()->isOwner())
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Shop</label>
                        <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($shops as $s)
                            <option value="{{ $s->id }}" {{ $shop->id == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    @endif

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-custom w-100 mt-3">
                        <i class="bi bi-arrow-clockwise me-1"></i> Calculate Figures
                    </button>
                </form>
            </div>
        </div>

        <!-- Handover Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-cash-coin me-2" style="color:#ffb700;"></i>Settlement Calculations</div>
            <div class="card-body">
                <form method="POST" action="{{ route('handovers.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">

                    <!-- Stats Breakdown -->
                    <div class="mb-3 py-2 border-bottom">
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span class="small">Total Owner Sales:</span>
                            <span class="fw-bold">TZS {{ number_format($totalOwnerSales, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted mb-2">
                            <span class="small">Total Approved Expenses:</span>
                            <span class="fw-bold text-danger">- TZS {{ number_format($totalExpenses, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="fw-bold">Admin Profit:</span>
                            <span class="fw-bold text-success">TZS {{ number_format($netProfit, 0) }}</span>
                        </div>
                    </div>

                    <!-- Expected Amount -->
                    <div class="p-3 mb-4 rounded border text-center" style="background: rgba(0, 136, 204, 0.05); border-color: rgba(0, 136, 204, 0.15) !important;">
                        <h6 class="text-muted small uppercase mb-1">Expected Amount to Submit</h6>
                        <h3 class="fw-800 text-accent mb-0">TZS {{ number_format($expectedAmount, 0) }}</h3>
                        <input type="hidden" id="expected_amount_val" value="{{ $expectedAmount }}">
                    </div>

                    <!-- User Handover Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Actual Amount Submitted <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">TZS</span>
                            <input type="text" id="actual_amount_display" class="form-control" placeholder="Enter submitted cash" value="{{ old('actual_amount') ? number_format((float)old('actual_amount'), 0, '.', ',') : '' }}" required>
                            <input type="hidden" name="actual_amount" id="actual_amount" value="{{ old('actual_amount') }}">
                        </div>
                    </div>

                    <!-- Requested Commission -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Requested Commission to be Paid</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">TZS</span>
                            <input type="text" id="commission_amount_display" class="form-control" placeholder="Enter requested commission (optional)" value="{{ old('commission_amount') ? number_format((float)old('commission_amount'), 0, '.', ',') : '' }}">
                            <input type="hidden" name="commission_amount" id="commission_amount" value="{{ old('commission_amount') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="small fw-bold">Difference Status:</span>
                            <span id="difference_text" class="text-muted fw-bold">Exact Match</span>
                        </div>
                    </div>

                    <!-- Difference Reason (Mandatory on shortage/excess) -->
                    <input type="hidden" id="needs_reason" name="needs_reason" value="0">
                    <div class="mb-3" id="difference_reason_div" style="display: none;">
                        <label class="form-label small fw-bold">Difference Reason <span class="text-danger">*</span></label>
                        <select name="difference_reason" class="form-select form-select-sm">
                            <option value="">-- Select Reason --</option>
                            <option value="Expense paid directly">Expense paid directly</option>
                            <option value="Stock discrepancy">Stock discrepancy</option>
                            <option value="Manual cash adjustment">Manual cash adjustment</option>
                            <option value="Unresolved mismatch">Unresolved mismatch</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Explanation / Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Add details or remarks">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Supporting Evidence / Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.png,.jpg,.jpeg">
                        <div class="form-text small" style="font-size: 0.65rem;">Accepted types: PDF, PNG, JPG, JPEG (Max 5MB). Upload bank receipt, Airtel Money/M-Pesa statement, invoice, or defect proof.</div>
                    </div>

                    <!-- Buttons -->
                    @if(!$overlapExists)
                    <div class="d-flex gap-2">
                        <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary w-50">
                            <i class="bi bi-save me-1"></i> Save Draft
                        </button>
                        <button type="submit" name="submit_action" value="submit" class="btn btn-accent w-50">
                            <i class="bi bi-check-circle me-1"></i> Submit Report
                        </button>
                    </div>
                    @else
                    <button type="button" class="btn btn-secondary w-100" disabled>Overlapping Dates Detected</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- Details Breakdown Column -->
    <div class="col-md-7">
        <!-- Sales Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart-check-fill me-2" style="color:#3fb950;"></i>Sales Log ({{ $sales->count() }})</span>
                <span class="badge bg-secondary">{{ $sales->count() }} Invoice(s)</span>
            </div>
            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th class="text-end">Selling Price</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1 @endphp
                        @php
                            $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'INDEPENDENT') === 'INDEPENDENT';
                        @endphp
                        @forelse($sales as $sale)
                            @foreach($sale->items as $item)
                                @if($item->is_admin_stock)
                                    @continue
                                @endif
                                @php
                                    $priceVal = ($isIndependent && $sale->shop_id !== null) ? ($item->owner_realized_sp ?? $item->selling_price) : ($item->shop_realized_sp ?? $item->selling_price);
                                @endphp
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="small">{{ $sale->sale_date->format('Y-m-d') }}</td>
                                    <td>{{ $item->display_name }}</td>
                                    <td>
                                        <span class="badge bg-primary" style="font-size:0.6rem;">Owner</span>
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end">TZS {{ number_format($priceVal, 0) }}</td>
                                    <td class="text-end text-muted">TZS {{ number_format($item->owner_cost_price, 0) }}</td>
                                    <td class="text-end fw-bold">TZS {{ number_format($priceVal * $item->quantity, 0) }}</td>
                                </tr>
                            @endforeach
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-3 text-muted">No sales in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses Details -->
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-wallet2 me-2" style="color:#e94560;"></i>Expenses Ledger ({{ $expenses->count() }})</span>
                <span class="badge bg-secondary">Approved Only</span>
            </div>
            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Activity</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $index => $exp)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="small">{{ $exp->activity_date->format('Y-m-d') }}</td>
                            <td><span class="badge bg-light text-dark">{{ $exp->category->name }}</span></td>
                            <td><strong>{{ $exp->activity }}</strong></td>
                            <td class="text-muted small">{{ $exp->description }}</td>
                            <td class="text-end fw-bold text-danger">TZS {{ number_format($exp->amount, 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No approved expenses in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var displayInput = document.getElementById('actual_amount_display');
        var hiddenInput = document.getElementById('actual_amount');
        var commDisplayInput = document.getElementById('commission_amount_display');
        var commHiddenInput = document.getElementById('commission_amount');
        
        function formatNumber(val) {
            var clean = val.replace(/[^\d.]/g, '');
            if (!clean) return '';
            var parts = clean.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        if (displayInput && hiddenInput) {
            displayInput.addEventListener('input', function(e) {
                var selectionStart = this.selectionStart;
                var selectionEnd = this.selectionEnd;
                var originalLen = this.value.length;

                var rawVal = this.value.replace(/,/g, '');
                hiddenInput.value = rawVal;

                this.value = formatNumber(this.value);

                var newLen = this.value.length;
                this.selectionStart = selectionStart + (newLen - originalLen);
                this.selectionEnd = selectionEnd + (newLen - originalLen);

                // Calculate difference
                var expected = parseFloat(document.getElementById('expected_amount_val').value) || 0;
                var actual = parseFloat(rawVal) || 0;
                var diff = actual - expected;
                
                var diffText = document.getElementById('difference_text');
                var reasonDiv = document.getElementById('difference_reason_div');
                var needsReasonInput = document.getElementById('needs_reason');

                if (Math.abs(diff) < 0.01) {
                    diffText.innerHTML = "Exact Match";
                    diffText.className = "text-muted fw-bold";
                    reasonDiv.style.display = "none";
                    needsReasonInput.value = "0";
                } else if (diff < 0) {
                    diffText.innerHTML = "Shortage: TZS " + Math.abs(diff).toLocaleString();
                    diffText.className = "text-danger fw-bold";
                    reasonDiv.style.display = "block";
                    needsReasonInput.value = "1";
                } else {
                    diffText.innerHTML = "Excess: TZS " + diff.toLocaleString();
                    diffText.className = "text-success fw-bold";
                    reasonDiv.style.display = "block";
                    needsReasonInput.value = "1";
                }
            });
        }

        if (commDisplayInput && commHiddenInput) {
            commDisplayInput.addEventListener('input', function(e) {
                var selectionStart = this.selectionStart;
                var selectionEnd = this.selectionEnd;
                var originalLen = this.value.length;

                var rawVal = this.value.replace(/,/g, '');
                commHiddenInput.value = rawVal;

                this.value = formatNumber(this.value);

                var newLen = this.value.length;
                this.selectionStart = selectionStart + (newLen - originalLen);
                this.selectionEnd = selectionEnd + (newLen - originalLen);
            });
        }
    });
</script>
@endsection
