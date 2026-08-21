@extends('layouts.app')
@section('title', 'Handover Details')
@section('page-title', 'Handover Report: ' . $handover->handover_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('handovers.index') }}">Handovers</a></li>
<li class="breadcrumb-item active">{{ $handover->handover_no }}</li>
@endsection

@section('content')
<style>
    @media print {
        #sidebar, #top-navbar, .btn, .breadcrumb, .alert, .modal, .card-header, .no-print {
            display: none !important;
        }
        #main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: 0 !important;
            box-shadow: none !important;
        }
        body {
            background: #fff;
            color: #000;
        }
    }
</style>

<!-- Action Buttons / Status Banner -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <span class="fs-6 me-2">Status:</span>
        @if($handover->status === 'draft')
        <span class="badge bg-secondary">Draft</span>
        @elseif($handover->status === 'submitted')
        <span class="badge bg-primary">Submitted (Pending Owner Review)</span>
        @elseif($handover->status === 'approved')
        <span class="badge bg-info text-dark">Approved (Awaiting Cash confirmation)</span>
        @elseif($handover->status === 'rejected')
        <span class="badge bg-danger">Rejected</span>
        @elseif($handover->status === 'completed')
        <span class="badge bg-success">Completed</span>
        @endif
    </div>
    
    <div class="d-flex gap-2">
        <!-- Print Button -->
        <button type="button" class="btn btn-sm btn-outline-custom" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <!-- Excel Download -->
        <a href="{{ route('handovers.export-excel', $handover) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel Report
        </a>

        <!-- Admin Actions -->
        @if(auth()->user()->isShopAdmin() && ($handover->status === 'draft' || $handover->status === 'rejected'))
        <form method="POST" action="{{ route('handovers.submit', $handover) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-accent">
                <i class="bi bi-check-circle me-1"></i> Submit Report
            </button>
        </form>
        @endif

        <!-- Owner Actions -->
        @if(auth()->user()->isOwner())
            @if($handover->status === 'submitted')
            <form method="POST" action="{{ route('handovers.approve', $handover) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-check-lg me-1"></i> Approve
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
            @endif

            @if($handover->status === 'submitted' || $handover->status === 'approved')
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#confirmReceiptModal">
                <i class="bi bi-cash-stack me-1"></i> Confirm Cash Received
            </button>
            @endif
        @endif
    </div>
</div>

@if($handover->status === 'rejected')
<div class="alert alert-danger py-2 px-3 mb-4 no-print">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Rejection Remarks:</strong> "{{ $handover->received_remarks }}"
</div>
@endif

<div class="row g-4">
    <!-- Handover Financial Summary -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2" style="color:var(--accent);"></i>Report Information</div>
            <div class="card-body py-3">
                <div class="row g-2 mb-3 border-bottom pb-2">
                    <div class="col-6">
                        <span class="text-muted small">Handover No:</span>
                        <div class="fw-bold">{{ $handover->handover_no }}</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Shop:</span>
                        <div class="fw-bold">{{ $handover->shop->shop_name }}</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Shop Admin:</span>
                        <div class="fw-bold">{{ $handover->shopAdmin->name }}</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Period:</span>
                        <div class="fw-bold small">{{ $handover->start_date->format('Y-m-d') }} to {{ $handover->end_date->format('Y-m-d') }}</div>
                    </div>
                </div>

                <div class="mb-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between text-muted mb-1">
                        <span class="small">Total Owner Sales:</span>
                        <span class="fw-bold">TZS {{ number_format($handover->total_owner_sales, 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span class="small">Total Expenses:</span>
                        <span class="fw-bold text-danger">- TZS {{ number_format($handover->total_expenses, 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="fw-bold">Admin Net Profit:</span>
                        <span class="fw-bold text-success">TZS {{ number_format($handover->net_profit, 0) }}</span>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded border text-center bg-light">
                            <span class="text-muted small">Expected Amount</span>
                            <h5 class="fw-800 text-accent mb-0">TZS {{ number_format($ho = $handover->expected_amount, 0) }}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded border text-center bg-light">
                            <span class="text-muted small">Actual Submitted</span>
                            <h5 class="fw-800 text-accent mb-0">TZS {{ number_format($handover->actual_amount, 0) }}</h5>
                        </div>
                    </div>
                </div>

                <!-- Difference status -->
                @if($handover->difference_status !== 'exact')
                <div class="alert {{ $handover->difference_status === 'shortage' ? 'alert-danger' : 'alert-success' }} py-2 px-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Difference Status:</span>
                        <span class="fw-800">{{ strtoupper($handover->difference_status) }} (TZS {{ number_format($handover->difference, 0) }})</span>
                    </div>
                    @if($handover->difference_reason)
                    <div class="mt-2 small text-dark">
                        <strong>Reason:</strong> "{{ $handover->difference_reason }}"
                    </div>
                    @endif
                </div>
                @else
                <div class="alert alert-secondary py-1 px-3 mb-3 text-center">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> Expected and Actual amounts match exactly.
                </div>
                @endif

                @if($handover->notes)
                <div class="mb-3">
                    <span class="text-muted small">Notes:</span>
                    <p class="mb-0 bg-light p-2 rounded small" style="white-space: pre-line;">{{ $handover->notes }}</p>
                </div>
                @endif

                @if($handover->attachment_path)
                <div class="mb-3 no-print">
                    <span class="text-muted small d-block mb-1">Supporting Evidence / Attachment:</span>
                    <a href="{{ asset('storage/' . $handover->attachment_path) }}" target="_blank" class="btn btn-xs btn-outline-custom w-100 py-2">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> View / Download Attachment
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Cash Confirmation Details -->
        @if($handover->status === 'completed')
        <div class="card border-0 shadow-sm border-start border-success border-4">
            <div class="card-header bg-success text-white"><i class="bi bi-check2-all me-2"></i>Cash Confirmation Details</div>
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <span class="text-muted small">Amount Received:</span>
                        <div class="fw-bold text-success" style="font-size: 1.1rem;">TZS {{ number_format($handover->amount_received, 0) }}</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Confirmed By:</span>
                        <div class="fw-bold">{{ $handover->receiver->name }}</div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small">Confirmed Date:</span>
                        <div class="fw-bold">{{ $handover->received_at ? $handover->received_at->format('Y-m-d H:i') : 'N/A' }}</div>
                    </div>
                </div>
                @if($handover->received_remarks)
                <div>
                    <span class="text-muted small">Confirmation Remarks:</span>
                    <p class="mb-0 bg-light p-2 rounded small">{{ $handover->received_remarks }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Linked Transactions Details Table -->
    <div class="col-md-7">
        <!-- Sales Log -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header"><i class="bi bi-cart-check-fill me-2" style="color:#3fb950;"></i>Sales Log ({{ $sales->count() }})</div>
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
                            $isIndependent = \App\Models\Setting::get('store_pricing_mode', 'DEPENDENT') === 'INDEPENDENT';
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
                            <td colspan="8" class="text-center py-3 text-muted">No sales in this report.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses Ledger -->
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-wallet2 me-2" style="color:#e94560;"></i>Expenses Ledger ({{ $expenses->count() }})</div>
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
                            <td colspan="6" class="text-center py-3 text-muted">No approved expenses in this report.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (Owner) -->
@if(auth()->user()->isOwner() && $handover->status === 'submitted')
<div class="modal fade no-print" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('handovers.reject', $handover) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Handover Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Rejection Remarks / Reason <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter reason for rejection" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Confirm Receipt Modal (Owner) -->
@if(auth()->user()->isOwner() && ($handover->status === 'submitted' || $handover->status === 'approved'))
<div class="modal fade no-print" id="confirmReceiptModal" tabindex="-1" aria-labelledby="confirmReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('handovers.confirm-receipt', $handover) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmReceiptModalLabel">Confirm Cash Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amount Actually Received <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">TZS</span>
                            <input type="text" id="amount_received_display" class="form-control" placeholder="Enter received cash" value="{{ number_format((float)$handover->actual_amount, 0, '.', ',') }}" required>
                            <input type="hidden" name="amount_received" id="amount_received" value="{{ $handover->actual_amount }}">
                        </div>
                        <div class="form-text small" style="font-size: 0.65rem;">Defaults to the actual amount submitted by the Shop Admin.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea name="received_remarks" class="form-control" rows="3" placeholder="Add confirmation comments or bank references"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success">Confirm Received</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var displayInput = document.getElementById('amount_received_display');
        var hiddenInput = document.getElementById('amount_received');
        
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
                var originalLen = this.value.length;

                var rawVal = this.value.replace(/,/g, '');
                hiddenInput.value = rawVal;

                this.value = formatNumber(this.value);

                var newLen = this.value.length;
                this.selectionStart = selectionStart + (newLen - originalLen);
                this.selectionEnd = selectionStart + (newLen - originalLen);
            });
        }
    });
</script>

@if($print ?? false)
<script>
    window.addEventListener('DOMContentLoaded', () => {
        window.print();
    });
</script>
@endif
@endsection
