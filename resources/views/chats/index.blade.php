@extends('layouts.app')
@section('title', 'Live Chat & Messaging')
@section('page-title', 'Live Chat & Messaging')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Live Chat</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 overflow-hidden" style="height: calc(100vh - 180px); min-height: 550px;">
            <div class="row g-0 h-100 mobile-view-sidebar" id="chatAppRow">
                <!-- LEFT SIDEBAR: Channels & Users -->
                <div class="col-md-4 col-lg-3 border-end bg-light d-flex flex-column h-100" id="chatSidebar">
                    <!-- Tab Headers -->
                    <div class="p-3 border-bottom bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-800 text-dark mb-0"><i class="bi bi-chat-dots text-primary me-2"></i>Live Channels</h6>
                            @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                            <button class="btn btn-xs btn-outline-custom text-primary" data-bs-toggle="modal" data-bs-target="#smsModal" title="Send System SMS Broadcast">
                                <i class="bi bi-phone-vibrate"></i> Broadcast SMS
                            </button>
                            @endif
                        </div>
                        <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search channels or users...">
                    </div>

                    <!-- Channel / User Lists scrollable -->
                    <div class="flex-grow-1 overflow-y-auto py-2" id="chatListContainer">
                        <!-- Group Channels Section -->
                        <div class="px-3 pt-2 pb-1">
                            <span class="text-uppercase text-muted fw-700" style="font-size: 0.68rem; letter-spacing: 0.05em;">Channels</span>
                        </div>
                        <div class="list-group list-group-flush mb-3">
                            <a href="#" class="list-group-item list-group-item-action border-0 px-3 py-2.5 active chat-target" data-type="group" data-id="group">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-2.5" style="width: 34px; height: 34px;">
                                        <i class="bi bi-people-fill fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-700 text-truncate text-inherit" style="font-size: 0.85rem;"># Refreshment Room</h6>
                                        </div>
                                        <small class="text-muted text-truncate d-block" style="font-size: 0.7rem;">General refreshment group chat</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Direct Messages Section -->
                        <div class="px-3 pt-2 pb-1 d-flex justify-content-between align-items-center">
                            <span class="text-uppercase text-muted fw-700" style="font-size: 0.68rem; letter-spacing: 0.05em;">Direct Messages</span>
                            <button id="btnToggleMultiSend" class="btn btn-xs btn-outline-custom text-primary py-0 px-1" title="Send to multiple users" style="font-size:0.68rem;">
                                <i class="bi bi-check2-square me-1"></i>Multi-Send
                            </button>
                        </div>
                        <div class="list-group list-group-flush" id="usersListGroup">
                            @foreach($users as $user)
                            <a href="#" class="list-group-item list-group-item-action border-0 px-3 py-2.5 chat-target user-item" 
                               data-type="individual" 
                               data-id="{{ $user->id }}" 
                               data-name="{{ $user->name }}" 
                               data-shop="{{ $user->shop ? $user->shop->shop_name : 'Owner / Main Store' }}"
                               data-role="{{ str_replace('_', ' ', $user->role) }}"
                               data-avatar="{{ $user->avatar_path ? asset('media/' . $user->avatar_path) : '' }}"
                               data-phone="{{ $user->phone }}">
                                <input type="checkbox" class="multi-send-checkbox form-check-input me-2 d-none flex-shrink-0" value="{{ $user->id }}" style="margin-top:3px;">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-2.5">
                                        @if($user->avatar_path)
                                            <img src="{{ asset('media/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 34px; height: 34px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fw-700" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="position-absolute bottom-0 end-0 p-1 bg-secondary border border-white rounded-circle" style="width: 10px; height: 10px;"></span>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-600 text-truncate text-inherit" style="font-size: 0.82rem;">{{ $user->name }}</h6>
                                            <span class="badge bg-danger rounded-pill chat-unread-badge-container ms-2 {{ $user->unread_count > 0 ? '' : 'd-none' }}" style="font-size: 0.65rem;">
                                                {{ $user->unread_count }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-0.5">
                                            <span class="text-muted text-truncate d-block" style="font-size: 0.7rem;">
                                                {{ $user->shop ? $user->shop->shop_name : 'Owner Store' }}
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary py-0.5 px-1.5 rounded" style="font-size: 0.58rem; text-transform: uppercase; font-weight: 700;">
                                                {{ $user->role === 'shop_admin' ? 'admin' : ($user->role === 'owner' ? 'owner' : 'seller') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Multi-Send Compose Panel (hidden by default) -->
                    <div id="multiSendPanel" class="border-top bg-white d-none" style="padding:12px 14px;">
                        <div class="d-flex align-items-center mb-2 gap-2">
                            <span class="badge bg-primary rounded-pill" id="multiSendCount">0 selected</span>
                            <button class="btn btn-xs btn-outline-secondary py-0 ms-auto" id="btnClearMultiSelect" style="font-size:0.7rem;">
                                <i class="bi bi-x-circle me-1"></i>Clear
                            </button>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" id="multiSendInput" class="form-control" placeholder="Type message to send to selected users..." maxlength="2000">
                            <button class="btn btn-accent" id="btnSendBulk" disabled>
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                        <div class="text-muted mt-1" style="font-size:0.67rem;"><i class="bi bi-info-circle me-1"></i>Each recipient gets a private direct message.</div>
                    </div>
                </div>

                <!-- MAIN CHAT AREA -->
                <div class="col-md-8 col-lg-9 d-flex flex-column h-100 bg-white position-relative" id="chatAreaContainer">
                    <!-- Chat Header -->
                    <div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between sticky-top">
                        <div class="d-flex align-items-center min-w-0">
                            <!-- Back Button for Mobile -->
                            <button type="button" class="btn btn-sm btn-link text-dark p-0 me-3 d-md-none" id="btnBackToSidebar" style="font-size: 1.25rem; line-height: 1;">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <!-- Avatar / Icon -->
                            <div id="activeChatIcon" class="me-3">
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <h6 class="mb-0 fw-800 text-dark" id="activeChatName"># Refreshment Room</h6>
                                <span class="text-muted" style="font-size: 0.75rem;" id="activeChatStatus">General refreshment group chat</span>
                            </div>
                        </div>

                        <!-- Header Actions -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-custom btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#inquireModal" title="Inquire product availability">
                                <i class="bi bi-search me-1"></i> Inquire Product
                            </button>
                            @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                            <button type="button" class="btn btn-outline-custom btn-sm text-purple d-none" id="btnHeaderSms" title="Send direct SMS message">
                                <i class="bi bi-envelope"></i> Send SMS
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- Messages Log -->
                    <div class="flex-grow-1 overflow-y-auto p-4 bg-light" id="messageLog" style="background-image: radial-gradient(rgba(0, 136, 204, 0.04) 1px, transparent 1px); background-size: 20px 20px;">
                        <div class="text-center py-5 text-muted small" id="initialLoadingPlaceholder">
                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                            <div class="fw-500">Loading conversation history...</div>
                        </div>
                    </div>

                    <!-- Message Input Form -->
                    <div class="border-top bg-white" id="inputArea">
                        <!-- Reply preview bar (hidden by default, slides in) -->
                        <div id="replyPreviewBar" style="
                            display:none; align-items:stretch;
                            border-left: 4px solid #0088cc;
                            background: linear-gradient(90deg,rgba(0,136,204,0.08),rgba(0,136,204,0.03));
                            padding: 8px 14px 8px 12px;
                            gap: 10px;
                            font-size: 0.78rem;
                        ">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-700 mb-1" id="replyPreviewName" style="font-size:0.7rem; color:#0088cc; letter-spacing:0.01em;"></div>
                                <div class="text-muted" id="replyPreviewText" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                            </div>
                            <button type="button" id="btnCancelReply" title="Cancel reply"
                                style="flex-shrink:0; width:24px; height:24px; border-radius:50%; border:none; background:rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; cursor:pointer; color:#666; font-size:0.85rem; align-self:center;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div class="p-3">
                            <form id="messageForm" class="d-flex align-items-center gap-2">
                                <input type="hidden" id="replyToId" value="">
                                <input type="text" id="messageInput" class="form-control py-2 px-3 border" placeholder="Type your message here..." autocomplete="off">
                                <button type="submit" class="btn btn-accent px-4 py-2" id="btnSend">
                                    <i class="bi bi-send-fill me-1"></i> Send
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: PRODUCT INQUIRY -->
<div class="modal fade" id="inquireModal" tabindex="-1" aria-labelledby="inquireModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3" style="background: linear-gradient(135deg, #0088cc, #005f9e) !important;">
                <h6 class="modal-title fw-700" id="inquireModalLabel"><i class="bi bi-box-seam-fill me-2"></i>Inquire Product Availability</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="productSearchInput" class="form-label fw-600">Search Product</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearchInput" class="form-control" placeholder="Type model, brand, or name...">
                    </div>
                    <div class="form-text">Type at least 2 characters to search catalog.</div>
                </div>

                <!-- Product Search Results dropdown -->
                <div id="productResultsList" class="list-group shadow-sm border rounded mb-3 d-none" style="max-height: 200px; overflow-y: auto;">
                    <!-- Populated dynamically -->
                </div>

                <!-- Selected Product Preview -->
                <div id="selectedProductPreview" class="d-none p-3 border rounded mb-3 bg-light">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded bg-white p-2 border" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="bi bi-box-seam text-secondary fs-4" id="previewProdIcon"></i>
                            <img src="" id="previewProdImg" class="img-fluid rounded d-none" style="max-height:100%; object-fit:contain;">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-700" id="previewProdName">Product Name</h6>
                            <small class="text-muted d-block" id="previewProdMeta">Brand: - | Model: -</small>
                            <span class="badge bg-primary mt-1" id="previewProdSelected">Selected</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="inquiryNote" class="form-label fw-600">Message / Inquiry Note</label>
                    <input type="text" id="inquiryNote" class="form-control" value="Do we have this product in stock? Please verify availability and pricing." placeholder="e.g. Do we have this in stock?">
                </div>
            </div>
            <div class="modal-footer p-3 bg-light border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-accent" id="btnSubmitInquiry" disabled>
                    <i class="bi bi-check-circle-fill me-1"></i> Send Product Inquiry
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: SMS BROADCAST / DIRECT SMS -->
@if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-purple text-white p-3" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9) !important;">
                <h6 class="modal-title fw-700" id="smsModalLabel"><i class="bi bi-envelope-fill me-2"></i>Send SMS Notification</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Sandbox mode warning -->
                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.78rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Mode:</strong> 
                    @if(\App\Models\Setting::get('sms_enabled', '0') == '1' && !empty(\App\Models\Setting::get('sms_api_url', '')))
                        <span class="text-success fw-700">Live Gateway</span> (actual SMS cost will apply).
                    @else
                        <span class="text-dark fw-700">Sandbox/Logs Only</span> (logged to DB table `sms_logs`).
                    @endif
                </div>

                <div class="mb-3">
                    <label for="smsRecipientType" class="form-label fw-600">SMS Recipients</label>
                    <select id="smsRecipientType" class="form-select">
                        <option value="all">Broadcast to All Active Employees (With Phone Numbers)</option>
                        <option value="individual" id="smsOptIndividual">Specific Chat Participant</option>
                    </select>
                </div>

                <!-- Individual Target details -->
                <div id="smsIndividualTargetBlock" class="p-3 border rounded bg-light mb-3 d-none">
                    <div class="fw-700 text-dark mb-0.5" id="smsTargetName">Name</div>
                    <div class="text-secondary small">
                        Phone: <span class="fw-600 text-dark" id="smsTargetPhone">-</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="smsMessageText" class="form-label fw-600">SMS Message Text</label>
                    <textarea id="smsMessageText" class="form-control" rows="4" placeholder="Enter message here... (max 480 characters)" maxlength="480"></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Standard SMS counts: <span id="smsCountLabel">0</span> / 480 chars</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="smsPageCount">1 Page</span>
                </div>
            </div>
            <div class="modal-footer p-3 bg-light border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-accent bg-purple hover-purple text-white border-0" id="btnSubmitSMS">
                    <i class="bi bi-send-fill me-1"></i> Send SMS Message
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<style>
    /* Styling to blend smoothly into custom css design system */
    .text-purple { color: #8b5cf6 !important; }
    .bg-purple { background-color: #8b5cf6 !important; }
    .hover-purple:hover { background-color: #7c3aed !important; }
    .chat-target.active {
        background-color: rgba(0, 136, 204, 0.1) !important;
        border-left: 4px solid var(--accent) !important;
        color: var(--accent) !important;
    }
    .chat-target.active .text-inherit, .chat-target.active h6 {
        color: var(--accent) !important;
    }
    .chat-target {
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid transparent !important;
    }
    .chat-target:hover {
        background-color: rgba(0, 0, 0, 0.02) !important;
    }
    
    /* Message styling */
    .msg-wrapper {
        margin-bottom: 1.25rem;
        display: flex;
        flex-direction: column;
    }
    .msg-container {
        max-width: 75%;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        font-size: 0.88rem;
        line-height: 1.45;
        position: relative;
    }
    .msg-incoming {
        align-self: flex-start;
        background-color: #ffffff;
        border-top-left-radius: 2px;
        color: var(--text-primary);
        border: 1px solid var(--card-border);
    }
    .msg-outgoing {
        align-self: flex-end;
        background-color: #0088cc;
        color: #ffffff;
        border-top-right-radius: 2px;
    }
    .msg-sender-name {
        font-size: 0.68rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        display: block;
        text-align: left;
    }
    .msg-incoming .msg-sender-name {
        color: var(--accent);
    }
    .msg-outgoing .msg-sender-name {
        color: rgba(255, 255, 255, 0.9);
    }
    .msg-time {
        font-size: 0.6rem;
        margin-top: 0.25rem;
        text-align: right;
        display: block;
    }
    .msg-incoming .msg-time {
        color: var(--text-secondary);
    }
    .msg-outgoing .msg-time {
        color: rgba(255, 255, 255, 0.7);
    }
    
    /* Product card style in chats */
    .prod-card {
        border-radius: 10px;
        overflow: hidden;
        margin-top: 0.5rem;
        font-size: 0.8rem;
    }
    .msg-incoming .prod-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    .msg-outgoing .prod-card {
        background-color: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }
    .stocks-table {
        width: 100%;
        margin-top: 0.5rem;
        border-collapse: collapse;
    }
    .stocks-table th, .stocks-table td {
        padding: 4px 8px;
        text-align: left;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .msg-outgoing .stocks-table th, .msg-outgoing .stocks-table td {
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    /* ─── WhatsApp-style reply layout ──────────────────────────────────── */

    /* Each message row is a flex row: [btn?] [bubble] or [bubble] [btn?] */
    .msg-row {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        margin-bottom: 4px;
    }
    .msg-row-out { flex-direction: row-reverse; }
    .msg-row-in  { flex-direction: row; }

    /* The reply action button — always in the DOM, invisible until hover */
    .msg-reply-btn {
        flex-shrink: 0;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(0,136,204,0.12);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #0088cc;
        font-size: 1rem;
        opacity: 0;
        transition: opacity 0.18s, background 0.18s, transform 0.18s;
        transform: scale(0.8);
        pointer-events: none;
    }
    .msg-row:hover .msg-reply-btn {
        opacity: 1;
        transform: scale(1);
        pointer-events: auto;
    }
    .msg-reply-btn:hover {
        background: rgba(0,136,204,0.22);
        transform: scale(1.12);
    }
    .msg-row-out .msg-reply-btn { color: #0088cc; background: rgba(0,136,204,0.1); }

    /* ── Quoted reply block inside bubble ───────────────────────── */
    .msg-reply-quote {
        border-radius: 6px;
        padding: 5px 10px;
        margin-bottom: 5px;
        cursor: pointer;
        font-size: 0.72rem;
        transition: opacity 0.15s;
        overflow: hidden;
        position: relative;
    }
    .msg-reply-quote::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        border-radius: 3px 0 0 3px;
    }
    .msg-outgoing .msg-reply-quote {
        background: rgba(255,255,255,0.18);
        padding-left: 13px;
    }
    .msg-outgoing .msg-reply-quote::before { background: rgba(255,255,255,0.7); }
    .msg-incoming .msg-reply-quote {
        background: rgba(0,136,204,0.09);
        padding-left: 13px;
    }
    .msg-incoming .msg-reply-quote::before { background: #0088cc; }
    .msg-reply-quote:hover { opacity: 0.82; }
    .msg-reply-quote .quote-name {
        font-weight: 700;
        font-size: 0.68rem;
        margin-bottom: 1px;
        display: block;
    }
    .msg-outgoing .msg-reply-quote .quote-name { color: rgba(255,255,255,0.9); }
    .msg-incoming .msg-reply-quote .quote-name { color: #0088cc; }
    .msg-reply-quote .quote-text {
        display: block;
        opacity: 0.85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Reply preview bar animation ─────────────────────────────── */
    #replyPreviewBar {
        animation: slideInUp 0.18s ease-out;
    }
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Flash highlight when clicking a quote to jump to source */
    @keyframes msgFlash {
        0%   { box-shadow: 0 0 0 3px rgba(0,136,204,0.45); background: rgba(0,136,204,0.1); }
        100% { box-shadow: none; background: transparent; }
    }
    .msg-flash .msg-container { animation: msgFlash 1.4s ease-out; }

    /* Responsive Styling for Mobile Devices */
    @media (max-width: 767.98px) {
        #chatSidebar {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }
        #chatAreaContainer {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }

        /* Toggle panel display depending on active state */
        .mobile-view-sidebar #chatSidebar {
            display: flex !important;
        }
        .mobile-view-sidebar #chatAreaContainer {
            display: none !important;
        }

        .mobile-view-chat #chatSidebar {
            display: none !important;
        }
        .mobile-view-chat #chatAreaContainer {
            display: flex !important;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        let activeType = 'group'; // 'group' or 'individual'
        let activeId = 'group'; // 'group' or user_id
        let lastMessageId = 0;
        let pollingTimer = null;
        let selectedProduct = null;

        // UI Element Caching
        const messageLog = $('#messageLog');
        const messageForm = $('#messageForm');
        const messageInput = $('#messageInput');
        const activeChatName = $('#activeChatName');
        const activeChatStatus = $('#activeChatStatus');
        const activeChatIcon = $('#activeChatIcon');
        const userSearch = $('#userSearch');
        const productSearchInput = $('#productSearchInput');
        const productResultsList = $('#productResultsList');
        const selectedProductPreview = $('#selectedProductPreview');
        const btnSubmitInquiry = $('#btnSubmitInquiry');
        const btnHeaderSms = $('#btnHeaderSms');

        // Scroll to bottom helper
        function scrollToBottom() {
            messageLog.scrollTop(messageLog[0].scrollHeight);
        }

        // Initialize Chat view
        loadConversation();

        // Left sidebar target click handler
        $(document).on('click', '.chat-target', function (e) {
            e.preventDefault();
            $('.chat-target').removeClass('active');
            $(this).addClass('active');

            // Switch to chat panel on mobile
            $('#chatAppRow').removeClass('mobile-view-sidebar').addClass('mobile-view-chat');

            // Clear local unread badge on click
            $(this).find('.chat-unread-badge-container').addClass('d-none').text('0');

            activeType = $(this).data('type');
            activeId = $(this).data('id');
            lastMessageId = 0;

            // Reset headers and state
            if (activeType === 'group') {
                activeChatName.text('# Refreshment Room');
                activeChatStatus.text('General refreshment group chat');
                activeChatIcon.html(`
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                `);
                btnHeaderSms.addClass('d-none');
            } else {
                const name = $(this).data('name');
                const role = $(this).data('role');
                const shop = $(this).data('shop');
                const avatar = $(this).data('avatar');
                const phone = $(this).data('phone');

                activeChatName.text(name);
                activeChatStatus.html(`${role} &bull; ${shop}`);
                
                if (avatar) {
                    activeChatIcon.html(`<img src="${avatar}" alt="${name}" class="rounded-circle" style="width: 42px; height: 42px; object-fit: cover;">`);
                } else {
                    activeChatIcon.html(`
                        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fw-700" style="width: 42px; height: 42px; font-size: 1.1rem;">
                            ${name.charAt(0).toUpperCase()}
                        </div>
                    `);
                }

                // Show direct SMS button if recipient has a phone and user has admin permissions
                if (phone && (role !== 'owner' || "{{ auth()->user()->isOwner() }}" === "1")) {
                    btnHeaderSms.removeClass('d-none');
                } else {
                    btnHeaderSms.addClass('d-none');
                }
            }

            loadConversation();
        });

        // Load conversation messages
        function loadConversation() {
            if (lastMessageId === 0) {
                messageLog.html(`
                    <div class="text-center py-5 text-muted small" id="initialLoadingPlaceholder">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div class="fw-500">Loading conversation history...</div>
                    </div>
                `);
            }

            if (pollingTimer) {
                clearTimeout(pollingTimer);
            }

            pollMessages();
        }

        // Fetch messages polling function
        function pollMessages() {
            $.ajax({
                url: "{{ route('chats.messages') }}",
                type: 'GET',
                data: {
                    receiver_id: activeId,
                    last_id: lastMessageId
                },
                success: function (response) {
                    const messages = response.messages;
                    const curUserId = response.current_user_id;

                    if (lastMessageId === 0) {
                        messageLog.find('#initialLoadingPlaceholder').remove();
                        if (messages.length === 0) {
                            messageLog.html(`
                                <div class="text-center py-5 text-muted" id="noMessagesMsg">
                                    <i class="bi bi-chat-dots fs-2 d-block mb-2 text-secondary"></i>
                                    <p class="small mb-0">No messages in this chat yet. Say hello!</p>
                                </div>
                            `);
                        }
                    }

                    if (messages.length > 0) {
                        messageLog.find('#noMessagesMsg').remove();
                        
                        messages.forEach(function (msg) {
                            // Check if message already rendered
                            if ($(`#chat-msg-${msg.id}`).length > 0) return;

                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                            }

                            const isOut = msg.sender_id === curUserId;
                            const timeStr = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const senderName = isOut ? 'You' : msg.sender.name;
                            const shopLabel = (msg.sender.shop ? msg.sender.shop.shop_name : 'Owner Store');

                            let messageHtml = '';

                            if (msg.type === 'product_inquiry') {
                                const meta = msg.metadata;
                                if (meta) {
                                    let stockRows = '';
                                    if (msg.show_stocks || msg.show_own_stock_only) {
                                        if (meta.stocks && meta.stocks.length > 0) {
                                            meta.stocks.forEach(function(st) {
                                                const qtyBadge = st.quantity <= 0 ? '<span class="badge bg-danger">Out of Stock</span>' : `<span class="badge bg-success">${st.quantity} Available</span>`;
                                                const priceStr = st.price ? `&bull; <strong>Shs ${formatMoney(st.price)}</strong>` : '';
                                                stockRows += `
                                                    <tr>
                                                        <td class="p-1">${st.shop_name}</td>
                                                        <td class="p-1 text-end">${qtyBadge} ${priceStr}</td>
                                                    </tr>
                                                `;
                                            });
                                            if (msg.show_own_stock_only) {
                                                stockRows += `
                                                    <tr>
                                                        <td colspan="2" class="p-2 text-center text-muted italic" style="font-size:0.68rem; border-top: 1px solid rgba(0,0,0,0.05);"><i class="bi bi-hourglass-split text-warning me-1"></i> Other stores' availability hidden until recipient replies</td>
                                                    </tr>
                                                `;
                                            }
                                        } else {
                                            stockRows = '<tr><td colspan="2" class="p-1 text-muted text-center">No stock entries found</td></tr>';
                                            if (msg.show_own_stock_only) {
                                                stockRows += `
                                                    <tr>
                                                        <td colspan="2" class="p-2 text-center text-muted italic" style="font-size:0.68rem; border-top: 1px solid rgba(0,0,0,0.05);"><i class="bi bi-hourglass-split text-warning me-1"></i> Other stores' availability hidden until recipient replies</td>
                                                    </tr>
                                                `;
                                            }
                                        }
                                    } else {
                                        stockRows = '<tr><td colspan="2" class="p-2 text-center text-muted italic" style="font-size:0.72rem;"><i class="bi bi-hourglass-split text-warning me-1"></i> Stock availability hidden until recipient replies</td></tr>';
                                    }

                                    let imgHtml = '';
                                    if (meta.image_url) {
                                        imgHtml = `<img src="${meta.image_url}" alt="${meta.item_name}" class="img-fluid rounded border mb-2 img-lightbox" style="max-height: 100px; object-fit: contain; cursor:zoom-in;" onclick="openLightbox('${meta.image_url}', '${meta.item_name}')">`;
                                    }

                                    const replyQuoteInq = msg.reply_to ? `
                                        <div class="msg-reply-quote" data-target-id="${msg.reply_to.id}">
                                            <span class="quote-name">${escapeHtml(msg.reply_to.sender ? (msg.reply_to.sender.id === curUserId ? 'You' : msg.reply_to.sender.name) : 'Unknown')}</span>
                                            <span class="quote-text">${escapeHtml(msg.reply_to.message)}</span>
                                        </div>` : '';
                                    const replyBtnInq = `<button class="msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml('📦 ' + (msg.metadata ? msg.metadata.item_name : ''))}"><i class="bi bi-reply-fill"></i></button>`;
                                    messageHtml = `
                                        <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                            <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                                ${replyBtnInq}
                                                <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                    <div class="msg-sender-name">${senderName} (${shopLabel})</div>
                                                    ${replyQuoteInq}
                                                    <div class="small fw-700 mb-1"><i class="bi bi-box-seam-fill me-1"></i> Product Inquiry</div>
                                                <div class="prod-card p-2 rounded">
                                                    ${imgHtml}
                                                    <div class="fw-700">${meta.item_name}</div>
                                                    <div class="text-muted small mb-2" style="font-size:0.72rem;">Brand: ${meta.brand || '-'} | Model: ${meta.model || '-'}</div>
                                                    <div class="p-2 bg-white rounded text-dark mb-2" style="font-size:0.75rem;">
                                                        <strong>Note:</strong> ${meta.note || ''}
                                                    </div>
                                                    <div class="fw-600 mt-2 small border-top pt-1">Stock Availability:</div>
                                                    <table class="stocks-table" style="font-size: 0.72rem;">
                                                        <tbody>
                                                            ${stockRows}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                    <div class="msg-time">${timeStr}</div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }
                            } else {
                                const replyQuote = msg.reply_to ? `
                                    <div class="msg-reply-quote" data-target-id="${msg.reply_to.id}">
                                        <span class="quote-name">${escapeHtml(msg.reply_to.sender ? (msg.reply_to.sender.id === curUserId ? 'You' : msg.reply_to.sender.name) : 'Unknown')}</span>
                                        <span class="quote-text">${escapeHtml(msg.reply_to.message)}</span>
                                    </div>` : '';
                                const replyBtn = `<button class="msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-reply-fill"></i></button>`;
                                messageHtml = `
                                    <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                        <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                            ${replyBtn}
                                            <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                <div class="msg-sender-name">${senderName} (${shopLabel})</div>
                                                ${replyQuote}
                                                <div class="msg-text">${escapeHtml(msg.message)}</div>
                                                <div class="msg-time">${timeStr}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }

                            messageLog.append(messageHtml);
                        });

                        scrollToBottom();
                    }

                    // Schedule next poll in 3.5 seconds for snappy updates
                    pollingTimer = setTimeout(pollMessages, 3500);
                },
                error: function (xhr) {
                    console.error('Failed to poll chat messages', xhr);
                    pollingTimer = setTimeout(pollMessages, 8000); // Backoff on error
                }
            });
        }

        // Send text message handler
        messageForm.on('submit', function (e) {
            e.preventDefault();
            const text = $.trim(messageInput.val());
            if (!text) return;

            const replyToId = $('#replyToId').val() || null;
            messageInput.val('').focus();
            cancelReply();

            $.ajax({
                url: "{{ route('chats.send') }}",
                type: 'POST',
                data: {
                    message: text,
                    receiver_id: activeId,
                    reply_to_id: replyToId,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    // Triggers poll immediately to show message
                    if (pollingTimer) clearTimeout(pollingTimer);
                    pollMessages();
                },
                error: function (xhr) {
                    console.error('Failed to send message', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Sending Failed',
                        text: 'Unable to deliver your message. Check your connection.',
                        confirmButtonColor: '#0088cc'
                    });
                }
            });
        });

        // ─── REPLY FEATURE ───────────────────────────────────────────────────────
        const $replyBar  = $('#replyPreviewBar');
        const $replyId   = $('#replyToId');
        const $replyName = $('#replyPreviewName');
        const $replyText = $('#replyPreviewText');

        function cancelReply() {
            $replyId.val('');
            $replyBar.hide();
            $replyName.text('');
            $replyText.text('');
            messageInput.attr('placeholder', 'Type your message here...');
        }

        function showReplyBar(msgId, senderLabel, previewText) {
            $replyId.val(msgId);
            $replyName.html('<i class="bi bi-reply-fill me-1"></i> Replying to <strong>' + senderLabel + '</strong>');
            $replyText.text(previewText);
            $replyBar.css('display', 'flex');
            messageInput.attr('placeholder', 'Type your reply...').focus();
        }

        // Initially hidden
        $replyBar.hide();

        // Cancel reply button
        $('#btnCancelReply').on('click', cancelReply);

        // Click reply button on a message bubble
        messageLog.on('click', '.msg-reply-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const msgId   = $(this).data('msg-id');
            const sender  = $(this).data('msg-sender');
            const preview = $(this).data('msg-text');
            showReplyBar(msgId, sender, preview);
        });

        // Clicking a quoted block scrolls to and highlights the original message
        messageLog.on('click', '.msg-reply-quote', function (e) {
            e.stopPropagation();
            const targetId = '#chat-msg-' + $(this).data('target-id');
            const $target  = $(targetId);
            if ($target.length) {
                // Smooth scroll
                const logEl = messageLog[0];
                const targetTop = $target[0].offsetTop - logEl.offsetTop - 80;
                logEl.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
                // Flash the bubble
                $target.addClass('msg-flash');
                setTimeout(function () { $target.removeClass('msg-flash'); }, 1500);
            }
        });

        // Auto-cancel reply when switching conversations
        $(document).on('click', '.chat-target:not(.multi-mode)', cancelReply);
        
        // Back to sidebar button on mobile
        $('#btnBackToSidebar').on('click', function () {
            $('#chatAppRow').removeClass('mobile-view-chat').addClass('mobile-view-sidebar');
        });
        // ─────────────────────────────────────────────────────────────────────────

        // Search Users List in sidebar
        userSearch.on('input', function () {
            const val = $(this).val().toLowerCase();
            $('#usersListGroup .user-item').each(function () {
                const name = $(this).data('name').toLowerCase();
                const shop = $(this).data('shop').toLowerCase();
                const role = $(this).data('role').toLowerCase();

                if (name.includes(val) || shop.includes(val) || role.includes(val)) {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none');
                }
            });
        });

        // Product Inquiry: catalog search handler
        productSearchInput.on('input', function () {
            const query = $(this).val();
            if (query.length < 2) {
                productResultsList.addClass('d-none').html('');
                return;
            }

            $.ajax({
                url: "{{ route('chats.items.search') }}",
                type: 'GET',
                data: { query: query },
                success: function (items) {
                    if (items.length === 0) {
                        productResultsList.removeClass('d-none').html('<div class="p-3 text-muted text-center small">No items matched your search</div>');
                        return;
                    }

                    let resultsHtml = '';
                    items.forEach(function (item) {
                        resultsHtml += `
                            <a href="#" class="list-group-item list-group-item-action p-2.5 select-prod-item" 
                               data-id="${item.id}"
                               data-name="${item.item_name}"
                               data-brand="${item.brand || '-'}"
                               data-model="${item.model || '-'}"
                               data-spec="${item.specification || '-'}"
                               data-img="${item.image_path ? '/media/' + item.image_path : ''}">
                               <div class="fw-700 text-dark" style="font-size:0.8rem;">${item.item_name}</div>
                               <div class="text-secondary" style="font-size:0.7rem;">Brand: ${item.brand || '-'} &bull; Model: ${item.model || '-'}</div>
                            </a>
                        `;
                    });

                    productResultsList.removeClass('d-none').html(resultsHtml);
                }
            });
        });

        // Product item selected handler
        $(document).on('click', '.select-prod-item', function (e) {
            e.preventDefault();
            selectedProduct = {
                id: $(this).data('id'),
                name: $(this).data('name'),
                brand: $(this).data('brand'),
                model: $(this).data('model'),
                spec: $(this).data('spec'),
                img: $(this).data('img')
            };

            // Hide results list and update preview
            productResultsList.addClass('d-none').html('');
            productSearchInput.val(selectedProduct.name);

            $('#previewProdName').text(selectedProduct.name);
            $('#previewProdMeta').html(`Brand: <strong>${selectedProduct.brand}</strong> | Model: <strong>${selectedProduct.model}</strong>`);
            
            if (selectedProduct.img) {
                $('#previewProdIcon').addClass('d-none');
                $('#previewProdImg').removeClass('d-none').attr('src', selectedProduct.img);
            } else {
                $('#previewProdIcon').removeClass('d-none');
                $('#previewProdImg').addClass('d-none').attr('src', '');
            }

            selectedProductPreview.removeClass('d-none');
            btnSubmitInquiry.prop('disabled', false);
        });

        // Submit Product Inquiry Action
        $('#btnSubmitInquiry').on('click', function () {
            if (!selectedProduct) return;

            const noteText = $('#inquiryNote').val();
            const modalEl = document.getElementById('inquireModal');
            const modal = bootstrap.Modal.getInstance(modalEl);

            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sending...');

            $.ajax({
                url: "{{ route('chats.inquire') }}",
                type: 'POST',
                data: {
                    product_id: selectedProduct.id,
                    receiver_id: activeId,
                    note: noteText,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    // Reset inquiry form
                    selectedProduct = null;
                    productSearchInput.val('');
                    selectedProductPreview.addClass('d-none');
                    $('#inquiryNote').val('Do we have this product in stock? Please verify availability and pricing.');
                    
                    $('#btnSubmitInquiry').prop('disabled', true).html('<i class="bi bi-check-circle-fill me-1"></i> Send Product Inquiry');
                    modal.hide();

                    if (pollingTimer) clearTimeout(pollingTimer);
                    pollMessages();
                },
                error: function (xhr) {
                    $('#btnSubmitInquiry').prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i> Send Product Inquiry');
                    Swal.fire({
                        icon: 'error',
                        title: 'Inquiry Failed',
                        text: 'An error occurred while sending the product inquiry card.',
                        confirmButtonColor: '#0088cc'
                    });
                }
            });
        });

        // SMS Broadcasting modal triggered actions
        $('#smsModal').on('show.bs.modal', function () {
            // Check if active recipient is direct message
            if (activeType === 'individual') {
                const name = $('.chat-target.active').data('name');
                const phone = $('.chat-target.active').data('phone');
                
                $('#smsOptIndividual').text(`Current Contact (${name})`).removeClass('d-none');
                $('#smsRecipientType').val('individual');
                
                $('#smsTargetName').text(name);
                $('#smsTargetPhone').text(phone || 'No phone number available');
                $('#smsIndividualTargetBlock').removeClass('d-none');
            } else {
                $('#smsOptIndividual').addClass('d-none');
                $('#smsRecipientType').val('all');
                $('#smsIndividualTargetBlock').addClass('d-none');
            }
            $('#smsMessageText').val('');
            $('#smsCountLabel').text('0');
            $('#smsPageCount').text('1 Page');
        });

        // Toggle SMS individual target block based on select option
        $('#smsRecipientType').on('change', function () {
            if ($(this).val() === 'individual') {
                $('#smsIndividualTargetBlock').removeClass('d-none');
            } else {
                $('#smsIndividualTargetBlock').addClass('d-none');
            }
        });

        // Direct SMS Header button helper
        $('#btnHeaderSms').on('click', function () {
            const smsModal = new bootstrap.Modal(document.getElementById('smsModal'));
            smsModal.show();
        });

        // Character counter for SMS
        $('#smsMessageText').on('input', function () {
            const chars = $(this).val().length;
            $('#smsCountLabel').text(chars);
            
            // Standard SMS length is 160 characters
            const pages = Math.ceil(chars / 160) || 1;
            $('#smsPageCount').text(`${pages} Page` + (pages > 1 ? 's' : ''));
        });

        // Submit SMS Broadcast
        $('#btnSubmitSMS').on('click', function () {
            const msg = $.trim($('#smsMessageText').val());
            if (!msg) {
                Swal.fire({
                    icon: 'warning',
                    text: 'Please write an SMS message body first.',
                    confirmButtonColor: '#0088cc'
                });
                return;
            }

            const rType = $('#smsRecipientType').val();
            const modalEl = document.getElementById('smsModal');
            const modal = bootstrap.Modal.getInstance(modalEl);

            $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sending...');

            $.ajax({
                url: "{{ route('chats.send-sms') }}",
                type: 'POST',
                data: {
                    message: msg,
                    recipient_type: rType,
                    receiver_id: rType === 'individual' ? activeId : null,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    $('#btnSubmitSMS').prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send SMS Message');
                    modal.hide();

                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'SMS Dispatched' : 'SMS Failed',
                        text: response.message,
                        confirmButtonColor: '#0088cc'
                    });
                },
                error: function (xhr) {
                    $('#btnSubmitSMS').prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send SMS Message');
                    Swal.fire({
                        icon: 'error',
                        title: 'SMS Failed',
                        text: 'An error occurred while executing the SMS API gateway request.',
                        confirmButtonColor: '#0088cc'
                    });
                }
            });
        });

        // Helpers
        function formatMoney(amount) {
            return parseFloat(amount).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        window.sortSidebarUsers = function(unreadBySender) {
            const listGroup = $('#usersListGroup');
            const items = listGroup.children('.user-item').get();

            items.sort(function(a, b) {
                const idA = $(a).data('id').toString();
                const idB = $(b).data('id').toString();

                const infoA = unreadBySender[idA] || { count: 0, oldest_time: null };
                const infoB = unreadBySender[idB] || { count: 0, oldest_time: null };

                // 1. Sort by unread count > 0 first
                if (infoA.count > 0 && infoB.count === 0) return -1;
                if (infoA.count === 0 && infoB.count > 0) return 1;

                // 2. Both have unread, sort by oldest_time ascending (oldest first)
                if (infoA.count > 0 && infoB.count > 0) {
                    return (infoA.oldest_time || 0) - (infoB.oldest_time || 0);
                }

                // 3. Neither has unread, sort by shop name then user name (alphabetical)
                const shopA = $(a).data('shop').toLowerCase();
                const shopB = $(b).data('shop').toLowerCase();
                if (shopA !== shopB) {
                    return shopA.localeCompare(shopB);
                }

                const nameA = $(a).data('name').toLowerCase();
                const nameB = $(b).data('name').toLowerCase();
                return nameA.localeCompare(nameB);
            });

            // Append back sorted items
            $.each(items, function(i, li) {
                listGroup.append(li);
            });
        };

        // ─── MULTI-SEND FEATURE ──────────────────────────────────────────────────
        let multiSendActive = false;

        function updateMultiSendUI() {
            const checked = $('.multi-send-checkbox:checked');
            const count   = checked.length;
            $('#multiSendCount').text(count + ' selected');
            $('#btnSendBulk').prop('disabled', count === 0 || $('#multiSendInput').val().trim() === '');
        }

        // Toggle multi-send mode
        $('#btnToggleMultiSend').on('click', function (e) {
            e.stopPropagation();
            multiSendActive = !multiSendActive;

            if (multiSendActive) {
                $(this).addClass('btn-primary text-white').removeClass('text-primary btn-outline-custom');
                $(this).html('<i class="bi bi-x-square me-1"></i>Cancel');
                // Show checkboxes, prevent user-item click from opening chat
                $('.multi-send-checkbox').removeClass('d-none');
                $('#multiSendPanel').removeClass('d-none');
                // Disable chat-target click behaviour while in multi mode
                $('#usersListGroup .user-item').addClass('multi-mode');
            } else {
                $(this).removeClass('btn-primary text-white').addClass('text-primary btn-outline-custom');
                $(this).html('<i class="bi bi-check2-square me-1"></i>Multi-Send');
                $('.multi-send-checkbox').addClass('d-none').prop('checked', false);
                $('#multiSendPanel').addClass('d-none');
                $('#multiSendInput').val('');
                $('#usersListGroup .user-item').removeClass('multi-mode');
                updateMultiSendUI();
            }
        });

        // Checkbox change — update counter
        $(document).on('change', '.multi-send-checkbox', updateMultiSendUI);

        // Message input change — update send button state
        $('#multiSendInput').on('input', updateMultiSendUI);

        // Clicking a user row in multi-mode toggles its checkbox instead of opening chat
        $('#usersListGroup').on('click', '.user-item.multi-mode', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const cb = $(this).find('.multi-send-checkbox');
            cb.prop('checked', !cb.prop('checked'));
            updateMultiSendUI();
        });

        // Clear all selections
        $('#btnClearMultiSelect').on('click', function () {
            $('.multi-send-checkbox').prop('checked', false);
            updateMultiSendUI();
        });

        // Send bulk message
        $('#btnSendBulk').on('click', function () {
            const message     = $('#multiSendInput').val().trim();
            const receiverIds = $('.multi-send-checkbox:checked').map(function () { return $(this).val(); }).get();

            if (!message || receiverIds.length === 0) return;

            const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: '{{ route("chats.send-bulk") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                contentType: 'application/json',
                data: JSON.stringify({ message: message, receiver_ids: receiverIds }),
                success: function (res) {
                    if (res.success) {
                        // Show toast
                        const names = $('.multi-send-checkbox:checked').closest('.user-item').map(function () {
                            return $(this).data('name');
                        }).get().join(', ');
                        showToast(`✅ Message sent to ${res.sent} recipient${res.sent > 1 ? 's' : ''}: ${names}`);

                        // Reset
                        $('#multiSendInput').val('');
                        $('.multi-send-checkbox').prop('checked', false);
                        updateMultiSendUI();
                    }
                },
                error: function () {
                    showToast('❌ Failed to send bulk message. Please try again.', 'danger');
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i>');
                }
            });
        });

        function showToast(message, type = 'success') {
            const bgClass  = type === 'success' ? 'bg-success' : 'bg-danger';
            const $toast = $(`
                <div class="toast align-items-center text-white ${bgClass} border-0 shadow" role="alert" style="position:fixed;bottom:24px;right:24px;z-index:9999;min-width:280px;font-size:0.82rem;">
                    <div class="d-flex">
                        <div class="toast-body fw-500">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);
            $('body').append($toast);
            const bsToast = new bootstrap.Toast($toast[0], { delay: 4000 });
            bsToast.show();
            $toast[0].addEventListener('hidden.bs.toast', () => $toast.remove());
        }
        // ─────────────────────────────────────────────────────────────────────────
    });
</script>
@endpush
