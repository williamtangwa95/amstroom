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
    <!-- DataTables & Responsive -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
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

        /* ═════════════════════════════════════════════════════════
           MODERN RESPONSIVE TABLES & ACCORDION DRAWER (Reference 1)
           ═════════════════════════════════════════════════════════ */
        .card,
        .table-card {
            border-radius: 12px;
            border: 1px solid var(--card-border);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            background: var(--card-bg);
            transition: box-shadow 0.2s ease;
        }

        .table {
            margin-bottom: 0;
            color: var(--text-primary);
            vertical-align: middle;
            border-color: #f1f5f9;
            width: 100% !important;
        }

        .table> :not(caption)>*>* {
            padding: 0.85rem 1rem;
            border-bottom-width: 1px;
            border-color: #f1f5f9;
        }

        .table thead th {
            font-size: 0.73rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #64748b !important;
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: #f8fafc !important;
        }

        /* Parent row highlight when expanded (Reference Picture 1) */
        table.dataTable>tbody>tr.parent,
        table.dataTable.table-hover>tbody>tr.parent {
            background-color: #f1f5f9 !important;
        }

        /* Soft Badges */
        .badge-soft-primary {
            background: #e0f2fe !important;
            color: #0284c7 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(2, 132, 199, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-success {
            background: #d1fae5 !important;
            color: #059669 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(16, 185, 129, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-danger {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(239, 68, 68, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-warning {
            background: #fef3c7 !important;
            color: #d97706 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(245, 158, 11, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-info {
            background: #e0f2fe !important;
            color: #0284c7 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(2, 132, 199, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-secondary {
            background: #f1f5f9 !important;
            color: #475569 !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(100, 116, 139, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-purple {
            background: #ede9fe !important;
            color: #7c3aed !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(139, 92, 246, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-soft-dark {
            background: #334155 !important;
            color: #f8fafc !important;
            font-weight: 600;
            padding: .35em .65em;
            border-radius: 6px;
            font-size: .75rem;
            border: 1px solid rgba(51, 65, 85, .15);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* DataTables Responsive Caret Indicators (Reference Picture 1: ▶ / ▼) */
        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
            display: none !important;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control {
            position: relative;
            padding-left: 2rem !important;
            cursor: pointer;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control::after {
            content: '▶';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.68rem;
            color: #64748b;
            font-weight: 700;
            transition: color 0.15s ease;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control::after {
            content: '▼';
            color: #0f172a;
        }

        /* Expanded Child Drawer Styling (Reference Picture 1) */
        table.dataTable>tbody>tr.child {
            background: #ffffff !important;
        }

        table.dataTable>tbody>tr.child td.child {
            padding: 0.6rem 1.25rem 1.25rem 1.25rem !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        table.dataTable>tbody>tr.child ul.dtr-details {
            display: flex !important;
            flex-direction: column !important;
            gap: 0 !important;
            width: 100% !important;
            max-width: 520px;
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        table.dataTable>tbody>tr.child ul.dtr-details>li {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 0.65rem 0 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            font-size: 0.88rem !important;
        }

        table.dataTable>tbody>tr.child ul.dtr-details>li:last-child {
            border-bottom: none !important;
            padding-top: 0.85rem !important;
        }

        table.dataTable>tbody>tr.child span.dtr-title {
            font-weight: 800 !important;
            color: #0f172a !important;
            font-size: 0.88rem !important;
            min-width: 135px !important;
            width: 135px !important;
            text-transform: none !important;
            letter-spacing: normal !important;
            flex-shrink: 0;
        }

        table.dataTable>tbody>tr.child span.dtr-data {
            color: #334155 !important;
            font-size: 0.88rem !important;
            font-weight: 500 !important;
            display: inline-flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 0.45rem !important;
        }

        /* Action Buttons Inside Tables & Drawer */
        .action-btn-group,
        .dtr-data .btn-group {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
        }

        .action-btn-square,
        table.dataTable td .btn-xs,
        table.dataTable td .btn-sm {
            border-radius: 8px !important;
        }

        .dataTables_wrapper {
            width: 100%;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #0088cc;
            box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.4rem 2rem 0.4rem 0.75rem;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_paginate .pagination {
            margin-bottom: 0;
            gap: 4px;
        }

        .dataTables_wrapper .dataTables_paginate .page-item .page-link {
            border-radius: 8px !important;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.82rem;
            padding: 0.4rem 0.75rem;
            transition: all 0.15s;
        }

        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background-color: #0088cc;
            border-color: #0088cc;
            color: #fff;
            font-weight: 600;
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
            padding: 1rem 1.05rem;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: flex-start;
            gap: .72rem;
            background: linear-gradient(180deg, rgba(0, 119, 182, 0.98) 0%, var(--sidebar-solid) 100%);
        }

        .sidebar-brand .brand-icon,
        .sidebar-brand .brand-logo-img {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 11px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.14);
            flex-shrink: 0;
            margin-top: .08rem;
        }

        .sidebar-brand .brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #0077b6;
        }

        .sidebar-brand .brand-logo-img {
            object-fit: contain;
            padding: 3px;
        }

        .sidebar-brand .brand-text {
            flex: 1;
            min-width: 0;
            line-height: 1.25;
        }

        .sidebar-brand .brand-name {
            font-size: .9rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -.01em;
            line-height: 1.25;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .sidebar-brand .brand-sub {
            font-size: .68rem;
            color: #ffd166;
            font-weight: 600;
            letter-spacing: .015em;
            line-height: 1.3;
            margin-top: .18rem;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .sidebar-brand .brand-loc {
            display: inline-flex;
            align-items: flex-start;
            gap: .28rem;
            margin-top: .38rem;
            padding: .18rem .48rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-size: .64rem;
            font-weight: 500;
            line-height: 1.3;
            color: rgba(255, 255, 255, 0.94);
            white-space: normal;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .sidebar-brand .brand-loc i {
            font-size: .62rem;
            color: #ffd166;
            flex-shrink: 0;
            margin-top: .12rem;
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

        /* ── User Profile Trigger & Dropdown Menu ── */
        .user-profile-badge {
            background: #ffffff;
            border: 1px solid #e2e8f0 !important;
            border-radius: 999px;
            padding: .28rem .55rem .28rem .32rem;
            font-size: .78rem;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .user-profile-badge:hover,
        .user-profile-badge:focus-visible,
        .user-profile-badge[aria-expanded="true"],
        .show > .user-profile-badge {
            background: #f8fafc;
            border-color: rgba(0, 119, 182, 0.45) !important;
            color: var(--text-primary);
            box-shadow: 0 3px 10px rgba(0, 119, 182, 0.12) !important;
            outline: none;
        }

        .user-profile-badge::after {
            display: none !important;
        }

        .upb-avatar-wrap {
            position: relative;
            flex-shrink: 0;
            display: inline-flex;
        }

        .upb-avatar-img,
        .upb-avatar-initials {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .upb-avatar-img {
            object-fit: cover;
            border: 1.5px solid #e2e8f0;
        }

        .upb-avatar-initials {
            background: linear-gradient(135deg, #0077b6, #004e89);
            color: #ffffff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .upb-status-dot {
            position: absolute;
            right: -1px;
            bottom: -1px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #10b981;
            border: 2px solid #ffffff;
        }

        .upb-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.15;
            min-width: 0;
            text-align: left;
        }

        .upb-name {
            font-size: .79rem;
            font-weight: 600;
            color: var(--text-primary);
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upb-role {
            font-size: .63rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upb-chevron {
            font-size: .66rem;
            color: #94a3b8;
            transition: transform 0.2s ease, color 0.2s ease;
            flex-shrink: 0;
            margin-right: .1rem;
        }

        .user-profile-badge[aria-expanded="true"] .upb-chevron,
        .show > .user-profile-badge .upb-chevron {
            transform: rotate(180deg);
            color: #0077b6;
        }

        /* Dropdown Menu Panel */
        .user-profile-menu {
            width: 300px;
            max-width: calc(100vw - 1.5rem);
            padding: 0;
            margin-top: .55rem !important;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            z-index: 10000;
        }

        .user-profile-menu.show {
            animation: upmFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes upmFadeIn {
            from {
                opacity: 0;
                transform: translateY(-6px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .upm-header {
            padding: 1rem 1.05rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .upm-avatar-img,
        .upm-avatar-initials {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .upm-avatar-img {
            object-fit: cover;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
        }

        .upm-avatar-initials {
            background: linear-gradient(135deg, #0077b6, #003a6c);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .02em;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 119, 182, 0.22);
        }

        .upm-user-info {
            min-width: 0;
            flex: 1;
        }

        .upm-user-name {
            font-size: .88rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .upm-user-email {
            font-size: .73rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: .08rem;
        }

        .upm-badges {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .45rem;
        }

        .upm-shop-pill {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            padding: .15rem .5rem;
            border-radius: 20px;
            font-size: .65rem;
            font-weight: 600;
            color: #475569;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            max-width: 145px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .upm-list {
            padding: .45rem;
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .upm-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .58rem .68rem;
            border-radius: 10px;
            width: 100%;
            text-align: left;
            text-decoration: none;
            background: transparent;
            border: none;
            color: var(--text-primary);
            white-space: normal;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .upm-item:hover,
        .upm-item:focus {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .upm-item:active {
            background: #e2e8f0;
            color: var(--text-primary);
        }

        .upm-item-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .upm-icon-profile {
            background: rgba(0, 119, 182, 0.1);
            color: #0077b6;
        }

        .upm-icon-security {
            background: rgba(16, 185, 129, 0.11);
            color: #059669;
        }

        .upm-icon-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .upm-item-text {
            flex: 1;
            min-width: 0;
        }

        .upm-item-title {
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.25;
        }

        .upm-item-sub {
            font-size: .71rem;
            color: #64748b;
            line-height: 1.25;
            margin-top: .08rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .upm-item-arrow {
            font-size: .72rem;
            color: #94a3b8;
            flex-shrink: 0;
            transition: transform 0.15s ease, color 0.15s ease;
        }

        .upm-item:hover .upm-item-arrow {
            transform: translateX(2px);
            color: #475569;
        }

        .upm-footer {
            padding: .45rem;
            border-top: 1px solid #f1f5f9;
            background: #fafbfc;
        }

        .upm-item-danger .upm-item-title {
            color: #dc2626;
        }

        .upm-item-danger:hover,
        .upm-item-danger:focus {
            background: #fef2f2;
        }

        .upm-item-danger:hover .upm-icon-logout {
            background: #dc2626;
            color: #ffffff;
        }

        @media (max-width: 767.98px) {
            .user-profile-badge {
                padding: .22rem .42rem .22rem .22rem;
                gap: .35rem;
            }
            .upb-avatar-img,
            .upb-avatar-initials {
                width: 28px;
                height: 28px;
            }
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

        .role-system_admin {
            background: rgba(139, 92, 246, 0.15);
            color: #8b5cf6;
            border: 1px solid rgba(139, 92, 246, 0.3);
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

            /* Global Responsive Tables:
               - Only force horizontal scroll on plain non-DataTable tables inside .table-responsive
               - DataTables use Responsive accordion drawers with ZERO horizontal scrolling on mobile */
            .table-responsive > .table:not(.dataTable):not(.dt-mobile-table):not(.mobile-fit-table) {
                min-width: 640px;
            }

            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .dataTables_wrapper,
            .dataTables_wrapper .row > div {
                overflow-x: hidden !important;
                max-width: 100% !important;
            }

            table.table.dataTable,
            table.dataTable,
            table.dt-mobile-table,
            table.mobile-fit-table {
                min-width: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        /* ═════════════════════════════════════════════════════════
           UNIFIED SHOP-STOCK STYLE MOBILE DATATABLE SYSTEM (< 768px)
           Zero horizontal scroll, crisp 2-col summary row + clean drawer
           ═════════════════════════════════════════════════════════ */
        @media (max-width: 767.98px) {
            .dataTables_wrapper,
            .dataTables_wrapper .row,
            .dataTables_wrapper .row > div {
                overflow-x: hidden !important;
                max-width: 100% !important;
            }

            /* Stack DataTables top toolbar & pagination cleanly on phones */
            .dataTables_wrapper .d-flex {
                flex-wrap: wrap !important;
                gap: 0.5rem !important;
            }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                width: 100% !important;
                text-align: left !important;
                margin-bottom: 0.35rem;
            }
            .dataTables_wrapper .dataTables_filter label,
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-left: 0 !important;
            }
            .dataTables_wrapper .dt-buttons {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0.35rem !important;
                width: 100% !important;
            }
            .dataTables_wrapper .dataTables_info {
                font-size: 0.76rem !important;
                text-align: center !important;
                width: 100% !important;
                padding-top: 0.35rem !important;
            }
            .dataTables_wrapper .dataTables_paginate {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                margin-top: 0.4rem !important;
            }
            .dataTables_wrapper .dataTables_paginate .pagination {
                flex-wrap: wrap !important;
                justify-content: center !important;
            }

            table.table.dt-mobile-table,
            table.dt-mobile-table.dataTable,
            table.dt-mobile-table {
                min-width: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
            }

            table.dt-mobile-table th.iter-col,
            table.dt-mobile-table td.iter-col {
                width: 56px !important;
                padding-left: 1.75rem !important;
                padding-right: 0.35rem !important;
                white-space: nowrap !important;
            }

            table.dt-mobile-table th.id-col,
            table.dt-mobile-table td.id-col {
                width: 96px !important;
                padding-left: 1.75rem !important;
                padding-right: 0.4rem !important;
                white-space: nowrap !important;
            }

            table.dt-mobile-table th.compact-lead-col,
            table.dt-mobile-table td.compact-lead-col {
                width: 112px !important;
                padding-left: 1.75rem !important;
                padding-right: 0.45rem !important;
                white-space: normal !important;
                word-break: break-word !important;
            }

            table.dt-mobile-table > tbody > tr > td.dtr-control {
                padding-left: 1.75rem !important;
            }

            table.dt-mobile-table th.primary-col,
            table.dt-mobile-table td.primary-col,
            table.dt-mobile-table th.all:not(.iter-col):not(.id-col):not(.compact-lead-col),
            table.dt-mobile-table td:not(.dtr-control):not(.iter-col):not(.id-col):not(.compact-lead-col) {
                width: auto !important;
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            table.dt-mobile-table td .d-flex {
                min-width: 0;
                max-width: 100%;
            }
            table.dt-mobile-table td .d-flex > div:last-child {
                min-width: 0;
            }

            /* Clean full-width mobile accordion child drawer (like /shop-stock) */
            table.dataTable > tbody > tr.child td.child {
                padding: 0.55rem 0.4rem !important;
                width: 100% !important;
                max-width: 100% !important;
                white-space: normal !important;
            }

            table.dataTable > tbody > tr.child ul.dtr-details {
                width: 100% !important;
                max-width: 100% !important;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 0.25rem 0.75rem !important;
            }

            table.dataTable > tbody > tr.child ul.dtr-details > li {
                padding: 0.52rem 0 !important;
                gap: 0.5rem !important;
                align-items: flex-start !important;
                font-size: 0.82rem !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }

            table.dataTable > tbody > tr.child ul.dtr-details > li:last-child {
                border-bottom: none !important;
                padding-top: 0.6rem !important;
                padding-bottom: 0.4rem !important;
            }

            table.dataTable > tbody > tr.child span.dtr-title {
                min-width: 108px !important;
                width: 108px !important;
                font-size: 0.74rem !important;
                font-weight: 700 !important;
                color: #475569 !important;
                line-height: 1.35 !important;
                padding-top: 0.08rem;
            }

            table.dataTable > tbody > tr.child span.dtr-data {
                flex: 1 1 0% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                font-size: 0.82rem !important;
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
            }

            table.dataTable > tbody > tr.child span.dtr-data > * {
                max-width: 100% !important;
            }

            table.dataTable > tbody > tr.child span.dtr-data .btn,
            table.dataTable > tbody > tr.child span.dtr-data button,
            table.dataTable > tbody > tr.child span.dtr-data a.btn {
                white-space: nowrap !important;
            }

            /* Hide bulk-select checkbox row inside mobile drawer (col 0) */
            table.dt-has-checkbox > tbody > tr.child ul.dtr-details > li[data-dtr-index="0"] {
                display: none !important;
            }

            /* Nested diff tables (e.g. Activity Logs field changes) stay compact */
            table.dataTable > tbody > tr.child table.table,
            table.mobile-fit-table {
                min-width: 0 !important;
                width: 100% !important;
                table-layout: fixed !important;
                font-size: 0.74rem !important;
            }
            table.dataTable > tbody > tr.child table.table th,
            table.dataTable > tbody > tr.child table.table td,
            table.mobile-fit-table th,
            table.mobile-fit-table td {
                padding: 0.35rem 0.4rem !important;
                white-space: normal !important;
                word-break: break-word !important;
            }
        }

        @media (max-width: 399.98px) {
            table.dataTable > tbody > tr.child ul.dtr-details > li {
                flex-direction: column !important;
                gap: 0.2rem !important;
            }
            table.dataTable > tbody > tr.child span.dtr-title {
                width: auto !important;
                min-width: 0 !important;
                font-size: 0.7rem !important;
                text-transform: uppercase !important;
                letter-spacing: 0.03em !important;
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

    {{-- Global Floating Offline / Online Notification Toasts --}}
    <div id="globalOfflineBanner" class="align-items-center justify-content-center gap-2 shadow-lg" style="display: none; position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 9999; font-size: 0.88rem; font-weight: 600; background: linear-gradient(135deg, #dc2626, #be123c); color: #ffffff; border-radius: 50px; padding: 0.7rem 1.5rem; max-width: 620px; width: calc(100% - 32px); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2) !important;">
        <i class="bi bi-wifi-off fs-5 text-white flex-shrink-0"></i>
        <span class="text-truncate">No Internet Connection. Form submissions are disabled until reconnected.</span>
    </div>
    <div id="globalOnlineBanner" class="align-items-center justify-content-center gap-2 shadow-lg" style="display: none; position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 9999; font-size: 0.88rem; font-weight: 600; background: linear-gradient(135deg, #059669, #047857); color: #ffffff; border-radius: 50px; padding: 0.7rem 1.5rem; max-width: 620px; width: calc(100% - 32px); box-shadow: 0 10px 25px rgba(5, 150, 105, 0.4) !important;">
        <i class="bi bi-wifi fs-5 text-white flex-shrink-0"></i>
        <span class="text-truncate">Internet Connection Restored. You can now submit forms.</span>
    </div>

    {{-- ── SIDEBAR ── --}}
    <div id="sidebar">
        <div class="sidebar-brand">
            @if(!empty($appBranding['logo']))
                <img src="{{ $appBranding['logo'] }}" alt="Logo" class="brand-logo-img">
            @else
                <div class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></div>
            @endif
            <div class="brand-text">
                <div class="brand-name">{{ $appBranding['name'] }}</div>
                @if(!empty($appBranding['slogan']))
                    <div class="brand-sub">{{ $appBranding['slogan'] }}</div>
                @endif
                @if(!empty($appBranding['location']))
                    <div class="brand-loc">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $appBranding['location'] }}</span>
                    </div>
                @endif
            </div>

            <button type="button" class="btn-close-sidebar d-md-none" onclick="document.getElementById('sidebar').classList.remove('show')" title="Close Sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav" id="sidebarAccordion">
            <div class="nav-section-label">Main</div>

            <div class="nav-item-custom">
                <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('chats.index') }}" class="nav-link-custom {{ request()->routeIs('chats.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots-fill text-white"></i> Live Chat
                    <span class="nav-badge bg-danger text-white d-none" id="chatGlobalBadge" style="background-color: var(--accent-red) !important; color: #fff !important;">0</span>
                </a>
            </div>

            <div class="nav-item-custom">
                <a href="{{ route('diary.index') }}" class="nav-link-custom {{ request()->routeIs('diary.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-bookmark-fill" style="color:#ffffff;"></i> Daily Diary
                </a>
            </div>

            @if(auth()->user()->isOwner())
            {{-- Products collapsible --}}
            @php
            $isProductsActive = request()->routeIs('items.*') || request()->routeIs('categories.*') || request()->routeIs('units.*') || request()->routeIs('shops.*') || request()->routeIs('users.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isProductsActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#productsCollapseOwner" role="button"
                    aria-expanded="{{ $isProductsActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-box-seam-fill"></i> Products & Shops</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isProductsActive ? 'show' : '' }}" id="productsCollapseOwner" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('items.index') }}" class="nav-link-custom {{ request()->routeIs('items.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                        <a href="{{ route('categories.index') }}" class="nav-link-custom {{ request()->routeIs('categories.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                        <a href="{{ route('units.index') }}" class="nav-link-custom {{ request()->routeIs('units.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-rulers"></i> Units
                        </a>
                        <a href="{{ route('shops.index') }}" class="nav-link-custom {{ request()->routeIs('shops.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shop"></i> Shops
                        </a>
                        <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stocks collapsible --}}
            @php
            $isStocksActive = request()->routeIs('main-stock.*') || request()->routeIs('stock-requests.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('shop-stock.*') || request()->routeIs('reorder.*') || request()->routeIs('shop-swaps.*') || request()->routeIs('product-transfers.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isStocksActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#stocksCollapseOwner" role="button"
                    aria-expanded="{{ $isStocksActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-layers-fill"></i> Stocks & Warehouse</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isStocksActive ? 'show' : '' }}" id="stocksCollapseOwner" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('main-stock.index') }}" class="nav-link-custom {{ request()->routeIs('main-stock.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-building"></i> Main Store Stock
                        </a>
                        <a href="{{ route('reorder.index') }}" class="nav-link-custom {{ request()->routeIs('reorder.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-magic text-warning"></i> Reorder Suggestions
                        </a>
                        <a href="{{ route('shop-swaps.index') }}" class="nav-link-custom {{ request()->routeIs('shop-swaps.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right text-info"></i> Product Swaps
                        </a>
                        <a href="{{ route('product-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('product-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-box-arrow-right text-warning"></i> Product Transfer
                        </a>
                        <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Stock Requests
                            @php $pending = \App\Models\StockRequest::where('status','pending')->count() @endphp
                            @if($pending > 0) <span class="nav-badge">{{ $pending }}</span> @endif
                        </a>
                        <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Transfers
                        </a>
                        <a href="{{ route('shop-stock.finished') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.finished*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-archive-fill"></i> Finished Stocks
                        </a>
                        <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.index') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
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
                <div class="collapse {{ $isSalesActive ? 'show' : '' }}" id="salesCollapseOwner" data-bs-parent="#sidebarAccordion">
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
            $isDocsActive = request()->routeIs('documents.*');
            $docTypeParam = request()->query('type', '');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActive ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseOwner" role="button"
                    aria-expanded="{{ $isDocsActive ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActive ? 'show' : '' }}" id="docsCollapseOwner" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('documents.index') }}" class="nav-link-custom {{ $isDocsActive && $docTypeParam === '' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-folder2-open"></i> All Documents
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'invoice']) }}" class="nav-link-custom {{ $isDocsActive && $docTypeParam === 'invoice' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'proforma']) }}" class="nav-link-custom {{ $isDocsActive && $docTypeParam === 'proforma' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-ruled"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'delivery']) }}" class="nav-link-custom {{ $isDocsActive && $docTypeParam === 'delivery' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
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
                <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="reportsCollapseOwner" data-bs-parent="#sidebarAccordion">
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
                <div class="collapse {{ $isSystemActive ? 'show' : '' }}" id="systemCollapseOwner" data-bs-parent="#sidebarAccordion">
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
                <div class="collapse {{ $isManagementActive ? 'show' : '' }}" id="productsCollapseAdmin" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('shops.show', auth()->user()->shop_id ?? 0) }}" class="nav-link-custom {{ request()->routeIs('shops.show') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-shop"></i> Shop Profile
                        </a>
                        <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-people"></i> My Users
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stocks collapsible --}}
            @php
            $isStocksActiveAdmin = request()->routeIs('shop-stock.*') || request()->routeIs('stock-requests.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('reorder.*') || request()->routeIs('shop-swaps.*') || request()->routeIs('product-transfers.*');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isStocksActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#stocksCollapseAdmin" role="button"
                    aria-expanded="{{ $isStocksActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-layers-fill"></i> Stocks & Transfers</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isStocksActiveAdmin ? 'show' : '' }}" id="stocksCollapseAdmin" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('shop-stock.index') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.index') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-layers"></i> Shop Stock
                        </a>
                        <a href="{{ route('reorder.index') }}" class="nav-link-custom {{ request()->routeIs('reorder.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-magic text-warning"></i> Reorder Suggestions
                        </a>
                        <a href="{{ route('shop-swaps.index') }}" class="nav-link-custom {{ request()->routeIs('shop-swaps.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right text-info"></i> Product Swaps
                            @php $myIncomingSwaps = \App\Models\ShopProductSwap::where('status', 'pending_destination')->where('destination_shop_id', auth()->user()->shop_id)->count() @endphp
                            @if($myIncomingSwaps > 0) <span class="nav-badge bg-warning text-dark">{{ $myIncomingSwaps }}</span> @endif
                        </a>
                        <a href="{{ route('product-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('product-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-box-arrow-right text-warning"></i> Product Transfer
                            @php $myIncomingPt = \Illuminate\Support\Facades\Schema::hasTable('shop_product_transfers') ? \App\Models\ShopProductTransfer::where('status', 'pending')->where('from_shop_id', auth()->user()->shop_id)->count() : 0; @endphp
                            @if($myIncomingPt > 0) <span class="nav-badge bg-warning text-dark">{{ $myIncomingPt }}</span> @endif
                        </a>
                        <a href="{{ route('stock-requests.index') }}" class="nav-link-custom {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-arrow-left-right"></i> Stock Requests
                        </a>
                        <a href="{{ route('stock-transfers.index') }}" class="nav-link-custom {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-truck"></i> Stock Transfers
                            @php $pendingDispatches = \App\Models\StockTransfer::where('to_shop', auth()->user()->shop_id)->whereIn('status', ['pending_receipt','partially_received'])->count() @endphp
                            @if($pendingDispatches > 0) <span class="nav-badge">{{ $pendingDispatches }}</span> @endif
                        </a>
                        <a href="{{ route('shop-stock.finished') }}" class="nav-link-custom {{ request()->routeIs('shop-stock.finished*') ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-archive-fill"></i> Finished Stocks
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
                <div class="collapse {{ $isSalesActiveAdmin ? 'show' : '' }}" id="salesCollapseAdmin" data-bs-parent="#sidebarAccordion">
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
            $isDocsActiveAdmin = request()->routeIs('documents.*');
            $docTypeParamAdmin = request()->query('type', '');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActiveAdmin ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseAdmin" role="button"
                    aria-expanded="{{ $isDocsActiveAdmin ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActiveAdmin ? 'show' : '' }}" id="docsCollapseAdmin" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('documents.index') }}" class="nav-link-custom {{ $isDocsActiveAdmin && $docTypeParamAdmin === '' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-folder2-open"></i> All Documents
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'invoice']) }}" class="nav-link-custom {{ $isDocsActiveAdmin && $docTypeParamAdmin === 'invoice' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'proforma']) }}" class="nav-link-custom {{ $isDocsActiveAdmin && $docTypeParamAdmin === 'proforma' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-ruled"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'delivery']) }}" class="nav-link-custom {{ $isDocsActiveAdmin && $docTypeParamAdmin === 'delivery' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
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
                <div class="collapse {{ $isReportsActiveAdmin ? 'show' : '' }}" id="reportsCollapseAdmin" data-bs-parent="#sidebarAccordion">
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
                <div class="collapse {{ $isSystemActiveAdmin ? 'show' : '' }}" id="systemCollapseAdmin" data-bs-parent="#sidebarAccordion">
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
                <div class="collapse {{ $isStocksActiveSeller ? 'show' : '' }}" id="stocksCollapseSeller" data-bs-parent="#sidebarAccordion">
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
                <div class="collapse {{ $isSalesActiveSeller ? 'show' : '' }}" id="salesCollapseSeller" data-bs-parent="#sidebarAccordion">
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
            $isDocsActiveSeller = request()->routeIs('documents.*');
            $docTypeParamSeller = request()->query('type', '');
            @endphp
            <div class="nav-item-custom">
                <a class="nav-link-custom {{ $isDocsActiveSeller ? 'active' : 'collapsed' }} d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse" href="#docsCollapseSeller" role="button"
                    aria-expanded="{{ $isDocsActiveSeller ? 'true' : 'false' }}" style="cursor:pointer;">
                    <span><i class="bi bi-file-earmark-text-fill"></i> Documents</span>
                    <i class="bi bi-chevron-down" style="font-size:.7rem;transition:transform .2s;"></i>
                </a>
                <div class="collapse {{ $isDocsActiveSeller ? 'show' : '' }}" id="docsCollapseSeller" data-bs-parent="#sidebarAccordion">
                    <div style="padding-left:1.6rem;border-left:2px solid rgba(255,255,255,.2);margin:.25rem 0 .25rem 1rem;">
                        <a href="{{ route('documents.index') }}" class="nav-link-custom {{ $isDocsActiveSeller && $docTypeParamSeller === '' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-folder2-open"></i> All Documents
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'invoice']) }}" class="nav-link-custom {{ $isDocsActiveSeller && $docTypeParamSeller === 'invoice' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-text"></i> Invoices
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'proforma']) }}" class="nav-link-custom {{ $isDocsActiveSeller && $docTypeParamSeller === 'proforma' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
                            <i class="bi bi-file-earmark-ruled"></i> Proforma Quotes
                        </a>
                        <a href="{{ route('documents.index', ['type' => 'delivery']) }}" class="nav-link-custom {{ $isDocsActiveSeller && $docTypeParamSeller === 'delivery' ? 'active' : '' }}" style="font-size:.78rem;padding:.35rem .6rem;">
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
                <div class="collapse {{ $isSystemActiveSeller ? 'show' : '' }}" id="systemCollapseSeller" data-bs-parent="#sidebarAccordion">
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

                {{-- User Profile Dropdown --}}
                @php
                    $navUser = auth()->user();
                    $navUserInitials = collect(explode(' ', trim($navUser->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                        ->implode('') ?: 'U';
                    $navUserRoleLabel = str_replace('_', ' ', strtoupper($navUser->role));
                @endphp
                <div class="dropdown flex-shrink-0" id="userProfileDropdownContainer">
                    <button class="user-profile-badge dropdown-toggle border-0" type="button" id="userProfileDropdownBtn"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            title="Account menu ({{ $navUser->name }})">
                        <span class="upb-avatar-wrap">
                            @if($navUser->avatar_path)
                                <img src="{{ asset('media/' . $navUser->avatar_path) }}" alt="{{ $navUser->name }}" class="upb-avatar-img">
                            @else
                                <span class="upb-avatar-initials">{{ $navUserInitials }}</span>
                            @endif
                            <span class="upb-status-dot" aria-hidden="true"></span>
                        </span>
                        <span class="upb-meta d-none d-md-flex">
                            <span class="upb-name">{{ $navUser->name }}</span>
                            <span class="upb-role">{{ $navUserRoleLabel }}</span>
                        </span>
                        <i class="bi bi-chevron-down upb-chevron"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end user-profile-menu" aria-labelledby="userProfileDropdownBtn">
                        {{-- Identity Header --}}
                        <div class="upm-header">
                            @if($navUser->avatar_path)
                                <img src="{{ asset('media/' . $navUser->avatar_path) }}" alt="{{ $navUser->name }}" class="upm-avatar-img">
                            @else
                                <div class="upm-avatar-initials">{{ $navUserInitials }}</div>
                            @endif
                            <div class="upm-user-info">
                                <div class="upm-user-name">{{ $navUser->name }}</div>
                                <div class="upm-user-email">{{ $navUser->email }}</div>
                                <div class="upm-badges">
                                    <span class="role-pill role-{{ $navUser->role }}">{{ $navUserRoleLabel }}</span>
                                    @if($navUser->shop)
                                        <span class="upm-shop-pill" title="{{ $navUser->shop->shop_name }}">
                                            <i class="bi bi-shop"></i>
                                            <span class="text-truncate">{{ $navUser->shop->shop_name }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Account Actions --}}
                        <div class="upm-list">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item upm-item">
                                <span class="upm-item-icon upm-icon-profile">
                                    <i class="bi bi-person-gear"></i>
                                </span>
                                <span class="upm-item-text">
                                    <span class="upm-item-title d-block">View &amp; Edit Profile</span>
                                    <span class="upm-item-sub d-block">Update name, phone, email &amp; photo</span>
                                </span>
                                <i class="bi bi-chevron-right upm-item-arrow"></i>
                            </a>

                            <a href="{{ route('profile.edit') }}#passwordSection" class="dropdown-item upm-item">
                                <span class="upm-item-icon upm-icon-security">
                                    <i class="bi bi-shield-lock"></i>
                                </span>
                                <span class="upm-item-text">
                                    <span class="upm-item-title d-block">Change Password</span>
                                    <span class="upm-item-sub d-block">Manage personal login credentials</span>
                                </span>
                                <i class="bi bi-chevron-right upm-item-arrow"></i>
                            </a>
                        </div>

                        {{-- Sign Out Footer --}}
                        <div class="upm-footer">
                            <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item upm-item upm-item-danger">
                                    <span class="upm-item-icon upm-icon-logout">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </span>
                                    <span class="upm-item-text">
                                        <span class="upm-item-title d-block">Sign Out</span>
                                        <span class="upm-item-sub d-block">End your active session</span>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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
    <!-- Global Product Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="background: rgba(18, 24, 38, 0.96); backdrop-filter: blur(12px); color: #fff; border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-700 text-truncate me-3" id="zoomImageTitle" style="font-size: 1rem; color: #fff;">Product Image</h6>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle p-0 d-flex align-items-center justify-content-center" onclick="changeImageScale(0.25)" title="Zoom In" style="width: 32px; height: 32px;">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle p-0 d-flex align-items-center justify-content-center" onclick="changeImageScale(-0.25)" title="Zoom Out" style="width: 32px; height: 32px;">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light rounded-circle p-0 d-flex align-items-center justify-content-center" onclick="resetImageScale()" title="Reset Zoom" style="width: 32px; height: 32px;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body text-center p-4 d-flex align-items-center justify-content-center overflow-auto" style="min-height: 350px; max-height: 75vh;">
                    <img id="zoomImageTarget" src="" alt="Product Image" class="img-fluid rounded shadow-lg" style="max-height: 65vh; object-fit: contain; transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1); cursor: grab;">
                </div>
            </div>
        </div>
    </div>

    @if(auth()->check() && auth()->user()->isShopAdmin())
    {{-- ── Shop Admin: Pending Stock Transfer Confirmation Reminder Modal ── --}}
    <div class="modal fade" id="pendingTransferReminderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="pendingTransferReminderLabel" aria-hidden="true" style="z-index: 1080;">
        <div class="modal-dialog modal-dialog-centered modal-lg ptr-modal-dialog">
            <div class="modal-content ptr-modal-content">
                {{-- Executive Header Banner --}}
                <div class="modal-header ptr-modal-header">
                    <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                        <div class="ptr-header-icon">
                            <i class="bi bi-truck"></i>
                            <span class="ptr-pulse-dot" aria-hidden="true"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="ptr-eyebrow-pill">
                                    <i class="bi bi-bell-fill"></i> Action Required
                                </span>
                                <span class="ptr-count-pill" id="ptrPendingCountBadge">1 Pending Dispatch</span>
                            </div>
                            <h6 class="modal-title mb-0 ptr-modal-title" id="pendingTransferReminderLabel">Incoming Stock Awaiting Receipt Confirmation</h6>
                            <p class="ptr-modal-sub mb-0" id="ptrModalSubtitle">Verify physical stock delivered from Main Store and confirm receipt to update branch inventory.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ptr-dismiss-trigger ms-2" aria-label="Dismiss for 3 hours" title="Dismiss for 3 hours"></button>
                </div>

                {{-- Summary KPI Strip --}}
                <div class="ptr-summary-strip">
                    <div class="ptr-summary-cell">
                        <span class="ptr-summary-label"><i class="bi bi-receipt"></i> Pending Dispatches</span>
                        <span class="ptr-summary-val" id="ptrStatDispatches">1 Transfer</span>
                    </div>
                    <div class="ptr-summary-cell">
                        <span class="ptr-summary-label"><i class="bi bi-boxes"></i> Awaiting Products</span>
                        <span class="ptr-summary-val" id="ptrStatItems">0 Items &middot; 0 Pcs</span>
                    </div>
                    <div class="ptr-summary-cell">
                        <span class="ptr-summary-label"><i class="bi bi-hourglass-split"></i> Longest Wait</span>
                        <span class="ptr-summary-val ptr-summary-wait" id="ptrStatOldest">—</span>
                    </div>
                </div>

                {{-- Transfers Detailed Feed --}}
                <div class="modal-body ptr-modal-body">
                    <div id="ptrTransfersList" class="ptr-list"></div>
                </div>

                {{-- Action Footer --}}
                <div class="modal-footer ptr-modal-footer">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="ptr-btn-dismiss ptr-dismiss-trigger" id="btnDismissTransferReminder">
                            <i class="bi bi-clock-history"></i>
                            <span>Dismiss for 3 Hours</span>
                        </button>
                        <span class="ptr-snooze-note d-none d-sm-inline">Re-alerts automatically if unconfirmed</span>
                    </div>
                    <a href="{{ route('stock-transfers.index') }}" class="ptr-btn-primary" id="btnOpenTransferDetails">
                        <span>Review &amp; Confirm Now</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ptr-modal-dialog {
            max-width: 650px;
        }
        .ptr-modal-content {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .28), 0 4px 16px rgba(15, 23, 42, .08);
            overflow: hidden;
            background: #ffffff;
        }
        .ptr-modal-header {
            padding: 1.15rem 1.35rem;
            background: linear-gradient(135deg, #0077b6 0%, #005691 58%, #003a6c 100%);
            border-bottom: none;
            align-items: flex-start;
            color: #ffffff;
        }
        .ptr-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            backdrop-filter: blur(8px);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            position: relative;
        }
        .ptr-pulse-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #f59e0b;
            border: 2px solid #005691;
            animation: ptrPulse 1.6s infinite;
        }
        @keyframes ptrPulse {
            0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, .65); }
            70%  { box-shadow: 0 0 0 9px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .ptr-eyebrow-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .14rem .55rem;
            border-radius: 999px;
            background: rgba(245, 158, 11, .22);
            border: 1px solid rgba(253, 224, 71, .35);
            color: #fef08a;
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .ptr-count-pill {
            display: inline-flex;
            align-items: center;
            padding: .14rem .55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #ffffff;
            font-size: .65rem;
            font-weight: 600;
        }
        .ptr-modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin-top: .32rem;
            letter-spacing: -.01em;
        }
        .ptr-modal-sub {
            font-size: .75rem;
            color: rgba(255, 255, 255, .82);
            margin-top: .16rem;
            line-height: 1.35;
        }

        /* Summary KPI Strip */
        .ptr-summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }
        .ptr-summary-cell {
            padding: .7rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .14rem;
            border-right: 1px solid #f1f5f9;
        }
        .ptr-summary-cell:last-child {
            border-right: none;
        }
        .ptr-summary-label {
            font-size: .65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }
        .ptr-summary-val {
            font-size: .84rem;
            font-weight: 700;
            color: #0f172a;
        }
        .ptr-summary-wait.is-urgent {
            color: #dc2626;
        }
        .ptr-summary-wait.is-warn {
            color: #b45309;
        }

        /* Transfers Body & Cards */
        .ptr-modal-body {
            padding: 1rem 1.25rem;
            max-height: 410px;
            overflow-y: auto;
            background: #f8fafc;
        }
        .ptr-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .ptr-card {
            display: block;
            text-decoration: none;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            overflow: hidden;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .ptr-card:hover {
            border-color: #0077b6;
            box-shadow: 0 8px 22px rgba(0, 119, 182, .12);
            transform: translateY(-1px);
            color: #0f172a;
        }
        .ptr-card-head {
            padding: .8rem 1rem;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            flex-wrap: wrap;
        }
        .ptr-card-id-group {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }
        .ptr-card-num {
            font-size: .88rem;
            font-weight: 700;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .ptr-status-pill {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            padding: .16rem .52rem;
            border-radius: 6px;
            font-size: .65rem;
            font-weight: 700;
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .ptr-status-pill.is-partial {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        .ptr-elapsed-badge {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .22rem .62rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .ptr-elapsed-warn {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .ptr-elapsed-urgent {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Metadata Row inside Card */
        .ptr-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .5rem;
            padding: .65rem 1rem;
            background: #fafbfc;
            border-bottom: 1px solid #f1f5f9;
        }
        .ptr-meta-item {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .ptr-meta-k {
            font-size: .63rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #94a3b8;
        }
        .ptr-meta-v {
            font-size: .75rem;
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: .08rem;
        }

        /* Detailed Manifest Rows */
        .ptr-manifest {
            padding: .55rem 1rem .65rem;
        }
        .ptr-manifest-title {
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ptr-manifest-list {
            display: flex;
            flex-direction: column;
            gap: .38rem;
        }
        .ptr-manifest-row {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .42rem .55rem;
            border-radius: 9px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
        }
        .ptr-prod-thumb {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            color: #64748b;
            font-size: .85rem;
        }
        .ptr-prod-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2px;
        }
        .ptr-prod-info {
            flex: 1;
            min-width: 0;
        }
        .ptr-prod-name {
            font-size: .78rem;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.25;
        }
        .ptr-prod-meta {
            font-size: .68rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ptr-prod-qty {
            flex-shrink: 0;
            text-align: right;
        }
        .ptr-qty-pill {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            padding: .16rem .5rem;
            border-radius: 6px;
            background: rgba(0, 119, 182, .09);
            color: #005691;
            font-size: .72rem;
            font-weight: 700;
        }
        .ptr-price-sub {
            display: block;
            font-size: .65rem;
            color: #64748b;
            margin-top: .1rem;
        }
        .ptr-more-row {
            font-size: .71rem;
            font-weight: 600;
            color: #64748b;
            text-align: center;
            padding: .25rem 0 0;
        }

        /* Card Bottom Action Bar */
        .ptr-card-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .62rem 1rem;
            background: #f0f9ff;
            border-top: 1px solid #e0f2fe;
            font-size: .76rem;
            font-weight: 600;
            color: #005691;
            transition: background-color .15s ease;
        }
        .ptr-card:hover .ptr-card-cta {
            background: #e0f2fe;
        }
        .ptr-card:hover .ptr-card-cta i {
            transform: translateX(3px);
        }
        .ptr-card-cta i {
            transition: transform .15s ease;
        }

        /* Footer */
        .ptr-modal-footer {
            padding: .9rem 1.25rem;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .ptr-snooze-note {
            font-size: .71rem;
            color: #94a3b8;
        }
        .ptr-btn-dismiss {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .48rem .9rem;
            border-radius: 9px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
        }
        .ptr-btn-dismiss:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }
        .ptr-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem 1.15rem;
            border-radius: 9px;
            border: none;
            background: linear-gradient(135deg, #0077b6, #005691);
            color: #ffffff;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 119, 182, .25);
            transition: all .15s ease;
        }
        .ptr-btn-primary:hover {
            background: linear-gradient(135deg, #00669e, #004473);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 119, 182, .32);
        }

        @media (max-width: 575.98px) {
            .ptr-modal-header {
                padding: .95rem 1rem;
            }
            .ptr-summary-strip {
                grid-template-columns: 1fr;
            }
            .ptr-summary-cell {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: .52rem 1rem;
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
            }
            .ptr-summary-cell:last-child {
                border-bottom: none;
            }
            .ptr-meta-grid {
                grid-template-columns: 1fr 1fr;
            }
            .ptr-modal-body {
                padding: .85rem .9rem;
            }
            .ptr-modal-footer {
                padding: .8rem .95rem;
                flex-direction: column-reverse;
                align-items: stretch;
            }
            .ptr-btn-dismiss,
            .ptr-btn-primary {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    {{-- ── Shop Admin: Item Price Change Action Required Popup Modal ── --}}
    <div class="modal fade" id="pendingPriceReminderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="pendingPriceReminderLabel" aria-hidden="true" style="z-index: 1085;">
        <div class="modal-dialog modal-dialog-centered modal-lg ppr-modal-dialog">
            <div class="modal-content ppr-modal-content">
                {{-- Warm Amber Executive Header --}}
                <div class="modal-header ppr-modal-header">
                    <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                        <div class="ppr-header-icon">
                            <i class="bi bi-tags-fill"></i>
                            <span class="ppr-pulse-dot" aria-hidden="true"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="ppr-eyebrow-pill">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Price Action Required
                                </span>
                                <span class="ppr-count-pill" id="pprPendingCountBadge">1 Item Updated</span>
                            </div>
                            <h6 class="modal-title mb-0 ppr-modal-title" id="pendingPriceReminderLabel">Main Store Updated Product Buying Price</h6>
                            <p class="ppr-modal-sub mb-0" id="pprModalSubtitle">Review and update your shop selling price(s) now so affected items stay accurate and ready for sale.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ppr-dismiss-trigger ms-2" aria-label="Remind me in 1 hour" title="Remind me in 1 hour"></button>
                </div>

                {{-- Summary KPI Strip --}}
                <div class="ppr-summary-strip">
                    <div class="ppr-summary-cell">
                        <span class="ppr-summary-label"><i class="bi bi-tag"></i> Affected Products</span>
                        <span class="ppr-summary-val" id="pprStatItems">1 Product</span>
                    </div>
                    <div class="ppr-summary-cell">
                        <span class="ppr-summary-label"><i class="bi bi-boxes"></i> Stock Impacted</span>
                        <span class="ppr-summary-val" id="pprStatQty">0 Pcs</span>
                    </div>
                    <div class="ppr-summary-cell">
                        <span class="ppr-summary-label"><i class="bi bi-shield-exclamation"></i> Sales Status</span>
                        <span class="ppr-summary-val ppr-status-blocked" id="pprStatStatus">Review Needed</span>
                    </div>
                </div>

                {{-- Affected Items List with Inline Quick Price Update --}}
                <div class="modal-body ppr-modal-body">
                    <div id="pprItemsList" class="ppr-list"></div>
                </div>

                {{-- Action Footer --}}
                <div class="modal-footer ppr-modal-footer">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="ppr-btn-dismiss ppr-dismiss-trigger" id="btnDismissPriceReminder">
                            <i class="bi bi-clock-history"></i>
                            <span>Remind Me in 1 Hour</span>
                        </button>
                        <span class="ppr-snooze-note d-none d-sm-inline">Re-alerts automatically if still pending</span>
                    </div>
                    <a href="{{ route('shop-stock.index') }}" class="ppr-btn-primary" id="btnOpenPriceShopStock">
                        <i class="bi bi-shop"></i>
                        <span>Open Shop Stock</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ppr-modal-dialog {
            max-width: 680px;
        }
        .ppr-modal-content {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .28), 0 4px 16px rgba(15, 23, 42, .08);
            overflow: hidden;
            background: #ffffff;
        }
        .ppr-modal-header {
            padding: 1.15rem 1.35rem;
            background: linear-gradient(135deg, #d97706 0%, #b45309 55%, #78350f 100%);
            border-bottom: none;
            align-items: flex-start;
            color: #ffffff;
        }
        .ppr-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .26);
            backdrop-filter: blur(8px);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            position: relative;
        }
        .ppr-pulse-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ef4444;
            border: 2px solid #92400e;
            animation: pprPulse 1.5s infinite;
        }
        @keyframes pprPulse {
            0%   { box-shadow: 0 0 0 0 rgba(239, 68, 68, .75); }
            70%  { box-shadow: 0 0 0 9px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .ppr-eyebrow-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .14rem .55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .2);
            border: 1px solid rgba(255, 255, 255, .32);
            color: #fef3c7;
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .ppr-count-pill {
            display: inline-flex;
            align-items: center;
            padding: .14rem .55rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .24);
            border: 1px solid rgba(255, 255, 255, .2);
            color: #ffffff;
            font-size: .65rem;
            font-weight: 600;
        }
        .ppr-modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin-top: .32rem;
            letter-spacing: -.01em;
        }
        .ppr-modal-sub {
            font-size: .75rem;
            color: rgba(255, 255, 255, .86);
            margin-top: .16rem;
            line-height: 1.35;
        }
        .ppr-summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }
        .ppr-summary-cell {
            padding: .7rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .14rem;
            border-right: 1px solid #f1f5f9;
        }
        .ppr-summary-cell:last-child {
            border-right: none;
        }
        .ppr-summary-label {
            font-size: .65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }
        .ppr-summary-val {
            font-size: .84rem;
            font-weight: 700;
            color: #0f172a;
        }
        .ppr-status-blocked {
            color: #dc2626;
        }
        .ppr-status-review {
            color: #b45309;
        }
        .ppr-modal-body {
            padding: 1rem 1.25rem;
            max-height: 430px;
            overflow-y: auto;
            background: #f8fafc;
        }
        .ppr-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .ppr-card {
            background: #ffffff;
            border: 1px solid #fde68a;
            border-left: 4px solid #d97706;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            transition: border-color .2s ease, opacity .2s ease;
        }
        .ppr-card.is-resolved {
            border-color: #bbf7d0;
            border-left-color: #16a34a;
            background: #f0fdf4;
        }
        .ppr-card-top {
            padding: .8rem 1rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .ppr-thumb {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            color: #b45309;
            font-size: 1.05rem;
        }
        .ppr-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2px;
            background: #ffffff;
        }
        .ppr-prod-info {
            flex: 1;
            min-width: 0;
        }
        .ppr-prod-name {
            font-size: .86rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }
        .ppr-prod-meta {
            font-size: .71rem;
            color: #64748b;
            margin-top: .12rem;
        }
        .ppr-badges {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            flex-shrink: 0;
        }
        .ppr-badge-lock {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .52rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 700;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .ppr-badge-pending {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .52rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 700;
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .ppr-badge-stock {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .52rem;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        .ppr-prices-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .5rem;
            padding: .65rem 1rem;
            background: #fffdf7;
            border-bottom: 1px solid #f1f5f9;
        }
        .ppr-price-box {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .ppr-price-k {
            font-size: .63rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
        }
        .ppr-price-v {
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: .08rem;
        }
        .ppr-price-v.is-new-bp {
            color: #b45309;
        }
        .ppr-price-v.is-loss {
            color: #dc2626;
        }
        .ppr-action-bar {
            padding: .7rem 1rem;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            flex-wrap: wrap;
        }
        .ppr-action-input-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex: 1;
            min-width: 220px;
        }
        .ppr-action-input-wrap label {
            font-size: .72rem;
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
            margin-bottom: 0;
        }
        .ppr-input-group {
            display: flex;
            align-items: stretch;
            flex: 1;
            max-width: 210px;
        }
        .ppr-input-prefix {
            display: inline-flex;
            align-items: center;
            padding: 0 .55rem;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-right: none;
            border-radius: 8px 0 0 8px;
            font-size: .72rem;
            font-weight: 700;
            color: #475569;
        }
        .ppr-price-input {
            width: 100%;
            padding: .36rem .6rem;
            border: 1px solid #cbd5e1;
            border-radius: 0 8px 8px 0;
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
        }
        .ppr-price-input:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, .16);
        }
        .ppr-save-price-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem .9rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #ffffff;
            font-size: .76rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(180, 83, 9, .24);
            transition: all .15s ease;
        }
        .ppr-save-price-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #b45309, #92400e);
            transform: translateY(-1px);
        }
        .ppr-save-price-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }
        .ppr-card-error {
            width: 100%;
            font-size: .72rem;
            font-weight: 600;
            color: #dc2626;
            margin-top: .25rem;
            display: none;
        }
        .ppr-resolved-banner {
            padding: .68rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            color: #15803d;
            font-size: .78rem;
            font-weight: 700;
        }
        .ppr-modal-footer {
            padding: .9rem 1.25rem;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }
        .ppr-snooze-note {
            font-size: .71rem;
            color: #94a3b8;
        }
        .ppr-btn-dismiss {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .48rem .9rem;
            border-radius: 9px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
        }
        .ppr-btn-dismiss:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }
        .ppr-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem 1.1rem;
            border-radius: 9px;
            border: none;
            background: linear-gradient(135deg, #0077b6, #005691);
            color: #ffffff;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 119, 182, .25);
            transition: all .15s ease;
        }
        .ppr-btn-primary:hover {
            background: linear-gradient(135deg, #00669e, #004473);
            color: #ffffff;
            transform: translateY(-1px);
        }

        @media (max-width: 575.98px) {
            .ppr-modal-header { padding: .95rem 1rem; }
            .ppr-summary-strip { grid-template-columns: 1fr; }
            .ppr-summary-cell {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: .52rem 1rem;
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
            }
            .ppr-summary-cell:last-child { border-bottom: none; }
            .ppr-prices-grid { grid-template-columns: 1fr 1fr; }
            .ppr-modal-body { padding: .85rem .9rem; }
            .ppr-action-bar { flex-direction: column; align-items: stretch; }
            .ppr-action-input-wrap { min-width: 0; }
            .ppr-input-group { max-width: none; }
            .ppr-save-price-btn { justify-content: center; width: 100%; }
            .ppr-modal-footer {
                padding: .8rem .95rem;
                flex-direction: column-reverse;
                align-items: stretch;
            }
            .ppr-btn-dismiss,
            .ppr-btn-primary { justify-content: center; width: 100%; }
        }
    </style>
    @endif

    <style>
        .product-img-wrapper:hover .img-zoom-overlay {
            opacity: 1 !important;
        }

        .product-img-wrapper:hover .product-img-thumb {
            transform: scale(1.2) !important;
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
        let currentImageScale = 1.0;

        function zoomProductImage(src, title) {
            if (!src) return;
            $('#zoomImageTitle').text(title || 'Product Image');
            $('#zoomImageTarget').attr('src', src);
            currentImageScale = 1.0;
            $('#zoomImageTarget').css('transform', 'scale(1)');
            const modalElement = document.getElementById('imageZoomModal');
            if (modalElement) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            }
        }

        function changeImageScale(delta) {
            currentImageScale = Math.max(0.5, Math.min(3.5, currentImageScale + delta));
            $('#zoomImageTarget').css('transform', `scale(${currentImageScale})`);
        }

        function resetImageScale() {
            currentImageScale = 1.0;
            $('#zoomImageTarget').css('transform', 'scale(1)');
        }

        function openLightbox(src, caption) {
            zoomProductImage(src, caption);
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables & Responsive -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
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

        // Global DataTable defaults (Responsive Accordion Child Row Drawer Pattern)
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true,
            autoWidth: false,
            language: {
                search: '',
                searchPlaceholder: 'Search records...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total)',
                emptyTable: 'No records available',
                zeroRecords: 'No matching records found',
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>'
                }
            },
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            order: [],
            dom: "<'row mb-3 align-items-center g-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'row'<'col-sm-12'tr>><'row mt-3 align-items-center g-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [{
                orderable: false,
                targets: 'no-sort'
            }]
        });

        // SweetAlert confirm for delete/reject forms (event delegation handles static & dynamic DataTables elements)
        $(document).on('click', '[data-confirm]', function(e) {
            e.preventDefault();
            const btn = this;
            const form = btn.closest('form') || document.getElementById(btn.dataset.form);
            if (!form) return;
            Swal.fire({
                title: btn.dataset.confirm || 'Are you sure?',
                text: btn.dataset.text || 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e94560',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: btn.dataset.confirmBtn || 'Yes, proceed',
                background: '#ffffff',
                color: '#0f172a',
            }).then(result => {
                if (result.isConfirmed) form.submit();
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

        // ─── 15-MINUTE INACTIVITY AUTO-LOGOUT & IDLE POLLING MANAGEMENT ──────
        (function() {
            const FIFTEEN_MINUTES_MS = 15 * 60 * 1000; // 900,000 ms (15 minutes)
            const IDLE_PAUSE_MS = 3 * 60 * 1000; // 3 minutes idle to pause background polling
            const STORAGE_KEY = 'amstroom_last_activity_time';

            function updateActivity() {
                localStorage.setItem(STORAGE_KEY, Date.now().toString());
            }

            if (!localStorage.getItem(STORAGE_KEY)) {
                updateActivity();
            }

            let lastUpdate = 0;

            function onUserAction() {
                const now = Date.now();
                if (now - lastUpdate > 3000) {
                    lastUpdate = now;
                    updateActivity();
                }
            }

            ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(evt) {
                window.addEventListener(evt, onUserAction, {
                    passive: true
                });
            });

            $(document).ajaxError(function(event, jqXHR) {
                if (jqXHR.status === 401) {
                    window.location.href = "{{ route('login') }}?timeout=1";
                }
            });

            setInterval(function() {
                const lastActivity = parseInt(localStorage.getItem(STORAGE_KEY) || Date.now());
                const inactiveMs = Date.now() - lastActivity;

                if (inactiveMs >= FIFTEEN_MINUTES_MS) {
                    window.location.href = "{{ route('login') }}?timeout=1";
                }
            }, 5000);

            window.isUserActiveForPolling = function() {
                const lastActivity = parseInt(localStorage.getItem(STORAGE_KEY) || Date.now());
                const inactiveMs = Date.now() - lastActivity;
                return !document.hidden && (inactiveMs < IDLE_PAUSE_MS);
            };
        })();

        // Run poll on load and periodically when active
        $(document).ready(function() {
            window.pollNotifications();
            setInterval(function() {
                if (window.isUserActiveForPolling()) {
                    window.pollNotifications();
                }
            }, 10000);

            window.pollUnreadChatCount();
            setInterval(function() {
                if (window.isUserActiveForPolling()) {
                    window.pollUnreadChatCount();
                }
            }, 8000);
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

        // ── Global Network Status & Form Submission Guard ──
        (function() {
            let onlineTimer = null;

            function updateNetworkStatus() {
                const isOnline = navigator.onLine;
                const offlineBanner = document.getElementById('globalOfflineBanner');
                const onlineBanner = document.getElementById('globalOnlineBanner');
                const submitButtons = document.querySelectorAll('form button[type="submit"], form input[type="submit"], button.btn-submit, [data-action="submit"]');

                if (onlineTimer) {
                    clearTimeout(onlineTimer);
                    onlineTimer = null;
                }

                if (!isOnline) {
                    if (offlineBanner) offlineBanner.style.display = 'flex';
                    if (onlineBanner) onlineBanner.style.display = 'none';

                    submitButtons.forEach(btn => {
                        if (!btn.disabled) {
                            btn.dataset.disabledByOffline = "true";
                            btn.disabled = true;
                        }
                    });
                } else {
                    const wasOffline = offlineBanner && offlineBanner.dataset.wasOffline === "true";
                    if (offlineBanner) offlineBanner.style.display = 'none';

                    if (onlineBanner && wasOffline) {
                        onlineBanner.style.display = 'flex';
                        onlineTimer = setTimeout(() => {
                            onlineBanner.style.display = 'none';
                        }, 3500);
                    }

                    submitButtons.forEach(btn => {
                        if (btn.dataset.disabledByOffline === "true") {
                            delete btn.dataset.disabledByOffline;
                            btn.disabled = false;
                        }
                    });
                }

                if (offlineBanner) {
                    offlineBanner.dataset.wasOffline = (!isOnline).toString();
                }
            }

            window.addEventListener('online', updateNetworkStatus);
            window.addEventListener('offline', updateNetworkStatus);
            document.addEventListener('DOMContentLoaded', updateNetworkStatus);

            // Global Form Double-Submit & Offline Guard for all forms across the app
            $(document).on('submit', 'form', function(e) {
                const form = this;

                // Check offline status first and restrict submission
                if (!navigator.onLine) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'No Internet Connection',
                            text: 'Form submission is restricted because you are currently offline. Please reconnect to the internet and try again.',
                            confirmButtonColor: '#dc2626',
                            background: '#161b22',
                            color: '#e6edf3'
                        });
                    } else {
                        alert('No Internet Connection. Form submission is restricted until your internet connection is restored.');
                    }
                    return false;
                }

                // Prevent double submission if already submitting
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }

                form.dataset.submitting = 'true';

                const submitBtn = $(form).find('button[type="submit"], input[type="submit"]').first();
                if (submitBtn.length) {
                    setTimeout(function() {
                        submitBtn.prop('disabled', true);
                        if (submitBtn.is('button') && !submitBtn.find('.spinner-border').length) {
                            submitBtn.prepend('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>');
                        }
                    }, 50);
                }

                // Safety timeout: reset after 10s if page does not unload (e.g., validation error response)
                setTimeout(function() {
                    delete form.dataset.submitting;
                    if (submitBtn.length) {
                        submitBtn.prop('disabled', false);
                        submitBtn.find('.spinner-border').remove();
                    }
                }, 10000);
            });
        })();

        @if(auth()->check() && auth()->user()->isShopAdmin())
        // ── Shop Admin: Pending Stock Transfer Confirmation Reminder Popup ──
        (function() {
            const USER_ID = {{ (int) auth()->id() }};
            const SNOOZE_HOURS = 3;
            const SNOOZE_MS = SNOOZE_HOURS * 60 * 60 * 1000;
            const STORAGE_KEY = 'amstroom_transfer_reminder_snooze_u' + USER_ID;
            const POLL_INTERVAL_MS = 30000;

            let currentDueTransfers = [];
            let currentRingtoneUrl = null;
            let isModalOpen = false;
            let liveElapsedTimer = null;
            let pendingAudioUnlock = false;

            function escapeHtmlPtr(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getLocalSnoozes() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    const parsed = raw ? JSON.parse(raw) : {};
                    const now = Date.now();
                    const cleaned = {};
                    Object.keys(parsed).forEach(function(id) {
                        if (parsed[id] && parsed[id] > now) {
                            cleaned[id] = parsed[id];
                        }
                    });
                    return cleaned;
                } catch (e) {
                    return {};
                }
            }

            function saveLocalSnoozes(ids) {
                try {
                    const map = getLocalSnoozes();
                    const until = Date.now() + SNOOZE_MS;
                    ids.forEach(function(id) {
                        map[String(id)] = until;
                    });
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
                } catch (e) {
                    // ignore storage errors
                }
            }

            function formatElapsedClient(seconds) {
                seconds = Math.max(0, Math.floor(seconds));
                if (seconds < 60) return 'Just now';
                const days = Math.floor(seconds / 86400);
                const hours = Math.floor((seconds % 86400) / 3600);
                const mins = Math.floor((seconds % 3600) / 60);
                const parts = [];
                if (days > 0) parts.push(days + ' ' + (days === 1 ? 'day' : 'days'));
                if (hours > 0) parts.push(hours + ' ' + (hours === 1 ? 'hr' : 'hrs'));
                if (days === 0 && mins > 0) parts.push(mins + ' ' + (mins === 1 ? 'min' : 'mins'));
                return parts.join(', ') || 'Less than a minute';
            }

            function scheduleChimeNotes(ctx) {
                const notes = [
                    { freq: 880,  delay: 0,    dur: 0.25 },
                    { freq: 1175, delay: 0.14, dur: 0.28 },
                    { freq: 1318, delay: 0.28, dur: 0.42 }
                ];
                notes.forEach(function(n) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    const startAt = ctx.currentTime + n.delay;
                    osc.frequency.setValueAtTime(n.freq, startAt);
                    gain.gain.setValueAtTime(0.001, startAt);
                    gain.gain.linearRampToValueAtTime(0.28, startAt + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, startAt + n.dur);
                    osc.start(startAt);
                    osc.stop(startAt + n.dur);
                });
            }

            function playReminderChime(fallbackRingtoneUrl) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    if (ctx.state === 'running') {
                        scheduleChimeNotes(ctx);
                    } else {
                        ctx.resume().then(function() {
                            if (ctx.state === 'running') {
                                scheduleChimeNotes(ctx);
                            } else {
                                scheduleSoundOnFirstGesture(fallbackRingtoneUrl || null);
                            }
                        }).catch(function() {
                            scheduleSoundOnFirstGesture(fallbackRingtoneUrl || null);
                        });
                    }
                } catch (e) {
                    scheduleSoundOnFirstGesture(fallbackRingtoneUrl || null);
                }
            }

            function playTransferReminderSound(ringtoneUrl) {
                if (ringtoneUrl) {
                    const audio = new Audio(ringtoneUrl);
                    audio.play().catch(function() {
                        playReminderChime(ringtoneUrl);
                    });
                } else {
                    playReminderChime(null);
                }
            }

            function scheduleSoundOnFirstGesture(ringtoneUrl) {
                if (pendingAudioUnlock) return;
                pendingAudioUnlock = true;
                const unlockHandler = function() {
                    pendingAudioUnlock = false;
                    document.removeEventListener('pointerdown', unlockHandler);
                    document.removeEventListener('keydown', unlockHandler);
                    if (isModalOpen) {
                        playTransferReminderSound(ringtoneUrl);
                    }
                };
                document.addEventListener('pointerdown', unlockHandler, { once: true });
                document.addEventListener('keydown', unlockHandler, { once: true });
            }

            function isOnStockTransfersPage() {
                if ({{ request()->routeIs('stock-transfers.*') ? 'true' : 'false' }}) {
                    return true;
                }
                return /^\/stock-transfers(?:\/|$)/i.test(window.location.pathname || '');
            }

            function getCurrentViewingTransferId() {
                const match = window.location.pathname.match(/\/stock-transfers\/(\d+)(?:\/|$)/);
                return match ? parseInt(match[1], 10) : null;
            }

            function formatTzsPtr(amount) {
                if (amount === null || amount === undefined || isNaN(amount)) return '';
                return 'TZS ' + Number(amount).toLocaleString('en-US', { maximumFractionDigits: 0 });
            }

            function renderTransfers(transfers, indexUrl, shopName) {
                const $list = $('#ptrTransfersList');
                const count = transfers.length;
                const totalPendingItems = transfers.reduce(function(acc, t) { return acc + (t.pending_items_count || 0); }, 0);
                const totalPendingQty = transfers.reduce(function(acc, t) { return acc + (t.pending_total_qty || 0); }, 0);
                const oldestTransfer = transfers[0];

                $('#ptrPendingCountBadge').text(count + ' Pending ' + (count === 1 ? 'Dispatch' : 'Dispatches'));
                if (shopName) {
                    $('#ptrModalSubtitle').text(shopName + ' — Verify physical delivery from Main Store and confirm items to update branch inventory.');
                }

                $('#ptrStatDispatches').text(count + ' ' + (count === 1 ? 'Transfer' : 'Transfers'));
                $('#ptrStatItems').text(totalPendingItems + ' ' + (totalPendingItems === 1 ? 'Item' : 'Items') + ' · ' + totalPendingQty + ' Pcs');

                if (oldestTransfer) {
                    const $oldestEl = $('#ptrStatOldest');
                    $oldestEl
                        .text(oldestTransfer.elapsed_human)
                        .attr('data-oldest-ts', oldestTransfer.submitted_at_ts || 0)
                        .removeClass('is-warn is-urgent')
                        .addClass((oldestTransfer.elapsed_seconds || 0) >= 3 * 3600 ? 'is-urgent' : 'is-warn');
                }

                if (count === 1) {
                    $('#btnOpenTransferDetails')
                        .attr('href', transfers[0].url)
                        .find('span').text('Inspect & Confirm Transfer #' + transfers[0].id);
                } else {
                    $('#btnOpenTransferDetails')
                        .attr('href', indexUrl)
                        .find('span').text('Review All Pending (' + count + ')');
                }

                let html = '';
                transfers.forEach(function(t) {
                    const elapsedSec = t.elapsed_seconds || 0;
                    const isUrgent = elapsedSec >= 3 * 3600;
                    const elapsedClass = isUrgent ? 'ptr-elapsed-urgent' : 'ptr-elapsed-warn';
                    const isPartial = t.status === 'partially_received';
                    const statusLabel = isPartial ? 'Partially Received' : 'Pending Receipt';
                    const confirmedProgress = (t.received_items_count || 0) + ' / ' + (t.total_items_count || t.pending_items_count) + ' confirmed';

                    let itemsHtml = '';
                    (t.items_preview || []).forEach(function(it) {
                        const thumbHtml = it.image_url
                            ? `<img src="${escapeHtmlPtr(it.image_url)}" alt="${escapeHtmlPtr(it.name)}">`
                            : `<i class="bi bi-box-seam"></i>`;
                        const priceHtml = it.selling_price !== null
                            ? `<span class="ptr-price-sub">${formatTzsPtr(it.selling_price)} / unit</span>`
                            : '';
                        const metaHtml = it.meta
                            ? `<div class="ptr-prod-meta">${escapeHtmlPtr(it.meta)}</div>`
                            : '';

                        itemsHtml += `
                            <div class="ptr-manifest-row">
                                <div class="ptr-prod-thumb">${thumbHtml}</div>
                                <div class="ptr-prod-info">
                                    <div class="ptr-prod-name">${escapeHtmlPtr(it.name)}</div>
                                    ${metaHtml}
                                </div>
                                <div class="ptr-prod-qty">
                                    <span class="ptr-qty-pill">Qty: ${it.qty} ${escapeHtmlPtr(it.unit || 'pcs')}</span>
                                    ${priceHtml}
                                </div>
                            </div>
                        `;
                    });

                    if (t.extra_items_count > 0) {
                        itemsHtml += `<div class="ptr-more-row">+ ${t.extra_items_count} additional product${t.extra_items_count > 1 ? 's' : ''} inside this transfer</div>`;
                    }

                    html += `
                        <a href="${t.url}" class="ptr-card" data-transfer-id="${t.id}" data-submitted-ts="${t.submitted_at_ts || 0}">
                            <div class="ptr-card-head">
                                <div class="ptr-card-id-group">
                                    <span class="ptr-card-num">
                                        <i class="bi bi-receipt-cutoff text-primary"></i>
                                        Transfer #${t.id}
                                    </span>
                                    <span class="ptr-status-pill ${isPartial ? 'is-partial' : ''}">
                                        <i class="bi ${isPartial ? 'bi-pie-chart-fill' : 'bi-hourglass-split'}"></i>
                                        ${statusLabel}
                                    </span>
                                </div>
                                <span class="ptr-elapsed-badge ${elapsedClass}" data-elapsed-badge="1">
                                    <i class="bi bi-clock-fill"></i>
                                    <span class="ptr-elapsed-text">Waiting ${escapeHtmlPtr(t.elapsed_human)}</span>
                                </span>
                            </div>

                            <div class="ptr-meta-grid">
                                <div class="ptr-meta-item">
                                    <span class="ptr-meta-k">Dispatched By</span>
                                    <span class="ptr-meta-v" title="${escapeHtmlPtr(t.dispatched_by)}">${escapeHtmlPtr(t.dispatched_by)}</span>
                                </div>
                                <div class="ptr-meta-item">
                                    <span class="ptr-meta-k">Submitted On</span>
                                    <span class="ptr-meta-v" title="${escapeHtmlPtr(t.submitted_at_display)}">${escapeHtmlPtr(t.submitted_at_display)}</span>
                                </div>
                                <div class="ptr-meta-item">
                                    <span class="ptr-meta-k">Receipt Progress</span>
                                    <span class="ptr-meta-v">${confirmedProgress}</span>
                                </div>
                            </div>

                            <div class="ptr-manifest">
                                <div class="ptr-manifest-title">
                                    <span>Products Awaiting Confirmation</span>
                                    <span>${t.pending_items_count} item${t.pending_items_count > 1 ? 's' : ''} &middot; ${t.pending_total_qty} pcs</span>
                                </div>
                                <div class="ptr-manifest-list">${itemsHtml}</div>
                            </div>

                            <div class="ptr-card-cta">
                                <span>Open Transfer #${t.id} to inspect &amp; confirm receipt</span>
                                <i class="bi bi-arrow-right"></i>
                            </div>
                        </a>
                    `;
                });

                $list.html(html);
            }

            function refreshLiveElapsedLabels(serverClockOffsetSec) {
                const nowSec = Math.floor(Date.now() / 1000) + serverClockOffsetSec;
                $('#ptrTransfersList .ptr-card').each(function() {
                    const submittedTs = parseInt($(this).attr('data-submitted-ts'), 10);
                    if (!submittedTs) return;
                    const diff = Math.max(0, nowSec - submittedTs);
                    const $badge = $(this).find('[data-elapsed-badge]');
                    $badge.find('.ptr-elapsed-text').text('Waiting ' + formatElapsedClient(diff));
                    if (diff >= 3 * 3600) {
                        $badge.removeClass('ptr-elapsed-warn').addClass('ptr-elapsed-urgent');
                    }
                });

                const $oldestEl = $('#ptrStatOldest');
                const oldestTs = parseInt($oldestEl.attr('data-oldest-ts'), 10);
                if (oldestTs) {
                    const oldestDiff = Math.max(0, nowSec - oldestTs);
                    $oldestEl
                        .text(formatElapsedClient(oldestDiff))
                        .removeClass('is-warn is-urgent')
                        .addClass(oldestDiff >= 3 * 3600 ? 'is-urgent' : 'is-warn');
                }
            }

            function showReminderToast(message) {
                const $toast = $(`
                    <div class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" style="position:fixed;bottom:22px;right:22px;z-index:1090;border-radius:10px;font-size:.8rem;">
                        <div class="d-flex">
                            <div class="toast-body"><i class="bi bi-clock-history text-warning me-2"></i>${escapeHtmlPtr(message)}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `);
                $('body').append($toast);
                const bsToast = new bootstrap.Toast($toast[0], { delay: 3500 });
                bsToast.show();
                $toast[0].addEventListener('hidden.bs.toast', function() { $toast.remove(); });
            }

            function checkPendingTransferReminders() {
                if (isOnStockTransfersPage()) return;
                if (isModalOpen) return;
                // Do not stack over another open modal
                if ($('.modal.show').length > 0) return;

                $.ajax({
                    url: "{{ route('stock-transfers.pending-reminders') }}",
                    type: 'GET',
                    success: function(response) {
                        const rawDue = response.due_transfers || [];
                        const localSnoozes = getLocalSnoozes();
                        const viewingTransferId = getCurrentViewingTransferId();
                        const serverOffset = (response.server_now_ts || Math.floor(Date.now() / 1000)) - Math.floor(Date.now() / 1000);

                        const dueTransfers = rawDue.filter(function(t) {
                            if (viewingTransferId && t.id === viewingTransferId) return false;
                            const snoozedUntil = localSnoozes[String(t.id)];
                            return !snoozedUntil || Date.now() >= snoozedUntil;
                        });

                        if (dueTransfers.length === 0) return;

                        currentDueTransfers = dueTransfers;
                        currentRingtoneUrl = response.ringtone_url || null;

                        renderTransfers(dueTransfers, response.index_url || "{{ route('stock-transfers.index') }}", response.shop_name || '');

                        const modalEl = document.getElementById('pendingTransferReminderModal');
                        if (!modalEl) return;

                        isModalOpen = true;
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                        playTransferReminderSound(currentRingtoneUrl);

                        if (liveElapsedTimer) clearInterval(liveElapsedTimer);
                        liveElapsedTimer = setInterval(function() {
                            if (isModalOpen) refreshLiveElapsedLabels(serverOffset);
                        }, 30000);
                    }
                });
            }

            $(document).on('click', '.ptr-dismiss-trigger', function() {
                const ids = currentDueTransfers.map(function(t) { return t.id; });
                if (ids.length > 0) {
                    saveLocalSnoozes(ids);
                    $.ajax({
                        url: "{{ route('stock-transfers.pending-reminders.dismiss') }}",
                        type: 'POST',
                        data: {
                            transfer_ids: ids,
                            _token: "{{ csrf_token() }}"
                        }
                    });
                }

                const modalEl = document.getElementById('pendingTransferReminderModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                showReminderToast('Reminder dismissed. We will remind you again in 3 hours if still unconfirmed.');
            });

            const reminderModalEl = document.getElementById('pendingTransferReminderModal');
            if (reminderModalEl) {
                reminderModalEl.addEventListener('hidden.bs.modal', function() {
                    isModalOpen = false;
                    if (liveElapsedTimer) {
                        clearInterval(liveElapsedTimer);
                        liveElapsedTimer = null;
                    }
                    if (typeof window.checkPendingPriceReminders === 'function') {
                        setTimeout(window.checkPendingPriceReminders, 450);
                    }
                });
            }

            $(document).ready(function() {
                if (isOnStockTransfersPage()) return;
                setTimeout(checkPendingTransferReminders, 900);
                setInterval(function() {
                    if (isOnStockTransfersPage()) return;
                    if (typeof window.isUserActiveForPolling !== 'function' || window.isUserActiveForPolling()) {
                        checkPendingTransferReminders();
                    }
                }, POLL_INTERVAL_MS);
            });
        })();

        // ── Shop Admin: Item Price Change Action Required Popup ──
        (function() {
            const USER_ID = {{ (int) auth()->id() }};
            const SNOOZE_MS = 60 * 60 * 1000; // 1 hour
            const STORAGE_KEY = 'amstroom_price_reminder_snooze_u' + USER_ID;
            const NAV_SKIP_KEY = 'amstroom_price_popup_nav_skip_u' + USER_ID;
            const POLL_INTERVAL_MS = 15000;

            let currentDueItems = [];
            let currentRingtoneUrl = null;
            let isPriceModalOpen = false;
            let pendingPriceAudioUnlock = false;

            function escapeHtmlPpr(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatTzsPpr(amount) {
                if (amount === null || amount === undefined || isNaN(amount)) return 'TZS 0';
                return 'TZS ' + Number(amount).toLocaleString('en-US', { maximumFractionDigits: 0 });
            }

            function formatNumPpr(amount) {
                if (amount === null || amount === undefined || isNaN(amount)) return '';
                return Number(amount).toLocaleString('en-US', { maximumFractionDigits: 0 });
            }

            function getPriceSnoozeKey(itemId, updatedAtTs) {
                return String(itemId) + '_' + String(updatedAtTs);
            }

            function getLocalPriceSnoozes() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    const parsed = raw ? JSON.parse(raw) : {};
                    const now = Date.now();
                    const cleaned = {};
                    Object.keys(parsed).forEach(function(k) {
                        if (parsed[k] && parsed[k] > now) {
                            cleaned[k] = parsed[k];
                        }
                    });
                    return cleaned;
                } catch (e) {
                    return {};
                }
            }

            function saveLocalPriceSnoozes(items) {
                try {
                    const map = getLocalPriceSnoozes();
                    const until = Date.now() + SNOOZE_MS;
                    items.forEach(function(it) {
                        map[getPriceSnoozeKey(it.item_id, it.updated_at_ts)] = until;
                    });
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
                } catch (e) {
                    // ignore storage errors
                }
            }

            function playPriceAlertChime(fallbackRingtoneUrl) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    const runNotes = function() {
                        const notes = [
                            { freq: 784,  delay: 0,    dur: 0.22 },
                            { freq: 1047, delay: 0.14, dur: 0.24 },
                            { freq: 1318, delay: 0.28, dur: 0.36 }
                        ];
                        notes.forEach(function(n) {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = 'triangle';
                            const startAt = ctx.currentTime + n.delay;
                            osc.frequency.setValueAtTime(n.freq, startAt);
                            gain.gain.setValueAtTime(0.001, startAt);
                            gain.gain.linearRampToValueAtTime(0.24, startAt + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.001, startAt + n.dur);
                            osc.start(startAt);
                            osc.stop(startAt + n.dur);
                        });
                    };

                    if (ctx.state === 'running') {
                        runNotes();
                    } else {
                        ctx.resume().then(function() {
                            if (ctx.state === 'running') runNotes();
                            else schedulePriceSoundOnGesture(fallbackRingtoneUrl || null);
                        }).catch(function() {
                            schedulePriceSoundOnGesture(fallbackRingtoneUrl || null);
                        });
                    }
                } catch (e) {
                    schedulePriceSoundOnGesture(fallbackRingtoneUrl || null);
                }
            }

            function playPriceAlertSound(ringtoneUrl) {
                if (ringtoneUrl) {
                    const audio = new Audio(ringtoneUrl);
                    audio.play().catch(function() {
                        playPriceAlertChime(ringtoneUrl);
                    });
                } else {
                    playPriceAlertChime(null);
                }
            }

            function schedulePriceSoundOnGesture(ringtoneUrl) {
                if (pendingPriceAudioUnlock) return;
                pendingPriceAudioUnlock = true;
                const unlock = function() {
                    pendingPriceAudioUnlock = false;
                    document.removeEventListener('pointerdown', unlock);
                    document.removeEventListener('keydown', unlock);
                    if (isPriceModalOpen) {
                        playPriceAlertSound(ringtoneUrl);
                    }
                };
                document.addEventListener('pointerdown', unlock, { once: true });
                document.addEventListener('keydown', unlock, { once: true });
            }

            function showPriceToast(message, isSuccess) {
                const iconClass = isSuccess ? 'bi-check-circle-fill text-success' : 'bi-clock-history text-warning';
                const $toast = $(`
                    <div class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" style="position:fixed;bottom:22px;right:22px;z-index:1095;border-radius:10px;font-size:.8rem;">
                        <div class="d-flex">
                            <div class="toast-body"><i class="bi ${iconClass} me-2"></i>${escapeHtmlPpr(message)}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `);
                $('body').append($toast);
                const bsToast = new bootstrap.Toast($toast[0], { delay: 3800 });
                bsToast.show();
                $toast[0].addEventListener('hidden.bs.toast', function() { $toast.remove(); });
            }

            function updatePriceModalStats(items, shopName) {
                const count = items.length;
                const totalQty = items.reduce(function(acc, it) { return acc + (it.remaining_qty || 0); }, 0);
                const totalBatches = items.reduce(function(acc, it) { return acc + (it.batches_count || 1); }, 0);
                const anyBlocked = items.some(function(it) { return !it.is_sellable; });

                $('#pprPendingCountBadge').text(count + ' ' + (count === 1 ? 'Item Updated' : 'Items Updated'));
                $('#pprStatItems').text(count + ' ' + (count === 1 ? 'Product' : 'Products'));
                $('#pprStatQty').text(totalQty + ' Pcs · ' + totalBatches + ' ' + (totalBatches === 1 ? 'Batch' : 'Batches'));

                const $statusEl = $('#pprStatStatus');
                if (anyBlocked) {
                    $statusEl.text('Sales Blocked on POS').removeClass('ppr-status-review').addClass('ppr-status-blocked');
                } else {
                    $statusEl.text('Approval Required').removeClass('ppr-status-blocked').addClass('ppr-status-review');
                }

                if (shopName) {
                    $('#pprModalSubtitle').text(shopName + ' — Main Store updated product price(s). Update your selling price below so this item is ready before a sale.');
                }
            }

            function renderPriceItems(items, indexUrl, shopName) {
                const $list = $('#pprItemsList');
                updatePriceModalStats(items, shopName);
                $('#btnOpenPriceShopStock').attr('href', indexUrl);

                let html = '';
                items.forEach(function(it) {
                    const thumbHtml = it.image_url
                        ? `<img src="${escapeHtmlPpr(it.image_url)}" alt="${escapeHtmlPpr(it.name)}">`
                        : `<i class="bi bi-tag-fill"></i>`;
                    const metaHtml = it.meta
                        ? `<div class="ppr-prod-meta">${escapeHtmlPpr(it.meta)} &middot; Updated ${escapeHtmlPpr(it.elapsed_human)}</div>`
                        : `<div class="ppr-prod-meta">Updated ${escapeHtmlPpr(it.elapsed_human)}</div>`;

                    const statusBadge = !it.is_sellable
                        ? `<span class="ppr-badge-lock"><i class="bi bi-lock-fill"></i> POS Locked</span>`
                        : `<span class="ppr-badge-pending"><i class="bi bi-exclamation-circle-fill"></i> Price Pending</span>`;

                    const lossNote = it.is_below_cost
                        ? ` <span class="badge bg-danger ms-1" style="font-size:.6rem;">Below Cost!</span>`
                        : '';

                    const btnLabel = it.action_method === 'PATCH' ? 'Update &amp; Unlock' : 'Approve Price';

                    html += `
                        <div class="ppr-card" data-item-id="${it.item_id}" data-shop-stock-id="${it.shop_stock_id}" data-updated-ts="${it.updated_at_ts}" data-action-url="${escapeHtmlPpr(it.action_url)}" data-action-method="${escapeHtmlPpr(it.action_method)}" data-buying-price="${it.buying_price}">
                            <div class="ppr-card-top">
                                <div class="ppr-thumb">${thumbHtml}</div>
                                <div class="ppr-prod-info">
                                    <div class="ppr-prod-name">${escapeHtmlPpr(it.name)}</div>
                                    ${metaHtml}
                                </div>
                                <div class="ppr-badges">
                                    ${statusBadge}
                                    <span class="ppr-badge-stock">${it.remaining_qty} ${escapeHtmlPpr(it.unit || 'pcs')}</span>
                                </div>
                            </div>

                            <div class="ppr-prices-grid">
                                <div class="ppr-price-box">
                                    <span class="ppr-price-k">New Buying Price</span>
                                    <span class="ppr-price-v is-new-bp">${formatTzsPpr(it.buying_price)}</span>
                                </div>
                                <div class="ppr-price-box">
                                    <span class="ppr-price-k">Current Selling Price</span>
                                    <span class="ppr-price-v ${it.is_below_cost ? 'is-loss' : ''}">${formatTzsPpr(it.selling_price)}${lossNote}</span>
                                </div>
                                <div class="ppr-price-box">
                                    <span class="ppr-price-k">Changed On</span>
                                    <span class="ppr-price-v" style="font-size:.74rem; color:#475569;">${escapeHtmlPpr(it.updated_at_display)}</span>
                                </div>
                            </div>

                            <div class="ppr-action-bar">
                                <div class="ppr-action-input-wrap">
                                    <label for="ppr_input_${it.item_id}">New Selling Price:</label>
                                    <div class="ppr-input-group">
                                        <span class="ppr-input-prefix">TZS</span>
                                        <input type="text"
                                               inputmode="numeric"
                                               class="ppr-price-input currency-input"
                                               id="ppr_input_${it.item_id}"
                                               value="${formatNumPpr(it.suggested_price)}"
                                               placeholder="Enter selling price">
                                    </div>
                                </div>
                                <button type="button" class="ppr-save-price-btn">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>${btnLabel}</span>
                                </button>
                                <div class="ppr-card-error"></div>
                            </div>
                        </div>
                    `;
                });

                $list.html(html);
            }

            function isOnStockTransfersPagePpr() {
                if ({{ request()->routeIs('stock-transfers.*') ? 'true' : 'false' }}) {
                    return true;
                }
                return /^\/stock-transfers(?:\/|$)/i.test(window.location.pathname || '');
            }

            window.checkPendingPriceReminders = function() {
                if (isOnStockTransfersPagePpr()) return;
                if (isPriceModalOpen) return;
                if ($('.modal.show').length > 0) return;

                // If user just clicked "Open Shop Stock" from this popup, skip re-popping briefly on arrival
                try {
                    const skipUntil = parseInt(sessionStorage.getItem(NAV_SKIP_KEY) || '0', 10);
                    if (skipUntil && Date.now() < skipUntil && window.location.pathname.indexOf('/shop-stock') === 0) {
                        return;
                    }
                } catch (e) {}

                $.ajax({
                    url: "{{ route('shop-stock.price-reminders') }}",
                    type: 'GET',
                    success: function(response) {
                        const rawDue = response.due_items || [];
                        const localSnoozes = getLocalPriceSnoozes();

                        const dueItems = rawDue.filter(function(it) {
                            const snoozedUntil = localSnoozes[getPriceSnoozeKey(it.item_id, it.updated_at_ts)];
                            return !snoozedUntil || Date.now() >= snoozedUntil;
                        });

                        if (dueItems.length === 0) return;

                        currentDueItems = dueItems;
                        currentRingtoneUrl = response.ringtone_url || null;

                        renderPriceItems(dueItems, response.index_url || "{{ route('shop-stock.index') }}", response.shop_name || '');

                        const modalEl = document.getElementById('pendingPriceReminderModal');
                        if (!modalEl) return;

                        isPriceModalOpen = true;
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                        playPriceAlertSound(currentRingtoneUrl);
                    }
                });
            };

            // Inline Quick Price Update right inside the popup
            $(document).on('click', '.ppr-save-price-btn', function() {
                const $btn = $(this);
                const $card = $btn.closest('.ppr-card');
                const $input = $card.find('.ppr-price-input');
                const $err = $card.find('.ppr-card-error');

                const actionUrl = $card.attr('data-action-url');
                const actionMethod = $card.attr('data-action-method') || 'PATCH';
                const buyingPrice = parseFloat($card.attr('data-buying-price') || '0');
                const itemId = parseInt($card.attr('data-item-id'), 10);
                const rawVal = String($input.val() || '').replace(/,/g, '').trim();
                const newPrice = parseFloat(rawVal);

                $err.hide().text('');

                if (isNaN(newPrice) || newPrice <= 0) {
                    $err.text('Please enter a valid selling price.').show();
                    $input.focus();
                    return;
                }

                if (newPrice < buyingPrice) {
                    $err.text('Selling price must be at least the new buying price (' + formatTzsPpr(buyingPrice) + ').').show();
                    $input.focus();
                    return;
                }

                const origHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span><span>Saving...</span>');

                const payload = {
                    _token: "{{ csrf_token() }}",
                    selling_price: newPrice
                };
                if (actionMethod === 'PATCH') {
                    payload._method = 'PATCH';
                }

                $.ajax({
                    url: actionUrl,
                    type: 'POST',
                    data: payload,
                    headers: { 'Accept': 'application/json' },
                    success: function(res) {
                        $card.addClass('is-resolved');
                        $card.find('.ppr-action-bar').replaceWith(`
                            <div class="ppr-resolved-banner">
                                <span><i class="bi bi-check-circle-fill me-1"></i>Selling price updated to ${formatTzsPpr(newPrice)} — ready for sale!</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Resolved</span>
                            </div>
                        `);

                        currentDueItems = currentDueItems.filter(function(it) { return it.item_id !== itemId; });
                        updatePriceModalStats(currentDueItems, '');

                        // Live-refresh Shop Stock table if currently viewing Shop Stock page
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#shopStockTable')) {
                            $('#shopStockTable').DataTable().ajax.reload(null, false);
                        }

                        if (currentDueItems.length === 0) {
                            $('.ss-price-banner').fadeOut(250);
                            showPriceToast(res.message || 'All pending item prices updated and unlocked!', true);
                            setTimeout(function() {
                                const modalEl = document.getElementById('pendingPriceReminderModal');
                                if (modalEl) {
                                    const modal = bootstrap.Modal.getInstance(modalEl);
                                    if (modal) modal.hide();
                                }
                            }, 850);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(origHtml);
                        let msg = 'Failed to update price. Please try again.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors && xhr.responseJSON.errors.selling_price) {
                                msg = xhr.responseJSON.errors.selling_price[0];
                            } else if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                        }
                        $err.text(msg).show();
                    }
                });
            });

            // Allow pressing Enter inside the quick price input to submit that card
            $(document).on('keydown', '.ppr-price-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).closest('.ppr-card').find('.ppr-save-price-btn').trigger('click');
                }
            });

            // Brief skip when clicking "Open Shop Stock" from the popup
            $(document).on('click', '#btnOpenPriceShopStock', function() {
                try {
                    sessionStorage.setItem(NAV_SKIP_KEY, String(Date.now() + 30000));
                } catch (e) {}
            });

            // Snooze for 1 hour
            $(document).on('click', '.ppr-dismiss-trigger', function() {
                if (currentDueItems.length > 0) {
                    saveLocalPriceSnoozes(currentDueItems);
                    $.ajax({
                        url: "{{ route('shop-stock.price-reminders.dismiss') }}",
                        type: 'POST',
                        data: {
                            items: currentDueItems.map(function(it) {
                                return { item_id: it.item_id, updated_at_ts: it.updated_at_ts };
                            }),
                            _token: "{{ csrf_token() }}"
                        }
                    });
                }

                const modalEl = document.getElementById('pendingPriceReminderModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                if (currentDueItems.length > 0) {
                    showPriceToast('Price reminder snoozed for 1 hour. You will be alerted again if still pending.', false);
                }
            });

            const priceModalEl = document.getElementById('pendingPriceReminderModal');
            if (priceModalEl) {
                priceModalEl.addEventListener('hidden.bs.modal', function() {
                    isPriceModalOpen = false;
                });
            }

            $(document).ready(function() {
                if (isOnStockTransfersPagePpr()) return;
                setTimeout(window.checkPendingPriceReminders, 1300);
                setInterval(function() {
                    if (isOnStockTransfersPagePpr()) return;
                    if (typeof window.isUserActiveForPolling !== 'function' || window.isUserActiveForPolling()) {
                        window.checkPendingPriceReminders();
                    }
                }, POLL_INTERVAL_MS);
            });
        })();
        @endif
    </script>

    @stack('scripts')
</body>

</html>