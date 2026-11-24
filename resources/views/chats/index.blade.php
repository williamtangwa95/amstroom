@extends('layouts.app')
@section('title', 'Live Chat & Messaging')
@section('page-title', 'Live Chat & Messaging')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Live Chat</li>
@endsection

@push('styles')
<style>
    /* ══════════════════════════════════════════════
       LIVE CHAT & MESSAGING — Modern Responsive UI
       ══════════════════════════════════════════════ */
    .chat-shell {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .05);
        overflow: hidden;
        height: calc(100vh - 155px);
        min-height: 540px;
        display: flex;
    }

    #chatAppRow {
        display: flex;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    /* ── Left Sidebar ── */
    #chatSidebar {
        width: 315px;
        min-width: 280px;
        max-width: 340px;
        flex-shrink: 0;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    .chat-sidebar-header {
        padding: .95rem 1rem .85rem;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .chat-sidebar-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .7rem;
    }

    .chat-sidebar-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .92rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .chat-sidebar-title-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .88rem;
    }

    .chat-btn-sms-broadcast {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        padding: .28rem .6rem;
        font-size: .71rem;
        font-weight: 600;
        color: #0077b6;
        background: rgba(0, 119, 182, .08);
        border: 1px solid rgba(0, 119, 182, .2);
        border-radius: 8px;
        transition: all .15s ease;
        cursor: pointer;
        white-space: nowrap;
    }

    .chat-btn-sms-broadcast:hover {
        background: #0077b6;
        color: #ffffff;
        border-color: #0077b6;
    }

    .chat-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .chat-search-wrap .chat-search-icon {
        position: absolute;
        left: .75rem;
        color: #94a3b8;
        font-size: .8rem;
        pointer-events: none;
    }

    .chat-search-input {
        width: 100%;
        padding: .46rem 2rem .46rem 2.15rem;
        font-size: .8rem;
        background: #f1f5f9;
        border: 1px solid transparent;
        border-radius: 10px;
        color: var(--text-primary);
        transition: all .15s ease;
    }

    .chat-search-input::placeholder {
        color: #94a3b8;
    }

    .chat-search-input:focus {
        outline: none;
        background: #ffffff;
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .12);
    }

    .chat-search-clear {
        position: absolute;
        right: .45rem;
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: #94a3b8;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        cursor: pointer;
    }

    .chat-search-clear:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /* Scrollable list */
    #chatListContainer {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: .5rem .55rem .75rem;
    }

    .chat-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .55rem .55rem .3rem;
    }

    .chat-section-label {
        font-size: .66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
    }

    .chat-multisend-toggle {
        display: inline-flex;
        align-items: center;
        gap: .28rem;
        padding: .18rem .5rem;
        font-size: .68rem;
        font-weight: 600;
        color: #0077b6;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        cursor: pointer;
        transition: all .15s ease;
    }

    .chat-multisend-toggle:hover {
        border-color: #0077b6;
        background: rgba(0, 119, 182, .06);
    }

    .chat-multisend-toggle.is-active {
        background: #0077b6;
        color: #ffffff;
        border-color: #0077b6;
    }

    /* Sidebar Contact / Channel Item */
    .chat-list-group {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .chat-target {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .58rem .68rem;
        border-radius: 11px;
        text-decoration: none;
        color: var(--text-primary);
        border: 1px solid transparent;
        background: transparent;
        cursor: pointer;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .chat-target:hover {
        background: #ffffff;
        border-color: #e2e8f0;
        color: var(--text-primary);
    }

    .chat-target.active {
        background: #ffffff !important;
        border-color: rgba(0, 119, 182, .32) !important;
        box-shadow: 0 2px 8px rgba(0, 119, 182, .08);
    }

    .chat-target.active .chat-item-name {
        color: #005691;
    }

    .chat-target.multi-mode.is-checked {
        background: rgba(0, 119, 182, .07);
        border-color: rgba(0, 119, 182, .28);
    }

    .chat-avatar-box {
        position: relative;
        flex-shrink: 0;
    }

    .chat-avatar-img,
    .chat-avatar-initials,
    .chat-channel-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .chat-avatar-img {
        object-fit: cover;
        border: 1.5px solid #e2e8f0;
    }

    .chat-channel-icon {
        border-radius: 11px;
        background: linear-gradient(135deg, #0077b6, #004e89);
        color: #ffffff;
        font-size: 1rem;
        box-shadow: 0 2px 6px rgba(0, 119, 182, .22);
    }

    .chat-avatar-initials {
        background: linear-gradient(135deg, #e0f2fe, #bae6fd);
        color: #0369a1;
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .chat-avatar-dot {
        position: absolute;
        right: -1px;
        bottom: -1px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #94a3b8;
        border: 2px solid #f8fafc;
    }

    .chat-target.active .chat-avatar-dot,
    .chat-target:hover .chat-avatar-dot {
        border-color: #ffffff;
    }

    .chat-item-body {
        flex: 1;
        min-width: 0;
    }

    .chat-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .4rem;
    }

    .chat-item-name {
        font-size: .82rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-item-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .4rem;
        margin-top: .16rem;
    }

    .chat-item-sub {
        font-size: .71rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-role-tag {
        font-size: .58rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: .1rem .38rem;
        border-radius: 5px;
        flex-shrink: 0;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .chat-role-tag.role-tag-owner {
        background: #fef3c7;
        color: #b45309;
        border-color: #fde68a;
    }

    .chat-role-tag.role-tag-shop_admin {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #bae6fd;
    }

    .chat-role-tag.role-tag-system_admin {
        background: #ede9fe;
        color: #6d28d9;
        border-color: #ddd6fe;
    }

    .chat-unread-badge-container {
        font-size: .65rem;
        font-weight: 700;
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ef4444;
        color: #ffffff;
        flex-shrink: 0;
    }

    /* Multi-Send Panel */
    #multiSendPanel {
        padding: .75rem .85rem;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 12px rgba(15, 23, 42, .04);
        flex-shrink: 0;
    }

    /* ── Main Chat Area ── */
    #chatAreaContainer {
        flex: 1;
        min-width: 0;
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        position: relative;
    }

    .chat-main-header {
        padding: .8rem 1.2rem;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-shrink: 0;
        z-index: 5;
    }

    .chat-main-header-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
        flex: 1;
    }

    .chat-back-btn {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: var(--text-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        cursor: pointer;
        transition: all .15s;
    }

    .chat-back-btn:hover {
        background: #e2e8f0;
    }

    .chat-active-title {
        font-size: .92rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-active-sub {
        font-size: .73rem;
        color: #64748b;
        display: block;
        margin-top: .12rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-header-actions {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-shrink: 0;
    }

    .chat-hdr-btn {
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        padding: .4rem .75rem;
        font-size: .76rem;
        font-weight: 600;
        border-radius: 9px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }

    .chat-hdr-btn-primary {
        color: #0077b6;
        border-color: rgba(0, 119, 182, .25);
        background: rgba(0, 119, 182, .05);
    }

    .chat-hdr-btn-primary:hover {
        background: #0077b6;
        border-color: #0077b6;
        color: #ffffff;
    }

    .chat-hdr-btn-sms {
        color: #4f46e5;
        border-color: rgba(79, 70, 229, .24);
        background: rgba(79, 70, 229, .05);
    }

    .chat-hdr-btn-sms:hover {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #ffffff;
    }

    /* ── Message Feed Canvas ── */
    #messageLog {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 1.25rem 1.35rem;
        background-color: #f8fafc;
        background-image: radial-gradient(rgba(15, 23, 42, .045) 1px, transparent 1px);
        background-size: 22px 22px;
    }

    /* Slim Custom Scrollbars */
    #chatListContainer::-webkit-scrollbar,
    #messageLog::-webkit-scrollbar,
    #emojiGridContainer::-webkit-scrollbar {
        width: 5px;
    }
    #chatListContainer::-webkit-scrollbar-track,
    #messageLog::-webkit-scrollbar-track,
    #emojiGridContainer::-webkit-scrollbar-track {
        background: transparent;
    }
    #chatListContainer::-webkit-scrollbar-thumb,
    #messageLog::-webkit-scrollbar-thumb,
    #emojiGridContainer::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .4);
        border-radius: 99px;
    }
    #chatListContainer::-webkit-scrollbar-thumb:hover,
    #messageLog::-webkit-scrollbar-thumb:hover,
    #emojiGridContainer::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, .6);
    }

    /* ── Date Dividers ── */
    .chat-date-divider {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin: 1.2rem 0 .9rem;
    }

    .chat-date-divider::before,
    .chat-date-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .chat-date-pill {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .68rem;
        font-weight: 600;
        padding: .2rem .7rem;
        border-radius: 999px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        letter-spacing: .02em;
        white-space: nowrap;
    }

    /* ── Message Bubbles ── */
    .msg-wrapper {
        margin-bottom: .85rem;
        display: flex;
        flex-direction: column;
    }

    .msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        position: relative;
    }

    .msg-row-out { flex-direction: row-reverse; }
    .msg-row-in  { flex-direction: row; }

    .msg-container {
        max-width: 72%;
        min-width: 110px;
        padding: .68rem .95rem .58rem;
        border-radius: 16px;
        font-size: .85rem;
        line-height: 1.48;
        position: relative;
        word-break: break-word;
    }

    .msg-text {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .msg-incoming {
        background: #ffffff;
        color: var(--text-primary);
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .04);
    }

    .msg-outgoing {
        background: linear-gradient(135deg, #0077b6 0%, #005b94 100%);
        color: #ffffff;
        border-bottom-right-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 119, 182, .2);
    }

    .msg-sender-name {
        font-size: .69rem;
        font-weight: 700;
        margin-bottom: .22rem;
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .msg-incoming .msg-sender-name {
        color: #0077b6;
    }

    .msg-outgoing .msg-sender-name {
        color: rgba(255, 255, 255, .92);
    }

    .msg-sender-shop {
        font-size: .63rem;
        font-weight: 500;
        opacity: .75;
    }

    .msg-time {
        font-size: .63rem;
        margin-top: .32rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
    }

    .msg-incoming .msg-time {
        color: #94a3b8;
    }

    .msg-outgoing .msg-time {
        color: rgba(255, 255, 255, .78);
    }

    .msg-edited-tag {
        font-size: .6rem;
        font-style: italic;
        opacity: .9;
    }

    .msg-incoming .msg-edited-tag { color: #d97706; }
    .msg-outgoing .msg-edited-tag { color: #fde68a; }

    /* Floating Message Actions Pill */
    .msg-actions {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 3px 4px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
        opacity: 0;
        transform: translateY(2px) scale(.95);
        pointer-events: none;
        transition: opacity .16s ease, transform .16s ease;
        flex-shrink: 0;
        align-self: center;
    }

    .msg-row:hover .msg-actions,
    .msg-row.actions-open .msg-actions {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .msg-action-btn {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: none;
        background: transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #475569;
        font-size: .78rem;
        transition: background-color .14s ease, color .14s ease;
    }

    .msg-action-btn:hover {
        background: rgba(0, 119, 182, .1);
        color: #0077b6;
    }

    .msg-action-btn.msg-edit-btn:hover {
        background: rgba(245, 158, 11, .14);
        color: #d97706;
    }

    .msg-action-btn.msg-delete-btn:hover {
        background: rgba(239, 68, 68, .12);
        color: #dc2626;
    }

    /* Quoted Reply inside Bubble */
    .msg-reply-quote {
        border-radius: 8px;
        padding: 6px 10px 6px 12px;
        margin-bottom: 6px;
        cursor: pointer;
        font-size: .73rem;
        transition: opacity .15s;
        overflow: hidden;
        position: relative;
    }

    .msg-reply-quote::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
    }

    .msg-outgoing .msg-reply-quote {
        background: rgba(255, 255, 255, .14);
    }
    .msg-outgoing .msg-reply-quote::before {
        background: rgba(255, 255, 255, .75);
    }

    .msg-incoming .msg-reply-quote {
        background: #f1f5f9;
    }
    .msg-incoming .msg-reply-quote::before {
        background: #0077b6;
    }

    .msg-reply-quote:hover { opacity: .85; }
    .msg-reply-quote .quote-name {
        font-weight: 700;
        font-size: .68rem;
        margin-bottom: 1px;
        display: block;
    }
    .msg-outgoing .msg-reply-quote .quote-name { color: #ffffff; }
    .msg-incoming .msg-reply-quote .quote-name { color: #0077b6; }
    .msg-reply-quote .quote-text {
        display: block;
        opacity: .85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Product Inquiry Card inside Bubble ── */
    .prod-card {
        border-radius: 11px;
        overflow: hidden;
        margin-top: .4rem;
        font-size: .78rem;
        background: #ffffff;
        color: var(--text-primary);
        border: 1px solid #e2e8f0;
        padding: .75rem;
    }

    .msg-outgoing .prod-card {
        border-color: rgba(255, 255, 255, .25);
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .prod-card-head {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        margin-bottom: .55rem;
    }

    .prod-card-note {
        padding: .48rem .65rem;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: .75rem;
        color: #334155;
        margin-bottom: .6rem;
    }

    .stocks-table {
        width: 100%;
        margin-top: .35rem;
        border-collapse: collapse;
        font-size: .72rem;
    }

    .stocks-table td {
        padding: .35rem .4rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }

    .stocks-table tr:last-child td {
        border-bottom: none;
    }

    /* ── Composer & Preview Bars ── */
    #inputArea {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
        position: relative;
    }

    .chat-context-bar {
        display: none;
        align-items: center;
        gap: .65rem;
        padding: .55rem 1.15rem;
        font-size: .77rem;
        border-bottom: 1px solid #f1f5f9;
        animation: slideInUp .16s ease-out;
    }

    #replyPreviewBar {
        border-left: 3.5px solid #0077b6;
        background: #f0f9ff;
    }

    #editPreviewBar {
        border-left: 3.5px solid #d97706;
        background: #fffbeb;
    }

    .chat-context-close {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: none;
        background: rgba(15, 23, 42, .07);
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: .85rem;
        transition: background .15s;
    }

    .chat-context-close:hover {
        background: rgba(15, 23, 42, .14);
        color: #0f172a;
    }

    .chat-composer-wrap {
        padding: .75rem 1.15rem;
    }

    .chat-composer-dock {
        display: flex;
        align-items: center;
        gap: .45rem;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 13px;
        padding: .3rem .35rem .3rem .4rem;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .chat-composer-dock:focus-within {
        background: #ffffff;
        border-color: #0077b6;
        box-shadow: 0 0 0 3px rgba(0, 119, 182, .12);
    }

    .chat-emoji-trigger {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        border: none;
        background: transparent;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s, color .15s;
    }

    .chat-emoji-trigger:hover {
        background: #e2e8f0;
        color: #d97706;
    }

    .chat-message-input {
        flex: 1;
        min-width: 0;
        border: none;
        background: transparent;
        padding: .42rem .35rem;
        font-size: .85rem;
        color: var(--text-primary);
    }

    .chat-message-input:focus {
        outline: none;
    }

    .chat-message-input::placeholder {
        color: #94a3b8;
    }

    .chat-send-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .46rem 1rem;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #0077b6, #005691);
        color: #ffffff;
        font-size: .81rem;
        font-weight: 600;
        cursor: pointer;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0, 119, 182, .22);
        transition: all .15s ease;
    }

    .chat-send-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, #00669e, #004473);
        transform: translateY(-1px);
    }

    .chat-send-btn.is-editing {
        background: linear-gradient(135deg, #d97706, #b45309);
        box-shadow: 0 2px 6px rgba(217, 119, 6, .25);
    }

    /* ── Emoji Picker Popover ── */
    #emojiPickerPopup {
        display: none;
        position: absolute;
        bottom: calc(100% + 8px);
        left: 1.15rem;
        width: 340px;
        max-width: calc(100vw - 2rem);
        height: 360px;
        z-index: 1050;
        flex-direction: column;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .16), 0 2px 8px rgba(15, 23, 42, .06);
        animation: slideInUp .16s ease-out;
    }

    .emoji-tab-btn {
        font-size: 1rem;
        padding: 3px 8px;
        border-radius: 7px;
        background: transparent;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .14s ease;
    }

    .emoji-tab-btn:hover {
        background: rgba(0, 119, 182, .1);
    }

    .emoji-tab-btn.active {
        background: #0077b6;
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
        font-size: 1.3rem;
        line-height: 1;
        transition: transform .12s ease, background-color .12s ease;
    }

    .emoji-btn:hover {
        background: rgba(0, 119, 182, .12);
        transform: scale(1.18);
    }

    /* ── Modals Polish ── */
    .chat-modal .modal-content {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .18);
        overflow: hidden;
    }

    .chat-modal .modal-header {
        padding: 1rem 1.3rem;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }

    .chat-modal-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .chat-modal .modal-body {
        padding: 1.25rem 1.3rem;
    }

    .chat-modal .modal-footer {
        padding: .85rem 1.3rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }

    /* Animations */
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes msgFlash {
        0%   { box-shadow: 0 0 0 3px rgba(0, 119, 182, .4); }
        100% { box-shadow: none; }
    }

    .msg-flash .msg-container {
        animation: msgFlash 1.4s ease-out;
    }

    /* ── Responsive Breakpoints ── */
    @media (max-width: 991.98px) {
        #chatSidebar {
            width: 270px;
            min-width: 245px;
        }
        .msg-container {
            max-width: 82%;
        }
    }

    @media (max-width: 767.98px) {
        .chat-shell {
            height: calc(100dvh - 125px);
            min-height: 460px;
            border-radius: 12px;
        }
        #chatSidebar,
        #chatAreaContainer {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            border-right: none;
        }
        .mobile-view-sidebar #chatSidebar { display: flex !important; }
        .mobile-view-sidebar #chatAreaContainer { display: none !important; }
        .mobile-view-chat #chatSidebar { display: none !important; }
        .mobile-view-chat #chatAreaContainer { display: flex !important; }

        .chat-main-header {
            padding: .7rem .85rem;
        }
        .chat-hdr-btn {
            padding: .38rem .58rem;
            font-size: .74rem;
        }
        .chat-hdr-btn .btn-label {
            display: none;
        }
        #messageLog {
            padding: .9rem .85rem;
        }
        .msg-container {
            max-width: 86%;
            font-size: .83rem;
        }
        .chat-composer-wrap {
            padding: .6rem .75rem;
        }
        .chat-send-btn {
            padding: .46rem .75rem;
        }
        .chat-send-btn .send-label {
            display: none;
        }
        #emojiPickerPopup {
            left: .75rem;
            right: .75rem;
            width: auto;
            max-width: none;
            height: 310px;
        }
    }
