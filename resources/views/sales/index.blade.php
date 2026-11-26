@extends('layouts.app')
@section('title', 'Sales History')
@section('page-title', 'Sales Transactions')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       SALES — EXECUTIVE & RESPONSIVE
       ══════════════════════════════════════════════ */
    .sl-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .sl-title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }
    .sl-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
        letter-spacing: -.01em;
    }
    .sl-rev-pill {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .2rem .65rem;
        border-radius: 50px;
        background: rgba(16, 185, 129, .1);
        color: #059669;
        font-size: .74rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    /* ── Executive 4-Card KPI Grid ── */
    .sl-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .sl-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: .95rem 1.05rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .sl-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .sl-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.12rem;
        flex-shrink: 0;
    }
    .sl-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .sl-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sl-kpi-val {
        font-size: clamp(.9rem, 1.2vw, 1.15rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.015em;
        word-break: break-word;
    }
    .sl-kpi-sub {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sl-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }
    .sl-filter-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--text-secondary, #64748b);
        margin-bottom: .3rem;
        display: block;
    }

    @media (max-width: 991.98px) {
        .sl-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }
    }
    @media (max-width: 575.98px) {
        .sl-kpi-card {
            padding: .8rem .85rem;
            gap: .65rem;
            border-radius: 12px;
        }
        .sl-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .sl-kpi-val {
            font-size: .9rem;
        }
    }
    @media (max-width: 420px) {
        .sl-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
{{-- Compact Header --}}
<div class="sl-header">
    <div class="sl-title-row">
        <h5 class="sl-title">
            <i class="bi bi-cart-check-fill text-primary me-1"></i>
            @if(request('status') === 'draft_proforma')
                Proforma Quotes
            @elseif(request('status') === 'completed')
                Completed Sales
            @else
                Sales Transactions
            @endif
        </h5>
        <span class="sl-rev-pill">
            <i class="bi bi-graph-up"></i>
            <span id="totalRevenueText">TZS {{ number_format($totalRevenue, 0) }}</span>
        </span>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-accent shadow-sm" style="height:34px;border-radius:9px;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="bi bi-plus-circle"></i> New Sale
    </a>
</div>

{{-- Executive Payment Summary Cards --}}
<div class="sl-kpi-grid">
    <div class="sl-kpi-card">
        <div class="sl-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="sl-kpi-body">
            <p class="sl-kpi-label">Cash</p>
            <div class="sl-kpi-val" id="cardCashAmount" style="color:#059669;">TZS {{ number_format($paymentSummary['cash'] ?? 0, 0) }}</div>
            <div class="sl-kpi-sub">Physical cash</div>
        </div>
    </div>

    <div class="sl-kpi-card">
        <div class="sl-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-phone"></i>
        </div>
        <div class="sl-kpi-body">
            <p class="sl-kpi-label">Mobile Money</p>
            <div class="sl-kpi-val" id="cardMobileAmount" style="color:#0077b6;">TZS {{ number_format($paymentSummary['mobile_money'] ?? 0, 0) }}</div>
            <div class="sl-kpi-sub">M-Pesa / Tigo Pesa</div>
        </div>
    </div>

    <div class="sl-kpi-card">
        <div class="sl-kpi-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
            <i class="bi bi-credit-card"></i>
        </div>
        <div class="sl-kpi-body">
            <p class="sl-kpi-label">Card Sales</p>
            <div class="sl-kpi-val" id="cardCardAmount" style="color:#7c3aed;">TZS {{ number_format($paymentSummary['card'] ?? 0, 0) }}</div>
            <div class="sl-kpi-sub">POS / Credit card</div>
        </div>
    </div>

    <div class="sl-kpi-card">
        <div class="sl-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-bank"></i>
        </div>
        <div class="sl-kpi-body">
            <p class="sl-kpi-label">Bank Transfer</p>
            <div class="sl-kpi-val" id="cardBankAmount" style="color:#d97706;">TZS {{ number_format($paymentSummary['bank_transfer'] ?? 0, 0) }}</div>
            <div class="sl-kpi-sub">Direct bank</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="sl-card mb-3">
    <div class="p-3">
        <form id="filterForm" method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
            <div class="col-6 {{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-3' }}">
                <label class="sl-filter-label">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from', $dateFrom) }}">
            </div>
            <div class="col-6 {{ auth()->user()->isOwner() ? 'col-md-2' : 'col-md-3' }}">
                <label class="sl-filter-label">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to', $dateTo) }}">
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-12 col-sm-6 col-md-3">
                <label class="sl-filter-label">Shop</label>
                <select name="shop_id" class="form-select form-select-sm">
                    <option value="">All Shops</option>
                    <option value="main_store" {{ request('shop_id') === 'main_store' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 col-sm-6 col-md-2">
                <label class="sl-filter-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="draft_proforma" {{ request('status') === 'draft_proforma' ? 'selected' : '' }}>Proforma</option>
                </select>
            </div>
            <div class="col-12 {{ auth()->user()->isOwner() ? 'col-md-3' : 'col-md-4' }} d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-accent flex-grow-1" style="height:31px;">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-custom" style="height:31px;display:inline-flex;align-items:center;" title="Reset">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="sl-card">
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="salesTable">
            <thead>
                <tr>
                    <th data-priority="5" class="min-tablet-l text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all id-col">Sale ID</th>
                    <th data-priority="3" class="min-tablet-l">Shop</th>
                    <th data-priority="4" class="min-tablet-l">Seller</th>
                    <th data-priority="1" class="all primary-col">Customer</th>
                    <th data-priority="3" class="min-tablet-l">Items</th>
                    <th data-priority="4" class="min-tablet-l">Payment</th>
                    <th data-priority="2" class="min-tablet-l">Total Amount</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l">Date</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="editCustomerModalLabel"><i class="bi bi-person-gear me-2" style="color:var(--accent);"></i>Update Customer Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalCustomerName" class="form-label fw-600">Customer Name</label>
                        <input type="text" name="customer_name" id="modalCustomerName" class="form-control" placeholder="e.g. John Doe / Company Name" autofocus>
                        <small class="text-muted">Enter or update the customer name for this sale transaction.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Component Modal -->
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addComponentForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-700" id="addComponentModalLabel">
                        <i class="bi bi-plus-circle-dotted me-2" style="color:var(--accent);"></i>Add Component to Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 mb-1" style="font-size:.82rem;">Parent Product</label>
                        <input type="text" id="modalTargetItemName" class="form-control form-control-sm" readonly disabled style="background:var(--input-bg);">
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentSelect" class="form-label fw-600 mb-1" style="font-size:.82rem;">Select Component Item *</label>
                        <select name="component_item_id" id="modalComponentSelect" class="form-select form-select-sm" required style="width:100%;">
                            <option value="" disabled selected>Loading available items...</option>
                        </select>
                        <div id="modalStockAlert" class="mt-1" style="font-size:.75rem; color:var(--text-secondary);"></div>
                    </div>
                    <div class="mb-3">
                        <label for="modalComponentQty" class="form-label fw-600 mb-1" style="font-size:.82rem;">Quantity *</label>
                        <input type="number" name="quantity" id="modalComponentQty" class="form-control form-control-sm" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm" id="modalSubmitComponentBtn">
                        <i class="bi bi-check-lg me-1"></i> Add Component
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(() => {
        var table = $('#salesTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('sales.data') }}",
                data: function(d) {
                    var defaultFrom = '{{ $dateFrom }}';
                    var defaultTo   = '{{ $dateTo }}';
                    d.date_from = $('input[name="date_from"]').val() || defaultFrom;
                    d.date_to   = $('input[name="date_to"]').val()   || defaultTo;
                    d.status  = $('select[name="status"]').val();
                    d.shop_id = $('select[name="shop_id"]').val();
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration', orderable: false, searchable: false },
                { data: 'sale_id', name: 'sale_id' },
                { data: 'shop', name: 'shop' },
                { data: 'seller', name: 'seller' },
                { data: 'customer', name: 'customer' },
                { data: 'items', name: 'items' },
                { data: 'payment_method', name: 'payment_method' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'status', name: 'status' },
                { data: 'date', name: 'date' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[9, 'desc']],
            drawCallback: function(settings) {
                if (settings.json && settings.json.formattedTotalRevenue) {
                    $('#totalRevenueText').text(settings.json.formattedTotalRevenue);
                }
                if (settings.json && settings.json.paymentSummary) {
                    var ps = settings.json.paymentSummary;
                    if (ps.cash        !== undefined) $('#cardCashAmount').text(ps.cash);
                    if (ps.mobile_money !== undefined) $('#cardMobileAmount').text(ps.mobile_money);
                    if (ps.card        !== undefined) $('#cardCardAmount').text(ps.card);
                    if (ps.bank_transfer !== undefined) $('#cardBankAmount').text(ps.bank_transfer);
                }
            }
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            table.draw();
        });

        var detailsCache = {};
        var activeSaleIdForComp = null;

        function refreshSaleDetails(saleId) {
            delete detailsCache[saleId];
            var mobileContainer = $('#salesTable tbody .mobile-inline-details[data-details-id="' + saleId + '"]');
            var btn = $('.toggle-details[data-id="' + saleId + '"]');
            var tr = btn.closest('tr');
            var isInsideChildRow = tr.hasClass('child') || tr.hasClass('dtr-child') || btn.closest('.dtr-details').length > 0;

            if (mobileContainer.length && mobileContainer.is(':visible')) {
                $.ajax({
                    url: "{{ url('sales') }}/" + saleId + "/details",
                    method: 'GET',
                    success: function(html) {
                        detailsCache[saleId] = html;
                        mobileContainer.html(html);
                    }
                });
                return;
            }

            if (!isInsideChildRow) {
                var row = table.row(tr);
                if (row && row.child.isShown()) {
                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[saleId] = html;
                            row.child(html).show();
                        }
                    });
                }
            }
        }

        $('#salesTable tbody').on('click', '.toggle-details', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var btn = $(this);
            var tr = btn.closest('tr');
            var saleId = btn.data('id');
            var icon = btn.find('i');
            var isInsideChildRow = tr.hasClass('child') || tr.hasClass('dtr-child') || btn.closest('.dtr-details').length > 0;

            // Mobile mode: button lives inside DataTables Responsive tr.child drawer
            if (isInsideChildRow) {
                var childCell = btn.closest('td.child');
                if (!childCell.length) {
                    childCell = tr.find('td.child').first();
                }
                var container = childCell.find('.mobile-inline-details[data-details-id="' + saleId + '"]');

                if (container.length && container.is(':visible')) {
                    container.slideUp(180);
                    icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                    return;
                }

                if (!container.length) {
                    container = $('<div class="mobile-inline-details mt-2 w-100" data-details-id="' + saleId + '" style="display:none;"></div>');
                    childCell.append(container);
                }

                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                if (detailsCache[saleId]) {
                    container.html(detailsCache[saleId]).slideDown(200);
                } else {
                    container.html('<div class="p-3 text-center text-muted small"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading sold items...</div>').slideDown(180);

                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[saleId] = html;
                            container.html(html);
                        },
                        error: function() {
                            container.html('<div class="p-3 text-center text-danger small"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load sale details.</div>');
                        }
                    });
                }
                return;
            }

            // Desktop mode: standard DataTables row.child()
            var row = table.row(tr);
            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                if (detailsCache[saleId]) {
                    row.child(detailsCache[saleId]).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    var loadingHtml = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading details...</div>';
                    row.child(loadingHtml).show();
                    tr.addClass('shown');
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/details",
                        method: 'GET',
                        success: function(html) {
                            detailsCache[saleId] = html;
                            if (row.child.isShown()) {
                                row.child(html).show();
                            }
                        },
                        error: function() {
                            var errorHtml = '<div class="p-3 text-center text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Unable to load sale details.</div>';
                            row.child(errorHtml).show();
                        }
                    });
                }
            }
        });

        $(document).on('click', '.edit-customer-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('id');
            var customerName = $(this).data('name') || '';
            var actionUrl = "{{ url('sales') }}/" + saleId + "/customer";
            
            $('#editCustomerForm').attr('action', actionUrl);
            $('#modalCustomerName').val(customerName);
            $('#editCustomerModal').modal('show');
        });

        $(document).on('click', '.open-add-component-modal-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');

            activeSaleIdForComp = saleId;
            $('#modalTargetItemName').val(itemName);
            $('#modalComponentQty').val(1);
            $('#modalStockAlert').html('');
            $('#modalSubmitComponentBtn').prop('disabled', true);

            var actionUrl = "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/components";
            $('#addComponentForm').attr('action', actionUrl);

            var select = $('#modalComponentSelect');
            select.html('<option value="" disabled selected>Loading available items...</option>');

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            $('#addComponentModal').modal('show');

            $.ajax({
                url: "{{ url('sales') }}/" + saleId + "/items/" + itemId + "/available-components",
                method: 'GET',
                success: function(res) {
                    if (res.success && res.items && res.items.length > 0) {
                        var options = '<option value="" disabled selected>Search / select item...</option>';
                        res.items.forEach(function(item) {
                            var escapedName = $('<div>').text(item.item_name).html();
                            options += '<option value="' + item.id + '" data-stock="' + item.stock + '">' + escapedName + ' (' + item.brand + ') [Stock: ' + item.stock + ']</option>';
                        });
                        select.html(options);
                        $('#modalSubmitComponentBtn').prop('disabled', false);
                    } else {
                        select.html('<option value="" disabled selected>No available items in stock</option>');
                        $('#modalStockAlert').html('<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>No stock available for components in this store.</span>');
                    }

                    select.select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('#addComponentModal'),
                        width: '100%'
                    });
                },
                error: function() {
                    select.html('<option value="" disabled selected>Error loading items</option>');
                    $('#modalStockAlert').html('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Failed to load items.</span>');
                }
            });
        });

        $('#modalComponentSelect').on('change', function() {
            var opt = $(this).find('option:selected');
            var stock = opt.data('stock');
            if (stock !== undefined) {
                $('#modalStockAlert').html('<span class="text-success"><i class="bi bi-box-seam me-1"></i>Available in stock: <strong>' + stock + '</strong></span>');
                $('#modalComponentQty').attr('max', stock);
            }
        });

        $('#addComponentForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = $('#modalSubmitComponentBtn');
            var origHtml = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Adding...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(res) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    $('#addComponentModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Component Added',
                        text: res.message || 'Component added successfully.',
                        timer: 2500,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                    if (activeSaleIdForComp) {
                        refreshSaleDetails(activeSaleIdForComp);
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(origHtml);
                    var msg = 'Failed to add component.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg,
                        background: '#161b22',
                        color: '#e6edf3'
                    });
                }
            });
        });

        $(document).on('click', '.remove-component-btn', function(e) {
            e.preventDefault();
            var saleId = $(this).data('sale-id');
            var compId = $(this).data('component-id');
            var compName = $(this).data('component-name') || 'this component';

            Swal.fire({
                title: 'Remove Component?',
                text: 'Are you sure you want to remove "' + compName + '" from this sale? The quantity will be restored to inventory stock.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e94560',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it',
                background: '#161b22',
                color: '#e6edf3'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('sales') }}/" + saleId + "/components/" + compId,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed',
                                text: res.message || 'Component removed successfully.',
                                timer: 2500,
                                showConfirmButton: false,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                            refreshSaleDetails(saleId);
                        },
                        error: function(xhr) {
                            var msg = 'Failed to remove component.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                                background: '#161b22',
                                color: '#e6edf3'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush

