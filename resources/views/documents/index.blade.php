@extends('layouts.app')
@section('title', 'Documents Center')
@section('page-title', 'Documents Center')

@section('breadcrumb')
<li class="breadcrumb-item active">Documents</li>
@endsection

@section('content')
<style>
    /* ══════════════════════════════════════════════
       DOCUMENTS CENTER — Executive UI
       ══════════════════════════════════════════════ */
    .doc-hero {
        background: linear-gradient(135deg, #0077b6 0%, #005691 60%, #003a6c 100%);
        border-radius: 14px;
        padding: 1rem 1.35rem;
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 86, 145, .18);
        margin-bottom: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .doc-hero-left { flex: 1; min-width: 220px; }
    .doc-hero-title-row { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
    .doc-hero-title { font-size: 1.08rem; font-weight: 700; margin: 0; letter-spacing: -.01em; color: #ffffff; }
    .doc-hero-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .16rem .58rem; border-radius: 999px;
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.22);
        color: #ffffff; font-size: .64rem; font-weight: 600;
    }
    .doc-hero-sub { font-size: .76rem; color: rgba(255,255,255,.82); margin: .22rem 0 0; line-height: 1.35; }
    .doc-hero-actions { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; justify-content: flex-end; }
    .doc-hero-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .38rem;
        padding: .44rem .82rem; border-radius: 9px; font-size: .77rem; font-weight: 600;
        border: 1px solid transparent; cursor: pointer; transition: all .15s ease; white-space: nowrap;
    }
    .doc-hero-btn-glass { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.24); color: #ffffff; }
    .doc-hero-btn-glass:hover { background: rgba(255,255,255,.24); color: #ffffff; transform: translateY(-1px); }
    .doc-hero-btn-primary { background: #ffffff; color: #005691; font-weight: 700; box-shadow: 0 3px 10px rgba(0,0,0,.14); }
    .doc-hero-btn-primary:hover { background: #f0f9ff; color: #003a6c; transform: translateY(-1px); }

    /* KPI Grid */
    .doc-kpi-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 13px;
        padding: .85rem 1rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
        display: flex;
        align-items: center;
        gap: .75rem;
        height: 100%;
        cursor: pointer;
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .doc-kpi-card:hover {
        border-color: rgba(0, 119, 182, .35);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .07);
        transform: translateY(-1px);
    }
    .doc-kpi-card.is-active {
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .12);
        background: #f8fcff;
    }
    .doc-kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .doc-kpi-body { flex: 1; min-width: 0; }
    .doc-kpi-label {
        font-size: .64rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: #64748b; margin: 0; line-height: 1.25;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .doc-kpi-val {
        font-size: clamp(.92rem, 1.4vw, 1.15rem); font-weight: 800;
        color: var(--text-primary); line-height: 1.2; margin-top: .12rem;
    }
    .doc-kpi-sub { font-size: .68rem; color: #64748b; margin-top: .08rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Filter Bar */
    .doc-filter-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        margin-bottom: 1.15rem;
        padding: .95rem 1.15rem;
    }
    .doc-pill-bar { display: flex; flex-wrap: wrap; gap: .4rem; }
    .doc-pill-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .34rem .78rem; border-radius: 999px;
        border: 1px solid #e2e8f0; background: #f8fafc;
        color: #475569; font-size: .75rem; font-weight: 600;
        cursor: pointer; transition: all .15s ease;
    }
    .doc-pill-btn:hover { background: #e2e8f0; color: #0f172a; }
    .doc-pill-btn.active {
        background: #0077b6; border-color: #0077b6; color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 119, 182, .22);
    }

    /* Type Selector Cards inside Create Modal */
    .doc-type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
    .doc-type-option {
        border: 1.5px solid #e2e8f0; border-radius: 11px; padding: .7rem .75rem;
        cursor: pointer; background: #ffffff; text-align: left;
        transition: all .15s ease; position: relative;
    }
    .doc-type-option:hover { border-color: #94a3b8; background: #f8fafc; }
    .doc-type-option.is-selected[data-type="invoice"] {
        border-color: #0284c7; background: #f0f9ff;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, .12);
    }
    .doc-type-option.is-selected[data-type="proforma"] {
        border-color: #d97706; background: #fffbeb;
        box-shadow: 0 0 0 3px rgba(217, 119, 6, .12);
    }
    .doc-type-option.is-selected[data-type="delivery"] {
        border-color: #059669; background: #ecfdf5;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, .12);
    }
    .doc-type-option .dt-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .95rem; margin-bottom: .4rem;
    }
    .doc-type-option .dt-title { font-size: .8rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .doc-type-option .dt-desc { font-size: .68rem; color: #64748b; margin-top: .15rem; line-height: 1.25; }

    /* Sale Preview Box inside Modal */
    .doc-sale-preview {
        background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 11px;
        padding: .85rem 1rem; font-size: .8rem;
    }
    .doc-sale-items-list {
        max-height: 145px; overflow-y: auto; border-top: 1px dashed #cbd5e1;
        margin-top: .55rem; padding-top: .5rem;
    }

    @media (max-width: 767.98px) {
        .doc-hero { padding: .9rem 1rem; }
        .doc-hero-actions { width: 100%; display: grid; grid-template-columns: repeat(2, 1fr); gap: .45rem; }
        .doc-hero-btn { width: 100%; font-size: .74rem; padding: .45rem .55rem; }
        .doc-hero-btn-primary { grid-column: 1 / -1; }
        .doc-kpi-card { padding: .7rem .75rem; gap: .55rem; }
        .doc-kpi-icon { width: 34px; height: 34px; font-size: .92rem; border-radius: 8px; }
        .doc-kpi-val { font-size: .9rem; }
        .doc-type-grid { grid-template-columns: 1fr; }
    }
</style>

{{-- ── Executive Hero Header ── --}}
<div class="doc-hero">
    <div class="doc-hero-left">
        <div class="doc-hero-title-row">
            <h1 class="doc-hero-title">Documents Center</h1>
            <span class="doc-hero-pill">
                <i class="bi bi-file-earmark-check-fill"></i>
                {{ auth()->user()->isOwner() ? 'All Branches' : (auth()->user()->shop?->shop_name ?? 'My Branch') }}
            </span>
        </div>
        <p class="doc-hero-sub">Create, manage, and print standalone Invoices, Proforma Quotes, and Delivery Notes directly by Sale ID.</p>
    </div>

    <div class="doc-hero-actions">
        <button type="button" class="doc-hero-btn doc-hero-btn-glass" onclick="openCreateDocumentModal('proforma')">
            <i class="bi bi-file-earmark-ruled"></i>
            <span>New Proforma</span>
        </button>
        <button type="button" class="doc-hero-btn doc-hero-btn-glass" onclick="openCreateDocumentModal('delivery')">
            <i class="bi bi-truck"></i>
            <span>New Delivery Note</span>
        </button>
        <button type="button" class="doc-hero-btn doc-hero-btn-primary" onclick="openCreateDocumentModal('invoice')">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Create Document</span>
        </button>
    </div>
</div>

{{-- ── Interactive KPI Cards ── --}}
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="doc-kpi-card {{ $activeType === '' ? 'is-active' : '' }}" data-filter-type="">
            <div class="doc-kpi-icon" style="background: rgba(0,119,182,.1); color: #0077b6;">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div class="doc-kpi-body">
                <p class="doc-kpi-label">All Documents</p>
                <div class="doc-kpi-val" id="statTotalDocs">{{ number_format($stats['total']) }}</div>
                <div class="doc-kpi-sub">Issued records</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="doc-kpi-card {{ $activeType === 'invoice' ? 'is-active' : '' }}" data-filter-type="invoice">
            <div class="doc-kpi-icon" style="background: rgba(2,132,199,.12); color: #0284c7;">
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div class="doc-kpi-body">
                <p class="doc-kpi-label">Invoices</p>
                <div class="doc-kpi-val" style="color: #0284c7;" id="statInvoices">{{ number_format($stats['invoice']) }}</div>
                <div class="doc-kpi-sub">Official billing docs</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="doc-kpi-card {{ $activeType === 'proforma' ? 'is-active' : '' }}" data-filter-type="proforma">
            <div class="doc-kpi-icon" style="background: rgba(217,119,6,.12); color: #d97706;">
                <i class="bi bi-file-earmark-ruled-fill"></i>
            </div>
            <div class="doc-kpi-body">
                <p class="doc-kpi-label">Proforma Quotes</p>
                <div class="doc-kpi-val" style="color: #d97706;" id="statProformas">{{ number_format($stats['proforma']) }}</div>
                <div class="doc-kpi-sub">Price quotations</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="doc-kpi-card {{ $activeType === 'delivery' ? 'is-active' : '' }}" data-filter-type="delivery">
            <div class="doc-kpi-icon" style="background: rgba(5,150,105,.12); color: #059669;">
                <i class="bi bi-truck"></i>
            </div>
            <div class="doc-kpi-body">
                <p class="doc-kpi-label">Delivery Notes</p>
                <div class="doc-kpi-val" style="color: #059669;" id="statDeliveries">{{ number_format($stats['delivery']) }}</div>
                <div class="doc-kpi-sub">Dispatch confirmations</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filter Bar Card ── --}}
<div class="doc-filter-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="doc-pill-bar">
            <button type="button" class="doc-pill-btn {{ $activeType === '' ? 'active' : '' }}" data-filter-type="">
                <i class="bi bi-grid-fill"></i> All
            </button>
            <button type="button" class="doc-pill-btn {{ $activeType === 'invoice' ? 'active' : '' }}" data-filter-type="invoice">
                <i class="bi bi-file-earmark-text"></i> Invoices
            </button>
            <button type="button" class="doc-pill-btn {{ $activeType === 'proforma' ? 'active' : '' }}" data-filter-type="proforma">
                <i class="bi bi-file-earmark-ruled"></i> Proforma Quotes
            </button>
            <button type="button" class="doc-pill-btn {{ $activeType === 'delivery' ? 'active' : '' }}" data-filter-type="delivery">
                <i class="bi bi-truck"></i> Delivery Notes
            </button>
        </div>
        <button type="button" id="btnResetDocFilters" class="btn btn-sm btn-outline-custom">
            <i class="bi bi-arrow-clockwise me-1"></i> Reset
        </button>
    </div>

    <div class="row g-2 align-items-end">
        @if(auth()->user()->isOwner())
        <div class="col-12 col-sm-6 col-md-4">
            <label for="filterDocShop" class="form-label small fw-600 text-secondary mb-1">
                <i class="bi bi-shop text-primary me-1"></i>Branch / Store
            </label>
            <select id="filterDocShop" class="form-select form-select-sm no-select2">
                <option value="">All Branches</option>
                <option value="main">Main Store (Owner)</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}">{{ $s->shop_name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-6 col-md-4">
            <label for="filterDocFrom" class="form-label small fw-600 text-secondary mb-1">
                <i class="bi bi-calendar-event text-primary me-1"></i>From Date
            </label>
            <input type="date" id="filterDocFrom" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4">
            <label for="filterDocTo" class="form-label small fw-600 text-secondary mb-1">
                <i class="bi bi-calendar-check text-primary me-1"></i>To Date
            </label>
            <input type="date" id="filterDocTo" class="form-control form-control-sm">
        </div>
    </div>
