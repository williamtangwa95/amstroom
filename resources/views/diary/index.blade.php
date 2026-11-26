@extends('layouts.app')
@section('title', 'Daily Diary')
@section('page-title', 'Daily Diary')
@section('breadcrumb')
<li class="breadcrumb-item active">Daily Diary</li>
@endsection

@push('styles')
<style>
    /* ── Diary Executive KPI Cards ── */
    .diary-kpi-grid {
        display: grid;
        gap: .85rem;
        margin-bottom: 1.25rem;
    }
    .diary-kpi-grid-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .diary-kpi-grid-5 {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .diary-kpi-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--card-border, #e2e8f0);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        display: flex;
        align-items: center;
        gap: .85rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
        height: 100%;
    }
    .diary-kpi-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
    }
    .diary-kpi-card.is-clickable {
        cursor: pointer;
    }

    .diary-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .diary-kpi-body {
        min-width: 0;
        flex: 1;
    }
    .diary-kpi-label {
        font-size: .69rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
        color: var(--text-secondary, #64748b);
        margin: 0 0 .2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .diary-kpi-val {
        font-size: clamp(1rem, 1.4vw, 1.28rem);
        font-weight: 800;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .diary-kpi-val.is-currency {
        font-size: clamp(.92rem, 1.2vw, 1.12rem);
        white-space: normal;
        word-break: break-word;
    }
    .diary-kpi-sub {
        font-size: .71rem;
        color: #94a3b8;
        margin-top: .2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1199.98px) {
        .diary-kpi-grid-5 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 991.98px) {
        .diary-kpi-grid-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 767.98px) {
        .diary-kpi-grid {
            gap: .65rem;
        }
        .diary-kpi-grid-5 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .diary-kpi-grid-5 .diary-kpi-cost {
            grid-column: 1 / -1;
        }
        .diary-kpi-card {
            padding: .85rem .9rem;
            gap: .7rem;
            border-radius: 12px;
        }
        .diary-kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
    }
    @media (max-width: 420px) {
        .diary-kpi-grid-4 {
            grid-template-columns: 1fr;
        }
        .diary-kpi-grid-5 {
            grid-template-columns: 1fr;
        }
    }

    /* ── Compact Reminder Preset Pills ── */
    .diary-preset-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .32rem;
        margin-top: .45rem;
    }
    .diary-preset-label {
        font-size: .68rem;
        font-weight: 600;
        color: #64748b;
        margin-right: .15rem;
    }
    .diary-preset-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .16rem .58rem;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.3;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        transition: all .15s ease;
    }
    .diary-preset-pill:hover:not(:disabled) {
        border-color: #0077b6;
        color: #0077b6;
        background: #f0f9ff;
    }
    .diary-preset-pill.is-active {
        background: #0077b6;
        border-color: #0077b6;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 119, 182, .22);
    }
    .diary-preset-pill:disabled {
        opacity: .42;
        cursor: not-allowed;
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-700">Daily Diary</h5>
        <small style="color:var(--text-secondary);">
            @if(!$canUseShopDiary)
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
@if(!$canUseShopDiary)
<div class="diary-kpi-grid diary-kpi-grid-4">
    <div class="diary-kpi-card is-clickable" data-kpi-filter="all" title="Show all entries">
        <div class="diary-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-journal-text"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Total Entries</p>
            <div class="diary-kpi-val" id="cardTotalEntries" style="color:#0077b6;">{{ number_format($totalEntries) }}</div>
            <div class="diary-kpi-sub">Personal Entries</div>
        </div>
    </div>

    <div class="diary-kpi-card is-clickable" data-kpi-filter="followup" title="Filter entries needing follow-up">
        <div class="diary-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-bell-fill"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Follow-up</p>
            <div class="diary-kpi-val" id="cardFollowUp" style="color:#d97706;">{{ number_format($followUpCount) }}</div>
            <div class="diary-kpi-sub">Needs Follow-up</div>
        </div>
    </div>

    <div class="diary-kpi-card is-clickable" data-kpi-filter="closed" title="Filter entries with no follow-up">
        <div class="diary-kpi-icon" style="background:rgba(16,185,129,.12);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Closed</p>
            <div class="diary-kpi-val" id="cardNoFollowUp" style="color:#059669;">{{ number_format($noFollowUp) }}</div>
            <div class="diary-kpi-sub">No Follow-up Needed</div>
        </div>
    </div>

    <div class="diary-kpi-card diary-kpi-cost">
        <div class="diary-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Total Cost</p>
            <div class="diary-kpi-val is-currency" id="cardTotalCost" style="color:#e11d48;">TZS {{ number_format($totalCost, 0) }}</div>
            <div class="diary-kpi-sub">Combined Recorded Cost</div>
        </div>
    </div>
</div>
@else
<div class="diary-kpi-grid diary-kpi-grid-5">
    <div class="diary-kpi-card is-clickable" data-kpi-filter="all" title="Show all accessible entries">
        <div class="diary-kpi-icon" style="background:rgba(0,119,182,.1);color:#0077b6;">
            <i class="bi bi-journal-text"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Total</p>
            <div class="diary-kpi-val" id="cardTotalEntries" style="color:#0077b6;">{{ number_format($totalEntries) }}</div>
            <div class="diary-kpi-sub">All Accessible</div>
        </div>
    </div>

    <div class="diary-kpi-card is-clickable" data-kpi-filter="personal" title="Filter personal diary entries">
        <div class="diary-kpi-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Personal</p>
            <div class="diary-kpi-val" id="cardPersonalCount" style="color:#7c3aed;">{{ number_format($personalCount) }}</div>
            <div class="diary-kpi-sub">My Private Logs</div>
        </div>
    </div>

    <div class="diary-kpi-card is-clickable" data-kpi-filter="shop" title="Filter shared shop diary entries">
        <div class="diary-kpi-icon" style="background:rgba(14,165,233,.12);color:#0284c7;">
            <i class="bi bi-shop"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Shop</p>
            <div class="diary-kpi-val" id="cardShopCount" style="color:#0284c7;">{{ number_format($shopCount) }}</div>
            <div class="diary-kpi-sub">Shared Records</div>
        </div>
    </div>

    <div class="diary-kpi-card is-clickable" data-kpi-filter="followup" title="Filter entries needing follow-up">
        <div class="diary-kpi-icon" style="background:rgba(217,119,6,.12);color:#d97706;">
            <i class="bi bi-bell-fill"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Follow-up</p>
            <div class="diary-kpi-val" id="cardFollowUp" style="color:#d97706;">{{ number_format($followUpCount) }}</div>
            <div class="diary-kpi-sub">Needs Follow-up</div>
        </div>
    </div>

    <div class="diary-kpi-card diary-kpi-cost">
        <div class="diary-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="diary-kpi-body">
            <p class="diary-kpi-label">Total Cost</p>
            <div class="diary-kpi-val is-currency" id="cardTotalCost" style="color:#e11d48;">TZS {{ number_format($totalCost, 0) }}</div>
            <div class="diary-kpi-sub">Combined Cost</div>
        </div>
    </div>
</div>
@endif

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
            @if($canUseShopDiary)
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
        <table id="diaryTable" class="table table-hover align-middle w-100 mb-0 dt-mobile-table" style="font-size:0.85rem;">
            <thead>
                <tr style="color:var(--text-secondary);font-size:0.75rem;text-transform:uppercase;">
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="3" class="min-tablet-l">Type / Shop</th>
                    <th data-priority="1" class="all primary-col">Event Name</th>
                    <th data-priority="4" class="min-tablet-l">Description</th>
                    <th data-priority="2" class="min-tablet-l">Event Date</th>
                    <th data-priority="3" class="min-tablet-l">Due Date</th>
                    <th data-priority="2" class="min-tablet-l">Cost</th>
                    <th data-priority="2" class="min-tablet-l">Status</th>
                    <th data-priority="3" class="min-tablet-l no-sort text-end">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
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

                            @if(!$canUseShopDiary)
                                {{-- Owner or shop without any seller: Personal Diary only --}}
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
                        @if($canUseShopDiary)
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
                            <input type="text" inputmode="decimal" class="form-control currency-input" id="inputCost" name="cost"
                                placeholder="0" autocomplete="off"
                                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                        </div>

                        {{-- Needs Follow-up --}}
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="inputNeedsFollowUp" name="needs_follow_up" value="1" style="width:2.5em;height:1.3em;">
                                <label class="form-check-label ms-2 fw-600" for="inputNeedsFollowUp" style="color:var(--text-secondary);cursor:pointer;">
                                    <i class="bi bi-bell me-1" style="color:#e3b341;"></i> Enable Follow-up Reminder
                                </label>
                            </div>
                        </div>

                        {{-- Follow-up Reminder & Email Settings (shown when Follow-up is enabled) --}}
                        <div class="col-12 d-none" id="followUpSettingsPanel">
                            <div class="p-3 rounded-3 border" style="background:rgba(0,119,182,0.04);border-color:rgba(0,119,182,0.22) !important;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="fw-700 small text-primary">
                                        <i class="bi bi-alarm-fill me-1"></i>Follow-up Reminder Schedule
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="reminderSchedulePreview" style="font-size:.7rem;">
                                        Set a Due Date to schedule reminder
                                    </span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-600 mb-1" style="color:var(--text-secondary);">
                                            Remind Me (Days Before Due)
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="inputRemindDaysBefore" name="remind_days_before" min="1" max="365" value="1" placeholder="1">
                                            <span class="input-group-text">day(s)</span>
                                        </div>
                                        <div class="form-text" id="remindDaysRangeHint" style="font-size:.7rem;">
                                            Min: 1 day &middot; Max: days between Event &amp; Due Date
                                        </div>
                                        <div class="diary-preset-bar">
                                            <span class="diary-preset-label">Quick:</span>
                                            <button type="button" class="diary-preset-pill btn-days-preset" data-days="1">1d</button>
                                            <button type="button" class="diary-preset-pill btn-days-preset" data-days="2">2d</button>
                                            <button type="button" class="diary-preset-pill btn-days-preset" data-days="3">3d</button>
                                            <button type="button" class="diary-preset-pill btn-days-preset" data-days="7">7d</button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6 col-md-4">
                                        <label class="form-label small fw-600 mb-1" style="color:var(--text-secondary);">
                                            Reminder Date
                                        </label>
                                        <input type="date" class="form-control form-control-sm" id="inputReminderDate" name="reminder_date">
                                        <div class="form-text" style="font-size:.7rem;">
                                            Auto-calculated from Due Date
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6 col-md-4">
                                        <label class="form-label small fw-600 mb-1" style="color:var(--text-secondary);">
                                            Reminder Time
                                        </label>
                                        <input type="time" class="form-control form-control-sm" id="inputReminderTime" name="reminder_time" value="10:00">
                                        <div class="form-text" style="font-size:.7rem;">
                                            Default: 10:00 AM
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="p-2 px-3 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <div class="fw-600 small text-dark">
                                                    <i class="bi bi-envelope-fill text-primary me-1"></i>Email Notification
                                                </div>
                                                <div class="text-muted" style="font-size:.72rem;">
                                                    Choose whether to also receive an email alert at <strong>{{ auth()->user()->email }}</strong>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="inputNotifyEmail" name="notify_email" value="1" style="width:2.5em;height:1.3em;">
                                                <label class="form-check-label small fw-600 ms-1" for="inputNotifyEmail" id="notifyEmailLabel" style="cursor:pointer;">Off</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:var(--input-bg);border:1px solid var(--card-border);">
                            <span class="small text-muted d-block" style="font-size:0.70rem;">Reminder Date</span>
                            <span class="fw-600 text-primary" id="viewReminderDate" style="font-size:0.82rem;">—</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded" style="background:var(--input-bg);border:1px solid var(--card-border);">
                            <span class="small text-muted d-block" style="font-size:0.70rem;">Email Notification</span>
                            <span class="fw-600" id="viewNotifyEmail" style="font-size:0.82rem;">Off</span>
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
    const canUseShopDiary = {{ $canUseShopDiary ? 'true' : 'false' }};

    // Toggle shop selection visibility based on radio buttons (when Shop Diary is enabled)
    if (canUseShopDiary) {
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
        lengthMenu: [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, 'All']],
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

    // Quick-filter table when clicking KPI cards
    $(document).on('click', '.diary-kpi-card[data-kpi-filter]', function () {
        const filter = $(this).data('kpi-filter');
        if (filter === 'all') {
            if ($('#filterType').length) $('#filterType').val('');
            $('#filterFollowUp').val('');
        } else if (filter === 'personal' || filter === 'shop') {
            if ($('#filterType').length) $('#filterType').val(filter);
            $('#filterFollowUp').val('');
        } else if (filter === 'followup') {
            $('#filterFollowUp').val('1');
        } else if (filter === 'closed') {
            $('#filterFollowUp').val('0');
        }
        table.ajax.reload();
    });

    // ── Follow-up Reminder Schedule Panel Helpers ─────────────────────────────
    function pad2(n) { return String(n).padStart(2, '0'); }
    function formatDateYmd(d) {
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function parseYmd(str) {
        if (!str || !/^\d{4}-\d{2}-\d{2}$/.test(str)) return null;
        const p = str.split('-').map(Number);
        return new Date(p[0], p[1] - 1, p[2]);
    }
    function formatNiceDate(d) {
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    function formatNiceTime(timeStr) {
        const clean = String(timeStr || '10:00').trim();
        const m = clean.match(/^(\d{2}):(\d{2})/);
        if (!m) return '10:00 AM';
        let h = parseInt(m[1], 10);
        const min = m[2];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return pad2(h) + ':' + min + ' ' + ampm;
    }

    function getMaxReminderDays() {
        const eventStr = $('#inputEventDate').val();
        const dueStr = $('#inputDueDate').val();
        const eventDate = parseYmd(eventStr);
        const dueDate = parseYmd(dueStr);
        if (eventDate && dueDate) {
            return Math.round((dueDate.getTime() - eventDate.getTime()) / 86400000);
        }
        return 365;
    }

    function updateFollowUpPanelVisibility() {
        const enabled = $('#inputNeedsFollowUp').is(':checked');
        if (enabled) {
            $('#followUpSettingsPanel').removeClass('d-none');
            syncReminderFromDays();
        } else {
            $('#followUpSettingsPanel').addClass('d-none');
        }
        $('#notifyEmailLabel').text($('#inputNotifyEmail').is(':checked') ? 'Send Email' : 'Off');
    }

    function updatePresetButtons(maxDays, activeDays) {
        const currentDays = activeDays !== undefined ? activeDays : parseInt($('#inputRemindDaysBefore').val() || '0', 10);
        $('.btn-days-preset').each(function () {
            const presetDays = parseInt($(this).data('days'), 10);
            const allowed = maxDays >= 1 && presetDays <= maxDays;
            $(this).prop('disabled', !allowed);
            $(this).toggleClass('is-active', allowed && presetDays === currentDays);
        });
    }

    function syncReminderFromDays() {
        const eventStr = $('#inputEventDate').val();
        const dueStr = $('#inputDueDate').val();
        const $daysInput = $('#inputRemindDaysBefore');
        const $remInput = $('#inputReminderDate');
        const $preview = $('#reminderSchedulePreview');
        const $hint = $('#remindDaysRangeHint');

        if (!dueStr) {
            $daysInput.attr('min', 1).attr('max', 365);
            if ((parseInt($daysInput.val() || '1', 10) || 0) < 1) $daysInput.val(1);
            $remInput.val('').removeAttr('min').removeAttr('max');
            $preview.removeClass('bg-danger-subtle text-danger border-danger-subtle').addClass('bg-primary-subtle text-primary border-primary-subtle');
            $preview.text('Set a Due Date to schedule reminder');
            $hint.text('Min: 1 day · Max: days between Event Date & Due Date');
            updatePresetButtons(365);
            return;
        }

        const due = parseYmd(dueStr);
        if (!due) return;

        const maxDays = getMaxReminderDays();
        updatePresetButtons(maxDays);

        if (maxDays < 1) {
            $daysInput.attr('min', 1).attr('max', 1).val(1);
            $remInput.val('').removeAttr('min').removeAttr('max');
            $preview.removeClass('bg-primary-subtle text-primary border-primary-subtle').addClass('bg-danger-subtle text-danger border-danger-subtle');
            $preview.text('Due Date must be at least 1 day after Event Date');
            $hint.html('<span class="text-danger">Increase Due Date by at least 1 day after Event Date.</span>');
            return;
        }

        $preview.removeClass('bg-danger-subtle text-danger border-danger-subtle').addClass('bg-primary-subtle text-primary border-primary-subtle');
        $daysInput.attr('min', 1).attr('max', maxDays);

        let days = parseInt($daysInput.val() || '1', 10);
        if (isNaN(days) || days < 1) days = 1;
        if (days > maxDays) days = maxDays;
        $daysInput.val(days);
        updatePresetButtons(maxDays, days);

        const maxRemDate = new Date(due.getTime());
        maxRemDate.setDate(maxRemDate.getDate() - 1);
        $remInput.attr('max', formatDateYmd(maxRemDate));
        if (eventStr) {
            $remInput.attr('min', eventStr);
            $hint.text(`Min: 1 day · Max: ${maxDays} ${maxDays === 1 ? 'day' : 'days'} (between Event & Due Date)`);
        } else {
            $remInput.removeAttr('min');
            $hint.text('Min: 1 day before Due Date');
        }

        const rem = new Date(due.getTime());
        rem.setDate(rem.getDate() - days);
        $remInput.val(formatDateYmd(rem));
        const niceTime = formatNiceTime($('#inputReminderTime').val() || '10:00');
        $preview.text('Reminder: ' + formatNiceDate(rem) + ' at ' + niceTime + ' (' + days + (days === 1 ? ' day' : ' days') + ' before due)');
    }

    function syncDaysFromReminder() {
        const dueStr = $('#inputDueDate').val();
        const remStr = $('#inputReminderDate').val();
        const due = parseYmd(dueStr);
        const rem = parseYmd(remStr);

        if (!due || !rem) {
            syncReminderFromDays();
            return;
        }

        const maxDays = getMaxReminderDays();
        if (maxDays < 1) {
            syncReminderFromDays();
            return;
        }

        let diffDays = Math.round((due.getTime() - rem.getTime()) / 86400000);
        if (diffDays < 1) diffDays = 1;
        if (diffDays > maxDays) diffDays = maxDays;

        $('#inputRemindDaysBefore').val(diffDays);
        updatePresetButtons(maxDays, diffDays);
        const clampedRem = new Date(due.getTime());
        clampedRem.setDate(clampedRem.getDate() - diffDays);
        $('#inputReminderDate').val(formatDateYmd(clampedRem));
        const niceTime = formatNiceTime($('#inputReminderTime').val() || '10:00');
        $('#reminderSchedulePreview').text(
            'Reminder: ' + formatNiceDate(clampedRem) + ' at ' + niceTime + ' (' + diffDays + (diffDays === 1 ? ' day' : ' days') + ' before due)'
        );
    }

    $('#inputNeedsFollowUp').on('change', updateFollowUpPanelVisibility);
    $('#inputNotifyEmail').on('change', function () {
        $('#notifyEmailLabel').text($(this).is(':checked') ? 'Send Email' : 'Off');
    });
    $('#inputEventDate, #inputDueDate, #inputRemindDaysBefore, #inputReminderTime').on('change input', function () {
        if ($('#inputNeedsFollowUp').is(':checked')) {
            syncReminderFromDays();
        }
    });
    $('#inputReminderDate').on('change', function () {
        if ($('#inputNeedsFollowUp').is(':checked')) {
            syncDaysFromReminder();
        }
    });
    $(document).on('click', '.btn-days-preset', function () {
        if ($(this).prop('disabled')) return;
        $('#inputRemindDaysBefore').val($(this).data('days'));
        syncReminderFromDays();
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
        const remDate = btn.data('reminder-date') || '—';
        const remTime = btn.data('reminder-time') || '10:00 AM';
        const daysBefore = parseInt(btn.data('remind-days-before') || '1', 10);
        $('#viewReminderDate').text(
            needsFollowUp && remDate !== '—'
                ? remDate + ' · ' + remTime + ' (' + daysBefore + 'd before)'
                : '—'
        );
        const notifyEmail = btn.data('notify-email') == 1;
        $('#viewNotifyEmail').html(
            notifyEmail
                ? '<span class="text-success"><i class="bi bi-envelope-check-fill me-1"></i>Enabled</span>'
                : '<span class="text-muted">Off (In-App Only)</span>'
        );
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

        if (canUseShopDiary) {
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
        const rawCost = btn.data('cost');
        $('#inputCost').val(rawCost !== '' && rawCost !== undefined && rawCost !== null ? (window.formatCurrencyValue ? window.formatCurrencyValue(rawCost) : rawCost) : '');
        const needsFollowUp = btn.data('needs-follow-up') == 1;
        $('#inputNeedsFollowUp').prop('checked', needsFollowUp);
        const editDays = Math.max(1, parseInt(btn.data('remind-days-before') || '1', 10) || 1);
        $('#inputRemindDaysBefore').val(editDays);
        $('#inputReminderDate').val(btn.data('reminder-date') || '');
        $('#inputReminderTime').val(btn.data('reminder-time') || '10:00');
        $('#inputNotifyEmail').prop('checked', btn.data('notify-email') == 1);

        updateFollowUpPanelVisibility();
        if (needsFollowUp && btn.data('reminder-date')) {
            $('#inputReminderDate').val(btn.data('reminder-date'));
            syncDaysFromReminder();
        }

        $('#diaryModal').modal('show');
    });

    function resetModal() {
        $('#modalTitleText').text('New Diary Entry');
        $('#diaryMethod').val('POST');
        $('#diaryId').val('');
        $('#diaryForm').attr('action', '{{ route("diary.store") }}');
        $('#diaryForm')[0].reset();
        $('#inputRemindDaysBefore').val(1);
        $('#inputReminderDate').val('');
        $('#inputReminderTime').val('10:00');
        $('#inputNotifyEmail').prop('checked', false);

        if (canUseShopDiary) {
            $('#typePersonal').prop('checked', true).trigger('change');
        }

        // Set today as default event date
        const today = new Date().toISOString().split('T')[0];
        $('#inputEventDate').val(today);
        updateFollowUpPanelVisibility();
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
