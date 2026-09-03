<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #SL-{{ $sale->id }} — {{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #F7FAFC;
            margin: 0;
            padding: 20px 0;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #1A202C;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .receipt-container {
            width: 320px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .bold { font-weight: 600; }
        .logo-wrapper {
            margin-bottom: 12px;
        }
        .logo {
            max-height: 48px;
            max-width: 140px;
            object-fit: contain;
        }
        .shop-name {
            font-size: 15px;
            font-weight: 700;
            color: #1A202C;
            margin-bottom: 2px;
        }
        .shop-info {
            font-size: 11px;
            color: #718096;
            line-height: 1.4;
        }
        .divider {
            border-top: 1px dashed #CBD5E0;
            margin: 14px 0;
        }
        .metadata-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            row-gap: 5px;
            column-gap: 15px;
            font-size: 11px;
            color: #4A5568;
            margin-bottom: 10px;
        }
        .metadata-label {
            color: #718096;
        }
        .metadata-val {
            font-weight: 500;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th {
            font-size: 11px;
            font-weight: 600;
            color: #718096;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 6px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 0;
            font-size: 12px;
            color: #2D3748;
            vertical-align: middle;
            border-bottom: 1px solid #EDF2F7;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            font-size: 14px;
            font-weight: 700;
            color: #1A202C;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        .footer-text {
            font-size: 11px;
            color: #718096;
            line-height: 1.5;
            margin-top: 15px;
        }
        .footer-disclaimer {
            font-size: 10px;
            color: #A0AEC0;
            margin-top: 4px;
        }
        /* Buttons & Header Switcher styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            gap: 6px;
        }
        .btn-print {
            background-color: #2B6CB0;
            color: #fff;
        }
        .btn-print:hover {
            background-color: #2B6CB0;
            opacity: 0.9;
        }
        .btn-back {
            background-color: transparent;
            color: #4A5568;
            border: 1px solid #CBD5E0;
        }
        .btn-back:hover {
            background-color: #EDF2F7;
        }
        .no-print-area {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 320px;
            margin-left: auto;
            margin-right: auto;
        }
        .header-switcher {
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .switcher-title {
            font-size: 11px;
            font-weight: 700;
            color: #4A5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .btn-group-toggle {
            display: flex;
            gap: 8px;
        }
        .btn-toggle {
            flex: 1;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid #CBD5E0;
            background: #F7FAFC;
            color: #4A5568;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-toggle:hover {
            background: #EDF2F7;
        }
        .btn-toggle.active {
            background: #2B6CB0;
            color: #ffffff;
            border-color: #2B6CB0;
            box-shadow: 0 2px 4px rgba(43, 108, 176, 0.25);
        }
        
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
                width: 80mm;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                padding: 12px;
                width: 80mm;
                box-sizing: border-box;
            }
            .no-print-area {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    {{-- Owner Header Block --}}
    <div id="headerOwnerBlock" class="text-center" style="{{ ($initialHeader ?? 'owner') === 'owner' ? '' : 'display:none;' }}">
        @if(!empty($ownerHeader['logo']))
            <div class="logo-wrapper">
                <img src="{{ asset('media/' . $ownerHeader['logo']) }}" class="logo" alt="Owner Logo">
            </div>
        @else
            <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; color: #1A202C;">{{ $ownerHeader['name'] ?? 'AMSTROOM' }}</h2>
        @endif
        
        <div class="shop-name">{{ $ownerHeader['name'] ?? 'AMSTROOM (Owner)' }}</div>
        @if(!empty($ownerHeader['slogan']))
            <div style="font-size:10px; font-style:italic; color:#718096; margin-bottom:4px;">{{ $ownerHeader['slogan'] }}</div>
        @endif
        <div class="shop-info">
            <div>{{ $ownerHeader['location'] ?? 'Main Store / HQ' }}</div>
            <div>Tel: {{ $ownerHeader['phone'] ?? '+255700000001' }}</div>
        </div>
    </div>

    {{-- Admin / Shop Header Block --}}
    <div id="headerAdminBlock" class="text-center" style="{{ ($initialHeader ?? 'owner') === 'admin' ? '' : 'display:none;' }}">
        @if(!empty($adminHeader['logo']))
            <div class="logo-wrapper">
                <img src="{{ asset('media/' . $adminHeader['logo']) }}" class="logo" alt="Admin Logo">
            </div>
        @else
            <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; color: #1A202C;">{{ $adminHeader['name'] ?? 'AMSTROOM' }}</h2>
        @endif
        
        <div class="shop-name">{{ $adminHeader['name'] ?? 'Admin Store' }}</div>
        @if(!empty($adminHeader['slogan']))
            <div style="font-size:10px; font-style:italic; color:#718096; margin-bottom:4px;">{{ $adminHeader['slogan'] }}</div>
        @endif
        <div class="shop-info">
            <div>{{ $adminHeader['location'] ?? 'Main Store / HQ' }}</div>
            <div>Tel: {{ $adminHeader['phone'] ?? '+255700000001' }}</div>
        </div>
    </div>


    <div class="divider"></div>

    <div class="metadata-grid">
        <span class="metadata-label">Receipt No:</span>
        <span class="metadata-val">#SL-{{ $sale->id }}</span>

        <span class="metadata-label">Date:</span>
        <span class="metadata-val">{{ $sale->sale_date->format('d/m/Y H:i') }}</span>

        <span class="metadata-label">Seller:</span>
        <span class="metadata-val">{{ $sale->seller->name }}</span>

        <span class="metadata-label">Customer:</span>
        <span class="metadata-val">{{ $sale->customer_name ?: 'Walk-in Customer' }}</span>

        <span class="metadata-label">Payment:</span>
        <span class="metadata-val">{{ strtoupper(str_replace('_',' ',$sale->payment_method)) }}</span>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th class="text-start">Item</th>
                <th class="text-center" style="width: 40px;">Qty</th>
                <th class="text-end" style="width: 90px;">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items->where('parent_id', null) as $item)
            <tr>
                <td style="font-weight: 500;">{{ $item->display_name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">
                    @if($item->selling_price == 0)
                        Included
                    @else
                        {{ number_format($item->selling_price, 0) }}
                    @endif
                </td>
            </tr>
            @if($item->components->isNotEmpty())
                @foreach($item->components as $component)
                <tr>
                    <td style="padding-left: 12px; color: #718096; font-size: 11px;">
                        <span style="color: #CBD5E0; margin-right: 2px;">└─</span> {{ $component->display_name }}
                    </td>
                    <td class="text-center" style="color: #718096; font-size: 11px;">{{ $component->quantity }}</td>
                    <td class="text-end" style="color: #718096; font-size: 11px; font-style: italic;">Included</td>
                </tr>
                @endforeach
            @endif
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="total-row">
        <span>TOTAL:</span>
        <span>TZS {{ number_format($sale->total_amount, 0) }}</span>
    </div>

    <div class="divider"></div>

    <div class="text-center footer-text">
        <div class="bold">Thank you for shopping with us!</div>
        <div class="footer-disclaimer">Goods once sold are non-refundable</div>
    </div>
</div>

<div class="no-print-area text-center">
    <div class="header-switcher">
        <div class="switcher-title">Receipt Header Switcher</div>
        <div class="btn-group-toggle">
            <button type="button" id="btnHeaderOwner" class="btn-toggle {{ ($initialHeader ?? 'owner') === 'owner' ? 'active' : '' }}" onclick="switchReceiptHeader('owner')">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M14.763.075A.5.5 0 0 0 14.283 0L2 3.8 2 12.5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5V.5a.5.5 0 0 0-.237-.425zM13 1.286v10.714H3V4.414l10-3.128z"/>
                </svg>
                Owner Header
            </button>
            <button type="button" id="btnHeaderAdmin" class="btn-toggle {{ ($initialHeader ?? 'owner') === 'admin' ? 'active' : '' }}" onclick="switchReceiptHeader('admin')">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.371 2.371 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37c0-.38.144-.744.401-1.016L2.97 1.35z"/>
                </svg>
                Admin Header
            </button>
        </div>
    </div>

    <div style="display:flex; gap:8px; width:100%;">
        <button onclick="window.print()" class="btn btn-print" style="flex:1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
              <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
              <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Print Receipt
        </button>
        <a href="{{ route('sales.index') }}" class="btn btn-back">Back to Sales</a>
    </div>
</div>

<script>
    function switchReceiptHeader(type) {
        var ownerBlock = document.getElementById('headerOwnerBlock');
        var adminBlock = document.getElementById('headerAdminBlock');
        var btnOwner = document.getElementById('btnHeaderOwner');
        var btnAdmin = document.getElementById('btnHeaderAdmin');

        if (type === 'owner') {
            if (ownerBlock) ownerBlock.style.display = 'block';
            if (adminBlock) adminBlock.style.display = 'none';
            if (btnOwner) btnOwner.classList.add('active');
            if (btnAdmin) btnAdmin.classList.remove('active');
        } else {
            if (ownerBlock) ownerBlock.style.display = 'none';
            if (adminBlock) adminBlock.style.display = 'block';
            if (btnAdmin) btnAdmin.classList.add('active');
            if (btnOwner) btnOwner.classList.remove('active');
        }
    }
</script>

</body>
</html>
