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
                        <!-- Edit preview bar (hidden by default, slides in) -->
                        <div id="editPreviewBar" style="
                            display:none; align-items:stretch;
                            border-left: 4px solid #f59e0b;
                            background: linear-gradient(90deg,rgba(245,158,11,0.12),rgba(245,158,11,0.03));
                            padding: 8px 14px 8px 12px;
                            gap: 10px;
                            font-size: 0.78rem;
                        ">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-700 mb-1" id="editPreviewTitle" style="font-size:0.7rem; color:#d97706; letter-spacing:0.01em;">
                                    <i class="bi bi-pencil-square me-1"></i> Editing message
                                </div>
                                <div class="text-muted" id="editPreviewText" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                            </div>
                            <button type="button" id="btnCancelEdit" title="Cancel edit (Esc)"
                                style="flex-shrink:0; width:24px; height:24px; border-radius:50%; border:none; background:rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; cursor:pointer; color:#666; font-size:0.85rem; align-self:center;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <!-- Emoji Picker Popover -->
                        <div id="emojiPickerPopup" class="shadow-lg border rounded-3 bg-white" style="
                            display: none;
                            position: absolute;
                            bottom: 70px;
                            left: 15px;
                            width: 350px;
                            max-width: calc(100vw - 30px);
                            height: 380px;
                            z-index: 1050;
                            flex-direction: column;
                            overflow: hidden;
                        ">
                            <!-- Header: Search and Categories -->
                            <div class="p-2 border-bottom bg-light">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="emojiSearchInput" class="form-control border-start-0" placeholder="Search emojis (e.g. smile, box, ok, fire)...">
                                </div>
                                <!-- Category Nav -->
                                <div class="d-flex justify-content-between align-items-center px-1 emoji-nav-tabs">
                                    <button type="button" class="btn btn-xs emoji-tab-btn active" data-category="quick" title="Quick & Frequent">⭐</button>
                                    <button type="button" class="btn btn-xs emoji-tab-btn" data-category="smileys" title="Smileys & Emotion">😀</button>
                                    <button type="button" class="btn btn-xs emoji-tab-btn" data-category="gestures" title="People & Gestures">👍</button>
                                    <button type="button" class="btn btn-xs emoji-tab-btn" data-category="commerce" title="Shop & Commerce">📦</button>
                                    <button type="button" class="btn btn-xs emoji-tab-btn" data-category="symbols" title="Symbols & Alerts">💡</button>
                                    <button type="button" class="btn btn-xs text-muted py-0 px-1 ms-1" id="btnCloseEmojiPicker" title="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            
                            <!-- Emoji List Container -->
                            <div class="flex-grow-1 overflow-y-auto p-2" id="emojiGridContainer" style="font-size: 1.35rem; user-select: none;">
                                <!-- Populated dynamically with categorized grid of emojis -->
                            </div>
                            
                            <!-- Footer with preview label -->
                            <div class="px-2.5 py-1.5 bg-light border-top text-muted d-flex justify-content-between align-items-center" style="font-size: 0.68rem;">
                                <span id="emojiPreviewLabel"><i class="bi bi-cursor me-1"></i>Click emoji to insert</span>
                                <span class="badge bg-secondary-subtle text-secondary">Esc to close</span>
                            </div>
                        </div>

                        <div class="p-3 position-relative">
                            <form id="messageForm" class="d-flex align-items-center gap-2">
                                <input type="hidden" id="replyToId" value="">
                                <input type="hidden" id="editingMsgId" value="">
                                <button type="button" class="btn btn-light border px-2.5 py-2 text-secondary flex-shrink-0" id="btnEmojiToggle" title="Add Emoji" style="border-radius: 8px;">
                                    <i class="bi bi-emoji-smile fs-5 text-warning"></i>
                                </button>
                                <input type="text" id="messageInput" class="form-control py-2 px-3 border" placeholder="Type your message here..." autocomplete="off">
                                <button type="submit" class="btn btn-accent px-4 py-2 flex-shrink-0" id="btnSend">
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
        font-size: 0.62rem;
        margin-top: 0.35rem;
        text-align: right;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
    }
    .msg-incoming .msg-time {
        color: var(--text-secondary);
    }
    .msg-outgoing .msg-time {
        color: rgba(255, 255, 255, 0.82);
    }
    .msg-edited-tag {
        font-size: 0.58rem;
        opacity: 0.85;
        font-style: italic;
    }
    .msg-incoming .msg-edited-tag {
        color: #d97706;
    }
    .msg-outgoing .msg-edited-tag {
        color: #fde68a;
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

    /* ─── WhatsApp-style Action Buttons (Reply, Edit, Delete) ──────────── */

    /* Each message row is a flex row: [actions?] [bubble] or [bubble] [actions?] */
    .msg-row {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        margin-bottom: 4px;
        position: relative;
    }
    .msg-row-out { flex-direction: row-reverse; }
    .msg-row-in  { flex-direction: row; }

    /* Action buttons container — always in DOM, appears on hover */
    .msg-actions {
        display: flex;
        align-items: center;
        gap: 3px;
        opacity: 0;
        transition: opacity 0.18s ease, transform 0.18s ease;
        transform: scale(0.9);
        pointer-events: none;
        flex-shrink: 0;
    }
    .msg-row:hover .msg-actions {
        opacity: 1;
        transform: scale(1);
        pointer-events: auto;
    }

    .msg-action-btn {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(0,136,204,0.12);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #0088cc;
        font-size: 0.85rem;
        transition: opacity 0.15s, background 0.15s, transform 0.15s, color 0.15s;
    }
    .msg-action-btn:hover {
        background: rgba(0,136,204,0.24);
        transform: scale(1.12);
    }
    .msg-row-out .msg-action-btn { color: #0088cc; background: rgba(0,136,204,0.1); }
    .msg-row-out .msg-action-btn:hover { background: rgba(0,136,204,0.22); }

    .msg-action-btn.msg-edit-btn {
        color: #d97706;
        background: rgba(245, 158, 11, 0.12);
    }
    .msg-action-btn.msg-edit-btn:hover {
        color: #b45309;
        background: rgba(245, 158, 11, 0.25);
    }

    .msg-action-btn.msg-delete-btn {
        color: #dc2626;
        background: rgba(239, 68, 68, 0.12);
    }
    .msg-action-btn.msg-delete-btn:hover {
        color: #b91c1c;
        background: rgba(239, 68, 68, 0.25);
    }

    /* ── Date dividers ───────────────────────────────────────────── */
    .chat-date-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 1.25rem 0 0.85rem;
        position: relative;
    }
    .chat-date-pill {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 3px 12px;
        border-radius: 20px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        letter-spacing: 0.02em;
    }

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

    /* ── Reply & Edit preview bar animation ──────────────────────── */
    #replyPreviewBar, #editPreviewBar, #emojiPickerPopup {
        animation: slideInUp 0.18s ease-out;
    }
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Emoji Picker Styling ────────────────────────────────────────── */
    #emojiPickerPopup {
        box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.08) !important;
        border: 1px solid rgba(0,0,0,0.12) !important;
    }
    .emoji-tab-btn {
        font-size: 1.05rem;
        padding: 2px 8px;
        border-radius: 6px;
        background: transparent;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .emoji-tab-btn:hover {
        background: rgba(0, 136, 204, 0.1);
    }
    .emoji-tab-btn.active {
        background: #0088cc;
        color: #ffffff;
    }
    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 3px;
    }
    .emoji-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        border-radius: 8px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 1.35rem;
        line-height: 1;
        transition: transform 0.12s ease, background-color 0.12s ease;
    }
    .emoji-btn:hover {
        background-color: rgba(0, 136, 204, 0.12);
        transform: scale(1.22);
    }
    .emoji-section-title {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin: 8px 4px 4px;
    }

    /* Flash highlight when clicking a quote or updating message */
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
        let lastRenderedDateKey = null;
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

        // Date & Time formatting helpers
        function formatMessageDateTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;

            const now = new Date();
            const isToday = d.toDateString() === now.toDateString();

            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            const isYesterday = d.toDateString() === yesterday.toDateString();

            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (isToday) {
                return `Today, ${timeStr}`;
            } else if (isYesterday) {
                return `Yesterday, ${timeStr}`;
            } else {
                const dateOptions = { month: 'short', day: 'numeric' };
                if (d.getFullYear() !== now.getFullYear()) {
                    dateOptions.year = 'numeric';
                }
                const dateFormatted = d.toLocaleDateString([], dateOptions);
                return `${dateFormatted}, ${timeStr}`;
            }
        }

        function getMessageDateGroupKey(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            return d.toISOString().split('T')[0];
        }

        function formatGroupDateHeader(dateStr) {
            const d = new Date(dateStr);
            const now = new Date();
            if (d.toDateString() === now.toDateString()) return 'Today';
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';

            const opts = { weekday: 'short', month: 'short', day: 'numeric' };
            if (d.getFullYear() !== now.getFullYear()) opts.year = 'numeric';
            return d.toLocaleDateString(undefined, opts);
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
            lastRenderedDateKey = null;

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

            cancelReply();
            cancelEdit();
            loadConversation();
        });

        // Load conversation messages
        function loadConversation() {
            if (lastMessageId === 0) {
                lastRenderedDateKey = null;
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

                    // Process deleted messages sync in real-time
                    if (response.deleted_ids && response.deleted_ids.length > 0) {
                        response.deleted_ids.forEach(function (delId) {
                            const $delEl = $(`#chat-msg-${delId}`);
                            if ($delEl.length) {
                                $delEl.fadeOut(250, function () { $(this).remove(); });
                            }
                            if ($('#editingMsgId').val() == delId) {
                                cancelEdit();
                            }
                        });
                    }

                    // Process updated messages sync in real-time
                    if (response.updated_messages && response.updated_messages.length > 0) {
                        response.updated_messages.forEach(function (uMsg) {
                            updateMessageInDOM(uMsg, curUserId);
                        });
                    }

                    if (messages.length > 0) {
                        messageLog.find('#noMessagesMsg').remove();
                        
                        messages.forEach(function (msg) {
                            // Check if message already rendered
                            if ($(`#chat-msg-${msg.id}`).length > 0) return;

                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                            }

                            // Render date divider if new day
                            const dateKey = getMessageDateGroupKey(msg.created_at);
                            if (dateKey && dateKey !== lastRenderedDateKey) {
                                lastRenderedDateKey = dateKey;
                                const dateLabel = formatGroupDateHeader(msg.created_at);
                                messageLog.append(`
                                    <div class="chat-date-divider" data-date-key="${dateKey}">
                                        <span class="chat-date-pill"><i class="bi bi-calendar3 me-1 text-primary"></i>${dateLabel}</span>
                                    </div>
                                `);
                            }

                            const isOut = msg.sender_id === curUserId;
                            const dateTimeStr = formatMessageDateTime(msg.created_at);
                            const fullTimestamp = new Date(msg.created_at).toLocaleString();
                            const editedTag = msg.is_edited ? `<span class="msg-edited-tag ms-1"><i class="bi bi-pencil-fill" style="font-size:0.55rem;"></i> edited</span>` : '';
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
                                    
                                    const rawNote = meta.note || '';
                                    let actionsInqHtml = '';
                                    if (isOut) {
                                        actionsInqHtml = `
                                            <div class="msg-actions">
                                                <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml('📦 ' + (meta ? meta.item_name : ''))}"><i class="bi bi-reply-fill"></i></button>
                                                <button class="msg-action-btn msg-edit-btn" title="Edit inquiry note" data-msg-id="${msg.id}" data-msg-type="inquiry" data-msg-text="${escapeHtml(rawNote)}"><i class="bi bi-pencil-fill"></i></button>
                                                <button class="msg-action-btn msg-delete-btn" title="Delete inquiry" data-msg-id="${msg.id}"><i class="bi bi-trash3-fill"></i></button>
                                            </div>
                                        `;
                                    } else {
                                        actionsInqHtml = `
                                            <div class="msg-actions">
                                                <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml('📦 ' + (meta ? meta.item_name : ''))}"><i class="bi bi-reply-fill"></i></button>
                                            </div>
                                        `;
                                    }

                                    messageHtml = `
                                        <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                            <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                                ${actionsInqHtml}
                                                <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                    <div class="msg-sender-name">${senderName} (${shopLabel})</div>
                                                    ${replyQuoteInq}
                                                    <div class="small fw-700 mb-1"><i class="bi bi-box-seam-fill me-1"></i> Product Inquiry</div>
                                                <div class="prod-card p-2 rounded">
                                                    ${imgHtml}
                                                    <div class="fw-700">${meta.item_name}</div>
                                                    <div class="text-muted small mb-2" style="font-size:0.72rem;">Brand: ${meta.brand || '-'} | Model: ${meta.model || '-'}</div>
                                                    <div class="p-2 bg-white rounded text-dark mb-2" style="font-size:0.75rem;">
                                                        <strong>Note:</strong> <span class="inquiry-note-text">${escapeHtml(meta.note || '')}</span>
                                                    </div>
                                                    <div class="fw-600 mt-2 small border-top pt-1">Stock Availability:</div>
                                                    <table class="stocks-table" style="font-size: 0.72rem;">
                                                        <tbody>
                                                            ${stockRows}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                    <div class="msg-time" title="${fullTimestamp}"><span>${dateTimeStr}</span>${editedTag}</div>
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
                                
                                let actionsHtml = '';
                                if (isOut) {
                                    actionsHtml = `
                                        <div class="msg-actions">
                                            <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-reply-fill"></i></button>
                                            <button class="msg-action-btn msg-edit-btn" title="Edit message" data-msg-id="${msg.id}" data-msg-type="text" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-pencil-fill"></i></button>
                                            <button class="msg-action-btn msg-delete-btn" title="Delete message" data-msg-id="${msg.id}"><i class="bi bi-trash3-fill"></i></button>
                                        </div>
                                    `;
                                } else {
                                    actionsHtml = `
                                        <div class="msg-actions">
                                            <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-reply-fill"></i></button>
                                        </div>
                                    `;
                                }

                                messageHtml = `
                                    <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                        <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                            ${actionsHtml}
                                            <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                <div class="msg-sender-name">${senderName} (${shopLabel})</div>
                                                ${replyQuote}
                                                <div class="msg-text">${escapeHtml(msg.message)}</div>
                                                <div class="msg-time" title="${fullTimestamp}"><span>${dateTimeStr}</span>${editedTag}</div>
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

        // Helper to update a message in the DOM when edited
        function updateMessageInDOM(msg, curUserId) {
            const $el = $(`#chat-msg-${msg.id}`);
            if (!$el.length) return;

            if (msg.type === 'product_inquiry') {
                const meta = msg.metadata;
                if (meta && meta.note) {
                    $el.find('.inquiry-note-text').text(meta.note);
                }
                $el.find('.msg-edit-btn').data('msg-text', meta ? meta.note : '');
            } else {
                $el.find('.msg-text').text(msg.message);
                $el.find('.msg-reply-btn').data('msg-text', msg.message);
                $el.find('.msg-edit-btn').data('msg-text', msg.message);
            }

            const dateTimeStr = formatMessageDateTime(msg.created_at);
            const fullTimestamp = new Date(msg.created_at).toLocaleString();
            const editedTag = `<span class="msg-edited-tag ms-1"><i class="bi bi-pencil-fill" style="font-size:0.55rem;"></i> edited</span>`;
            $el.find('.msg-time').attr('title', fullTimestamp).html(`<span>${dateTimeStr}</span>${editedTag}`);

            // Flash highlight effect
            $el.addClass('msg-flash');
            setTimeout(function () { $el.removeClass('msg-flash'); }, 1500);
        }

        // Send or Edit message handler
        messageForm.on('submit', function (e) {
            e.preventDefault();
            const text = $.trim(messageInput.val());
            if (!text) return;

            const editingId = $('#editingMsgId').val();

            if (editingId) {
                // Perform Edit Update
                $('#btnSend').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                $.ajax({
                    url: `{{ url('/chats/messages') }}/${editingId}`,
                    type: 'PUT',
                    data: {
                        message: text,
                        note: text,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        $('#btnSend').prop('disabled', false);
                        cancelEdit();
                        messageInput.val('');
                        if (response.success && response.message) {
                            updateMessageInDOM(response.message, response.current_user_id || {{ auth()->id() }});
                            showToast('Message updated successfully', 'success');
                        }
                    },
                    error: function (xhr) {
                        $('#btnSend').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save');
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: xhr.responseJSON?.message || 'Unable to update message. Check connection.',
                            confirmButtonColor: '#0088cc'
                        });
                    }
                });
                return;
            }

            // Normal Send Message
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

        // ─── EDIT FEATURE ────────────────────────────────────────────────────────
        const $editBar   = $('#editPreviewBar');
        const $editId    = $('#editingMsgId');
        const $editText  = $('#editPreviewText');

        function cancelEdit() {
            $editId.val('');
            $editBar.hide();
            $editText.text('');
            messageInput.attr('placeholder', 'Type your message here...');
            $('#btnSend').removeClass('btn-warning text-dark fw-600').addClass('btn-accent').html('<i class="bi bi-send-fill me-1"></i> Send');
        }

        function startEditMessage(msgId, text, type) {
            cancelReply();
            $editId.val(msgId);
            $editText.text(text);
            $editBar.css('display', 'flex');
            messageInput.val(text).focus();
            messageInput.attr('placeholder', type === 'inquiry' ? 'Edit inquiry note...' : 'Edit your message...');
            $('#btnSend').removeClass('btn-accent').addClass('btn-warning text-dark fw-600').html('<i class="bi bi-check2-circle me-1"></i> Save');
        }

        $('#btnCancelEdit').on('click', cancelEdit);

        // Click Edit button on a message bubble
        messageLog.on('click', '.msg-edit-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const msgId   = $(this).data('msg-id');
            const msgType = $(this).data('msg-type');
            const msgText = $(this).data('msg-text');
            startEditMessage(msgId, msgText, msgType);
        });

        // ─── DELETE FEATURE ──────────────────────────────────────────────────────
        messageLog.on('click', '.msg-delete-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const msgId = $(this).data('msg-id');

            Swal.fire({
                title: 'Delete Message?',
                text: 'Are you sure you want to delete this message? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('/chats/messages') }}/${msgId}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (res) {
                            if (res.success) {
                                if ($('#editingMsgId').val() == msgId) {
                                    cancelEdit();
                                }
                                const $msgEl = $(`#chat-msg-${msgId}`);
                                if ($msgEl.length) {
                                    $msgEl.fadeOut(250, function () {
                                        $(this).remove();
                                    });
                                }
                                showToast('Message deleted successfully', 'success');
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: xhr.responseJSON?.message || 'Unable to delete message. Check connection.',
                                confirmButtonColor: '#0088cc'
                            });
                        }
                    });
                }
            });
        });

        // ─── EMOJI PICKER FEATURE ────────────────────────────────────────────────
        const emojiData = [
            // Quick / Favorites
            { emoji: '👍', name: 'thumbs up ok yes agree good', cat: 'quick' },
            { emoji: '❤️', name: 'red heart love like best', cat: 'quick' },
            { emoji: '😂', name: 'laughing tears joy funny lol haha', cat: 'quick' },
            { emoji: '🔥', name: 'fire lit hot fast popular', cat: 'quick' },
            { emoji: '😊', name: 'smiling blush happy nice friendly', cat: 'quick' },
            { emoji: '🙏', name: 'praying hands please thanks thank you', cat: 'quick' },
            { emoji: '📦', name: 'package box parcel stock product item', cat: 'quick' },
            { emoji: '💰', name: 'money bag cash price shillings payment', cat: 'quick' },
            { emoji: '🛒', name: 'shopping cart buy order customer sale', cat: 'quick' },
            { emoji: '✅', name: 'check checkmark done finished ok yes', cat: 'quick' },
            { emoji: '❌', name: 'cross red x cancel no reject out of stock', cat: 'quick' },
            { emoji: '🎉', name: 'party popper celebration congrats welcome', cat: 'quick' },
            { emoji: '🤝', name: 'handshake deal agreement partner customer', cat: 'quick' },
            { emoji: '💡', name: 'light bulb idea note info suggestion', cat: 'quick' },
            { emoji: '📞', name: 'telephone phone call contact call me', cat: 'quick' },
            { emoji: '⏳', name: 'hourglass sand time waiting pending delay', cat: 'quick' },

            // Smileys & Emotion
            { emoji: '😀', name: 'grinning face happy smile laugh', cat: 'smileys' },
            { emoji: '😃', name: 'smiling face big eyes happy joyful', cat: 'smileys' },
            { emoji: '😄', name: 'grinning face smiling eyes happy', cat: 'smileys' },
            { emoji: '😁', name: 'beaming face smiling eyes grin teeth', cat: 'smileys' },
            { emoji: '😆', name: 'grinning squinting face laugh haha', cat: 'smileys' },
            { emoji: '😅', name: 'sweat smile relief phew work', cat: 'smileys' },
            { emoji: '🤣', name: 'rolling on floor laughing rofl funny lol', cat: 'smileys' },
            { emoji: '🥲', name: 'smiling face with tear touched bittersweet', cat: 'smileys' },
            { emoji: '🥹', name: 'face holding back tears proud emotional grateful', cat: 'smileys' },
            { emoji: '☺️', name: 'smiling face calm pleasant blush', cat: 'smileys' },
            { emoji: '😇', name: 'smiling face with halo angel good innocent', cat: 'smileys' },
            { emoji: '🙂', name: 'slightly smiling face ok fine calm', cat: 'smileys' },
            { emoji: '😉', name: 'winking face playful joke wink', cat: 'smileys' },
            { emoji: '😌', name: 'relieved face peaceful calm satisfied', cat: 'smileys' },
            { emoji: '😍', name: 'heart eyes love enamored attractive crush', cat: 'smileys' },
            { emoji: '🥰', name: 'smiling face with hearts loved affectionate', cat: 'smileys' },
            { emoji: '😘', name: 'face blowing a kiss love kiss', cat: 'smileys' },
            { emoji: '😋', name: 'face savoring food delicious tasty yum', cat: 'smileys' },
            { emoji: '😛', name: 'face with tongue playful silly tongue', cat: 'smileys' },
            { emoji: '😜', name: 'winking face with tongue crazy joke fun', cat: 'smileys' },
            { emoji: '🤪', name: 'zany face wild goofy silly crazy', cat: 'smileys' },
            { emoji: '😎', name: 'smiling face with sunglasses cool boss confident smart', cat: 'smileys' },
            { emoji: '🤩', name: 'star struck excited amazed wow great', cat: 'smileys' },
            { emoji: '🥳', name: 'partying face celebration birthday congrats party', cat: 'smileys' },
            { emoji: '😏', name: 'smirking face sly confident smirk', cat: 'smileys' },
            { emoji: '🤔', name: 'thinking face wondering curious hmm puzzle', cat: 'smileys' },
            { emoji: '🤫', name: 'shushing face quiet secret silence hush', cat: 'smileys' },
            { emoji: '🤭', name: 'face with hand over mouth oops giggle surprise', cat: 'smileys' },
            { emoji: '🫢', name: 'face with open eyes and hand over mouth gasp surprise shock', cat: 'smileys' },
            { emoji: '🫡', name: 'saluting face respect yes sir copy that Roger', cat: 'smileys' },
            { emoji: '🤐', name: 'zipper mouth face silent quiet secret zip', cat: 'smileys' },
            { emoji: '🤨', name: 'face with raised eyebrow skeptical really huh doubt', cat: 'smileys' },
            { emoji: '😐', name: 'neutral face poker straight neutral plain', cat: 'smileys' },
            { emoji: '😑', name: 'expressionless face whatever no comment bored', cat: 'smileys' },
            { emoji: '😶', name: 'face without mouth speechless mute silent', cat: 'smileys' },
            { emoji: '🙄', name: 'face rolling eyes annoyed whatever duh eye roll', cat: 'smileys' },
            { emoji: '😬', name: 'grimacing face nervous awkward tense eek', cat: 'smileys' },
            { emoji: '😮‍💨', name: 'face exhaling sigh relief tired phew', cat: 'smileys' },
            { emoji: '😔', name: 'pensive face sad regretful sorry down', cat: 'smileys' },
            { emoji: '😴', name: 'sleeping face zzz night sleep tired', cat: 'smileys' },
            { emoji: '😷', name: 'face with medical mask sick safe health', cat: 'smileys' },
            { emoji: '🤒', name: 'face with thermometer sick fever unwell ill', cat: 'smileys' },
            { emoji: '🤕', name: 'face with head bandage injured hurt pain', cat: 'smileys' },
            { emoji: '🥵', name: 'hot face sweating warm boiling summer heat', cat: 'smileys' },
            { emoji: '🥶', name: 'cold face freezing chilled ice cold', cat: 'smileys' },
            { emoji: '🤯', name: 'exploding head mind blown shocked unbelievable insane', cat: 'smileys' },
            { emoji: '🤠', name: 'cowboy hat face yeehaw partner sheriff', cat: 'smileys' },
            { emoji: '🥸', name: 'disguised face undercover secret mask detective', cat: 'smileys' },
            { emoji: '🤓', name: 'nerd face smart geek study tech glasses', cat: 'smileys' },
            { emoji: '🧐', name: 'face with monocle examining analyzing inspect curious', cat: 'smileys' },
            { emoji: '😕', name: 'confused face unsure puzzled what', cat: 'smileys' },
            { emoji: '😟', name: 'worried face anxious concerned nervous', cat: 'smileys' },
            { emoji: '😮', name: 'face with open mouth surprised wow oh gasp', cat: 'smileys' },
            { emoji: '😲', name: 'astonished face shocked amazed whoa', cat: 'smileys' },
            { emoji: '😳', name: 'flushed face embarrassed blushing shocked red', cat: 'smileys' },
            { emoji: '🥺', name: 'pleading face puppy eyes please beg cute', cat: 'smileys' },
            { emoji: '😨', name: 'fearful face scared frightened afraid', cat: 'smileys' },
            { emoji: '😰', name: 'anxious face with sweat stressed worry panic', cat: 'smileys' },
            { emoji: '😥', name: 'sad but relieved face phew sweat close', cat: 'smileys' },
            { emoji: '😢', name: 'crying face sad tear sorrow grief', cat: 'smileys' },
            { emoji: '😭', name: 'loudly crying face bawling heartbroken sob tears', cat: 'smileys' },
            { emoji: '😱', name: 'face screaming in fear holy shocked panic scream', cat: 'smileys' },
            { emoji: '😤', name: 'face with steam from nose determined triumph angry proud', cat: 'smileys' },
            { emoji: '😡', name: 'pouting face angry mad furious red rage', cat: 'smileys' },
            { emoji: '😠', name: 'angry face mad annoyed cross annoyed', cat: 'smileys' },
            { emoji: '😈', name: 'smiling face with horns mischievous devil evil naughty', cat: 'smileys' },
            { emoji: '💀', name: 'skull dead dead laughing rip skeleton', cat: 'smileys' },
            { emoji: '💩', name: 'pile of poo poop crap funny', cat: 'smileys' },
            { emoji: '🤡', name: 'clown face fool silly joking circus', cat: 'smileys' },
            { emoji: '👻', name: 'ghost spooky boo phantom halloween', cat: 'smileys' },

            // Gestures & People
            { emoji: '👍', name: 'thumbs up like good yes approve correct agreed', cat: 'gestures' },
            { emoji: '👎', name: 'thumbs down dislike bad no disapprove decline', cat: 'gestures' },
            { emoji: '👏', name: 'clapping hands applause kudos bravo well done cheer', cat: 'gestures' },
            { emoji: '🙌', name: 'raising hands celebration praise hooray yay', cat: 'gestures' },
            { emoji: '👐', name: 'open hands welcome hugs open', cat: 'gestures' },
            { emoji: '🤲', name: 'palms up together offering pray dua blessing', cat: 'gestures' },
            { emoji: '🤝', name: 'handshake deal agreement partner client trust shake', cat: 'gestures' },
            { emoji: '🙏', name: 'folded hands pray please thank you namaste thanks', cat: 'gestures' },
            { emoji: '✍️', name: 'writing hand note signing record document paper', cat: 'gestures' },
            { emoji: '💪', name: 'flexed biceps strong strength power gym work solid', cat: 'gestures' },
            { emoji: '👀', name: 'eyes look look at see view watching attention glance', cat: 'gestures' },
            { emoji: '👋', name: 'waving hand hello hi goodbye bye wave greet', cat: 'gestures' },
            { emoji: '✋', name: 'raised hand stop wait high five high-five stop', cat: 'gestures' },
            { emoji: '👌', name: 'ok hand perfect fine nice good accurate', cat: 'gestures' },
            { emoji: '🤌', name: 'pinched fingers italian what do you want explain gesture', cat: 'gestures' },
            { emoji: '🤏', name: 'pinching hand small little bit tiny discount few', cat: 'gestures' },
            { emoji: '✌️', name: 'victory hand peace two 2 win v sign', cat: 'gestures' },
            { emoji: '🤞', name: 'crossed fingers good luck hope wishing cross fingers', cat: 'gestures' },
            { emoji: '🫰', name: 'hand with index finger and thumb crossed korean heart money love cash', cat: 'gestures' },
            { emoji: '🤟', name: 'love you gesture rock on ily', cat: 'gestures' },
            { emoji: '🤘', name: 'sign of the horns rock metal cool party', cat: 'gestures' },
            { emoji: '🤙', name: 'call me hand phone shaka hang loose call', cat: 'gestures' },
            { emoji: '👈', name: 'backhand index pointing left see this look left', cat: 'gestures' },
            { emoji: '👉', name: 'backhand index pointing right look there check point right', cat: 'gestures' },
            { emoji: '👆', name: 'backhand index pointing up above read previous up', cat: 'gestures' },
            { emoji: '👇', name: 'backhand index pointing down below see message note down', cat: 'gestures' },
            { emoji: '☝️', name: 'index pointing up one point first listen remember', cat: 'gestures' },
            { emoji: '👊', name: 'oncoming fist bump power hit bro punch', cat: 'gestures' },
            { emoji: '🤛', name: 'left facing fist bump greeting bro fist', cat: 'gestures' },
            { emoji: '🤜', name: 'right facing fist bump greeting bro fist', cat: 'gestures' },

            // Commerce & Shop
            { emoji: '📦', name: 'package box product stock parcel delivery item carton goods', cat: 'commerce' },
            { emoji: '🛍️', name: 'shopping bags retail store customer purchase buy items shopping', cat: 'commerce' },
            { emoji: '🛒', name: 'shopping cart supermarket checkout buy goods items order cart', cat: 'commerce' },
            { emoji: '💰', name: 'money bag shillings cash price revenue payment income paid rich', cat: 'commerce' },
            { emoji: '💵', name: 'dollar banknote cash money paper bill shillings note', cat: 'commerce' },
            { emoji: '💳', name: 'credit card payment card pos swipe cashless visa mastercard debit', cat: 'commerce' },
            { emoji: '🧾', name: 'receipt bill invoice paper account transaction report slip', cat: 'commerce' },
            { emoji: '🏷️', name: 'label tag price item model category brand discount sale promo', cat: 'commerce' },
            { emoji: '📊', name: 'bar chart stats analytics sales report growth finance data metrics', cat: 'commerce' },
            { emoji: '📈', name: 'chart increasing upward trend sales profit growth rise up', cat: 'commerce' },
            { emoji: '📉', name: 'chart decreasing downward loss drop low reduce down', cat: 'commerce' },
            { emoji: '📋', name: 'clipboard list inventory checklist audit verification task checklist', cat: 'commerce' },
            { emoji: '📌', name: 'pushpin pin important notice memo fix location pinned', cat: 'commerce' },
            { emoji: '📍', name: 'round pushpin location map pin shop branch store address map', cat: 'commerce' },
            { emoji: '🏢', name: 'office building company headquarters owner main store building', cat: 'commerce' },
            { emoji: '🏬', name: 'department store shop outlet mall marketplace branch shop', cat: 'commerce' },
            { emoji: '🏪', name: 'convenience store mini market kiosk sub shop boutique', cat: 'commerce' },
            { emoji: '💼', name: 'briefcase work job business executive office manager briefcase', cat: 'commerce' },
            { emoji: '📁', name: 'file folder document organize data records folder', cat: 'commerce' },
            { emoji: '📅', name: 'calendar date today day schedule handover month date', cat: 'commerce' },
            { emoji: '🗓️', name: 'spiral calendar date appointment schedule time calendar', cat: 'commerce' },
            { emoji: '⏰', name: 'alarm clock time deadline alert reminder urgent clock', cat: 'commerce' },
            { emoji: '⏳', name: 'hourglass not done pending waiting queue processing delay wait', cat: 'commerce' },
            { emoji: '⌛', name: 'hourglass done finished time up complete ended done', cat: 'commerce' },
            { emoji: '🔒', name: 'locked security private safe authorized protected lock', cat: 'commerce' },
            { emoji: '🔓', name: 'unlocked open access granted permission permitted unlock', cat: 'commerce' },
            { emoji: '🔍', name: 'magnifying glass left search inquire verify find inspect check', cat: 'commerce' },
            { emoji: '🔎', name: 'magnifying glass right search inquiry query check verify examine', cat: 'commerce' },
            { emoji: '💡', name: 'light bulb idea inspiration tip advice solution bright', cat: 'commerce' },
            { emoji: '📞', name: 'telephone receiver call phone contact customer client dial', cat: 'commerce' },
            { emoji: '📱', name: 'mobile phone smartphone sms message whatsapp call mobile', cat: 'commerce' },
            { emoji: '💻', name: 'laptop computer pc ide pos system tech screen laptop', cat: 'commerce' },
            { emoji: '🖥️', name: 'desktop computer monitor display office screen pc', cat: 'commerce' },
            { emoji: '🖨️', name: 'printer print receipt document report printout paper print', cat: 'commerce' },
            { emoji: '🚚', name: 'delivery truck transfer stock transport moving logistics truck', cat: 'commerce' },
            { emoji: '🚛', name: 'articulated lorry heavy cargo transfer stock truck supply lorry', cat: 'commerce' },
            { emoji: '🛵', name: 'motor scooter delivery rider dispatch fast bike', cat: 'commerce' },

            // Symbols & Alerts
            { emoji: '✅', name: 'check mark button verified approved correct yes success ok confirm', cat: 'symbols' },
            { emoji: '❌', name: 'cross mark cancel rejected no failed error out of stock decline', cat: 'symbols' },
            { emoji: '⚠️', name: 'warning alert caution attention low stock notice hazard warn', cat: 'symbols' },
            { emoji: '🚨', name: 'police car light emergency alert siren critical urgent danger', cat: 'symbols' },
            { emoji: '⛔', name: 'no entry stop forbidden not allowed prohibited stop', cat: 'symbols' },
            { emoji: '💯', name: 'hundred points score perfect 100 accurate full total hundred', cat: 'symbols' },
            { emoji: '🔥', name: 'fire hot fast popular best seller burning lit flame hot', cat: 'symbols' },
            { emoji: '⭐', name: 'star rating favorite top special premium high quality star', cat: 'symbols' },
            { emoji: '🌟', name: 'glowing star bright shiny excel excellent star glow', cat: 'symbols' },
            { emoji: '✨', name: 'sparkles new fresh shiny clean magic special bonus sparkle', cat: 'symbols' },
            { emoji: '⚡', name: 'high voltage quick instant fast electric power flash fast', cat: 'symbols' },
            { emoji: '🔔', name: 'bell notification alert chime ring sound on notify', cat: 'symbols' },
            { emoji: '🔕', name: 'bell with slash mute silent notifications off quiet silent', cat: 'symbols' },
            { emoji: '📢', name: 'loudspeaker announcement broadcast sms public notice alert speak', cat: 'symbols' },
            { emoji: '📣', name: 'megaphone shouting cheer broadcast announce tell team cheer', cat: 'symbols' },
            { emoji: '🎯', name: 'bullseye direct target goal achievement quota target hit goal', cat: 'symbols' },
            { emoji: '🚀', name: 'rocket fast quick launch boost skyrocket speed boost launch', cat: 'symbols' },
            { emoji: '🏆', name: 'trophy champion win winner #1 award prize best victory', cat: 'symbols' },
            { emoji: '🥇', name: '1st place medal gold first winner top seller gold', cat: 'symbols' },
            { emoji: '🥈', name: '2nd place medal silver second runner up silver', cat: 'symbols' },
            { emoji: '🥉', name: '3rd place medal bronze third bronze medal', cat: 'symbols' },
            { emoji: '❤️', name: 'red heart love passion like favorite adore heart', cat: 'symbols' },
            { emoji: '💙', name: 'blue heart brand loyalty peace trust blue', cat: 'symbols' },
            { emoji: '💚', name: 'green heart money profit success go live green', cat: 'symbols' },
            { emoji: '💛', name: 'yellow heart friendship warmth bright joy yellow', cat: 'symbols' },
            { emoji: '💜', name: 'purple heart royal luxury admin owner premium purple', cat: 'symbols' },
            { emoji: '🖤', name: 'black heart dark sleek style gothic solid black', cat: 'symbols' },
            { emoji: '🤍', name: 'white heart pure peace clarity light clean white', cat: 'symbols' },
            { emoji: '💔', name: 'broken heart broken damaged defective defect fault defect break', cat: 'symbols' },
            { emoji: '💬', name: 'speech balloon chat talk message discuss question comment', cat: 'symbols' },
            { emoji: '💭', name: 'thought balloon think consider plan ideas memory dream', cat: 'symbols' },
            { emoji: '💥', name: 'collision explosion bang boom discount offer impact boom', cat: 'symbols' },
            { emoji: '🎉', name: 'party popper congrats celebration success happy joy celebrate', cat: 'symbols' }
        ];

        const $emojiPopup       = $('#emojiPickerPopup');
        const $emojiGrid        = $('#emojiGridContainer');
        const $emojiSearch      = $('#emojiSearchInput');
        const $emojiPreview     = $('#emojiPreviewLabel');
        let currentEmojiCategory = 'quick';

        function renderEmojiGrid(category = 'quick', searchKeyword = '') {
            let filtered = [];
            const keyword = $.trim(searchKeyword).toLowerCase();

            if (keyword) {
                filtered = emojiData.filter(function(item) {
                    return item.name.toLowerCase().includes(keyword) || item.emoji.includes(keyword);
                });
            } else {
                if (category === 'quick') {
                    filtered = emojiData.filter(item => item.cat === 'quick');
                } else {
                    filtered = emojiData.filter(item => item.cat === category);
                }
            }

            if (filtered.length === 0) {
                $emojiGrid.html(`
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-emoji-frown fs-3 d-block mb-1 text-secondary"></i>
                        No matching emojis found
                    </div>
                `);
                return;
            }

            let html = '<div class="emoji-grid">';
            filtered.forEach(function(item) {
                html += `<button type="button" class="emoji-btn" data-emoji="${item.emoji}" title="${item.name.split(' ').slice(0, 3).join(' ')}">${item.emoji}</button>`;
            });
            html += '</div>';

            $emojiGrid.html(html);
        }

        function insertEmojiAtCursor(emoji) {
            const input = document.getElementById('messageInput');
            if (!input) return;

            const startPos = input.selectionStart || input.value.length;
            const endPos = input.selectionEnd || input.value.length;
            const oldVal = input.value;

            input.value = oldVal.substring(0, startPos) + emoji + oldVal.substring(endPos);
            input.focus();
            input.setSelectionRange(startPos + emoji.length, startPos + emoji.length);

            $emojiPreview.html(`<span class="text-success fw-700"><i class="bi bi-check2 me-1"></i>Inserted ${emoji}</span>`);
            setTimeout(function () {
                $emojiPreview.html('<i class="bi bi-cursor me-1"></i>Click emoji to insert');
            }, 1800);
        }

        // Toggle Emoji Picker Popup
        $('#btnEmojiToggle').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if ($emojiPopup.is(':visible')) {
                $emojiPopup.hide();
            } else {
                $emojiPopup.css('display', 'flex');
                $emojiSearch.val('');
                currentEmojiCategory = 'quick';
                $('.emoji-tab-btn').removeClass('active');
                $('.emoji-tab-btn[data-category="quick"]').addClass('active');
                renderEmojiGrid('quick');
                $emojiSearch.focus();
            }
        });

        // Category Tab switching
        $(document).on('click', '.emoji-tab-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('.emoji-tab-btn').removeClass('active');
            $(this).addClass('active');
            currentEmojiCategory = $(this).data('category');
            $emojiSearch.val('');
            renderEmojiGrid(currentEmojiCategory);
        });

        // Search in Emoji Picker
        $emojiSearch.on('input', function () {
            const q = $(this).val();
            if (q) {
                $('.emoji-tab-btn').removeClass('active');
            } else {
                $(`.emoji-tab-btn[data-category="${currentEmojiCategory}"]`).addClass('active');
            }
            renderEmojiGrid(currentEmojiCategory, q);
        });

        // Click on Emoji
        $(document).on('click', '.emoji-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const emojiChar = $(this).data('emoji');
            insertEmojiAtCursor(emojiChar);
        });

        // Close Emoji Picker button
        $('#btnCloseEmojiPicker').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $emojiPopup.hide();
            messageInput.focus();
        });

        // Close Emoji Picker on clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#emojiPickerPopup, #btnEmojiToggle').length) {
                if ($emojiPopup.is(':visible')) {
                    $emojiPopup.hide();
                }
            }
        });

        // Cancel on Escape key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                if ($emojiPopup.is(':visible')) $emojiPopup.hide();
                if ($editId.val()) cancelEdit();
                if ($replyId.val()) cancelReply();
            }
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
