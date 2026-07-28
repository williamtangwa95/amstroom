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

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
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
            width: 42px; height: 42px;
            background: #ffffff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #0088cc;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .sidebar-brand .brand-text { line-height: 1.2; }
        .sidebar-brand .brand-name { font-size: 1rem; font-weight: 800; color: #ffffff; letter-spacing: -.01em; }
        .sidebar-brand .brand-sub { font-size: .7rem; color: #ffb700; font-weight: 700; letter-spacing: .02em; }

        .sidebar-nav { padding: .75rem 0 2rem 0; flex: 1; }

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

        .nav-link-custom i { font-size: .95rem; width: 16px; text-align: center; flex-shrink: 0; }

        .nav-badge {
            margin-left: auto;
            background: var(--accent-gold);
            color: #000;
            font-size: .6rem;
            padding: .15rem .4rem;
            border-radius: 10px;
            font-weight: 800;
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
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .page-title { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); }

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

        .role-owner   { background: rgba(255, 183, 0, 0.15); color: var(--accent-gold); border: 1px solid rgba(255, 183, 0, 0.3); }
        .role-shop_admin { background: rgba(0, 136, 204, 0.15); color: var(--accent-blue); border: 1px solid rgba(0, 136, 204, 0.3); }
        .role-seller  { background: rgba(6, 182, 212, 0.15); color: #22d3ee; border: 1px solid rgba(6, 182, 212, 0.3); }

        /* ── Content Area ── */
        .content-area { padding: 1.5rem; flex: 1; }

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

        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08); }

        .stat-card .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            margin-bottom: .75rem;
        }

        .stat-card .stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1; margin-bottom: .25rem; }
        .stat-card .stat-label { font-size: .75rem; color: var(--text-secondary); font-weight: 600; }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
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

        .table td { border-color: #f1f5f9; vertical-align: middle; }
        .table-hover tbody tr:hover { background: rgba(0, 136, 204, 0.04); }

        /* ── Forms ── */
        .form-control, .form-select {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 8px;
            font-size: .85rem;
        }

        .form-control:focus, .form-select:focus {
            background: var(--input-bg);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
        }

        .form-control::placeholder { color: #94a3b8; }

        .form-label { font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }

        /* ── Buttons ── */
        .btn-accent {
            background: linear-gradient(135deg, #0088cc, #006699);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .83rem;
            transition: all .15s;
            box-shadow: 0 4px 12px rgba(0, 136, 204, 0.25);
        }

        .btn-accent:hover { background: linear-gradient(135deg, #0077b6, #004d73); color: #fff; transform: translateY(-1px); }

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

        .btn-gold:hover { background: linear-gradient(135deg, #e5a300, #b45309); color: #000; transform: translateY(-1px); }

        .btn-outline-custom {
            border: 1px solid var(--input-border);
            color: #475569;
            background: #ffffff;
            border-radius: 8px;
            font-size: .83rem;
        }

        .btn-outline-custom:hover { border-color: var(--accent); color: var(--accent); background: #f8fafc; }

        /* ── Badges ── */
        .badge-pending  { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
        .badge-active   { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

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
        .alert-success { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-danger  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .alert-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .alert-info    { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

        /* ── DataTables & Table Styling ── */
        .dataTables_wrapper {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .dataTables_wrapper > .row:first-child,
        .dataTables_wrapper > div.dt-row + div.row,
        .dataTables_wrapper > .row:last-child {
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
        .breadcrumb { background: none; padding: 0; font-size: .78rem; margin: 0; }
        .breadcrumb-item { color: #64748b; }
        .breadcrumb-item a { color: #0284c7; text-decoration: none; font-weight: 500; }
        .breadcrumb-item a:hover { color: #0369a1; text-decoration: underline; }
        .breadcrumb-item.active { color: #0f172a; font-weight: 600; }
        .breadcrumb-item + .breadcrumb-item::before { color: #94a3b8; }

        /* ── Low Stock Alert ── */
        .low-stock-row { background: #fff5f5 !important; }

        /* ── Print ── */
        @media print {
            #sidebar, #top-navbar { display: none !important; }
            #main-content { margin-left: 0; }
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
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 999;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #sidebar.show ~ .sidebar-backdrop { display: block; }
            #main-content { margin-left: 0; }
        }

        /* ── Animations ── */
        .fade-in { animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Cart item ── */
        .cart-item-row { background: var(--input-bg); border-radius: 8px; padding: .75rem; margin-bottom: .5rem; border: 1px solid var(--input-border); }

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

        @if(auth()->user()->isOwner())
        <div class="nav-section-label">Management</div>

        <div class="nav-item-custom">
            <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> System Branding
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('shops.index') }}" class="nav-link-custom {{ request()->routeIs('shops.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i> Shops
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Employees
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('categories.index') }}" class="nav-link-custom {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags-fill"></i> Categories
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('items.index') }}" class="nav-link-custom {{ request()->routeIs('items.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Products
            </a>
        </div>

        <div class="nav-section-label">Warehouse</div>

        <div class="nav-item-custom">
            <a href="{{ route('main-stock.index') }}" class="nav-link-custom {{ request()->routeIs('main-stock.*') ? 'active' : '' }}">
                <i class="bi bi-building-fill"></i> Main Store Stock
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Stock Requests
                @php $pending = \App\Models\StockRequest::where('status','pending')->count() @endphp
                @if($pending > 0) <span class="nav-badge">{{ $pending }}</span> @endif
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Transfers
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}">
                <i class="bi bi-layers-fill"></i> Shop Stocks
            </a>
        </div>

        <div class="nav-section-label">Operations</div>

        <div class="nav-item-custom">
            <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check-fill"></i> Sales
            </a>
        </div>

        {{-- Documents collapsible --}}
        <div class="nav-item-custom">
            <a class="nav-link-custom {{ request()->routeIs('sales.*') ? '' : '' }} d-flex justify-content-between align-items-center"
               data-bs-toggle="collapse" href="#docsCollapseOwner" role="button"
               aria-expanded="false" style="cursor:pointer;">
                <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
            </a>
            <div class="collapse" id="docsCollapseOwner">
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

        <div class="nav-item-custom">
            <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-counterclockwise"></i> Sales Returns
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Defective Items
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Expenses Ledger
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('stock-logs.index') }}" class="nav-link-custom {{ request()->routeIs('stock-logs.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Audit Logs
            </a>
        </div>

        <div class="nav-section-label">Reports</div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.sales') }}" class="nav-link-custom {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Sales Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.stock') }}" class="nav-link-custom {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i> Stock Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.transfer') }}" class="nav-link-custom {{ request()->routeIs('reports.transfer') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Transfer Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.defect') }}" class="nav-link-custom {{ request()->routeIs('reports.defect') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i> Defect Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.expenses') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Expenses Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.sales-vs-expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-vs-expenses') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Sales vs Expenses
            </a>
        </div>

        <div class="nav-section-label">System</div>
        <div class="nav-item-custom">
            <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </div>

        @elseif(auth()->user()->isShopAdmin())
        <div class="nav-section-label">Shop Operations</div>

        <div class="nav-item-custom">
            <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}">
                <i class="bi bi-layers-fill"></i> Shop Stock
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Stock Requests
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Stock Transfers
                @php $pendingDispatches = \App\Models\StockTransfer::where('to_shop', auth()->user()->shop_id)->whereIn('status', ['pending_receipt','partially_received'])->count() @endphp
                @if($pendingDispatches > 0) <span class="nav-badge">{{ $pendingDispatches }}</span> @endif
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check-fill"></i> Sales
            </a>
        </div>

        {{-- Documents collapsible --}}
        <div class="nav-item-custom">
            <a class="nav-link-custom d-flex justify-content-between align-items-center"
               data-bs-toggle="collapse" href="#docsCollapseAdmin" role="button"
               aria-expanded="false" style="cursor:pointer;">
                <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                <i class="bi bi-chevron-down" style="font-size:.7rem;"></i>
            </a>
            <div class="collapse" id="docsCollapseAdmin">
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

        <div class="nav-item-custom">
            <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-counterclockwise"></i> Sales Returns
                @php $pendingReturns = \App\Models\SaleReturn::whereHas('sale', function ($q) { $q->where('shop_id', auth()->user()->shop_id); })->where('status', 'pending')->count() @endphp
                @if($pendingReturns > 0) <span class="nav-badge">{{ $pendingReturns }}</span> @endif
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Defective Items
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Expenses Ledger
            </a>
        <div class="nav-section-label">Reports</div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.sales') }}" class="nav-link-custom {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i> Sales Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.stock') }}" class="nav-link-custom {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i> Stock Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.transfer') }}" class="nav-link-custom {{ request()->routeIs('reports.transfer') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Transfer Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.defect') }}" class="nav-link-custom {{ request()->routeIs('reports.defect') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation"></i> Defect Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.expenses') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Expenses Report
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('reports.sales-vs-expenses') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-vs-expenses') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Sales vs Expenses
            </a>
        </div>

        <div class="nav-section-label">System</div>
        <div class="nav-item-custom">
            <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </div>

        @else {{-- Seller --}}
        <div class="nav-section-label">My Workspace</div>

        <div class="nav-item-custom">
            <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.*') ? 'active' : '' }}">
                <i class="bi bi-layers-fill"></i> Available Stock
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('sales.create') }}" class="nav-link-custom {{ request()->is('sales/create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle-fill"></i> New Sale
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-cart-check-fill"></i> My Sales
            </a>
        </div>

        {{-- Documents collapsible --}}
        <div class="nav-item-custom">
            <a class="nav-link-custom d-flex justify-content-between align-items-center"
               data-bs-toggle="collapse" href="#docsCollapseSeller" role="button"
               aria-expanded="false" style="cursor:pointer;">
                <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                <i class="bi bi-chevron-down" style="font-size:.7rem;"></i>
            </a>
            <div class="collapse" id="docsCollapseSeller">
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

        <div class="nav-item-custom">
            <a href="{{ route('sales-returns.index') }}" class="nav-link-custom {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-counterclockwise"></i> Returns & Refunds
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('defects.index') }}" class="nav-link-custom {{ request()->routeIs('defects.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Report Defect
            </a>
        </div>

        <div class="nav-item-custom">
            <a href="{{ route('expenses.index') }}" class="nav-link-custom {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> My Expenses
            </a>
        </div>

        <div class="nav-section-label">System</div>
        <div class="nav-item-custom">
            <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-printer-fill"></i> Printer Settings
            </a>
        </div>
        @endif

    </nav>

    {{-- User info at bottom --}}
    <div style="padding: 1rem; border-top: 1px solid var(--sidebar-border); margin-top: auto; background: rgba(0, 0, 0, 0.08);">
        <div style="display:flex; align-items:center; gap:.65rem;">
            <a href="{{ route('profile.edit') }}" style="width:34px;height:34px;border-radius:50%;background:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow: 0 2px 6px rgba(0,0,0,0.1);text-decoration:none;" title="My Profile">
                <i class="bi bi-person-fill" style="font-size:.95rem;color:#0088cc;"></i>
            </a>
            <div style="flex:1;min-width:0;">
                <a href="{{ route('profile.edit') }}" style="font-size:.82rem;font-weight:700;color:#ffffff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;text-decoration:none;" title="Edit Profile">
                    {{ auth()->user()->name }}
                </a>
                <div style="font-size:.68rem;color:rgba(255,255,255,0.75);">{{ auth()->user()->email }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <a href="{{ route('profile.edit') }}" style="color:rgba(255,255,255,0.9);text-decoration:none;" title="My Profile">
                    <i class="bi bi-gear-fill" style="font-size:1rem;"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:rgba(255,255,255,0.9);cursor:pointer;padding:0;" title="Logout">
                        <i class="bi bi-box-arrow-right" style="font-size:1.05rem;"></i>
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

        <div>
            <div class="page-title">@yield('page-title', 'Dashboard')</div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <!-- Notification Bell Dropdown -->
            <div class="dropdown me-1" id="notificationDropdownContainer">
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
            <div class="user-badge">
                <i class="bi bi-shop text-primary"></i>
                <span class="fw-600">{{ auth()->user()->shop->shop_name }}</span>
            </div>
            @endif

            <a href="{{ route('profile.edit') }}" class="user-badge text-decoration-none" title="Manage Profile">
                @if(auth()->user()->avatar_path)
                    <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="rounded-circle me-1" style="width: 24px; height: 24px; object-fit: cover; border: 1px solid var(--accent);">
                @else
                    <i class="bi bi-person-circle text-primary"></i>
                @endif
                <span class="fw-600 text-dark">{{ auth()->user()->name }}</span>
                <span class="role-pill role-{{ auth()->user()->role }}">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-custom" title="Logout">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
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
        language: { search: '', searchPlaceholder: 'Search...' },
        pageLength: 10,
        order: [],
        columnDefs: [{ orderable: false, targets: 'no-sort' }]
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
                        itemsHtml += `
                            <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action p-2.5 border-bottom notification-item transition-all" style="font-size: .8rem; border-left: 3px solid #0088cc;">
                                <div class="fw-700 text-dark">${item.title}</div>
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

    // Run poll on load and every 10 seconds
    $(document).ready(function() {
        window.pollNotifications();
        setInterval(window.pollNotifications, 10000);
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
</script>

@stack('scripts')
</body>
</html>