</div>

{{-- ── Documents Table Card ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="documentsTable">
            <thead>
                <tr>
                    <th data-priority="5" class="min-tablet-l text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all id-col">Document #</th>
                    <th data-priority="2" class="min-tablet-l">Type</th>
                    <th data-priority="2" class="min-tablet-l">Sale Ref</th>
                    <th data-priority="1" class="all primary-col">Customer</th>
                    <th data-priority="3" class="min-tablet-l">Shop</th>
                    <th data-priority="2" class="min-tablet-l">Amount</th>
                    <th data-priority="3" class="min-tablet-l">Issue Date</th>
                    <th data-priority="4" class="min-tablet-l">Issued By</th>
                    <th data-priority="2" class="min-tablet-l no-sort text-end" style="min-width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     CREATE DOCUMENT BY SALE ID MODAL
     ══════════════════════════════════════════════ --}}
<div class="modal fade" id="createDocumentModal" tabindex="-1" aria-labelledby="createDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #0077b6, #005691);">
                <div>
                    <h5 class="modal-title fw-700 mb-0" id="createDocumentModalLabel">
                        <i class="bi bi-file-earmark-plus-fill me-2"></i>Create New Document
                    </h5>
                    <div style="font-size: .74rem; color: rgba(255,255,255,.82);">Enter a Sale ID to generate an Invoice, Proforma Quote, or Delivery Note.</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <div id="createDocAlert" class="alert d-none py-2 px-3 small mb-3" role="alert"></div>

                {{-- Step 1: Select Document Type --}}
                <div class="mb-3">
                    <label class="form-label small fw-700 text-dark mb-2">1. Select Document Type <span class="text-danger">*</span></label>
                    <input type="hidden" id="docTypeInput" value="invoice">
                    <div class="doc-type-grid">
                        <button type="button" class="doc-type-option is-selected" data-type="invoice">
                            <div class="dt-icon" style="background: rgba(2,132,199,.12); color: #0284c7;">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div class="dt-title">Invoice</div>
                            <div class="dt-desc">Official tax/sales invoice for a completed sale</div>
                        </button>
                        <button type="button" class="doc-type-option" data-type="proforma">
                            <div class="dt-icon" style="background: rgba(217,119,6,.12); color: #d97706;">
                                <i class="bi bi-file-earmark-ruled-fill"></i>
                            </div>
                            <div class="dt-title">Proforma Invoice</div>
                            <div class="dt-desc">Quotation with validity period & payment terms</div>
                        </button>
                        <button type="button" class="doc-type-option" data-type="delivery">
                            <div class="dt-icon" style="background: rgba(5,150,105,.12); color: #059669;">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="dt-title">Delivery Note</div>
                            <div class="dt-desc">Dispatch manifest & goods receipt sign-off</div>
                        </button>
                    </div>
                </div>

                {{-- Step 2: Enter Sale ID --}}
                <div class="mb-3">
                    <label for="docSaleIdInput" class="form-label small fw-700 text-dark mb-1">2. Enter Sale ID <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-700 text-secondary" style="font-size:.82rem;">#SL-</span>
                        <input type="text" id="docSaleIdInput" class="form-control" placeholder="Enter Sale ID (e.g. 9 or #SL-9)" autocomplete="off">
                        <button type="button" class="btn btn-primary fw-600 px-3" id="btnLookupSale">
                            <i class="bi bi-search me-1"></i> Find Sale
                        </button>
                    </div>
                    @if($recentSales->isNotEmpty())
                    <div class="mt-2">
                        <select id="recentSaleQuickPick" class="form-select form-select-sm no-select2 text-secondary">
                            <option value="">Or quickly pick from recent sales...</option>
                            @foreach($recentSales as $rs)
                                <option value="{{ $rs->id }}">
                                    #SL-{{ $rs->id }} — {{ $rs->customer_name ?: 'Walk-in Customer' }} ({{ $rs->shop?->shop_name ?? 'Main Store' }} · TZS {{ number_format($rs->total_amount, 0) }} · {{ $rs->status === 'draft_proforma' ? 'Proforma Draft' : 'Completed' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>

                {{-- Live Sale Lookup Preview --}}
                <div id="salePreviewWrapper" class="d-none mb-3">
                    <div class="doc-sale-preview" id="salePreviewCard"></div>
                </div>

                {{-- Step 3: Customizable Document Metadata --}}
                <div id="docMetadataSection" class="d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-700 text-dark mb-0">3. Document Details</label>
                        <span class="small text-muted">Pre-filled from sale — customize as needed</span>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label for="docCustomerName" class="form-label small fw-600 mb-1">Customer / Client Name</label>
                            <input type="text" id="docCustomerName" class="form-control form-control-sm" placeholder="Walk-in Customer">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="docCustomerId" class="form-label small fw-600 mb-1">Customer ID / TIN</label>
                            <input type="text" id="docCustomerId" class="form-control form-control-sm" placeholder="Optional">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="docCustomerPoBox" class="form-label small fw-600 mb-1">P.O. Box</label>
                            <input type="text" id="docCustomerPoBox" class="form-control form-control-sm" placeholder="Optional">
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="docIssueDate" class="form-label small fw-600 mb-1">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" id="docIssueDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-6 col-md-4 doc-field-proforma d-none">
                            <label for="docValidityDate" class="form-label small fw-600 mb-1">Valid Until</label>
                            <input type="date" id="docValidityDate" class="form-control form-control-sm" value="{{ now()->addDays(14)->toDateString() }}">
                        </div>
                        <div class="col-12 col-md-4 doc-field-terms">
                            <label for="docTerms" class="form-label small fw-600 mb-1">Terms of Payment</label>
                            <input type="text" id="docTerms" class="form-control form-control-sm" placeholder="e.g. Cash / Net 14 Days">
                        </div>
                        <div class="col-12 col-md-6 doc-field-delivery">
                            <label for="docDeliverTo" class="form-label small fw-600 mb-1">Deliver To / Location</label>
                            <input type="text" id="docDeliverTo" class="form-control form-control-sm" placeholder="Delivery address or site">
                        </div>
                        <div class="col-6 col-md-3 doc-field-delivery">
                            <label for="docDeliveryDate" class="form-label small fw-600 mb-1">Delivery Date</label>
                            <input type="date" id="docDeliveryDate" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-md-3 doc-field-delivery">
                            <label for="docDeliveryTime" class="form-label small fw-600 mb-1">Delivery Time</label>
                            <input type="time" id="docDeliveryTime" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label for="docNotes" class="form-label small fw-600 mb-1">Notes / Remarks</label>
                            <textarea id="docNotes" class="form-control form-control-sm" rows="2" placeholder="Optional instructions, warranty terms, or delivery remarks..."></textarea>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="openPrintAfterCreate" checked>
                        <label class="form-check-label small fw-600 text-secondary" for="openPrintAfterCreate">
                            Open printable A4 document immediately after creating
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-600 px-4" id="btnSubmitCreateDoc" disabled>
                    <i class="bi bi-check-circle-fill me-1"></i> Create &amp; Issue Document
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     EDIT DOCUMENT DETAILS MODAL
     ══════════════════════════════════════════════ --}}
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-labelledby="editDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-700 mb-0" id="editDocumentModalLabel">
                    <i class="bi bi-pencil-square me-2 text-info"></i>Edit Document <span id="editDocNumTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div id="editDocAlert" class="alert d-none py-2 px-3 small mb-3" role="alert"></div>
                <input type="hidden" id="editDocUpdateUrl">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-600 mb-1">Customer / Client Name</label>
                        <input type="text" id="editCustomerName" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-600 mb-1">Customer ID / TIN</label>
                        <input type="text" id="editCustomerId" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-600 mb-1">P.O. Box</label>
                        <input type="text" id="editCustomerPoBox" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-600 mb-1">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" id="editIssueDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-600 mb-1">Validity Date</label>
                        <input type="date" id="editValidityDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-600 mb-1">Terms of Payment</label>
                        <input type="text" id="editTerms" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-600 mb-1">Deliver To</label>
                        <input type="text" id="editDeliverTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-600 mb-1">Delivery Date</label>
                        <input type="date" id="editDeliveryDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-600 mb-1">Delivery Time</label>
                        <input type="time" id="editDeliveryTime" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-600 mb-1">Notes / Remarks</label>
                        <textarea id="editNotes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-sm btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-600 px-4" id="btnSubmitEditDoc">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let activeDocType = @json($activeType);
