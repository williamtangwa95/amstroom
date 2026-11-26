@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('page-title', 'Stock Transfers & Dispatches')

@section('breadcrumb')
<li class="breadcrumb-item active">Stock Transfers</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       STOCK TRANSFERS INDEX — Executive Responsive UI
       ══════════════════════════════════════════════ */
    .sti-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px;
        padding: 1rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .18);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .sti-hero-top {
        display: flex;
        align-items: center;
        gap: .55rem;
        flex-wrap: wrap;
    }
    .sti-hero-title {
        font-size: 1.08rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -.01em;
        line-height: 1.25;
    }
    .sti-hero-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .16rem .58rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .2);
        color: #ffffff;
        font-size: .64rem;
        font-weight: 600;
        letter-spacing: .02em;
    }
    .sti-hero-sub {
        font-size: .76rem;
        color: rgba(255, 255, 255, .8);
        margin: .22rem 0 0;
        line-height: 1.35;
    }
    .sti-hero-cta {
        display: inline-flex;
        align-items: center;
        gap: .42rem;
        padding: .5rem 1rem;
        border-radius: 9px;
        background: #ffffff;
        color: #005691;
        text-decoration: none;
        font-size: .8rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .14);
        transition: all .15s ease;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .sti-hero-cta:hover {
        background: #f0f9ff;
        color: #003a6c;
        transform: translateY(-1px);
    }

    /* ── KPI Strip ── */
    .sti-kpi-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 13px;
        padding: .85rem .95rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        display: flex;
        align-items: center;
        gap: .7rem;
        height: 100%;
        overflow: hidden;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .sti-kpi-card > .min-w-0 {
        flex: 1;
        min-width: 0;
    }
    .sti-kpi-card:hover {
        border-color: rgba(0, 119, 182, .35);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .07);
        transform: translateY(-1px);
    }
    .sti-kpi-card.is-selected {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .1);
    }
    .sti-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .sti-kpi-label {
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin: 0;
    }
    .sti-kpi-val {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.15;
        margin-top: .1rem;
    }
    .sti-kpi-sub {
        font-size: .68rem;
        color: #64748b;
        margin-top: .06rem;
    }

    /* ── Pending Callout for Shop Admin ── */
    .sti-pending-banner {
        background: #ffffff;
        border: 1px solid #fde68a;
        border-left: 4px solid #d97706;
        border-radius: 12px;
        padding: .85rem 1.1rem;
        box-shadow: 0 2px 10px rgba(217, 119, 6, .06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        flex-wrap: wrap;
        margin-bottom: 1.15rem;
    }
    .sti-pending-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #fef3c7;
        color: #d97706;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* ── Cards & Filter Bar ── */
    .sti-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        overflow: hidden;
    }
    .sti-filter-body {
        padding: .95rem 1.15rem;
    }
    .sti-filter-label {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: .32rem;
    }
    .sti-filter-label i {
        color: #0077b6;
    }
    .sti-card-header {
        padding: .9rem 1.2rem;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .sti-card-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        font-size: .92rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .sti-card-title-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .92rem;
        flex-shrink: 0;
    }
    .sti-table-body {
        padding: 1rem 1.2rem 1.15rem;
    }

    @media (max-width: 767.98px) {
        .sti-hero {
            padding: .9rem 1rem;
        }
        .sti-hero-cta {
            width: 100%;
            justify-content: center;
        }
        .sti-filter-body,
        .sti-table-body {
            padding: .85rem .9rem;
        }
        .sti-card-header {
            padding: .8rem .95rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $isOwner = auth()->user()->isOwner();
    $isShopAdmin = auth()->user()->isShopAdmin();
    $pendingCount = $stats['pending'] ?? 0;
@endphp

{{-- ── Executive Header Banner ── --}}
<div class="sti-hero">
    <div style="flex:1; min-width:0;">
        <div class="sti-hero-top">
            <h1 class="sti-hero-title">Stock Transfers &amp; Dispatches</h1>
            <span class="sti-hero-pill">
                <i class="bi bi-truck"></i>
                {{ $isOwner ? 'Warehouse Dispatch' : (auth()->user()->shop?->shop_name ?? 'Branch Receipts') }}
            </span>
        </div>
        <p class="sti-hero-sub">
            @if($isOwner)
                Dispatch inventory from Main Store to branches and monitor receipt confirmations in real time.
            @else
                Track incoming stock dispatches from Main Store and confirm physical delivery for your branch.
            @endif
        </p>
    </div>

    @if($isOwner)
        <a href="{{ route('stock-transfers.create') }}" class="sti-hero-cta">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Assign Stock to Shop</span>
        </a>
    @elseif($pendingCount > 0)
        <button type="button" class="sti-hero-cta" id="btnQuickFilterPending">
            <i class="bi bi-hourglass-split text-warning"></i>
            <span>{{ $pendingCount }} Awaiting Receipt</span>
        </button>
    @endif
</div>

{{-- ── KPI Summary Strip (Clickable Quick Filters) ── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="sti-kpi-card is-selected" data-quick-status="" title="Show all transfers">
            <div class="sti-kpi-icon" style="background:rgba(0,119,182,.1); color:#0077b6;">
                <i class="bi bi-truck"></i>
            </div>
            <div class="min-w-0">
                <p class="sti-kpi-label">Total Transfers</p>
                <div class="sti-kpi-val">{{ number_format($stats['total'] ?? 0) }}</div>
                <div class="sti-kpi-sub">All recorded dispatches</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sti-kpi-card" data-quick-status="awaiting" title="Filter awaiting receipt">
            <div class="sti-kpi-icon" style="background:rgba(217,119,6,.12); color:#d97706;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="min-w-0">
                <p class="sti-kpi-label">Awaiting Receipt</p>
                <div class="sti-kpi-val">{{ number_format($stats['pending'] ?? 0) }}</div>
                <div class="sti-kpi-sub">Pending or partial</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sti-kpi-card" data-quick-status="received" title="Filter fully received">
            <div class="sti-kpi-icon" style="background:rgba(16,185,129,.12); color:#059669;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="sti-kpi-label">Fully Received</p>
                <div class="sti-kpi-val">{{ number_format($stats['received'] ?? 0) }}</div>
                <div class="sti-kpi-sub">Confirmed by branch</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sti-kpi-card" data-quick-status="rejected" title="Filter rejected transfers">
            <div class="sti-kpi-icon" style="background:rgba(220,38,38,.1); color:#dc2626;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="min-w-0">
                <p class="sti-kpi-label">Has Rejections</p>
                <div class="sti-kpi-val">{{ number_format($stats['rejected'] ?? 0) }}</div>
                <div class="sti-kpi-sub">Discrepancies flagged</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Shop Admin Pending Callout ── --}}
@if($isShopAdmin && $pendingCount > 0)
<div class="sti-pending-banner">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <div class="sti-pending-icon">
            <i class="bi bi-bell-fill"></i>
        </div>
        <div class="min-w-0">
            <div class="fw-700 text-dark" style="font-size:.86rem;">
                {{ $pendingCount }} dispatch{{ $pendingCount > 1 ? 'es' : '' }} awaiting your receipt confirmation
            </div>
            <div class="text-secondary" style="font-size:.76rem;">
                Click <strong>Review</strong> on any pending transfer below to verify quantities and add stock to your branch.
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-warning fw-600 px-3" id="btnBannerFilterPending" style="border-radius:8px; font-size:.76rem; white-space:nowrap;">
        <i class="bi bi-funnel-fill me-1"></i> Show Pending Only
    </button>
</div>
@endif

{{-- ── Filter Bar Card ── --}}
<div class="sti-card mb-3">
    <div class="sti-filter-body">
        <div class="row g-2 align-items-end">
            {{-- Filter by Product / Item --}}
            <div class="col-12 col-sm-6 {{ $isOwner ? 'col-lg-3' : 'col-lg-4' }}">
                <label for="filterItem" class="sti-filter-label">
                    <i class="bi bi-box-seam"></i>Filter by Product / Item
                </label>
                <select id="filterItem" class="form-select form-select-sm">
                    <option value="">All Products</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->item_name }}{{ $item->brand ? ' (' . $item->brand . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter by Destination Shop --}}
            @if($isOwner)
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="filterShop" class="sti-filter-label">
                    <i class="bi bi-shop"></i>Destination Shop
                </label>
                <select id="filterShop" class="form-select form-select-sm">
                    <option value="">All Destination Shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}">{{ $shop->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Filter by Dispatched By --}}
            <div class="col-12 col-sm-6 {{ $isOwner ? 'col-lg-2' : 'col-lg-3' }}">
                <label for="filterDispatcher" class="sti-filter-label">
                    <i class="bi bi-person-badge"></i>Dispatched By
                </label>
                <select id="filterDispatcher" class="form-select form-select-sm">
                    <option value="">All Dispatchers</option>
                    @foreach($dispatchers as $disp)
                        <option value="{{ $disp->id }}">{{ $disp->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter by Status --}}
            <div class="col-12 col-sm-6 {{ $isOwner ? 'col-lg-2' : 'col-lg-3' }}">
                <label for="filterStatus" class="sti-filter-label">
                    <i class="bi bi-flag"></i>Status
                </label>
                <select id="filterStatus" class="form-select form-select-sm no-select2">
                    <option value="">All Statuses</option>
                    <option value="awaiting">Awaiting Receipt (Pending &amp; Partial)</option>
                    <option value="pending_receipt">Pending Receipt</option>
                    <option value="partially_received">Partially Received</option>
                    <option value="received">Fully Received</option>
                    <option value="rejected">Has Rejections</option>
                </select>
            </div>

            {{-- Reset Button --}}
            <div class="col-12 col-lg-2">
                <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-custom w-100" title="Reset Filters" style="height:31px;">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Transfers DataTable Card ── --}}
