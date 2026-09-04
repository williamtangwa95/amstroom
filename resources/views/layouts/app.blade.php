<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $appBranding['name'] ?? 'AMSTROOM' }}</title>
    <meta name="description" content="{{ $appBranding['name'] ?? 'AMSTROOM' }} — {{ $appBranding['slogan'] ?? 'Computer Shop Management System' }}">

    <!-- Dynamic Favicon -->
    @if(!empty($appBranding['logo']))
    <link rel="icon" href="{{ $appBranding['logo'] }}">
    <link rel="apple-touch-icon" href="{{ $appBranding['logo'] }}">
    @else
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%230088cc'/><text x='50%' y='68%' font-size='55' text-anchor='middle' fill='%23ffffff' font-family='Arial, sans-serif' font-weight='900'>A</text></svg>">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: linear-gradient(180deg, #0088cc 0%, #005f9e 100%);
            --sidebar-solid: #0088cc;
            --sidebar-border: rgba(255, 255, 255, 0.15);
            --sidebar-text: rgba(255, 255, 255, 0.85);
            --sidebar-active: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.12);
            --accent: #0088cc;
            --accent-gold: #ffb700;
            --accent-blue: #0284c7;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-purple: #8b5cf6;
            --accent-red: #ef4444;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --body-bg: #f4f6f9;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ── Premium Stat Cards Hover Effects ── */
        .premium-stat-card {
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .premium-stat-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform .3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.05);
        }

        /* Sleek sidebar scrollbar */
        #sidebar::-webkit-scrollbar {
            width: 5px;
        }

        #sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 10px;
        }

        #sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45);
        }

        .sidebar-brand {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 1.25rem;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: .75rem;
            background: var(--sidebar-solid);
        }

        .sidebar-brand .brand-icon {
            width: 42px;
            height: 42px;
            background: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #0088cc;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .sidebar-brand .brand-text {
            line-height: 1.2;
        }

        .sidebar-brand .brand-name {
            font-size: 1rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -.01em;
        }

        .sidebar-brand .brand-sub {
            font-size: .7rem;
            color: #ffb700;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .sidebar-nav {
            padding: .75rem 0 2rem 0;
            flex: 1;
        }

        .nav-section-label {
            padding: .5rem 1.25rem .25rem;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, 0.7);
            margin-top: .5rem;
        }

        .nav-item-custom {
            margin: 2px .65rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .55rem .85rem;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: .84rem;
            font-weight: 500;
            transition: all .15s ease;
        }

        .nav-link-custom:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .nav-link-custom.active {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-link-custom i {
            font-size: .95rem;
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--accent-gold);
            color: #000;
            font-size: .6rem;
            padding: .15rem .4rem;
            border-radius: 10px;
            font-weight: 800;
        }

        /* Collapsible navigation items style */
        .nav-link-custom[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }

        .nav-link-custom[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        /* ── Main Content ── */
        #main-content {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top Navbar ── */
        #top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .page-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .user-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .35rem .75rem;
            font-size: .78rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .role-pill {
            display: inline-block;
            padding: .15rem .5rem;
            border-radius: 20px;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .role-owner {
            background: rgba(255, 183, 0, 0.15);
            color: var(--accent-gold);
            border: 1px solid rgba(255, 183, 0, 0.3);
        }

        .role-shop_admin {
            background: rgba(0, 136, 204, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(0, 136, 204, 0.3);
        }

        .role-seller {
            background: rgba(6, 182, 212, 0.15);
            color: #22d3ee;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        /* ── Content Area ── */
        .content-area {
            padding: 1.5rem;
            flex: 1;
        }

        /* ── Cards ── */
        /* ── Cards ── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 1.25rem;
            font-weight: 700;
            font-size: .9rem;
            color: var(--text-primary);
        }

        /* ── Stat Cards ── */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: .75rem;
        }

        .stat-card .stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: .25rem;
        }

        .stat-card .stat-label {
            font-size: .75rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            opacity: .05;
        }

        /* ── Tables ── */
        .table {
            color: var(--text-primary);
            font-size: .83rem;
        }

        .table th {
            color: #475569;
            font-weight: 700;
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-color: var(--card-border);
            background: #f8fafc;
        }

        .table td {
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background: rgba(0, 136, 204, 0.04);
        }

        /* ── Forms ── */
        .form-control,
        .form-select {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 8px;
            font-size: .85rem;
        }

        .form-control:focus,
        .form-select:focus {
            background: var(--input-bg);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: .35rem;
        }

        /* ── Buttons ── */
        .btn-accent {
            background: linear-gradient(135deg, #0088cc, #006699);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .83rem;
            padding: .4rem .85rem;
            transition: all .15s;
            box-shadow: 0 4px 12px rgba(0, 136, 204, 0.25);
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, #0077b6, #004d73);
            color: #fff;
            transform: translateY(-1px);
        }

        /* Fix DataTables buttons stripping inner padding via generated <span> */
        .dt-buttons .btn {
            padding: .4rem .85rem !important;
        }

        .dt-buttons .btn.btn-sm {
            padding: .3rem .7rem !important;
        }

        .dt-buttons .btn span {
            pointer-events: none;
        }


        .btn-gold {
            background: linear-gradient(135deg, #ffb700, #d97706);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: .83rem;
            transition: all .15s;
            box-shadow: 0 4px 12px rgba(255, 183, 0, 0.25);
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, #e5a300, #b45309);
            color: #000;
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            border: 1px solid var(--input-border);
            color: #475569;
            background: #ffffff;
            border-radius: 8px;
            font-size: .83rem;
        }

        .btn-outline-custom:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: #f8fafc;
        }

        /* ── Badges ── */
        .badge-pending {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .badge-approved {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        .badge-active {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-inactive {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .65rem;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* ── Alerts ── */
        .alert {
            border-radius: 10px;
            border: none;
            font-size: .85rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .alert-warning {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .alert-info {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* ── DataTables & Table Styling ── */
        .dataTables_wrapper {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .dataTables_wrapper>.row:first-child,
        .dataTables_wrapper>div.dt-row+div.row,
        .dataTables_wrapper>.row:last-child {
            padding: 0.75rem 1.25rem;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 0.5rem 1.25rem;
            color: #64748b !important;
            font-size: .82rem;
        }

        .dataTables_wrapper .dataTables_filter {
            text-align: right;
        }

        .dataTables_wrapper .dataTables_filter input {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            border-radius: 6px;
            padding: .3rem .6rem;
            font-size: .83rem;
            margin-left: .5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            border-radius: 6px;
            padding: .3rem .5rem !important;
            font-size: .83rem;
            margin: 0 .4rem;
            display: inline-block !important;
            width: auto !important;
            min-width: 65px !important;
            background-image: none !important;
        }

        .dataTables_wrapper .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .dataTables_wrapper .paginate_button {
            border-radius: 6px !important;
            color: #64748b !important;
            padding: .3rem .7rem !important;
            margin-left: 3px !important;
            font-size: .82rem;
        }

        .dataTables_wrapper .paginate_button.current {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
            color: #fff !important;
        }

        .dataTables_wrapper table.dataTable {
            margin-top: 0.5rem !important;
            margin-bottom: 0.5rem !important;
            border-top: 1px solid var(--card-border) !important;
            border-bottom: 1px solid var(--card-border) !important;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            background: none;
            padding: 0;
            font-size: .78rem;
            margin: 0;
        }

        .breadcrumb-item {
            color: #64748b;
        }

        .breadcrumb-item a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-item a:hover {
            color: #0369a1;
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: #0f172a;
            font-weight: 600;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            color: #94a3b8;
        }

        /* ── Low Stock Alert ── */
        .low-stock-row {
            background: #fff5f5 !important;
        }

        /* ── Print ── */
        @media print {

            #sidebar,
            #top-navbar {
                display: none !important;
            }

            #main-content {
                margin-left: 0;
            }
        }

        /* ── Mobile Sidebar Close Button & Backdrop ── */
        .btn-close-sidebar {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: background .15s ease;
            flex-shrink: 0;
        }

        .btn-close-sidebar:hover {
            background: rgba(255, 255, 255, 0.3);
            color: #ffffff;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 999;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.show {
                transform: translateX(0);
            }

            #sidebar.show~.sidebar-backdrop {
                display: block;
            }

            #main-content {
                margin-left: 0;
            }

            /* Responsive Navbar adjustments */
            #top-navbar {
                padding: .5rem .75rem !important;
                gap: .4rem !important;
            }

            .page-title {
                font-size: .92rem !important;
                line-height: 1.1;
            }

            .user-badge {
                padding: .3rem .5rem !important;
                gap: .3rem !important;
            }

            /* Global Responsive Tables: prevent column collapsing, force scroll within card */
            .table {
                min-width: 800px !important;
            }

            .card-body,
            .table-responsive,
            .dataTables_wrapper {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* ── Animations ── */
        .fade-in {
            animation: fadeIn .3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Cart item ── */
        .cart-item-row {
            background: var(--input-bg);
            border-radius: 8px;
            padding: .75rem;
            margin-bottom: .5rem;
            border: 1px solid var(--input-border);
        }

        /* ── Select2 dark override & bootstrap-5 theme support ── */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple,
        .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-search__field {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            color: var(--text-primary) !important;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--accent) !important;
            color: #ffffff !important;
        }
    </style>

    @stack('styles')
</head>

<body>

    {{-- ── SIDEBAR ── --}}
    <div id="sidebar">
        <div class="sidebar-brand">
            @if(!empty($appBranding['logo']))
            <img src="{{ $appBranding['logo'] }}" alt="Logo" class="brand-logo-img rounded-3 shadow-sm" style="width:42px;height:42px;object-fit:contain;background:#ffffff;padding:3px;flex-shrink:0;">
            @else
            <div class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></div>
            @endif
            <div class="brand-text overflow-hidden me-auto">
                <div class="brand-name text-truncate" title="{{ $appBranding['name'] }}">{{ $appBranding['name'] }}</div>
                <div class="brand-sub text-truncate" title="{{ $appBranding['slogan'] }}">{{ $appBranding['slogan'] }}</div>
                @if(!empty($appBranding['location']))
                <div class="brand-loc text-truncate" style="font-size: .65rem; color: rgba(255, 255, 255, 0.95); font-weight: 500; display: flex; align-items: center; gap: 3px; margin-top: 1px;" title="{{ $appBranding['location'] }}">
                    <i class="bi bi-geo-alt-fill" style="font-size:.62rem; color:#ffb700;"></i> {{ $appBranding['location'] }}
                </div>
                @endif
            </div>

            <button type="button" class="btn-close-sidebar d-md-none" onclick="document.getElementById('sidebar').classList.remove('show')" title="Close Sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>

            <div class="nav-item-custom">
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('chats.index') }}" class="nav-link-custom {{ request()->routeIs('chats.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots-fill text-warning"></i> Live Chat
                    <span class="nav-badge bg-danger text-white d-none" id="chatGlobalBadge" style="background-color: var(--accent-red) !important; color: #fff !important;">0</span>
                </a>
            </div>

            @if(auth()->user()->isOwner())
            {{-- Products collapsible --}}
            @php
            $isProductsActive = request()->routeIs('items.*') || request()->routeIs('categories.*') || request()->routeIs('shops.*') || request()->routeIs('users.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isProductsActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#productsCollapseOwner" role="button"
                    aria-expanded="{{ $isProductsActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-box-seam-fill"></i> Products & Shops</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isProductsActive ? 'show' : '' }}" id="productsCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('items.index') }}" class="nav-link-custom {{ request()->routeIs('items.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                        <a href="{{ route('categories.index') }}" class="nav-link-custom {{ request()->routeIs('categories.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                        <a href="{{ route('shops.index') }}" class="nav-link-custom {{ request()->routeIs('shops.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shop"></i> Shops
                        </a>
                        <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-people"></i> Employees
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stocks collapsible --}}
            @php
            $isStocksActive = request()->routeIs('main-stock.*') || request()->routeIs('stock-requests.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('shop-stock.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isStocksActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#stocksCollapseOwner" role="button"
                    aria-expanded="{{ $isStocksActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-layers-fill"></i> Stocks & Warehouse</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isStocksActive ? 'show' : '' }}" id="stocksCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('main-stock.index') }}" class="nav-link-custom {{ request()->routeIs('main-stock.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-building"></i> Main Store Stock
                        </a>
                        <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Stock Requests
                            @php $pending = \App\Models\StockRequest::where('status','pending')->count() @endphp
                            @if($pending > 0) <span class="nav-badge">{{ $pending }}</span> @endif
                        </a>
                        <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Transfers
                        </a>
                        <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-layers"></i> Shop Stocks
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sales collapsible --}}
            @php
            $isSalesActive = (request()->routeIs('sales.*') && !request()->has('status')) || request()->routeIs('sales-returns.*') || request()->routeIs('defects.*') || request()->routeIs('expenses.*') || request()->routeIs('handovers.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSalesActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#salesCollapseOwner" role="button"
                    aria-expanded="{{ $isSalesActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-cart-check-fill"></i> Sales & Operations</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSalesActive ? 'show' : '' }}" id="salesCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') && !request()->has('status') && !request()->is('sales/create') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-cart-check"></i> Sales List
                        </a>
                        <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-counterclockwise"></i> Sales Returns
                        </a>
                        <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-exclamation-triangle"></i> Defective Items
                        </a>
                        <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-wallet2"></i> Expenses Ledger
                        </a>
                        <a href="{{ route('handovers.index') }}" class="nav-link-custom {{ request()->routeIs('handovers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-cash-coin"></i> Cash Handovers
                        </a>
                    </div>
                </div>
            </div>

            {{-- Documents collapsible --}}
            @php
            $isDocsActive = request()->routeIs('sales.*') && request()->has('status');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseOwner" role="button"
                    aria-expanded="{{ $isDocsActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActive ? 'show' : '' }}" id="docsCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('sales.index') }}?status=draft_proforma" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Delivery Notes
                        </a>
                    </div>
                </div>
            </div>

            {{-- Reports collapsible --}}
            @php
            $isReportsActive = request()->routeIs('reports.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isReportsActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#reportsCollapseOwner" role="button"
                    aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-graph-up-arrow"></i> Reports</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="reportsCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('reports.sales') }}" class="nav-link-custom {{ request()->routeIs('reports.sales') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-graph-up-arrow"></i> Sales Report
                        </a>
                        <a href="{{ route('reports.stock') }}" class="nav-link-custom {{ request()->routeIs('reports.stock') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-bar-chart-fill"></i> Stock Report
                        </a>
                        <a href="{{ route('reports.transfer') }}" class="nav-link-custom {{ request()->routeIs('reports.transfer') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Transfer Report
                        </a>
                        <a href="{{ route('reports.defect') }}" class="nav-link-custom {{ request()->routeIs('reports.defect') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shield-exclamation"></i> Defect Report
                        </a>
                        <a href="{{ route('reports.expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.expenses') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-wallet2"></i> Expenses Report
                        </a>
                        <a href="{{ route('reports.sales-vs-expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-vs-expenses') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-bar-graph"></i> Sales vs Expenses
                        </a>
                        <a href="{{ route('reports.analytics') }}" class="nav-link-custom {{ request()->routeIs('reports.analytics') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-stars"></i> Analytics
                        </a>
                        <a href="{{ route('reports.visitors') }}" class="nav-link-custom {{ request()->routeIs('reports.visitors') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-globe"></i> Visitor Analytics
                        </a>
                    </div>
                </div>
            </div>

            {{-- System collapsible --}}
            @php
            $isSystemActive = request()->routeIs('stock-logs.*') || request()->routeIs('activity-logs.*') || request()->routeIs('settings.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSystemActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#systemCollapseOwner" role="button"
                    aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-gear-fill"></i> System</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSystemActive ? 'show' : '' }}" id="systemCollapseOwner">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('stock-logs.index') }}" class="nav-link-custom {{ request()->routeIs('stock-logs.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-clock-history"></i> Audit Logs
                        </a>
                        <a href="{{ route('activity-logs.index') }}" class="nav-link-custom {{ request()->routeIs('activity-logs.index') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-journal-text"></i> User Activity Logs
                        </a>
                        <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </div>
                </div>
            </div>



            @elseif(auth()->user()->isShopAdmin())
            {{-- Management collapsible --}}
            @php
            $isManagementActive = request()->routeIs('shops.show') || request()->routeIs('users.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isManagementActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#productsCollapseAdmin" role="button"
                    aria-expanded="{{ $isManagementActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-shop"></i> Management</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isManagementActive ? 'show' : '' }}" id="productsCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('shops.show', auth()->user()->shop_id ?? 0) }}" class="nav-link-custom {{ request()->routeIs('shops.show') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shop"></i> Shop Profile
                        </a>
                        <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-people"></i> My Employees
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stocks collapsible --}}
            @php
            $isStocksActiveAdmin = request()->routeIs('shop-stock.*') || request()->routeIs('stock-requests.*') || request()->routeIs('stock-transfers.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isStocksActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#stocksCollapseAdmin" role="button"
                    aria-expanded="{{ $isStocksActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-layers-fill"></i> Stocks & Transfers</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isStocksActiveAdmin ? 'show' : '' }}" id="stocksCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-layers"></i> Shop Stock
                        </a>
                        <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Stock Requests
                        </a>
                        <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Stock Transfers
                            @php $pendingDispatches = \App\Models\StockTransfer::where('to_shop', auth()->user()->shop_id)->whereIn('status', ['pending_receipt','partially_received'])->count() @endphp
                            @if($pendingDispatches > 0) <span class="nav-badge">{{ $pendingDispatches }}</span> @endif
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sales collapsible --}}
            @php
            $isSalesActiveAdmin = (request()->routeIs('sales.*') && !request()->has('status')) || request()->routeIs('sales-returns.*') || request()->routeIs('defects.*') || request()->routeIs('expenses.*') || request()->routeIs('handovers.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSalesActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#salesCollapseAdmin" role="button"
                    aria-expanded="{{ $isSalesActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-cart-check-fill"></i> Sales & Operations</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSalesActiveAdmin ? 'show' : '' }}" id="salesCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') && !request()->has('status') && !request()->is('sales/create') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-cart-check"></i> Sales List
                        </a>
                        <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-counterclockwise"></i> Sales Returns
                            @php $pendingReturns = \App\Models\SaleReturn::whereHas('sale', function ($q) { $q->where('shop_id', auth()->user()->shop_id); })->where('status', 'pending')->count() @endphp
                            @if($pendingReturns > 0) <span class="nav-badge">{{ $pendingReturns }}</span> @endif
                        </a>
                        <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-exclamation-triangle"></i> Defective Items
                        </a>
                        <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-wallet2"></i> Expenses Ledger
                        </a>
                        <a href="{{ route('handovers.index') }}" class="nav-link-custom {{ request()->routeIs('handovers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-cash-coin"></i> Cash Handovers
                        </a>
                    </div>
                </div>
            </div>

            {{-- Documents collapsible --}}
            @php
            $isDocsActiveAdmin = request()->routeIs('sales.*') && request()->has('status');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseAdmin" role="button"
                    aria-expanded="{{ $isDocsActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActiveAdmin ? 'show' : '' }}" id="docsCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('sales.index') }}?status=draft_proforma" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Delivery Notes
                        </a>
                    </div>
                </div>
            </div>

            {{-- Reports collapsible --}}
            @php
            $isReportsActiveAdmin = request()->routeIs('reports.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isReportsActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#reportsCollapseAdmin" role="button"
                    aria-expanded="{{ $isReportsActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-graph-up-arrow"></i> Reports</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isReportsActiveAdmin ? 'show' : '' }}" id="reportsCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('reports.sales') }}" class="nav-link-custom {{ request()->routeIs('reports.sales') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-graph-up-arrow"></i> Sales Report
                        </a>
                        <a href="{{ route('reports.stock') }}" class="nav-link-custom {{ request()->routeIs('reports.stock') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-bar-chart-fill"></i> Stock Report
                        </a>
                        <a href="{{ route('reports.transfer') }}" class="nav-link-custom {{ request()->routeIs('reports.transfer') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Transfer Report
                        </a>
                        <a href="{{ route('reports.defect') }}" class="nav-link-custom {{ request()->routeIs('reports.defect') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shield-exclamation"></i> Defect Report
                        </a>
                        <a href="{{ route('reports.expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.expenses') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-wallet2"></i> Expenses Report
                        </a>
                        <a href="{{ route('reports.sales-vs-expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-vs-expenses') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-bar-graph"></i> Sales vs Expenses
                        </a>
                        <a href="{{ route('reports.analytics') }}" class="nav-link-custom {{ request()->routeIs('reports.analytics') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-stars"></i> Analytics
                        </a>
                        <a href="{{ route('reports.visitors') }}" class="nav-link-custom {{ request()->routeIs('reports.visitors') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-globe"></i> Visitor Analytics
                        </a>
                    </div>
                </div>
            </div>

            {{-- System collapsible --}}
            @php
            $isSystemActiveAdmin = request()->routeIs('activity-logs.*') || request()->routeIs('settings.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSystemActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#systemCollapseAdmin" role="button"
                    aria-expanded="{{ $isSystemActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-gear-fill"></i> System</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSystemActiveAdmin ? 'show' : '' }}" id="systemCollapseAdmin">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('activity-logs.index') }}" class="nav-link-custom {{ request()->routeIs('activity-logs.index') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-journal-text"></i> User Activity Logs
                        </a>
                        <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </div>
                </div>
            </div>

            @else {{-- Seller --}}
            {{-- Stocks collapsible --}}
            @php
            $isStocksActiveSeller = request()->routeIs('shop-stock.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isStocksActiveSeller ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#stocksCollapseSeller" role="button"
                    aria-expanded="{{ $isStocksActiveSeller ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-layers-fill"></i> Stocks</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isStocksActiveSeller ? 'show' : '' }}" id="stocksCollapseSeller">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-layers"></i> Available Stock
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sales collapsible --}}
            @php
            $isSalesActiveSeller = (request()->routeIs('sales.*') && !request()->has('status')) || request()->routeIs('sales-returns.*') || request()->routeIs('defects.*') || request()->routeIs('expenses.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSalesActiveSeller ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#salesCollapseSeller" role="button"
                    aria-expanded="{{ $isSalesActiveSeller ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-cart-check-fill"></i> Sales & Operations</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSalesActiveSeller ? 'show' : '' }}" id="salesCollapseSeller">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.create') }}" class="nav-link-custom {{ request()->is('sales/create') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-plus-circle"></i> New Sale
                        </a>
                        <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.index') && !request()->has('status') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-cart-check"></i> My Sales
                        </a>
                        <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-counterclockwise"></i> Returns & Refunds
                        </a>
                        <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-exclamation-triangle"></i> Report Defect
                        </a>
                        <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-wallet2"></i> My Expenses
                        </a>
                    </div>
                </div>
            </div>

            {{-- Documents collapsible --}}
            @php
            $isDocsActiveSeller = request()->routeIs('sales.*') && request()->has('status');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActiveSeller ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseSeller" role="button"
                    aria-expanded="{{ $isDocsActiveSeller ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActiveSeller ? 'show' : '' }}" id="docsCollapseSeller">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('sales.index') }}?status=draft_proforma" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('sales.index') }}?status=completed" class="nav-link-custom" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Delivery Notes
                        </a>
                    </div>
                </div>
            </div>

            {{-- System collapsible --}}
            @php
            $isSystemActiveSeller = request()->routeIs('activity-logs.*') || request()->routeIs('settings.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isSystemActiveSeller ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#systemCollapseSeller" role="button"
                    aria-expanded="{{ $isSystemActiveSeller ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-gear-fill"></i> System</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isSystemActiveSeller ? 'show' : '' }}" id="systemCollapseSeller">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('activity-logs.index') }}" class="nav-link-custom {{ request()->routeIs('activity-logs.index') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-journal-text"></i> User Activity Logs
                        </a>
                        <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-printer"></i> Printer Settings
                        </a>
                    </div>
                </div>
            </div>
            @endif

        </nav>

        {{-- User info at bottom --}}
        <div style="padding: 0.85rem 1rem; border-top: 1px solid var(--sidebar-border); margin-top: auto; background: rgba(0, 0, 0, 0.12); flex-shrink: 0;">
            <div style="display:flex; align-items:center; gap:.65rem; min-width: 0;">
                <a href="{{ route('profile.edit') }}" style="width:36px;height:36px;border-radius:50%;background:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow: 0 2px 6px rgba(0,0,0,0.15);text-decoration:none;overflow:hidden;" title="My Profile: {{ auth()->user()->name }}">
                    @if(auth()->user()->avatar_path)
                    <img src="{{ asset('media/' . auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                    <i class="bi bi-person-fill" style="font-size:1rem;color:#0088cc;"></i>
                    @endif
                </a>
                <div style="flex:1;min-width:0;line-height:1.25;">
                    <a href="{{ route('profile.edit') }}" style="font-size:.82rem;font-weight:700;color:#ffffff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;text-decoration:none;" title="{{ auth()->user()->name }}">
                        {{ auth()->user()->name }}
                    </a>
                    <div style="font-size:.68rem;color:rgba(255,255,255,0.75);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ auth()->user()->email }}">
                        {{ auth()->user()->email }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:.35rem;flex-shrink:0;">
                    <a href="{{ route('profile.edit') }}" style="color:rgba(255,255,255,0.85);text-decoration:none;padding:.25rem .35rem;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:all .15s ease;" title="My Profile / Settings" onmouseover="this.style.background='rgba(255,255,255,0.15)';this.style.color='#ffffff'" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.85)'">
                        <i class="bi bi-gear-fill" style="font-size:1.05rem;"></i>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:rgba(255,255,255,0.85);cursor:pointer;padding:.25rem .35rem;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:all .15s ease;" title="Logout" onmouseover="this.style.background='rgba(255,255,255,0.15)';this.style.color='#ffffff'" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.85)'">
                            <i class="bi bi-box-arrow-right" style="font-size:1.1rem;"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-backdrop d-md-none" onclick="document.getElementById('sidebar').classList.remove('show')"></div>

    {{-- ── MAIN CONTENT ── --}}
    <div id="main-content">
        {{-- Top Navbar --}}
        <div id="top-navbar">
            <button class="btn btn-sm d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')"
                style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                <i class="bi bi-list"></i>
            </button>

            <div class="d-none d-sm-block">
                <div class="page-title">@yield('page-title', 'Dashboard')</div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @yield('breadcrumb')
                    </ol>
                </nav>
            </div>
            <div class="d-block d-sm-none">
                <div class="page-title" style="font-size: .85rem;">@yield('page-title', 'Dashboard')</div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- Notification Bell Dropdown -->
                <div class="dropdown me-1 flex-shrink-0" id="notificationDropdownContainer">
                    <button class="btn btn-sm btn-outline-custom position-relative" type="button" id="notificationBellBtn" data-bs-toggle="dropdown" aria-expanded="false" style="padding: .35rem .65rem; border: 1px solid var(--input-border); background: var(--input-bg);">
                        <i class="bi bi-bell fs-6"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notificationBadge" style="font-size: .55rem; padding: .25em .45em;">
                            0
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2" aria-labelledby="notificationBellBtn" style="width: 320px; border-radius: 12px; overflow: hidden; z-index: 10000;">
                        <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0088cc, #005f9e) !important;">
                            <span class="fw-700 small">Notifications</span>
                            <span class="badge bg-white text-primary rounded-pill px-2 py-1" id="notificationDropdownCount" style="font-size: .65rem; font-weight: 800; color: #0088cc !important;">0 New</span>
                        </div>
                        <div class="list-group list-group-flush" id="notificationDropdownList" style="max-height: 250px; overflow-y: auto;">
                            <div class="p-3 text-center text-muted small" id="noNotificationsPlaceholder">
                                <i class="bi bi-bell-slash d-block mb-1 fs-5"></i> No new notifications
                            </div>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center py-2 text-primary border-top small fw-600 bg-light" style="color: #0088cc !important;">
                            View All Notifications
                        </a>
                    </div>
                </div>

                @if(auth()->user()->shop)
                <div class="user-badge flex-shrink-0" title="{{ auth()->user()->shop->shop_name }}">
                    <i class="bi bi-shop text-primary"></i>
                    <span class="fw-600 d-none d-md-inline text-truncate" style="max-width: 140px; vertical-align: middle;">{{ auth()->user()->shop->shop_name }}</span>
                </div>
                @endif

                <a href="{{ route('profile.edit') }}" class="user-badge text-decoration-none flex-shrink-0" title="Manage Profile: {{ auth()->user()->name }}">
                    @if(auth()->user()->avatar_path)
                    <img src="{{ asset('media/' . auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover; border: 1px solid var(--accent); flex-shrink: 0;">
                    @else
                    <i class="bi bi-person-circle text-primary" style="flex-shrink: 0;"></i>
                    @endif
                    <span class="fw-600 text-dark d-none d-md-inline text-truncate" style="max-width: 130px; vertical-align: middle;">{{ auth()->user()->name }}</span>
                    <span class="role-pill role-{{ auth()->user()->role }} d-none d-lg-inline flex-shrink-0">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0 flex-shrink-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-custom d-flex align-items-center gap-1" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Content --}}
        <div class="content-area fade-in">
            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    {{-- ── Global Image Lightbox ── --}}
    <div id="imgLightbox" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.88); backdrop-filter:blur(6px);
    align-items:center; justify-content:center; cursor:zoom-out;
    animation: lbFadeIn .18s ease;