</style>
@endpush

@section('content')
<div class="chat-shell">
    <div class="mobile-view-sidebar" id="chatAppRow">
        <!-- LEFT SIDEBAR: Channels & Users -->
        <aside id="chatSidebar">
            <div class="chat-sidebar-header">
                <div class="chat-sidebar-title-row">
                    <h6 class="chat-sidebar-title">
                        <span class="chat-sidebar-title-icon"><i class="bi bi-chat-dots-fill"></i></span>
                        <span>Messages</span>
                    </h6>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <button type="button" class="chat-btn-sms-broadcast" data-bs-toggle="modal" data-bs-target="#smsModal" title="Send System SMS Broadcast">
                        <i class="bi bi-phone-vibrate"></i>
                        <span>Broadcast SMS</span>
                    </button>
                    @endif
                </div>
                <div class="chat-search-wrap">
                    <i class="bi bi-search chat-search-icon"></i>
                    <input type="text" id="userSearch" class="chat-search-input" placeholder="Search channels or people..." autocomplete="off">
                    <button type="button" id="btnClearUserSearch" class="chat-search-clear" title="Clear search">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>

            <!-- Channel / User Lists Scrollable -->
            <div id="chatListContainer">
                <!-- Group Channels Section -->
                <div class="chat-section-head">
                    <span class="chat-section-label">Channels</span>
                </div>
                <div class="chat-list-group mb-2" id="channelsListGroup">
                    <a href="#" class="chat-target active" data-type="group" data-id="group" data-name="Refreshment Room">
                        <div class="chat-avatar-box">
                            <div class="chat-channel-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                        <div class="chat-item-body">
                            <div class="chat-item-top">
                                <h6 class="chat-item-name"># Refreshment Room</h6>
                            </div>
                            <div class="chat-item-bottom">
                                <span class="chat-item-sub">Team-wide group channel</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Direct Messages Section -->
                <div class="chat-section-head">
                    <span class="chat-section-label">Direct Messages ({{ $users->count() }})</span>
                    <button type="button" id="btnToggleMultiSend" class="chat-multisend-toggle" title="Send a private message to multiple people">
                        <i class="bi bi-check2-square"></i>
                        <span>Multi-Send</span>
                    </button>
                </div>
                <div class="chat-list-group" id="usersListGroup">
                    @foreach($users as $user)
                    @php
                        $userInitials = collect(explode(' ', trim($user->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn($p) => strtoupper(substr($p, 0, 1)))
                            ->implode('') ?: 'U';
                        $roleShort = $user->role === 'shop_admin' ? 'admin' : ($user->role === 'owner' ? 'owner' : ($user->role === 'system_admin' ? 'sys admin' : 'seller'));
                    @endphp
                    <a href="#" class="chat-target user-item"
                       data-type="individual"
                       data-id="{{ $user->id }}"
                       data-name="{{ $user->name }}"
                       data-initials="{{ $userInitials }}"
                       data-shop="{{ $user->shop ? $user->shop->shop_name : 'Owner / Main Store' }}"
                       data-role="{{ str_replace('_', ' ', $user->role) }}"
                       data-avatar="{{ $user->avatar_path ? asset('media/' . $user->avatar_path) : '' }}"
                       data-phone="{{ $user->phone }}">
                        <input type="checkbox" class="multi-send-checkbox form-check-input m-0 d-none flex-shrink-0" value="{{ $user->id }}">
                        <div class="chat-avatar-box">
                            @if($user->avatar_path)
                                <img src="{{ asset('media/' . $user->avatar_path) }}" alt="{{ $user->name }}" class="chat-avatar-img">
                            @else
                                <div class="chat-avatar-initials">{{ $userInitials }}</div>
                            @endif
                            <span class="chat-avatar-dot" aria-hidden="true"></span>
                        </div>
                        <div class="chat-item-body">
                            <div class="chat-item-top">
                                <h6 class="chat-item-name">{{ $user->name }}</h6>
                                <span class="chat-unread-badge-container {{ $user->unread_count > 0 ? '' : 'd-none' }}">
                                    {{ $user->unread_count }}
                                </span>
                            </div>
                            <div class="chat-item-bottom">
                                <span class="chat-item-sub">
                                    {{ $user->shop ? $user->shop->shop_name : ($user->isOwner() ? 'Main Store' : 'Unassigned') }}
                                </span>
                                <span class="chat-role-tag role-tag-{{ $user->role }}">{{ $roleShort }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div id="noContactsFound" class="text-center py-4 text-muted small d-none">
                    <i class="bi bi-search d-block mb-1" style="font-size:1.1rem; opacity:.5;"></i>
                    No matching contacts
                </div>
            </div>

            <!-- Multi-Send Compose Panel -->
            <div id="multiSendPanel" class="d-none">
                <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                    <span class="badge rounded-pill" id="multiSendCount" style="background:#0077b6; font-size:.7rem;">0 selected</span>
                    <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none p-0" id="btnClearMultiSelect" style="font-size:.73rem; font-weight:600;">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
                <div class="input-group input-group-sm">
                    <input type="text" id="multiSendInput" class="form-control" placeholder="Write private message to selected..." maxlength="2000" style="border-radius: 8px 0 0 8px;">
                    <button type="button" class="btn btn-accent" id="btnSendBulk" disabled style="border-radius: 0 8px 8px 0;">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="text-muted mt-1" style="font-size:.67rem;">
                    <i class="bi bi-lock me-1"></i>Each recipient receives a separate direct message.
                </div>
            </div>
        </aside>

        <!-- MAIN CHAT AREA -->
        <section id="chatAreaContainer">
            <!-- Chat Header -->
            <div class="chat-main-header">
                <div class="chat-main-header-left">
                    <button type="button" class="chat-back-btn d-md-none" id="btnBackToSidebar" title="Back to conversations">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div id="activeChatIcon" class="flex-shrink-0">
                        <div class="chat-channel-icon" style="width:40px; height:40px; font-size:1.05rem;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h6 class="chat-active-title" id="activeChatName"># Refreshment Room</h6>
                        <span class="chat-active-sub" id="activeChatStatus">Team-wide group channel</span>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="chat-header-actions">
                    <button type="button" class="chat-hdr-btn chat-hdr-btn-primary" data-bs-toggle="modal" data-bs-target="#inquireModal" title="Inquire product availability">
                        <i class="bi bi-box-seam"></i>
                        <span class="btn-label">Inquire Product</span>
                    </button>
                    @if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
                    <button type="button" class="chat-hdr-btn chat-hdr-btn-sms d-none" id="btnHeaderSms" title="Send direct SMS message">
                        <i class="bi bi-chat-square-text"></i>
                        <span class="btn-label">Send SMS</span>
                    </button>
                    @endif
                </div>
            </div>

            <!-- Messages Log -->
            <div id="messageLog">
                <div class="text-center py-5 text-muted small" id="initialLoadingPlaceholder">
                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                    <div class="fw-500">Loading conversation history...</div>
                </div>
            </div>

            <!-- Message Input Area -->
            <div id="inputArea">
                <!-- Reply preview bar -->
                <div id="replyPreviewBar" class="chat-context-bar">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-700" id="replyPreviewName" style="font-size:.71rem; color:#0077b6;"></div>
                        <div class="text-muted text-truncate" id="replyPreviewText" style="font-size:.76rem;"></div>
                    </div>
                    <button type="button" id="btnCancelReply" class="chat-context-close" title="Cancel reply">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <!-- Edit preview bar -->
                <div id="editPreviewBar" class="chat-context-bar">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-700" id="editPreviewTitle" style="font-size:.71rem; color:#b45309;">
                            <i class="bi bi-pencil-square me-1"></i>Editing message
                        </div>
                        <div class="text-muted text-truncate" id="editPreviewText" style="font-size:.76rem;"></div>
                    </div>
                    <button type="button" id="btnCancelEdit" class="chat-context-close" title="Cancel edit (Esc)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <!-- Emoji Picker Popover -->
                <div id="emojiPickerPopup">
                    <div class="p-2 border-bottom bg-light">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="emojiSearchInput" class="form-control border-start-0" placeholder="Search emojis (smile, box, ok, fire)...">
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-1 emoji-nav-tabs">
                            <button type="button" class="emoji-tab-btn active" data-category="quick" title="Quick & Frequent">⭐</button>
                            <button type="button" class="emoji-tab-btn" data-category="smileys" title="Smileys & Emotion">😀</button>
                            <button type="button" class="emoji-tab-btn" data-category="gestures" title="People & Gestures">👍</button>
                            <button type="button" class="emoji-tab-btn" data-category="commerce" title="Shop & Commerce">📦</button>
                            <button type="button" class="emoji-tab-btn" data-category="symbols" title="Symbols & Alerts">💡</button>
                            <button type="button" class="btn btn-sm text-muted py-0 px-1 ms-1 border-0" id="btnCloseEmojiPicker" title="Close"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>

                    <div class="flex-grow-1 overflow-y-auto p-2" id="emojiGridContainer" style="user-select: none;"></div>

                    <div class="px-3 py-2 bg-light border-top text-muted d-flex justify-content-between align-items-center" style="font-size: .68rem;">
                        <span id="emojiPreviewLabel"><i class="bi bi-cursor me-1"></i>Click emoji to insert</span>
                        <span class="badge bg-secondary-subtle text-secondary">Esc to close</span>
                    </div>
                </div>

                <!-- Unified Composer Dock -->
                <div class="chat-composer-wrap">
                    <form id="messageForm" class="chat-composer-dock">
                        <input type="hidden" id="replyToId" value="">
                        <input type="hidden" id="editingMsgId" value="">
                        <button type="button" class="chat-emoji-trigger" id="btnEmojiToggle" title="Add Emoji">
                            <i class="bi bi-emoji-smile"></i>
                        </button>
                        <input type="text" id="messageInput" class="chat-message-input" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="chat-send-btn" id="btnSend">
                            <i class="bi bi-send-fill"></i>
                            <span class="send-label">Send</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- MODAL 1: PRODUCT INQUIRY -->
<div class="modal fade chat-modal" id="inquireModal" tabindex="-1" aria-labelledby="inquireModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="chat-modal-icon" style="background:rgba(0,119,182,.1); color:#0077b6;">
                        <i class="bi bi-box-seam-fill"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-700 mb-0" id="inquireModalLabel" style="font-size:.94rem;">Inquire Product Availability</h6>
                        <div class="text-muted" style="font-size:.72rem;">Share a live stock card in this conversation</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="productSearchInput" class="form-label fw-600" style="font-size:.8rem;">Search Product</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="productSearchInput" class="form-control" placeholder="Type product name, brand, or model...">
                    </div>
                    <div class="form-text" style="font-size:.73rem;">Type at least 2 characters to search the catalog.</div>
                </div>

                <!-- Product Search Results dropdown -->
                <div id="productResultsList" class="list-group shadow-sm border rounded-3 mb-3 d-none" style="max-height: 200px; overflow-y: auto;"></div>

                <!-- Selected Product Preview -->
                <div id="selectedProductPreview" class="d-none p-3 border rounded-3 mb-3 bg-light">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-white p-2 border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="bi bi-box-seam text-secondary fs-4" id="previewProdIcon"></i>
                            <img src="" id="previewProdImg" class="img-fluid rounded d-none" style="max-height:100%; object-fit:contain;">
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="mb-0 fw-700 text-truncate" id="previewProdName" style="font-size:.86rem;">Product Name</h6>
                            <small class="text-muted d-block text-truncate" id="previewProdMeta">Brand: - | Model: -</small>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" id="previewProdSelected" style="font-size:.66rem;">Selected</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="inquiryNote" class="form-label fw-600" style="font-size:.8rem;">Inquiry Note</label>
                    <input type="text" id="inquiryNote" class="form-control" value="Do we have this product in stock? Please verify availability and pricing." placeholder="e.g. Do we have this in stock?">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-accent px-3" id="btnSubmitInquiry" disabled>
                    <i class="bi bi-send-fill me-1"></i> Send Inquiry
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: SMS BROADCAST / DIRECT SMS -->
@if(auth()->user()->isOwner() || auth()->user()->isShopAdmin())
<div class="modal fade chat-modal" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="chat-modal-icon" style="background:rgba(79,70,229,.1); color:#4f46e5;">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-700 mb-0" id="smsModalLabel" style="font-size:.94rem;">Send SMS Notification</h6>
                        <div class="text-muted" style="font-size:.72rem;">Dispatch an SMS alert to staff members</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 px-3 mb-3 rounded-3 border-0" style="font-size:.77rem; background:#fffbeb; color:#92400e;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Gateway Mode:</strong>
                    @if(\App\Models\Setting::get('sms_enabled', '0') == '1' && !empty(\App\Models\Setting::get('sms_api_url', '')))
                        <span class="text-success fw-700">Live Gateway</span> (standard SMS rates apply).
                    @else
                        <span class="fw-700">Sandbox / Log Only</span> (recorded in `sms_logs`).
                    @endif
                </div>

                <div class="mb-3">
                    <label for="smsRecipientType" class="form-label fw-600" style="font-size:.8rem;">Recipients</label>
                    <select id="smsRecipientType" class="form-select">
                        <option value="all">Broadcast to All Active Users (With Phone Numbers)</option>
                        <option value="individual" id="smsOptIndividual">Specific Chat Participant</option>
                    </select>
                </div>

                <div id="smsIndividualTargetBlock" class="p-3 border rounded-3 bg-light mb-3 d-none">
                    <div class="fw-700 text-dark" id="smsTargetName" style="font-size:.86rem;">Name</div>
                    <div class="text-secondary small">
                        <i class="bi bi-telephone me-1"></i><span class="fw-600 text-dark" id="smsTargetPhone">-</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="smsMessageText" class="form-label fw-600" style="font-size:.8rem;">Message</label>
                    <textarea id="smsMessageText" class="form-control" rows="4" placeholder="Enter your SMS message here... (max 480 characters)" maxlength="480"></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:.74rem;"><span id="smsCountLabel">0</span> / 480 characters</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="smsPageCount" style="font-size:.7rem;">1 Page</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm text-white px-3 border-0" id="btnSubmitSMS" style="background:linear-gradient(135deg,#4f46e5,#4338ca); border-radius:8px; font-weight:600;">
                    <i class="bi bi-send-fill me-1"></i> Send SMS
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        let activeType = 'group'; // 'group' or 'individual'
        let activeId = 'group';   // 'group' or user_id
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
        const btnClearUserSearch = $('#btnClearUserSearch');
        const productSearchInput = $('#productSearchInput');
        const productResultsList = $('#productResultsList');
        const selectedProductPreview = $('#selectedProductPreview');
        const btnSubmitInquiry = $('#btnSubmitInquiry');
        const btnHeaderSms = $('#btnHeaderSms');

        function scrollToBottom() {
            if (messageLog[0]) {
                messageLog.scrollTop(messageLog[0].scrollHeight);
            }
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
                return timeStr;
            } else if (isYesterday) {
                return `Yesterday, ${timeStr}`;
            } else {
                const dateOptions = { month: 'short', day: 'numeric' };
                if (d.getFullYear() !== now.getFullYear()) {
                    dateOptions.year = 'numeric';
                }
                return `${d.toLocaleDateString([], dateOptions)}, ${timeStr}`;
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
            if ($(this).hasClass('multi-mode')) return;
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

            if (activeType === 'group') {
                activeChatName.text('# Refreshment Room');
                activeChatStatus.text('Team-wide group channel');
                activeChatIcon.html(`
                    <div class="chat-channel-icon" style="width:40px; height:40px; font-size:1.05rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                `);
                btnHeaderSms.addClass('d-none');
            } else {
                const name = $(this).data('name');
                const initials = $(this).data('initials') || name.charAt(0).toUpperCase();
                const role = $(this).data('role');
                const shop = $(this).data('shop');
                const avatar = $(this).data('avatar');
                const phone = $(this).data('phone');

                activeChatName.text(name);
                activeChatStatus.html(`${escapeHtml(role)} &middot; ${escapeHtml(shop)}`);

                if (avatar) {
                    activeChatIcon.html(`<img src="${avatar}" alt="${escapeHtml(name)}" class="chat-avatar-img" style="width:40px; height:40px;">`);
                } else {
                    activeChatIcon.html(`
                        <div class="chat-avatar-initials" style="width:40px; height:40px; font-size:.9rem;">
                            ${escapeHtml(initials)}
                        </div>
                    `);
                }

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

        // Touch-friendly toggle for message actions on mobile / touch devices
        messageLog.on('click', '.msg-container', function (e) {
            if ($(e.target).closest('button, a, img, .msg-reply-quote').length) return;
            const $row = $(this).closest('.msg-row');
            const wasOpen = $row.hasClass('actions-open');
            messageLog.find('.msg-row.actions-open').removeClass('actions-open');
            if (!wasOpen) $row.addClass('actions-open');
        });

        function loadConversation() {
            if (lastMessageId === 0) {
                lastRenderedDateKey = null;
                messageLog.html(`
                    <div class="text-center py-5 text-muted small" id="initialLoadingPlaceholder">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div class="fw-500">Loading conversation...</div>
                    </div>
                `);
            }

            if (pollingTimer) {
                clearTimeout(pollingTimer);
            }

            pollMessages();
        }

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
                                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:52px; height:52px; background:rgba(0,119,182,.08); color:#0077b6;">
                                        <i class="bi bi-chat-dots fs-4"></i>
                                    </div>
                                    <div class="fw-600 text-dark mb-1" style="font-size:.88rem;">No messages yet</div>
                                    <p class="small mb-0">Start the conversation by sending a message below.</p>
                                </div>
                            `);
                        }
                    }

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

                    if (response.updated_messages && response.updated_messages.length > 0) {
                        response.updated_messages.forEach(function (uMsg) {
                            updateMessageInDOM(uMsg, curUserId);
                        });
                    }

                    if (messages.length > 0) {
                        messageLog.find('#noMessagesMsg').remove();

                        messages.forEach(function (msg) {
                            if ($(`#chat-msg-${msg.id}`).length > 0) return;

                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                            }

                            const dateKey = getMessageDateGroupKey(msg.created_at);
                            if (dateKey && dateKey !== lastRenderedDateKey) {
                                lastRenderedDateKey = dateKey;
                                const dateLabel = formatGroupDateHeader(msg.created_at);
                                messageLog.append(`
                                    <div class="chat-date-divider" data-date-key="${dateKey}">
                                        <span class="chat-date-pill">${dateLabel}</span>
                                    </div>
                                `);
                            }

                            const isOut = msg.sender_id === curUserId;
                            const dateTimeStr = formatMessageDateTime(msg.created_at);
                            const fullTimestamp = new Date(msg.created_at).toLocaleString();
                            const editedTag = msg.is_edited ? `<span class="msg-edited-tag">&middot; edited</span>` : '';
                            const senderName = isOut ? 'You' : msg.sender.name;
                            const shopLabel = (msg.sender.shop ? msg.sender.shop.shop_name : 'Owner Store');
                            const senderHeaderHtml = (!isOut || activeType === 'group')
                                ? `<div class="msg-sender-name"><span>${escapeHtml(senderName)}</span><span class="msg-sender-shop">&middot; ${escapeHtml(shopLabel)}</span></div>`
                                : '';

                            let messageHtml = '';

                            if (msg.type === 'product_inquiry') {
                                const meta = msg.metadata;
                                if (meta) {
                                    let stockRows = '';
                                    if (msg.show_stocks || msg.show_own_stock_only) {
                                        if (meta.stocks && meta.stocks.length > 0) {
                                            meta.stocks.forEach(function(st) {
                                                const qtyBadge = st.quantity <= 0
                                                    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Out of Stock</span>'
                                                    : `<span class="badge bg-success-subtle text-success border border-success-subtle">${st.quantity} Available</span>`;
                                                const priceStr = st.price ? `<span class="ms-1 text-dark fw-600">TZS ${formatMoney(st.price)}</span>` : '';
                                                stockRows += `
                                                    <tr>
                                                        <td class="fw-500">${escapeHtml(st.shop_name)}</td>
                                                        <td class="text-end">${qtyBadge} ${priceStr}</td>
                                                    </tr>
                                                `;
                                            });
                                            if (msg.show_own_stock_only) {
                                                stockRows += `
                                                    <tr>
                                                        <td colspan="2" class="pt-2 text-center text-muted" style="font-size:.68rem;"><i class="bi bi-hourglass-split text-warning me-1"></i>Other stores' availability hidden until recipient replies</td>
                                                    </tr>
                                                `;
                                            }
                                        } else {
                                            stockRows = '<tr><td colspan="2" class="text-muted text-center py-2">No stock entries found</td></tr>';
                                            if (msg.show_own_stock_only) {
                                                stockRows += `
                                                    <tr>
                                                        <td colspan="2" class="pt-2 text-center text-muted" style="font-size:.68rem;"><i class="bi bi-hourglass-split text-warning me-1"></i>Other stores' availability hidden until recipient replies</td>
                                                    </tr>
                                                `;
                                            }
                                        }
                                    } else {
                                        stockRows = '<tr><td colspan="2" class="py-2 text-center text-muted" style="font-size:.71rem;"><i class="bi bi-hourglass-split text-warning me-1"></i>Stock availability hidden until recipient replies</td></tr>';
                                    }

                                    let imgHtml = '';
                                    if (meta.image_url) {
                                        imgHtml = `<img src="${meta.image_url}" alt="${escapeHtml(meta.item_name)}" class="rounded border img-lightbox flex-shrink-0" style="width:58px; height:58px; object-fit:contain; background:#fff; padding:2px; cursor:zoom-in;" onclick="openLightbox('${meta.image_url}', '${escapeHtml(meta.item_name)}')">`;
                                    }

                                    const replyQuoteInq = msg.reply_to ? `
                                        <div class="msg-reply-quote" data-target-id="${msg.reply_to.id}">
                                            <span class="quote-name">${escapeHtml(msg.reply_to.sender ? (msg.reply_to.sender.id === curUserId ? 'You' : msg.reply_to.sender.name) : 'Unknown')}</span>
                                            <span class="quote-text">${escapeHtml(msg.reply_to.message)}</span>
                                        </div>` : '';

                                    const rawNote = meta.note || '';
                                    const actionsInqHtml = isOut ? `
                                        <div class="msg-actions">
                                            <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml('📦 ' + (meta ? meta.item_name : ''))}"><i class="bi bi-reply-fill"></i></button>
                                            <button class="msg-action-btn msg-edit-btn" title="Edit inquiry note" data-msg-id="${msg.id}" data-msg-type="inquiry" data-msg-text="${escapeHtml(rawNote)}"><i class="bi bi-pencil-fill"></i></button>
                                            <button class="msg-action-btn msg-delete-btn" title="Delete inquiry" data-msg-id="${msg.id}"><i class="bi bi-trash3-fill"></i></button>
                                        </div>
                                    ` : `
                                        <div class="msg-actions">
                                            <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml('📦 ' + (meta ? meta.item_name : ''))}"><i class="bi bi-reply-fill"></i></button>
                                        </div>
                                    `;

                                    messageHtml = `
                                        <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                            <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                                ${actionsInqHtml}
                                                <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                    ${senderHeaderHtml}
                                                    ${replyQuoteInq}
                                                    <div class="d-flex align-items-center gap-1 fw-700" style="font-size:.73rem; opacity:.92;">
                                                        <i class="bi bi-box-seam-fill"></i> Product Inquiry
                                                    </div>
                                                    <div class="prod-card">
                                                        <div class="prod-card-head">
                                                            ${imgHtml}
                                                            <div class="min-w-0 flex-grow-1">
                                                                <div class="fw-700 text-dark" style="font-size:.82rem;">${escapeHtml(meta.item_name)}</div>
                                                                <div class="text-muted" style="font-size:.71rem;">Brand: ${escapeHtml(meta.brand || '-')} &middot; Model: ${escapeHtml(meta.model || '-')}</div>
                                                            </div>
                                                        </div>
                                                        <div class="prod-card-note">
                                                            <strong>Note:</strong> <span class="inquiry-note-text">${escapeHtml(meta.note || '')}</span>
                                                        </div>
                                                        <div class="fw-700 text-uppercase text-muted" style="font-size:.63rem; letter-spacing:.04em;">Stock Availability</div>
                                                        <table class="stocks-table">
                                                            <tbody>${stockRows}</tbody>
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

                                const actionsHtml = isOut ? `
                                    <div class="msg-actions">
                                        <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-reply-fill"></i></button>
                                        <button class="msg-action-btn msg-edit-btn" title="Edit message" data-msg-id="${msg.id}" data-msg-type="text" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-pencil-fill"></i></button>
                                        <button class="msg-action-btn msg-delete-btn" title="Delete message" data-msg-id="${msg.id}"><i class="bi bi-trash3-fill"></i></button>
                                    </div>
                                ` : `
                                    <div class="msg-actions">
                                        <button class="msg-action-btn msg-reply-btn" title="Reply" data-msg-id="${msg.id}" data-msg-sender="${escapeHtml(senderName)}" data-msg-text="${escapeHtml(msg.message)}"><i class="bi bi-reply-fill"></i></button>
                                    </div>
                                `;

                                messageHtml = `
                                    <div class="msg-wrapper" id="chat-msg-${msg.id}">
                                        <div class="msg-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
                                            ${actionsHtml}
                                            <div class="msg-container ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                                ${senderHeaderHtml}
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

                    pollingTimer = setTimeout(pollMessages, 3500);
                },
                error: function (xhr) {
                    console.error('Failed to poll chat messages', xhr);
                    pollingTimer = setTimeout(pollMessages, 8000);
                }
            });
        }

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
            const editedTag = `<span class="msg-edited-tag">&middot; edited</span>`;
            $el.find('.msg-time').attr('title', fullTimestamp).html(`<span>${dateTimeStr}</span>${editedTag}`);

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
                $('#btnSend').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span><span class="send-label">Saving...</span>');
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
                        $('#btnSend').prop('disabled', false).html('<i class="bi bi-check2-circle"></i><span class="send-label">Save</span>');
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: xhr.responseJSON?.message || 'Unable to update message. Check connection.',
                            confirmButtonColor: '#0077b6'
                        });
                    }
                });
                return;
            }

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
                success: function () {
                    if (pollingTimer) clearTimeout(pollingTimer);
                    pollMessages();
                },
                error: function (xhr) {
                    console.error('Failed to send message', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Sending Failed',
                        text: 'Unable to deliver your message. Check your connection.',
                        confirmButtonColor: '#0077b6'
                    });
                }
            });
        });

        // ─── EDIT FEATURE ────────────────────────────────────────────────────────
        const $editBar  = $('#editPreviewBar');
        const $editId   = $('#editingMsgId');
        const $editText = $('#editPreviewText');

        function cancelEdit() {
            $editId.val('');
            $editBar.hide();
            $editText.text('');
            messageInput.attr('placeholder', 'Type a message...');
            $('#btnSend').removeClass('is-editing').html('<i class="bi bi-send-fill"></i><span class="send-label">Send</span>');
        }

        function startEditMessage(msgId, text, type) {
            cancelReply();
            $editId.val(msgId);
            $editText.text(text);
            $editBar.css('display', 'flex');
            messageInput.val(text).focus();
            messageInput.attr('placeholder', type === 'inquiry' ? 'Edit inquiry note...' : 'Edit your message...');
            $('#btnSend').addClass('is-editing').html('<i class="bi bi-check2-circle"></i><span class="send-label">Save</span>');
        }

        $('#btnCancelEdit').on('click', cancelEdit);

        messageLog.on('click', '.msg-edit-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            startEditMessage($(this).data('msg-id'), $(this).data('msg-text'), $(this).data('msg-type'));
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
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-trash3-fill me-1"></i> Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('/chats/messages') }}/${msgId}`,
                        type: 'DELETE',
                        data: { _token: "{{ csrf_token() }}" },
                        success: function (res) {
                            if (res.success) {
                                if ($('#editingMsgId').val() == msgId) cancelEdit();
                                const $msgEl = $(`#chat-msg-${msgId}`);
                                if ($msgEl.length) {
                                    $msgEl.fadeOut(250, function () { $(this).remove(); });
                                }
                                showToast('Message deleted successfully', 'success');
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: xhr.responseJSON?.message || 'Unable to delete message. Check connection.',
                                confirmButtonColor: '#0077b6'
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
            { emoji: '😜', name: 'winking face with tongue crazy joke fun', cat: 'smileys' },
            { emoji: '😎', name: 'smiling face with sunglasses cool boss confident smart', cat: 'smileys' },
            { emoji: '🤩', name: 'star struck excited amazed wow great', cat: 'smileys' },
            { emoji: '🥳', name: 'partying face celebration birthday congrats party', cat: 'smileys' },
            { emoji: '🤔', name: 'thinking face wondering curious hmm puzzle', cat: 'smileys' },
            { emoji: '🫡', name: 'saluting face respect yes sir copy that roger', cat: 'smileys' },
            { emoji: '😐', name: 'neutral face poker straight neutral plain', cat: 'smileys' },
            { emoji: '😬', name: 'grimacing face nervous awkward tense eek', cat: 'smileys' },
            { emoji: '😮‍💨', name: 'face exhaling sigh relief tired phew', cat: 'smileys' },
            { emoji: '😔', name: 'pensive face sad regretful sorry down', cat: 'smileys' },
            { emoji: '😴', name: 'sleeping face zzz night sleep tired', cat: 'smileys' },
            { emoji: '🤯', name: 'exploding head mind blown shocked unbelievable', cat: 'smileys' },
            { emoji: '🤓', name: 'nerd face smart geek study tech glasses', cat: 'smileys' },
            { emoji: '🧐', name: 'face with monocle examining analyzing inspect curious', cat: 'smileys' },
            { emoji: '😕', name: 'confused face unsure puzzled what', cat: 'smileys' },
            { emoji: '😟', name: 'worried face anxious concerned nervous', cat: 'smileys' },
            { emoji: '😮', name: 'face with open mouth surprised wow oh gasp', cat: 'smileys' },
            { emoji: '😢', name: 'crying face sad tear sorrow grief', cat: 'smileys' },
            { emoji: '😭', name: 'loudly crying face bawling heartbroken sob tears', cat: 'smileys' },
            { emoji: '😤', name: 'face with steam from nose determined triumph proud', cat: 'smileys' },
            { emoji: '😡', name: 'pouting face angry mad furious red rage', cat: 'smileys' },

            // Gestures & People
            { emoji: '👍', name: 'thumbs up like good yes approve correct agreed', cat: 'gestures' },
            { emoji: '👎', name: 'thumbs down dislike bad no disapprove decline', cat: 'gestures' },
            { emoji: '👏', name: 'clapping hands applause kudos bravo well done cheer', cat: 'gestures' },
            { emoji: '🙌', name: 'raising hands celebration praise hooray yay', cat: 'gestures' },
            { emoji: '🤝', name: 'handshake deal agreement partner client trust shake', cat: 'gestures' },
            { emoji: '🙏', name: 'folded hands pray please thank you thanks', cat: 'gestures' },
            { emoji: '✍️', name: 'writing hand note signing record document paper', cat: 'gestures' },
            { emoji: '💪', name: 'flexed biceps strong strength power work solid', cat: 'gestures' },
            { emoji: '👀', name: 'eyes look see view watching attention glance', cat: 'gestures' },
            { emoji: '👋', name: 'waving hand hello hi goodbye bye wave greet', cat: 'gestures' },
            { emoji: '✋', name: 'raised hand stop wait high five', cat: 'gestures' },
            { emoji: '👌', name: 'ok hand perfect fine nice good accurate', cat: 'gestures' },
            { emoji: '✌️', name: 'victory hand peace two win v sign', cat: 'gestures' },
            { emoji: '🤞', name: 'crossed fingers good luck hope wishing', cat: 'gestures' },
            { emoji: '🤙', name: 'call me hand phone call', cat: 'gestures' },
            { emoji: '👈', name: 'backhand index pointing left see this look left', cat: 'gestures' },
            { emoji: '👉', name: 'backhand index pointing right look there check point right', cat: 'gestures' },
            { emoji: '👆', name: 'backhand index pointing up above read previous up', cat: 'gestures' },
            { emoji: '👇', name: 'backhand index pointing down below see message note down', cat: 'gestures' },
            { emoji: '☝️', name: 'index pointing up one point first listen remember', cat: 'gestures' },

            // Commerce & Shop
            { emoji: '📦', name: 'package box product stock parcel delivery item carton goods', cat: 'commerce' },
            { emoji: '🛍️', name: 'shopping bags retail store customer purchase buy items shopping', cat: 'commerce' },
            { emoji: '🛒', name: 'shopping cart supermarket checkout buy goods items order cart', cat: 'commerce' },
            { emoji: '💰', name: 'money bag shillings cash price revenue payment income paid', cat: 'commerce' },
            { emoji: '💵', name: 'dollar banknote cash money paper bill shillings note', cat: 'commerce' },
            { emoji: '💳', name: 'credit card payment card pos swipe cashless', cat: 'commerce' },
            { emoji: '🧾', name: 'receipt bill invoice paper account transaction report slip', cat: 'commerce' },
            { emoji: '🏷️', name: 'label tag price item model category brand discount sale promo', cat: 'commerce' },
            { emoji: '📊', name: 'bar chart stats analytics sales report growth finance data', cat: 'commerce' },
            { emoji: '📈', name: 'chart increasing upward trend sales profit growth rise up', cat: 'commerce' },
            { emoji: '📉', name: 'chart decreasing downward loss drop low reduce down', cat: 'commerce' },
            { emoji: '📋', name: 'clipboard list inventory checklist audit verification task', cat: 'commerce' },
            { emoji: '📌', name: 'pushpin pin important notice memo fix location pinned', cat: 'commerce' },
            { emoji: '📍', name: 'round pushpin location map pin shop branch store address', cat: 'commerce' },
            { emoji: '🏢', name: 'office building company headquarters owner main store', cat: 'commerce' },
            { emoji: '🏬', name: 'department store shop outlet marketplace branch', cat: 'commerce' },
            { emoji: '💼', name: 'briefcase work job business executive office manager', cat: 'commerce' },
            { emoji: '📅', name: 'calendar date today day schedule handover month', cat: 'commerce' },
            { emoji: '⏰', name: 'alarm clock time deadline alert reminder urgent', cat: 'commerce' },
            { emoji: '⏳', name: 'hourglass pending waiting queue processing delay', cat: 'commerce' },
            { emoji: '🔒', name: 'locked security private safe authorized protected', cat: 'commerce' },
            { emoji: '🔍', name: 'magnifying glass search inquire verify find inspect', cat: 'commerce' },
            { emoji: '📞', name: 'telephone receiver call phone contact customer client', cat: 'commerce' },
            { emoji: '📱', name: 'mobile phone smartphone sms message whatsapp call', cat: 'commerce' },
            { emoji: '💻', name: 'laptop computer pc pos system tech screen', cat: 'commerce' },
            { emoji: '🖥️', name: 'desktop computer monitor display office screen pc', cat: 'commerce' },
            { emoji: '🖨️', name: 'printer print receipt document report printout', cat: 'commerce' },
            { emoji: '🚚', name: 'delivery truck transfer stock transport moving logistics', cat: 'commerce' },

            // Symbols & Alerts
            { emoji: '✅', name: 'check mark button verified approved correct yes success ok', cat: 'symbols' },
            { emoji: '❌', name: 'cross mark cancel rejected no failed error out of stock', cat: 'symbols' },
            { emoji: '⚠️', name: 'warning alert caution attention low stock notice hazard', cat: 'symbols' },
            { emoji: '🚨', name: 'police car light emergency alert siren critical urgent danger', cat: 'symbols' },
            { emoji: '⛔', name: 'no entry stop forbidden not allowed prohibited', cat: 'symbols' },
            { emoji: '💯', name: 'hundred points score perfect 100 accurate full total', cat: 'symbols' },
            { emoji: '🔥', name: 'fire hot fast popular best seller burning', cat: 'symbols' },
            { emoji: '⭐', name: 'star rating favorite top special premium high quality', cat: 'symbols' },
            { emoji: '✨', name: 'sparkles new fresh shiny clean magic special bonus', cat: 'symbols' },
            { emoji: '⚡', name: 'high voltage quick instant fast electric power flash', cat: 'symbols' },
            { emoji: '🔔', name: 'bell notification alert chime ring sound notify', cat: 'symbols' },
            { emoji: '📢', name: 'loudspeaker announcement broadcast sms public notice alert', cat: 'symbols' },
            { emoji: '🎯', name: 'bullseye direct target goal achievement quota', cat: 'symbols' },
            { emoji: '🚀', name: 'rocket fast quick launch boost speed', cat: 'symbols' },
            { emoji: '🏆', name: 'trophy champion win winner award prize best victory', cat: 'symbols' },
            { emoji: '❤️', name: 'red heart love passion like favorite adore', cat: 'symbols' },
            { emoji: '💙', name: 'blue heart brand loyalty peace trust blue', cat: 'symbols' },
            { emoji: '💚', name: 'green heart money profit success go live green', cat: 'symbols' },
            { emoji: '💬', name: 'speech balloon chat talk message discuss question comment', cat: 'symbols' },
            { emoji: '🎉', name: 'party popper congrats celebration success happy joy', cat: 'symbols' }
        ];

        const $emojiPopup   = $('#emojiPickerPopup');
        const $emojiGrid    = $('#emojiGridContainer');
        const $emojiSearch  = $('#emojiSearchInput');
        const $emojiPreview = $('#emojiPreviewLabel');
        let currentEmojiCategory = 'quick';

        function renderEmojiGrid(category = 'quick', searchKeyword = '') {
            const keyword = $.trim(searchKeyword).toLowerCase();
            const filtered = keyword
                ? emojiData.filter(item => item.name.toLowerCase().includes(keyword) || item.emoji.includes(keyword))
                : emojiData.filter(item => item.cat === category);

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
            }, 1600);
        }

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

        $(document).on('click', '.emoji-tab-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('.emoji-tab-btn').removeClass('active');
            $(this).addClass('active');
            currentEmojiCategory = $(this).data('category');
            $emojiSearch.val('');
            renderEmojiGrid(currentEmojiCategory);
        });

        $emojiSearch.on('input', function () {
            const q = $(this).val();
            if (q) {
                $('.emoji-tab-btn').removeClass('active');
            } else {
                $(`.emoji-tab-btn[data-category="${currentEmojiCategory}"]`).addClass('active');
            }
            renderEmojiGrid(currentEmojiCategory, q);
        });

        $(document).on('click', '.emoji-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            insertEmojiAtCursor($(this).data('emoji'));
        });

        $('#btnCloseEmojiPicker').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $emojiPopup.hide();
            messageInput.focus();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#emojiPickerPopup, #btnEmojiToggle').length) {
                if ($emojiPopup.is(':visible')) $emojiPopup.hide();
            }
        });

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
            messageInput.attr('placeholder', 'Type a message...');
        }

        function showReplyBar(msgId, senderLabel, previewText) {
            cancelEdit();
            $replyId.val(msgId);
            $replyName.html('<i class="bi bi-reply-fill me-1"></i>Replying to <strong>' + escapeHtml(senderLabel) + '</strong>');
            $replyText.text(previewText);
            $replyBar.css('display', 'flex');
            messageInput.attr('placeholder', 'Type your reply...').focus();
        }

        $replyBar.hide();
        $('#btnCancelReply').on('click', cancelReply);

        messageLog.on('click', '.msg-reply-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showReplyBar($(this).data('msg-id'), $(this).data('msg-sender'), $(this).data('msg-text'));
        });

        messageLog.on('click', '.msg-reply-quote', function (e) {
            e.stopPropagation();
            const $target = $('#chat-msg-' + $(this).data('target-id'));
            if ($target.length) {
                const logEl = messageLog[0];
                const targetTop = $target[0].offsetTop - logEl.offsetTop - 80;
                logEl.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
                $target.addClass('msg-flash');
                setTimeout(function () { $target.removeClass('msg-flash'); }, 1500);
            }
        });

        $('#btnBackToSidebar').on('click', function () {
            $('#chatAppRow').removeClass('mobile-view-chat').addClass('mobile-view-sidebar');
        });

        // ─── SIDEBAR SEARCH ──────────────────────────────────────────────────────
        function filterContacts() {
            const val = userSearch.val().toLowerCase().trim();
            btnClearUserSearch.css('display', val ? 'inline-flex' : 'none');

            let visibleCount = 0;
            $('#usersListGroup .user-item').each(function () {
                const name = ($(this).data('name') || '').toString().toLowerCase();
                const shop = ($(this).data('shop') || '').toString().toLowerCase();
                const role = ($(this).data('role') || '').toString().toLowerCase();

                if (!val || name.includes(val) || shop.includes(val) || role.includes(val)) {
                    $(this).removeClass('d-none');
                    visibleCount++;
                } else {
                    $(this).addClass('d-none');
                }
            });

            $('#noContactsFound').toggleClass('d-none', visibleCount > 0);
        }

        userSearch.on('input', filterContacts);
        btnClearUserSearch.on('click', function () {
            userSearch.val('').focus();
            filterContacts();
        });

        // ─── PRODUCT INQUIRY MODAL ───────────────────────────────────────────────
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
                            <a href="#" class="list-group-item list-group-item-action px-3 py-2 select-prod-item"
                               data-id="${item.id}"
                               data-name="${escapeHtml(item.item_name)}"
                               data-brand="${escapeHtml(item.brand || '-')}"
                               data-model="${escapeHtml(item.model || '-')}"
                               data-spec="${escapeHtml(item.specification || '-')}"
                               data-img="${item.image_path ? '/media/' + item.image_path : ''}">
                               <div class="fw-600 text-dark" style="font-size:.82rem;">${escapeHtml(item.item_name)}</div>
                               <div class="text-secondary" style="font-size:.71rem;">Brand: ${escapeHtml(item.brand || '-')} &middot; Model: ${escapeHtml(item.model || '-')}</div>
                            </a>
                        `;
                    });

                    productResultsList.removeClass('d-none').html(resultsHtml);
                }
            });
        });

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

            productResultsList.addClass('d-none').html('');
            productSearchInput.val(selectedProduct.name);

            $('#previewProdName').text(selectedProduct.name);
            $('#previewProdMeta').html(`Brand: <strong>${escapeHtml(selectedProduct.brand)}</strong> &middot; Model: <strong>${escapeHtml(selectedProduct.model)}</strong>`);

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
                success: function () {
                    selectedProduct = null;
                    productSearchInput.val('');
                    selectedProductPreview.addClass('d-none');
                    $('#inquiryNote').val('Do we have this product in stock? Please verify availability and pricing.');
                    $('#btnSubmitInquiry').prop('disabled', true).html('<i class="bi bi-send-fill me-1"></i> Send Inquiry');
                    modal.hide();

                    if (pollingTimer) clearTimeout(pollingTimer);
                    pollMessages();
                },
                error: function () {
                    $('#btnSubmitInquiry').prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send Inquiry');
                    Swal.fire({
                        icon: 'error',
                        title: 'Inquiry Failed',
                        text: 'An error occurred while sending the product inquiry card.',
                        confirmButtonColor: '#0077b6'
                    });
                }
            });
        });

        // ─── SMS MODAL ───────────────────────────────────────────────────────────
        $('#smsModal').on('show.bs.modal', function () {
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

        $('#smsRecipientType').on('change', function () {
            $('#smsIndividualTargetBlock').toggleClass('d-none', $(this).val() !== 'individual');
        });

        $('#btnHeaderSms').on('click', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('smsModal')).show();
        });

        $('#smsMessageText').on('input', function () {
            const chars = $(this).val().length;
            $('#smsCountLabel').text(chars);
            const pages = Math.ceil(chars / 160) || 1;
            $('#smsPageCount').text(`${pages} Page` + (pages > 1 ? 's' : ''));
        });

        $('#btnSubmitSMS').on('click', function () {
            const msg = $.trim($('#smsMessageText').val());
            if (!msg) {
                Swal.fire({
                    icon: 'warning',
                    text: 'Please write an SMS message body first.',
                    confirmButtonColor: '#0077b6'
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
                    $('#btnSubmitSMS').prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send SMS');
                    modal.hide();
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'SMS Dispatched' : 'SMS Failed',
                        text: response.message,
                        confirmButtonColor: '#0077b6'
                    });
                },
                error: function () {
                    $('#btnSubmitSMS').prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Send SMS');
                    Swal.fire({
                        icon: 'error',
                        title: 'SMS Failed',
                        text: 'An error occurred while executing the SMS API gateway request.',
                        confirmButtonColor: '#0077b6'
                    });
                }
            });
        });

        // ─── HELPERS & SIDEBAR SORT ──────────────────────────────────────────────
        function formatMoney(amount) {
            return parseFloat(amount).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text)
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

                if (infoA.count > 0 && infoB.count === 0) return -1;
                if (infoA.count === 0 && infoB.count > 0) return 1;

                if (infoA.count > 0 && infoB.count > 0) {
                    return (infoA.oldest_time || 0) - (infoB.oldest_time || 0);
                }

                const shopA = ($(a).data('shop') || '').toString().toLowerCase();
                const shopB = ($(b).data('shop') || '').toString().toLowerCase();
                if (shopA !== shopB) {
                    return shopA.localeCompare(shopB);
                }

                const nameA = ($(a).data('name') || '').toString().toLowerCase();
                const nameB = ($(b).data('name') || '').toString().toLowerCase();
                return nameA.localeCompare(nameB);
            });

            $.each(items, function(i, li) {
                listGroup.append(li);
            });
        };

        // ─── MULTI-SEND FEATURE ──────────────────────────────────────────────────
        let multiSendActive = false;

        function updateMultiSendUI() {
            const checked = $('.multi-send-checkbox:checked');
            const count = checked.length;
            $('#multiSendCount').text(count + ' selected');
            $('#btnSendBulk').prop('disabled', count === 0 || $('#multiSendInput').val().trim() === '');

            $('#usersListGroup .user-item').each(function () {
                $(this).toggleClass('is-checked', $(this).find('.multi-send-checkbox').is(':checked'));
            });
        }

        $('#btnToggleMultiSend').on('click', function (e) {
            e.stopPropagation();
            multiSendActive = !multiSendActive;

            if (multiSendActive) {
                $(this).addClass('is-active').html('<i class="bi bi-x-lg"></i><span>Cancel</span>');
                $('.multi-send-checkbox').removeClass('d-none');
                $('#multiSendPanel').removeClass('d-none');
                $('#usersListGroup .user-item').addClass('multi-mode');
            } else {
                $(this).removeClass('is-active').html('<i class="bi bi-check2-square"></i><span>Multi-Send</span>');
                $('.multi-send-checkbox').addClass('d-none').prop('checked', false);
                $('#multiSendPanel').addClass('d-none');
                $('#multiSendInput').val('');
                $('#usersListGroup .user-item').removeClass('multi-mode is-checked');
                updateMultiSendUI();
            }
        });

        $(document).on('change', '.multi-send-checkbox', updateMultiSendUI);
        $('#multiSendInput').on('input', updateMultiSendUI);

        $('#usersListGroup').on('click', '.user-item.multi-mode', function (e) {
            e.stopPropagation();
            if ($(e.target).is('.multi-send-checkbox')) return;
            e.preventDefault();
            const cb = $(this).find('.multi-send-checkbox');
            cb.prop('checked', !cb.prop('checked'));
            updateMultiSendUI();
        });

        $('#btnClearMultiSelect').on('click', function () {
            $('.multi-send-checkbox').prop('checked', false);
            updateMultiSendUI();
        });

        $('#btnSendBulk').on('click', function () {
            const message = $('#multiSendInput').val().trim();
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
                        const names = $('.multi-send-checkbox:checked').closest('.user-item').map(function () {
                            return $(this).data('name');
                        }).get().join(', ');
                        showToast(`Message sent to ${res.sent} recipient${res.sent > 1 ? 's' : ''}: ${escapeHtml(names)}`);

                        $('#multiSendInput').val('');
                        $('.multi-send-checkbox').prop('checked', false);
                        updateMultiSendUI();
                    }
                },
                error: function () {
                    showToast('Failed to send bulk message. Please try again.', 'danger');
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i>');
                }
            });
        });

        function showToast(message, type = 'success') {
            const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            const $toast = $(`
                <div class="toast align-items-center text-white ${bgClass} border-0 shadow" role="alert" style="position:fixed;bottom:24px;right:24px;z-index:9999;min-width:280px;border-radius:10px;font-size:0.82rem;">
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
    });
</script>
@endpush