<div class="sti-card">
    <div class="sti-card-header">
        <h6 class="sti-card-title">
            <span class="sti-card-title-icon"><i class="bi bi-list-ul"></i></span>
            <span>All Stock Transfers</span>
            <span class="badge bg-light text-secondary border rounded-pill" style="font-size:.68rem;">{{ number_format($stats['total'] ?? 0) }}</span>
        </h6>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($isOwner)
                <button type="button" id="btnBulkDeleteTransfers" class="btn btn-sm btn-outline-danger d-none">
                    <i class="bi bi-trash-fill me-1"></i> Delete Selected (<span id="selectedTransfersCount">0</span>)
                </button>
            @endif
        </div>
    </div>

    <div class="sti-table-body">
        <table class="table table-hover align-middle w-100 dt-mobile-table {{ $isOwner ? 'dt-has-checkbox' : '' }}" id="transfersTable">
            <thead>
                <tr>
                    @if($isOwner)
                        <th data-priority="1000" style="width: 38px;" class="min-tablet-l no-sort text-center">
                            <input type="checkbox" class="form-check-input" id="selectAllTransfers" style="cursor:pointer;">
                        </th>
                    @endif
                    <th data-priority="1" class="all id-col text-center" style="width: 60px;">ID</th>
                    <th data-priority="2" class="min-tablet-l">Transfer Date</th>
                    <th data-priority="1" class="all primary-col">Destination Shop</th>
                    <th data-priority="2" class="min-tablet-l">Items</th>
                    <th data-priority="4" class="min-tablet-l">Dispatched By</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end" style="min-width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    const isOwner = {{ auth()->user()->isOwner() ? 'true' : 'false' }};

    function syncKpiSelection(statusVal) {
        $('.sti-kpi-card').removeClass('is-selected');
        const $match = $(`.sti-kpi-card[data-quick-status="${statusVal || ''}"]`);
        if ($match.length) {
            $match.addClass('is-selected');
        }
    }

    if ($.fn.DataTable) {
        var columns = [];
        if (isOwner) {
            columns.push({ data: 'checkbox', name: 'checkbox', orderable: false, searchable: false });
        }
        columns.push({ data: 'iteration', name: 'iteration', className: 'text-center' });
        columns.push({ data: 'transfer_date', name: 'transfer_date' });
        columns.push({ data: 'destination_shop', name: 'destination_shop' });
        columns.push({ data: 'items', name: 'items', orderable: false, searchable: false });
        columns.push({ data: 'dispatched_by', name: 'dispatched_by' });
        columns.push({ data: 'status', name: 'status' });
        columns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' });

        const table = $('#transfersTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('stock-transfers.data') }}",
                data: function(d) {
                    d.item_id = $('#filterItem').val();
                    d.shop_id = $('#filterShop').val();
                    d.dispatched_by = $('#filterDispatcher').val();
                    d.status = $('#filterStatus').val();
                }
            },
            columns: columns,
            order: [[isOwner ? 2 : 1, 'desc']],
            language: {
                search: '',
                searchPlaceholder: 'Search transfers, shop, or items...',
                emptyTable: 'No stock transfers found.'
            }
        });

        $('#filterItem, #filterShop, #filterDispatcher, #filterStatus').on('change', function() {
            syncKpiSelection($('#filterStatus').val());
            table.ajax.reload();
        });

        // Clicking KPI cards applies quick status filter
        $('.sti-kpi-card').on('click', function() {
            const st = $(this).data('quick-status') || '';
            $('#filterStatus').val(st).trigger('change');
        });

        // Quick pending filter buttons for Shop Admin
        $('#btnQuickFilterPending, #btnBannerFilterPending').on('click', function() {
            $('#filterStatus').val('awaiting').trigger('change');
            $('html, body').animate({ scrollTop: $('#transfersTable').offset().top - 120 }, 250);
        });

        $('#btnResetFilters').on('click', function() {
            $('#filterItem').val('');
            $('#filterShop').val('');
            $('#filterDispatcher').val('');
            $('#filterStatus').val('');
            if ($.fn.select2) {
                $('#filterItem, #filterShop, #filterDispatcher').trigger('change.select2');
            }
            syncKpiSelection('');
            table.ajax.reload();
        });
    }

    // Hidden forms (avoid nested form issues)
    $('body').append('<form id="deleteTransferForm" method="POST" style="display:none;"><input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE"></form>');
    $('body').append('<form id="bulkDeleteTransfersForm" action="{{ route("stock-transfers.bulk-destroy") }}" method="POST" style="display:none;"><input name="_token" value="{{ csrf_token() }}"><input name="_method" value="DELETE"></form>');

    // Handle Select All Transfers Checkbox
    $(document).on('change', '#selectAllTransfers', function() {
        const isChecked = $(this).is(':checked');
        $('.transfer-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Handle Individual Transfer Checkbox Change
    $(document).on('change', '.transfer-checkbox', function() {
        const totalCheckboxes = $('.transfer-checkbox').length;
        const checkedCheckboxes = $('.transfer-checkbox:checked').length;

        $('#selectAllTransfers').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        $('#selectedTransfersCount').text(checkedCheckboxes);

        if (checkedCheckboxes > 0) {
            $('#btnBulkDeleteTransfers').removeClass('d-none');
        } else {
            $('#btnBulkDeleteTransfers').addClass('d-none');
        }
    });

    // Handle Single Transfer Delete
    $(document).on('click', '.btn-delete-transfer', function() {
        const url = $(this).data('url');
        const id  = $(this).data('id');
        Swal.fire({
            title: 'Delete Transfer #' + id + '?',
            html: 'All transfer items (including received stock) will be removed and stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete transfer',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteTransferForm');
                form.action = url;
                form.submit();
            }
        });
    });

    // Handle Bulk Delete Transfers Action
    $(document).on('click', '#btnBulkDeleteTransfers', function() {
        const selectedIds = $('.transfer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) return;

        Swal.fire({
            title: 'Delete ' + selectedIds.length + ' Transfer(s)?',
            html: 'All transfer items for the ' + selectedIds.length + ' selected transfer(s) will be removed and their stock <strong>returned to the Main Warehouse</strong>.<br>This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete selected',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                const $form = $('#bulkDeleteTransfersForm');
                $form.find('input[name="ids[]"]').remove();
                selectedIds.forEach(function(id) {
                    $form.append('<input type="hidden" name="ids[]" value="' + id + '">');
                });
                $form.submit();
            }
        });
    });
});
</script>
@endpush
