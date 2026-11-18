@extends('layouts.app')
@section('title', 'Daily Diary')
@section('page-title', 'Daily Diary')
@section('breadcrumb')
<li class="breadcrumb-item active">Daily Diary</li>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-700">Daily Diary</h5>
        <small style="color:var(--text-secondary);">
            @if(auth()->user()->isOwner())
                Personal diary logs — private records for you only
            @else
                Personal diary logs &amp; Shop diary records for team collaboration
            @endif
        </small>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#diaryModal" id="btn-new-diary">
            <i class="bi bi-plus-circle me-1"></i> New Diary Entry
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-2 g-md-3 mb-4">
    @if(auth()->user()->isOwner())
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Total Entries</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(88,166,255,0.15);color:#58a6ff;">
                        <i class="bi bi-journal-text fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardTotalEntries" style="color:#58a6ff;">{{ number_format($totalEntries) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Personal Entries</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Follow-up</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(227,179,65,0.15);color:#e3b341;">
                        <i class="bi bi-bell-fill fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardFollowUp" style="color:#e3b341;">{{ number_format($followUpCount) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Needs Follow-up</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Closed</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(63,185,80,0.15);color:#3fb950;">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardNoFollowUp" style="color:#3fb950;">{{ number_format($noFollowUp) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">No Follow-up Needed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Total Cost</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(233,69,96,0.15);color:#e94560;">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0 text-truncate" id="cardTotalCost" style="color:#e94560;">TZS {{ number_format($totalCost, 0) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Combined Recorded Cost</div>
            </div>
        </div>
    @else
        <div class="col-6 col-md-2">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Total</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(88,166,255,0.15);color:#58a6ff;">
                        <i class="bi bi-journal-text fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardTotalEntries" style="color:#58a6ff;">{{ number_format($totalEntries) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">All Accessible</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Personal</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(163,113,247,0.15);color:#a371f7;">
                        <i class="bi bi-person-fill fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardPersonalCount" style="color:#a371f7;">{{ number_format($personalCount) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">My Private Logs</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Shop</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(56,139,253,0.15);color:#388bfd;">
                        <i class="bi bi-shop fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardShopCount" style="color:#388bfd;">{{ number_format($shopCount) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Shared Records</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Follow-up</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(227,179,65,0.15);color:#e3b341;">
                        <i class="bi bi-bell-fill fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0" id="cardFollowUp" style="color:#e3b341;">{{ number_format($followUpCount) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Needs Follow-up</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--card-bg); border:1px solid var(--card-border) !important; border-radius:12px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-700 text-uppercase tracking-wider" style="color:var(--text-secondary); font-size:0.70rem;">Total Cost</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(233,69,96,0.15);color:#e94560;">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </div>
                </div>
                <div class="fw-800 fs-5 mb-0 text-truncate" id="cardTotalCost" style="color:#e94560;">TZS {{ number_format($totalCost, 0) }}</div>
                <div class="small text-muted" style="font-size:0.68rem;">Combined Cost</div>
            </div>
        </div>
    @endif
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3" style="background:var(--card-bg);border:1px solid var(--card-border) !important;border-radius:12px;">
    <div class="card-body p-3">
        <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1" style="color:var(--text-secondary);font-size:0.72rem;">From</label>
                <input type="date" class="form-control form-control-sm" id="filterDateFrom" name="date_from" value="{{ $dateFrom }}" style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);font-size:0.82rem;">
            </div>
            <div>
                <label class="form-label small mb-1" style="color:var(--text-secondary);font-size:0.72rem;">To</label>
                <input type="date" class="form-control form-control-sm" id="filterDateTo" name="date_to" value="{{ $dateTo }}" style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);font-size:0.82rem;">
            </div>
            @if(!auth()->user()->isOwner())
            <div>
                <label class="form-label small mb-1" style="color:var(--text-secondary);font-size:0.72rem;">Diary Scope</label>
                <select class="form-select form-select-sm" id="filterType" name="type" style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);font-size:0.82rem;">
                    <option value="">All Entries</option>
                    <option value="personal">Personal Diary</option>
                    <option value="shop">Shop Diary</option>
                </select>
            </div>
            @endif
            <div>
                <label class="form-label small mb-1" style="color:var(--text-secondary);font-size:0.72rem;">Follow-up</label>
                <select class="form-select form-select-sm" id="filterFollowUp" name="needs_follow_up" style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);font-size:0.82rem;">
                    <option value="">All Statuses</option>
                    <option value="1">Needs Follow-up</option>
                    <option value="0">No Follow-up</option>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-custom" id="btnApplyFilter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilter">
                    <i class="bi bi-x-lg me-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>

{{-- DataTable --}}
<div class="card border-0 shadow-sm" style="background:var(--card-bg);border:1px solid var(--card-border) !important;border-radius:12px;">
    <div class="card-body p-0 p-md-2">
        <div class="table-responsive">
            <table id="diaryTable" class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr style="color:var(--text-secondary);font-size:0.75rem;text-transform:uppercase;">
                        <th>#</th>
                        <th>Type / Shop</th>
                        <th>Event Name</th>
                        <th>Description</th>
                        <th>Event Date</th>
                        <th>Due Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create / Edit Modal --}}
<div class="modal fade" id="diaryModal" tabindex="-1" aria-labelledby="diaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="diaryModalLabel">
                    <i class="bi bi-journal-plus me-2" style="color:var(--accent-blue);"></i>
                    <span id="modalTitleText">New Diary Entry</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="diaryForm" method="POST" action="{{ route('diary.store') }}">
                @csrf
                <input type="hidden" id="diaryMethod" name="_method" value="POST">
                <input type="hidden" id="diaryId" name="diary_id" value="">

                <div class="modal-body">
                    <div class="row g-3">
                        {{-- Diary Scope / Type --}}
                        <div class="col-12">
                            <label class="form-label small fw-600 mb-1" style="color:var(--text-secondary);">
                                Diary Scope <span class="text-danger">*</span>
                            </label>

                            @if(auth()->user()->isOwner())
                                {{-- Owner can only have Personal Diary --}}
                                <input type="hidden" name="type" value="personal">
                                <div class="p-2 rounded d-flex align-items-center gap-2" style="background:rgba(163,113,247,0.1);border:1px solid rgba(163,113,247,0.25);">
                                    <i class="bi bi-person-fill fs-5" style="color:#a371f7;"></i>
                                    <div>
                                        <span class="small fw-700" style="color:#a371f7;">Personal Diary</span>
                                        <div class="small text-muted" style="font-size:0.72rem;">Private to you only</div>
                                    </div>
                                </div>
                            @else
                                {{-- Admin and Seller can choose between Personal and Shop --}}
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="typePersonal" value="personal" checked>
                                        <label class="form-check-label fw-600" for="typePersonal" style="color:var(--text-primary);cursor:pointer;">
                                            <i class="bi bi-person-fill text-purple me-1" style="color:#a371f7;"></i> Personal Diary
                                            <small class="d-block text-muted" style="font-size:0.72rem;font-weight:400;">Private to you only</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="typeShop" value="shop">
                                        <label class="form-check-label fw-600" for="typeShop" style="color:var(--text-primary);cursor:pointer;">
                                            <i class="bi bi-shop text-info me-1" style="color:#58a6ff;"></i> Shop Diary
                                            <small class="d-block text-muted" style="font-size:0.72rem;font-weight:400;">Shared with shop admin &amp; sellers</small>
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Shop Selection (For Shop Admin / Seller when Shop Diary is selected) --}}
                        @if(!auth()->user()->isOwner())
                            <div class="col-12 d-none" id="shopSelectionWrapper">
                                @if(auth()->user()->shop)
                                    <input type="hidden" name="shop_id" id="inputShopId" value="{{ auth()->user()->shop_id }}">
                                    <div class="p-2 rounded d-flex align-items-center gap-2" style="background:rgba(56,139,253,0.1);border:1px solid rgba(56,139,253,0.25);">
                                        <i class="bi bi-shop fs-5" style="color:#58a6ff;"></i>
                                        <div>
                                            <span class="small fw-700" style="color:#58a6ff;">Branch: {{ auth()->user()->shop->shop_name }}</span>
                                            <div class="small text-muted" style="font-size:0.72rem;">This entry will be visible to you and other team members of this shop.</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning p-2 small mb-0">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> You are not assigned to any shop. Shop diary entries cannot be created.
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Event Name --}}
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">
                                Event Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="inputEventName" name="event_name"
                                placeholder="e.g. Daily handover note, Stock checkup, Customer delivery"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);"
                                required maxlength="255">
                        </div>

                        {{-- Event Description --}}
                        <div class="col-12">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="inputEventDescription" name="event_description"
                                rows="4" placeholder="Describe the event, tasks, or follow-up details in full…"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);resize:vertical;"
                                required maxlength="5000"></textarea>
                        </div>

                        {{-- Event Date & Due Date --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Event Date</label>
                            <input type="date" class="form-control" id="inputEventDate" name="event_date"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Due Date</label>
                            <input type="date" class="form-control" id="inputDueDate" name="due_date"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                        </div>

                        {{-- Cost --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-600" style="color:var(--text-secondary);">Cost (TZS)</label>
                            <input type="number" class="form-control" id="inputCost" name="cost"
                                placeholder="0.00" min="0" step="0.01"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                        </div>

                        {{-- Needs Follow-up --}}
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="inputNeedsFollowUp" name="needs_follow_up" value="1" style="width:2.5em;height:1.3em;">
                                <label class="form-check-label ms-2 fw-600" for="inputNeedsFollowUp" style="color:var(--text-secondary);">
                                    <i class="bi bi-bell me-1" style="color:#e3b341;"></i> Needs Follow-up
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent" id="btnSaveDiary">
                        <i class="bi bi-save me-1"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Details Modal --}}
<div class="modal fade" id="viewDiaryModal" tabindex="-1" aria-labelledby="viewDiaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--card-bg);border:1px solid var(--card-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                <h5 class="modal-title fw-700" id="viewDiaryModalLabel">
                    <i class="bi bi-journal-text me-2" style="color:var(--accent-blue);"></i> Diary Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span id="viewTypeBadge"></span>
                    <span id="viewStatusBadge"></span>
                </div>

                <h5 class="fw-700 mb-1" id="viewEventName" style="color:var(--text-primary);"></h5>
                <div class="small text-muted mb-3" style="font-size:0.75rem;">
                    <i class="bi bi-person me-1"></i> Recorded by: <strong id="viewUserName" style="color:var(--text-primary);"></strong>
                    <span class="mx-1">•</span>
                    <span id="viewCreatedAt"></span>
                </div>

                <div class="p-3 rounded mb-3" style="background:var(--input-bg);border:1px solid var(--card-border);">
                    <label class="small fw-700 text-uppercase tracking-wider d-block mb-1" style="color:var(--text-secondary);font-size:0.68rem;">Description</label>
                    <div id="viewDescription" style="white-space:pre-wrap;color:var(--text-primary);font-size:0.85rem;line-height:1.5;"></div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:var(--input-bg);border:1px solid var(--card-border);">
                            <span class="small text-muted d-block" style="font-size:0.70rem;">Event Date</span>
                            <span class="fw-600" id="viewEventDate" style="font-size:0.82rem;"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:var(--input-bg);border:1px solid var(--card-border);">
                            <span class="small text-muted d-block" style="font-size:0.70rem;">Due Date</span>
                            <span class="fw-600" id="viewDueDate" style="font-size:0.82rem;"></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded d-flex justify-content-between align-items-center" style="background:var(--input-bg);border:1px solid var(--card-border);">
                            <span class="small text-muted" style="font-size:0.75rem;">Recorded Cost</span>
                            <span class="fw-700" id="viewCost" style="color:#e94560;font-size:0.90rem;"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--card-border);">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const isOwner = {{ auth()->user()->isOwner() ? 'true' : 'false' }};

    // Toggle shop selection visibility based on radio buttons (for Admin/Seller)
    if (!isOwner) {
        $('input[name="type"]').on('change', function () {
            if ($(this).val() === 'shop') {
                $('#shopSelectionWrapper').removeClass('d-none');
            } else {
                $('#shopSelectionWrapper').addClass('d-none');
            }
        });
    }

    // ── DataTable init ─────────────────────────────────────────────────────────
    const table = $('#diaryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("diary.data") }}',
            data: function (d) {
                d.date_from       = $('#filterDateFrom').val();
                d.date_to         = $('#filterDateTo').val();
                d.type            = $('#filterType').length ? $('#filterType').val() : '';
                d.needs_follow_up = $('#filterFollowUp').val();
            },
            dataSrc: function (json) {
                // Update summary cards from server totals
                if (json.totals) {
                    $('#cardTotalEntries').text(json.totals.total_entries.toLocaleString());
                    if ($('#cardPersonalCount').length) {
                        $('#cardPersonalCount').text(json.totals.personal_count.toLocaleString());
                    }
                    if ($('#cardShopCount').length) {
                        $('#cardShopCount').text(json.totals.shop_count.toLocaleString());
                    }
                    if ($('#cardNoFollowUp').length) {
                        $('#cardNoFollowUp').text(json.totals.no_follow_up.toLocaleString());
                    }
                    $('#cardFollowUp').text(json.totals.follow_up.toLocaleString());
                    $('#cardTotalCost').text('TZS ' + json.totals.total_cost.toLocaleString('en', {maximumFractionDigits: 0}));
                }
                return json.data;
            }
        },
        columns: [
            { data: 'id',                orderable: true  },
            { data: 'type',              orderable: true  },
            { data: 'event_name',        orderable: true  },
            { data: 'event_description', orderable: false },
            { data: 'event_date',        orderable: true  },
            { data: 'due_date',          orderable: true  },
            { data: 'cost',              orderable: true  },
            { data: 'status',            orderable: true  },
            { data: 'actions',           orderable: false },
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        language: {
            search: '',
            searchPlaceholder: 'Search entries…',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>',
            emptyTable: '<div class="text-center py-4" style="color:var(--text-secondary);"><i class="bi bi-journal-x fs-4 d-block mb-2"></i>No diary entries found</div>',
        },
        dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2"lf>rtip',
    });

    // ── Filters ────────────────────────────────────────────────────────────────
    $('#btnApplyFilter').on('click', function () { table.ajax.reload(); });
    $('#btnResetFilter').on('click', function () {
        const today = new Date();
        const y = today.getFullYear(), m = String(today.getMonth()+1).padStart(2,'0');
        $('#filterDateFrom').val(y + '-' + m + '-01');
        const lastDay = new Date(y, today.getMonth()+1, 0).getDate();
        $('#filterDateTo').val(y + '-' + m + '-' + String(lastDay).padStart(2,'0'));
        if ($('#filterType').length) $('#filterType').val('');
        $('#filterFollowUp').val('');
        table.ajax.reload();
    });
    $('#filterDateFrom, #filterDateTo, #filterType, #filterFollowUp').on('change', function () {
        table.ajax.reload();
    });

    // ── Open modal for NEW entry ───────────────────────────────────────────────
    $('#btn-new-diary').on('click', function () {
        resetModal();
    });

    // ── View Details Modal ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-view-diary', function () {
        const btn = $(this);
        const type = btn.data('type');
        const shopName = btn.data('shop-name');

        if (type === 'shop') {
            $('#viewTypeBadge').html('<span class="badge" style="background:rgba(56,139,253,0.15);color:#58a6ff;border:1px solid rgba(56,139,253,0.3);font-size:0.75rem;"><i class="bi bi-shop me-1"></i> ' + (shopName || 'Shop') + '</span>');
        } else {
            $('#viewTypeBadge').html('<span class="badge" style="background:rgba(163,113,247,0.15);color:#a371f7;border:1px solid rgba(163,113,247,0.3);font-size:0.75rem;"><i class="bi bi-person-fill me-1"></i> Personal</span>');
        }

        const needsFollowUp = btn.data('needs-follow-up') == 1;
        if (needsFollowUp) {
            $('#viewStatusBadge').html('<span class="badge" style="background:rgba(227,179,65,0.15);color:#e3b341;border:1px solid rgba(227,179,65,0.3);font-size:0.75rem;"><i class="bi bi-bell-fill me-1"></i> Needs Follow-up</span>');
        } else {
            $('#viewStatusBadge').html('<span class="badge" style="background:rgba(63,185,80,0.15);color:#3fb950;border:1px solid rgba(63,185,80,0.3);font-size:0.75rem;"><i class="bi bi-check-circle-fill me-1"></i> No Follow-up</span>');
        }

        $('#viewEventName').text(btn.data('event-name'));
        $('#viewUserName').text(btn.data('user-name'));
        $('#viewCreatedAt').text(btn.data('created-at'));
        $('#viewDescription').text(btn.data('event-description'));
        $('#viewEventDate').text(btn.data('event-date'));
        $('#viewDueDate').text(btn.data('due-date'));
        $('#viewCost').text(btn.data('cost'));

        $('#viewDiaryModal').modal('show');
    });

    // ── Open modal for EDIT entry ─────────────────────────────────────────────
    $(document).on('click', '.btn-edit-diary', function () {
        const btn = $(this);
        const id  = btn.data('id');
        const type = btn.data('type') || 'personal';

        $('#modalTitleText').text('Edit Diary Entry');
        $('#diaryMethod').val('PUT');
        $('#diaryId').val(id);
        $('#diaryForm').attr('action', '/diary/' + id);

        if (!isOwner) {
            if (type === 'shop') {
                $('#typeShop').prop('checked', true).trigger('change');
            } else {
                $('#typePersonal').prop('checked', true).trigger('change');
            }
        }

        $('#inputEventName').val(btn.data('event-name'));
        $('#inputEventDescription').val(btn.data('event-description'));
        $('#inputEventDate').val(btn.data('event-date'));
        $('#inputDueDate').val(btn.data('due-date'));
        $('#inputCost').val(btn.data('cost'));
        $('#inputNeedsFollowUp').prop('checked', btn.data('needs-follow-up') == 1);

        $('#diaryModal').modal('show');
    });

    function resetModal() {
        $('#modalTitleText').text('New Diary Entry');
        $('#diaryMethod').val('POST');
        $('#diaryId').val('');
        $('#diaryForm').attr('action', '{{ route("diary.store") }}');
        $('#diaryForm')[0].reset();

        if (!isOwner) {
            $('#typePersonal').prop('checked', true).trigger('change');
        }

        // Set today as default event date
        const today = new Date().toISOString().split('T')[0];
        $('#inputEventDate').val(today);
    }

    // ── Delete ─────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete-diary', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Delete Entry?',
            html: '<p style="color:var(--text-secondary);">Are you sure you want to delete the diary entry <strong style="color:var(--text-primary);">' + name + '</strong>?<br>This cannot be undone.</p>',
            icon: 'warning',
            background: '#161b22',
            color: '#e6edf3',
            showCancelButton: true,
            confirmButtonColor: '#e94560',
            cancelButtonColor: '#30363d',
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Delete',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/diary/' + id,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        if (res.success) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Diary entry deleted.',
                                background: '#161b22',
                                color: '#e6edf3',
                                timer: 1800,
                                showConfirmButton: false,
                            });
                        }
                    },
                    error: function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not delete the entry.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg, background: '#161b22', color: '#e6edf3' });
                    }
                });
            }
        });
    });

    // ── Tooltips ───────────────────────────────────────────────────────────────
    $(document).on('mouseover', '[data-bs-toggle="tooltip"]', function () {
        if (!$(this).data('bs.tooltip')) {
            new bootstrap.Tooltip(this);
            bootstrap.Tooltip.getInstance(this).show();
        }
    });

    // Auto-dismiss flash messages
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session("success") }}',
            background: '#161b22',
            color: '#e6edf3',
            timer: 2500,
            showConfirmButton: false,
        });
    @endif
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session("error") }}',
            background: '#161b22',
            color: '#e6edf3',
        });
    @endif
});
</script>
@endpush