let lookedUpSale = null;
let documentsTable = null;

function escDocHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function updateModalTypeFields(type) {
    $('#docTypeInput').val(type);
    $('.doc-type-option').removeClass('is-selected');
    $('.doc-type-option[data-type="' + type + '"]').addClass('is-selected');

    if (type === 'proforma') {
        $('.doc-field-proforma').removeClass('d-none');
        $('.doc-field-terms').removeClass('d-none');
    } else if (type === 'delivery') {
        $('.doc-field-proforma').addClass('d-none');
        $('.doc-field-terms').addClass('d-none');
    } else {
        $('.doc-field-proforma').addClass('d-none');
        $('.doc-field-terms').removeClass('d-none');
    }

    validateSaleCompatibility();
}

function validateSaleCompatibility() {
    const type = $('#docTypeInput').val();
    const $alert = $('#createDocAlert');
    const $submitBtn = $('#btnSubmitCreateDoc');

    if (!lookedUpSale) {
        $submitBtn.prop('disabled', true);
        return;
    }

    if ((type === 'invoice' || type === 'delivery') && lookedUpSale.status !== 'completed') {
        const label = type === 'invoice' ? 'Invoice' : 'Delivery Note';
        $alert.removeClass('d-none alert-success alert-danger').addClass('alert-warning')
            .html('<i class="bi bi-exclamation-triangle-fill me-1"></i> Sale <strong>' + escDocHtml(lookedUpSale.sale_ref) + '</strong> is a <strong>Proforma Draft</strong>. A ' + label + ' requires a completed sale, or switch document type to <strong>Proforma Invoice</strong>.');
        $submitBtn.prop('disabled', true);
        return;
    }

    $alert.addClass('d-none').text('');
    $submitBtn.prop('disabled', false);
}