" onclick="closeLightbox()">
        <button onclick="closeLightbox(event)" style="
        position:absolute; top:18px; right:22px;
        background:rgba(255,255,255,.12); border:none; border-radius:50%;
        width:40px; height:40px; color:#fff; font-size:1.3rem;
        display:flex; align-items:center; justify-content:center; cursor:pointer;
        transition: background .15s;
    " onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="imgLightboxImg" src="" alt="Product Image" style="
        max-width:90vw; max-height:88vh;
        object-fit:contain; border-radius:12px;
        box-shadow:0 24px 60px rgba(0,0,0,0.5);
        cursor:default;
        animation: lbZoomIn .2s ease;
    " onclick="event.stopPropagation()">
        <div id="imgLightboxCaption" style="
        position:absolute; bottom:24px; left:50%; transform:translateX(-50%);
        background:rgba(0,0,0,0.6); color:#fff; font-size:.82rem; font-weight:600;
        padding:.4rem 1rem; border-radius:20px; white-space:nowrap;
        letter-spacing:.02em; max-width:80vw; overflow:hidden; text-overflow:ellipsis;
    "></div>
    </div>
    <style>
        @keyframes lbFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes lbZoomIn {
            from {
                transform: scale(.88);
            }

            to {
                transform: scale(1);
            }
        }

        .img-lightbox {
            cursor: zoom-in !important;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .img-lightbox:hover {
            transform: scale(1.06);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18) !important;
        }
    </style>
    <script>
        function openLightbox(src, caption) {
            var lb = document.getElementById('imgLightbox');
            document.getElementById('imgLightboxImg').src = src;
            document.getElementById('imgLightboxCaption').textContent = caption || '';
            lb.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(e) {
            if (e) e.stopPropagation();
            document.getElementById('imgLightbox').style.display = 'none';
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons and dependencies -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Global Select2 initialization
        window.initSearchableSelect = function(element) {
            const $el = $(element);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            const parentModal = $el.closest('.modal');
            const config = {
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $el.attr('placeholder') || $el.find('option:first').text() || 'Select an option',
                allowClear: !$el.prop('required')
            };
            if (parentModal.length) {
                config.dropdownParent = parentModal;
            }
            $el.select2(config);
        };

        $(document).ready(function() {
            // Automatically target selects with > 8 options (excluding pagination/no-select2)
            $('select').each(function() {
                if ($(this).find('option').length > 8 && !$(this).hasClass('no-select2')) {
                    window.initSearchableSelect(this);
                }
            });

            // Initialize inside modal upon display
            $(document).on('shown.bs.modal', function(e) {
                $(e.target).find('select').each(function() {
                    if ($(this).find('option').length > 8 && !$(this).hasClass('no-select2')) {
                        window.initSearchableSelect(this);
                    }
                });
            });
        });

        // Global DataTable defaults
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                search: '',
                searchPlaceholder: 'Search...'
            },
            pageLength: 10,
            order: [],
            columnDefs: [{
                orderable: false,
                targets: 'no-sort'
            }]
        });

        // SweetAlert confirm for delete/reject forms
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form') || document.getElementById(this.dataset.form);
                Swal.fire({
                    title: this.dataset.confirm || 'Are you sure?',
                    text: this.dataset.text || 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0088cc',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: this.dataset.confirmBtn || 'Yes, proceed',
                    background: '#ffffff',
                    color: '#0f172a',
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // Real-time Notification Polling System
        function playSynthChime() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();

                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(880, ctx.currentTime); // A5
                gain1.gain.setValueAtTime(0.3, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc1.start(ctx.currentTime);
                osc1.stop(ctx.currentTime + 0.4);

                setTimeout(() => {
                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(1320, ctx.currentTime); // E6
                    gain2.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                    osc2.start(ctx.currentTime);
                    osc2.stop(ctx.currentTime + 0.5);
                }, 100);
            } catch (e) {
                console.error('Web Audio API error: ', e);
            }
        }

        function playNotificationSound(ringtoneUrl) {
            if (ringtoneUrl) {
                const audio = new Audio(ringtoneUrl);
                audio.play().catch(e => {
                    console.warn('Custom ringtone playback failed, falling back to synth chime: ', e);
                    playSynthChime();
                });
            } else {
                playSynthChime();
            }
        }

        window.pollNotifications = function() {
            $.ajax({
                url: "{{ route('notifications.poll') }}",
                type: 'GET',
                success: function(response) {
                    const badge = $('#notificationBadge');
                    const dropdownCount = $('#notificationDropdownCount');
                    const list = $('#notificationDropdownList');
                    const placeholder = $('#noNotificationsPlaceholder');

                    // Update unread badges
                    if (response.unread_count > 0) {
                        badge.removeClass('d-none').text(response.unread_count);
                        dropdownCount.text(`${response.unread_count} New`);
                    } else {
                        badge.addClass('d-none');
                        dropdownCount.text('0 New');
                    }

                    // Render recent notifications
                    if (response.recent.length === 0) {
                        placeholder.removeClass('d-none');
                        list.find('.notification-item').remove();
                    } else {
                        placeholder.addClass('d-none');
                        list.find('.notification-item').remove();

                        let itemsHtml = '';
                        response.recent.forEach(function(item) {
                            const linkUrl = item.destination_url ?
                                `/notifications/${item.id}/go?redirect=${encodeURIComponent(item.destination_url)}` :
                                `/notifications/${item.id}/go`;

                            itemsHtml += `
                            <a href="${linkUrl}" class="list-group-item list-group-item-action p-2.5 border-bottom notification-item transition-all" style="font-size: .8rem; border-left: 3px solid #0088cc;">
                                <div class="d-flex justify-content-between align-items-center mb-0.5">
                                    <div class="fw-700 text-dark">${item.title}</div>
                                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                                </div>
                                <div class="text-secondary text-truncate" style="font-size: .72rem; max-width: 280px;">${item.message}</div>
                            </a>
                        `;
                        });
                        list.prepend(itemsHtml);
                    }

                    // Play sound if flagged
                    if (response.play_sound) {
                        playNotificationSound(response.ringtone_url);
                    }
                },
                error: function(xhr) {
                    console.error('Notification poll failed: ', xhr);
                }
            });
        };

        window.pollUnreadChatCount = function() {
            $.ajax({
                url: "{{ route('chats.unread-badge') }}",
                type: 'GET',
                success: function(response) {
                    const badge = $('#chatGlobalBadge');
                    if (response.unread > 0) {
                        badge.removeClass('d-none').text(response.unread);
                    } else {
                        badge.addClass('d-none');
                    }

                    // Update dashboard floating badge if it exists
                    const dashBadge = $('#dashboardChatBadge');
                    if (dashBadge.length > 0) {
                        if (response.unread > 0) {
                            dashBadge.removeClass('d-none').text(response.unread);
                        } else {
                            dashBadge.addClass('d-none');
                        }
                    }

                    // Update individual contact badges in the chat sidebar if available
                    if (typeof response.unread_by_sender !== 'undefined') {
                        $('.chat-unread-badge-container').addClass('d-none').text('0');
                        for (const [senderId, data] of Object.entries(response.unread_by_sender)) {
                            const userBadge = $(`.chat-target[data-id="${senderId}"][data-type="individual"] .chat-unread-badge-container`);
                            if (userBadge.length > 0 && data.count > 0) {
                                userBadge.removeClass('d-none').text(data.count);
                            }
                        }

                        // Call sidebar sorter if defined
                        if (typeof window.sortSidebarUsers === 'function') {
                            window.sortSidebarUsers(response.unread_by_sender);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Chat unread badge poll failed: ', xhr);
                }
            });
        };

        // Run poll on load and every 10 seconds
        $(document).ready(function() {
            window.pollNotifications();
            setInterval(window.pollNotifications, 10000);

            window.pollUnreadChatCount();
            setInterval(window.pollUnreadChatCount, 8000);
        });

        window.formatCurrencyValue = function(val) {
            if (!val) return '';
            var clean = String(val).replace(/[^0-9.]/g, '');
            var parts = clean.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (parts.length > 2) {
                parts = [parts[0], parts.slice(1).join('')];
            }
            return parts.join('.');
        };

        $(document).on('input', '.currency-input', function() {
            var isText = (this.type === 'text' || this.type === 'search' || this.type === 'tel' || this.type === 'url' || !this.type);
            var selectionStart = isText ? this.selectionStart : 0;
            var selectionEnd = isText ? this.selectionEnd : 0;
            var originalLength = this.value.length;

            var formattedValue = window.formatCurrencyValue(this.value);
            this.value = formattedValue;

            if (isText) {
                var newLength = formattedValue.length;
                var diff = newLength - originalLength;
                this.setSelectionRange(selectionStart + diff, selectionEnd + diff);
            }
        });

        $(document).ready(function() {
            $('.currency-input').each(function() {
                this.value = window.formatCurrencyValue(this.value);
            });
        });

        $(document).on('submit', 'form', function() {
            $(this).find('.currency-input').each(function() {
                this.value = this.value.replace(/,/g, '');
            });
        });

        // Global Photo Preview Before Upload & WebP Compression Stats Handler
        $(document).on('change', 'input[type="file"]', function(e) {
            const input = this;
            const file = input.files && input.files[0];
            const targetElement = $(input).closest('.input-group').length ? $(input).closest('.input-group') : $(input);
            const maxUploadMb = Number("{{ (int) \App\Models\Setting::get('max_upload_size_mb', 5) }}") || 5;
            const maxUploadBytes = maxUploadMb * 1024 * 1024;

            // Clear previous oversize alert
            targetElement.siblings('.file-oversize-alert').remove();

            if (file && file.size > maxUploadBytes) {
                const fileSizeMbStr = (file.size / 1048576).toFixed(2);
                input.value = '';
                targetElement.siblings('.image-upload-preview-container').remove();

                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show mt-2 py-2 px-3 small shadow-sm file-oversize-alert" role="alert" style="font-size:0.8rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>File size too large!</strong><br>
                        The selected file size (<strong>${fileSizeMbStr} MB</strong>) exceeds the maximum allowed system limit of <strong>${maxUploadMb} MB</strong>. File upload is not allowed.
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                targetElement.after(alertHtml);
                return;
            }

            if (file && file.type && file.type.match(/^image\//)) {
                let previewContainer = targetElement.siblings('.image-upload-preview-container');
                if (previewContainer.length === 0 && $(input).siblings('.image-upload-preview-container').length) {
                    previewContainer = $(input).siblings('.image-upload-preview-container');
                }

                if (previewContainer.length === 0) {
                    previewContainer = $(`
                        <div class="image-upload-preview-container mt-2">
                            <div class="d-flex align-items-center gap-3 p-2.5 rounded border shadow-sm" style="background: var(--body-bg); border-color: var(--card-border) !important;">
                                <div class="position-relative">
                                    <img class="image-preview-thumb rounded" style="max-height: 100px; max-width: 150px; object-fit: contain; background:#fff; padding:3px; border:1px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.08);" src="" alt="Preview">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-600 text-truncate small preview-file-name mb-1" style="color: var(--text-primary);"></div>
                                    <div class="preview-file-size mb-1" style="font-size: 0.76rem;"></div>
                                    <div class="badge bg-success text-white" style="font-size: 0.68rem;"><i class="bi bi-magic me-1"></i>WebP Compression Preview</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-preview-btn py-1 px-2.5" style="font-size: 0.75rem;" title="Clear selected photo">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    `);

                    targetElement.after(previewContainer);
                }

                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewContainer.find('.image-preview-thumb').attr('src', evt.target.result);
                    previewContainer.find('.preview-file-name').text(file.name);

                    const origSizeStr = file.size > 1048576 ?
                        (file.size / 1048576).toFixed(2) + ' MB' :
                        (file.size / 1024).toFixed(1) + ' KB';

                    previewContainer.find('.preview-file-size').html(`Original Size: <strong>${origSizeStr}</strong> <span class="spinner-border spinner-border-sm text-accent ms-1" style="width:0.7rem;height:0.7rem;" role="status"></span> <span class="text-muted" style="font-size:0.68rem;">Calculating WebP...</span>`);
                    previewContainer.slideDown(200);

                    // Client-side WebP compression estimation using HTML5 Canvas
                    const tempImg = new Image();
                    tempImg.onload = function() {
                        try {
                            const canvas = document.createElement('canvas');
                            let width = tempImg.width;
                            let height = tempImg.height;
                            const maxWidth = 1200;

                            if (width > maxWidth) {
                                height = Math.round((height / width) * maxWidth);
                                width = maxWidth;
                            }

                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(tempImg, 0, 0, width, height);

                            canvas.toBlob(function(blob) {
                                if (blob) {
                                    const compSizeStr = blob.size > 1048576 ?
                                        (blob.size / 1048576).toFixed(2) + ' MB' :
                                        (blob.size / 1024).toFixed(1) + ' KB';

                                    const savedPct = Math.max(0, Math.round(((file.size - blob.size) / file.size) * 100));

                                    previewContainer.find('.preview-file-size').html(`
                                        <span class="text-muted text-decoration-line-through me-1">Raw: ${origSizeStr}</span>
                                        <i class="bi bi-arrow-right text-accent mx-1"></i>
                                        <strong class="text-success">Compressed WebP: ~${compSizeStr}</strong>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.65rem;">-${savedPct}% saved</span>
                                    `);
                                } else {
                                    previewContainer.find('.preview-file-size').html(`Original Size: <strong>${origSizeStr}</strong> (Auto-compresses to WebP on upload)`);
                                }
                            }, 'image/webp', 0.85);
                        } catch (err) {
                            previewContainer.find('.preview-file-size').html(`Original Size: <strong>${origSizeStr}</strong> (Auto-compresses to WebP on upload)`);
                        }
                    };
                    tempImg.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            } else if (!file) {
                const previewContainer = targetElement.parent().find('.image-upload-preview-container');
                if (previewContainer.length) {
                    previewContainer.slideUp(150, function() {
                        $(this).remove();
                    });
                }
            }
        });

        $(document).on('click', '.remove-preview-btn', function() {
            const container = $(this).closest('.image-upload-preview-container');
            const parent = container.parent();
            const input = parent.find('input[type="file"]');
            input.val('');
            container.slideUp(150, function() {
                $(this).remove();
            });
        });

    </script>

    @stack('scripts')
</body>

</html>