function openCreateDocumentModal(defaultType) {
    const targetType = defaultType || (activeDocType || 'invoice');
    lookedUpSale = null;

    $('#createDocAlert').addClass('d-none').text('');
    $('#docSaleIdInput').val('');
    $('#recentSaleQuickPick').val('');
    $('#salePreviewWrapper').addClass('d-none');
    $('#salePreviewCard').html('');
    $('#docMetadataSection').addClass('d-none');
    $('#docCustomerName, #docCustomerId, #docCustomerPoBox, #docDeliverTo, #docDeliveryDate, #docDeliveryTime, #docTerms, #docNotes').val('');
    $('#docIssueDate').val("{{ now()->toDateString() }}");
    $('#docValidityDate').val("{{ now()->addDays(14)->toDateString() }}");
    $('#btnSubmitCreateDoc').prop('disabled', true);

    updateModalTypeFields(targetType);

    const modalEl = document.getElementById('createDocumentModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(function() { $('#docSaleIdInput').focus(); }, 250);
}

function performSaleLookup(rawSaleId) {
    const val = String(rawSaleId || $('#docSaleIdInput').val() || '').trim();
    const $alert = $('#createDocAlert');
    const $btn = $('#btnLookupSale');

    if (!val) {
        $alert.removeClass('d-none alert-success alert-warning').addClass('alert-danger')
            .html('<i class="bi bi-exclamation-circle-fill me-1"></i> Please enter a Sale ID first (e.g. 9 or #SL-9).');
        return;
    }

    $alert.addClass('d-none').text('');
    const origBtnHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Searching...');

    $.ajax({
        url: "{{ route('documents.lookup-sale') }}",
        type: 'GET',
        data: { sale_id: val },
        success: function(res) {
            $btn.prop('disabled', false).html(origBtnHtml);
            if (!res.found || !res.sale) {
                lookedUpSale = null;
                $('#salePreviewWrapper, #docMetadataSection').addClass('d-none');
                $('#btnSubmitCreateDoc').prop('disabled', true);
                return;
            }

            const s = res.sale;
            lookedUpSale = s;

            let itemsHtml = '';
            (s.items || []).forEach(function(it) {
                itemsHtml += `<div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="border-color:#e2e8f0 !important; font-size:.75rem;">
                    <span class="fw-600 text-dark">${escDocHtml(it.name)} <span class="text-muted fw-normal">(${it.qty} ${escDocHtml(it.unit)})</span></span>
                    <span class="fw-600 text-dark">TZS ${Number(it.subtotal).toLocaleString('en-US', {maximumFractionDigits:0})}</span>
                </div>`;
                (it.components || []).forEach(function(c) {
                    itemsHtml += `<div class="d-flex justify-content-between align-items-center py-0.5 ps-3 text-muted" style="font-size:.71rem;">
                        <span>└─ ${escDocHtml(c.name)} (${c.qty} ${escDocHtml(c.unit)})</span>
                        <span class="fst-italic">Included</span>
                    </div>`;
                });
            });

            let existingHtml = '';
            if (s.existing_docs && s.existing_docs.length > 0) {
                const links = s.existing_docs.map(function(d) {
                    return `<a href="${d.print_url}" target="_blank" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none me-1">${escDocHtml(d.document_number)} (${escDocHtml(d.type_label)}) <i class="bi bi-box-arrow-up-right"></i></a>`;
                }).join('');
                existingHtml = `<div class="mt-2 pt-2 border-top small" style="border-color:#cbd5e1 !important;">
                    <span class="text-muted fw-600 me-1">Already issued:</span> ${links}
                </div>`;
            }

            const statusBadge = s.status === 'completed'
                ? '<span class="badge-soft-success"><i class="bi bi-check-circle-fill"></i> Completed</span>'
                : '<span class="badge-soft-warning"><i class="bi bi-hourglass-split"></i> Proforma Draft</span>';

            $('#salePreviewCard').html(`
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-800 text-primary" style="font-size:.92rem;">${escDocHtml(s.sale_ref)}</span>
                        ${statusBadge}
                        <span class="text-muted small">${escDocHtml(s.sale_date)}</span>
                    </div>
                    <strong class="text-success" style="font-size:.95rem;">${escDocHtml(s.formatted_total)}</strong>
                </div>
                <div class="row g-1 mt-1 small text-secondary">
                    <div class="col-sm-6"><i class="bi bi-person me-1"></i><strong>${escDocHtml(s.customer_name || 'Walk-in Customer')}</strong></div>
                    <div class="col-sm-6 text-sm-end"><i class="bi bi-shop me-1"></i>${escDocHtml(s.shop_name)}</div>
                </div>
                <div class="doc-sale-items-list">${itemsHtml || '<div class="text-muted small">No items listed.</div>'}</div>
                ${existingHtml}
            `);

            // Pre-fill customizable document fields from Sale
            $('#docCustomerName').val(s.customer_name || '');
            $('#docCustomerId').val(s.customer_id || '');
            $('#docCustomerPoBox').val(s.customer_po_box || '');
            $('#docDeliverTo').val(s.deliver_to || '');
            $('#docDeliveryDate').val(s.delivery_date || '');
            $('#docDeliveryTime').val(s.delivery_time || '');
            $('#docValidityDate').val(s.validity_date || "{{ now()->addDays(14)->toDateString() }}");
            $('#docTerms').val(s.terms_of_payment || '');

            $('#salePreviewWrapper').removeClass('d-none');
            $('#docMetadataSection').removeClass('d-none');

            validateSaleCompatibility();
        },
        error: function(xhr) {
            $btn.prop('disabled', false).html(origBtnHtml);
            lookedUpSale = null;
            $('#salePreviewWrapper, #docMetadataSection').addClass('d-none');
            $('#btnSubmitCreateDoc').prop('disabled', true);
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Sale not found.';
            $alert.removeClass('d-none alert-success alert-warning').addClass('alert-danger')
                .html('<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + escDocHtml(msg));
        }
    });
}

$(function() {
    documentsTable = $('#documentsTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('documents.data') }}",
            data: function(d) {
                d.type      = activeDocType;
                d.shop_id   = $('#filterDocShop').length ? $('#filterDocShop').val() : '';
                d.date_from = $('#filterDocFrom').val();
                d.date_to   = $('#filterDocTo').val();
            },
            dataSrc: function(json) {
                if (json.stats) {
                    $('#statTotalDocs').text(Number(json.stats.total || 0).toLocaleString());
                    $('#statInvoices').text(Number(json.stats.invoice || 0).toLocaleString());
                    $('#statProformas').text(Number(json.stats.proforma || 0).toLocaleString());
                    $('#statDeliveries').text(Number(json.stats.delivery || 0).toLocaleString());
                }
                return json.data || [];
            }
        },
        columns: [
            { data: 'iteration', name: 'iteration', orderable: false, searchable: false, className: 'min-tablet-l text-center' },
            { data: 'document_number', name: 'document_number', className: 'all id-col' },
            { data: 'type', name: 'type', className: 'min-tablet-l' },
            { data: 'sale_ref', name: 'sale_ref', className: 'min-tablet-l' },
            { data: 'customer', name: 'customer', className: 'all primary-col' },
            { data: 'shop', name: 'shop', orderable: false, className: 'min-tablet-l' },
            { data: 'amount', name: 'amount', className: 'min-tablet-l' },
            { data: 'issue_date', name: 'issue_date', className: 'min-tablet-l' },
            { data: 'creator', name: 'creator', orderable: false, className: 'min-tablet-l' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'min-tablet-l text-end' }
        ],
        order: [[7, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search doc #, Sale ID, customer...',
            emptyTable: 'No documents created yet. Click "Create Document" and enter a Sale ID to issue one.'
        }
    });

    function applyTypeFilter(type) {
        activeDocType = type || '';
        $('.doc-pill-btn').removeClass('active');
        $('.doc-pill-btn[data-filter-type="' + activeDocType + '"]').addClass('active');
        $('.doc-kpi-card').removeClass('is-active');
        $('.doc-kpi-card[data-filter-type="' + activeDocType + '"]').addClass('is-active');

        // Keep URL cleanly in sync
        const url = new URL(window.location.href);
        if (activeDocType) url.searchParams.set('type', activeDocType);
        else url.searchParams.delete('type');
        window.history.replaceState({}, '', url.toString());

        documentsTable.ajax.reload();
    }

    $(document).on('click', '.doc-pill-btn, .doc-kpi-card', function() {
        applyTypeFilter($(this).attr('data-filter-type'));
    });

    $('#filterDocShop, #filterDocFrom, #filterDocTo').on('change', function() {
        documentsTable.ajax.reload();
    });

    $('#btnResetDocFilters').on('click', function() {
        $('#filterDocShop').val('');
        $('#filterDocFrom, #filterDocTo').val('');
        applyTypeFilter('');
    });

    // Modal Document Type selector
    $(document).on('click', '.doc-type-option', function() {
        updateModalTypeFields($(this).attr('data-type'));
    });

    // Lookup Sale button & Enter key
    $('#btnLookupSale').on('click', function() {
        performSaleLookup($('#docSaleIdInput').val());
    });

    $('#docSaleIdInput').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSaleLookup($(this).val());
        }
    });

    $('#recentSaleQuickPick').on('change', function() {
        const sid = $(this).val();
        if (sid) {
            $('#docSaleIdInput').val(sid);
            performSaleLookup(sid);
        }
    });

    // Submit Create Document
    $('#btnSubmitCreateDoc').on('click', function() {
        if (!lookedUpSale) return;
        const $btn = $(this);
        const $alert = $('#createDocAlert');
        const origHtml = $btn.html();

        $alert.addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');

        $.ajax({
            url: "{{ route('documents.store') }}",
            type: 'POST',
            headers: { 'Accept': 'application/json' },
            data: {
                _token: "{{ csrf_token() }}",
                type: $('#docTypeInput').val(),
                sale_id: String(lookedUpSale.id),
                customer_name: $('#docCustomerName').val(),
                customer_id: $('#docCustomerId').val(),
                customer_po_box: $('#docCustomerPoBox').val(),
                deliver_to: $('#docDeliverTo').val(),
                issue_date: $('#docIssueDate').val(),
                delivery_date: $('#docDeliveryDate').val(),
                delivery_time: $('#docDeliveryTime').val(),
                validity_date: $('#docValidityDate').val(),
                terms_of_payment: $('#docTerms').val(),
                notes: $('#docNotes').val()
            },
            success: function(res) {
                $btn.prop('disabled', false).html(origHtml);
                const modalEl = document.getElementById('createDocumentModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                documentsTable.ajax.reload(null, false);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: res.document_number + ' Created',
                        text: res.message,
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-printer-fill me-1"></i> View / Print',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#0077b6'
                    }).then(function(result) {
                        if (result.isConfirmed && res.print_url) {
                            window.open(res.print_url, '_blank');
                        }
                    });
                }

                if ($('#openPrintAfterCreate').is(':checked') && res.print_url) {
                    window.open(res.print_url, '_blank');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origHtml);
                let msg = 'Failed to create document. Please review inputs.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if (xhr.responseJSON.errors) {
                        const firstErr = Object.values(xhr.responseJSON.errors)[0];
                        if (Array.isArray(firstErr) && firstErr[0]) msg = firstErr[0];
                    }
                }
                $alert.removeClass('d-none alert-success alert-warning').addClass('alert-danger')
                    .html('<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + escDocHtml(msg));
            }
        });
    });

    // Open Edit Document Modal
    $(document).on('click', '.btn-edit-doc', function() {
        const doc = $(this).data('doc');
        if (!doc) return;

        $('#editDocAlert').addClass('d-none').text('');
        $('#editDocNumTitle').text(doc.document_number + ' (' + doc.type_label + ')');
        $('#editDocUpdateUrl').val(doc.update_url);
        $('#editCustomerName').val(doc.customer_name || '');
        $('#editCustomerId').val(doc.customer_id || '');
        $('#editCustomerPoBox').val(doc.customer_po_box || '');
        $('#editDeliverTo').val(doc.deliver_to || '');
        $('#editIssueDate').val(doc.issue_date || '');
        $('#editDeliveryDate').val(doc.delivery_date || '');
        $('#editDeliveryTime').val(doc.delivery_time || '');
        $('#editValidityDate').val(doc.validity_date || '');
        $('#editTerms').val(doc.terms_of_payment || '');
        $('#editNotes').val(doc.notes || '');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editDocumentModal')).show();
    });

    // Submit Edit Document
    $('#btnSubmitEditDoc').on('click', function() {
        const url = $('#editDocUpdateUrl').val();
        if (!url) return;
        const $btn = $(this);
        const $alert = $('#editDocAlert');
        const origHtml = $btn.html();

        $alert.addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'Accept': 'application/json' },
            data: {
                _token: "{{ csrf_token() }}",
                _method: 'PUT',
                customer_name: $('#editCustomerName').val(),
                customer_id: $('#editCustomerId').val(),
                customer_po_box: $('#editCustomerPoBox').val(),
                deliver_to: $('#editDeliverTo').val(),
                issue_date: $('#editIssueDate').val(),
                delivery_date: $('#editDeliveryDate').val(),
                delivery_time: $('#editDeliveryTime').val(),
                validity_date: $('#editValidityDate').val(),
                terms_of_payment: $('#editTerms').val(),
                notes: $('#editNotes').val()
            },
            success: function(res) {
                $btn.prop('disabled', false).html(origHtml);
                const modal = bootstrap.Modal.getInstance(document.getElementById('editDocumentModal'));
                if (modal) modal.hide();
                documentsTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origHtml);
                let msg = 'Failed to update document.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                $alert.removeClass('d-none alert-success').addClass('alert-danger')
                    .html('<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + escDocHtml(msg));
            }
        });
    });
});
</script>
@endpush
