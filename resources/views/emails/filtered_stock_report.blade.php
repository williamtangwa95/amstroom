<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Valuation Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 36px 0;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
        }

        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 34px 28px;
            text-align: center;
            color: #ffffff;
        }

        .header .brand-title {
            margin: 0;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #38bdf8;
        }

        .header .scope-badge {
            display: inline-block;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
        }

        .header .timestamp {
            margin: 8px 0 0 0;
            font-size: 12px;
            color: #94a3b8;
        }

        .content {
            padding: 28px;
        }

        .note-card {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 22px;
        }

        .note-card .note-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1d4ed8;
            margin-bottom: 4px;
        }

        .note-card .note-body {
            font-size: 13px;
            color: #1e3a8a;
            line-height: 1.45;
        }

        .filter-tags-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 22px;
        }

        .filter-tags-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .tag-badge {
            display: inline-block;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .section-header {
            display: table;
            width: 100%;
            margin-top: 24px;
            margin-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-row {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 10px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }

        .stat-card-row td {
            padding: 14px 18px;
            vertical-align: middle;
            border: none;
        }

        .stat-card-row.qty {
            border-left: 5px solid #0284c7;
        }

        .stat-card-row.cost {
            border-left: 5px solid #8b5cf6;
        }

        .stat-card-row.selling {
            border-left: 5px solid #10b981;
        }

        .stat-meta .label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .stat-meta .subtext {
            font-size: 11px;
            color: #94a3b8;
        }

        .stat-amount {
            text-align: right;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 10px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 9px 10px;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tfoot td {
            padding: 10px;
            font-size: 12px;
            border-top: 2px solid #e2e8f0;
            background-color: #f1f5f9;
        }

        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            margin: 4px 0;
        }
    </style>
</head>

<body>

    <div class="email-wrapper">
        <div class="container">
            <!-- HEADER -->
            <div class="header">
                <div class="brand-title">{{ $reportData['branding']['name'] ?? 'AMSTROOM' }}</div>
                <div class="scope-badge">{{ $reportData['stock_type_label'] }} — Inventory Valuation</div>
                <div class="timestamp">Generated by {{ $reportData['sender_name'] }} on {{ $reportData['generated_at'] }}</div>
            </div>

            <div class="content">

                @if(!empty($reportData['note']))
                <div class="note-card">
                    <div class="note-title">Message from {{ $reportData['sender_name'] }}</div>
                    <div class="note-body">{{ $reportData['note'] }}</div>
                </div>
                @endif

                <div class="filter-tags-box">
                    <div class="filter-tags-title">Inventory Overview</div>
                    <span class="tag-badge">Report Type: <strong>{{ $reportData['stock_type_label'] }}</strong></span>
                    <span class="tag-badge">Total Remaining Units: <strong>{{ number_format($reportData['total_qty']) }}</strong></span>
                    <span class="tag-badge">Unique Products: <strong>{{ number_format(count($reportData['items_list'])) }}</strong></span>
                </div>

                <div class="section-header">
                    <span class="section-title">Valuation Highlights</span>
                </div>

                <!-- Total Qty -->
                <table class="stat-card-row qty">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Stock Quantity</div>
                            <div class="subtext">Available inventory units</div>
                        </td>
                        <td class="stat-amount text-primary" style="color: #0284c7;">
                            {{ number_format($reportData['total_qty']) }} Units
                        </td>
                    </tr>
                </table>

                @if(isset($reportData['total_cost_value']))
                <!-- Stock Cost Value -->
                <table class="stat-card-row cost">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Cost Valuation (Buying Price)</div>
                            <div class="subtext">Total capital investment in stock</div>
                        </td>
                        <td class="stat-amount" style="color: #7c3aed;">
                            TZS {{ number_format($reportData['total_cost_value'], 0) }}
                        </td>
                    </tr>
                </table>
                @endif

                <!-- Expected Selling Value -->
                <table class="stat-card-row selling">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Expected Retail Valuation</div>
                            <div class="subtext">Projected revenue at current selling prices</div>
                        </td>
                        <td class="stat-amount" style="color: #059669;">
                            TZS {{ number_format($reportData['total_sell_value'], 0) }}
                        </td>
                    </tr>
                </table>

                <div class="section-header" style="margin-top:26px;">
                    <span class="section-title">Inventory Breakdown ({{ count($reportData['items_list']) }} Products)</span>
                </div>

                @if(count($reportData['items_list']) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Product / Category</th>
                            @if($reportData['is_shop_view'] && !empty($reportData['show_shop_column']))
                            <th style="width: 20%;">Shop</th>
                            @endif
                            <th style="width: 15%; text-align: center;">Qty</th>
                            @if(isset($reportData['total_cost_value']))
                            <th style="width: 25%; text-align: right;">Cost Value</th>
                            @else
                            <th style="width: 25%; text-align: right;">Selling Value</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['items_list'] as $item)
                        <tr>
                            <td>
                                <div style="font-weight:600; color:#1e293b;">{{ $item['name'] }}</div>
                                <div style="font-size:11px; color:#64748b;">{{ $item['category'] }}</div>
                            </td>
                            @if($reportData['is_shop_view'] && !empty($reportData['show_shop_column']))
                            <td style="color:#64748b; font-size:11px;">{{ $item['shop'] }}</td>
                            @endif
                            <td style="text-align:center; font-weight:700; color:#0f172a;">
                                {{ number_format($item['qty']) }}
                            </td>
                            <td style="text-align:right; font-weight:700; color:#059669;">
                                TZS {{ number_format($item['valuation'], 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="font-weight:800; text-transform:uppercase;">TOTAL</td>
                            @if($reportData['is_shop_view'] && !empty($reportData['show_shop_column']))
                            <td></td>
                            @endif
                            <td style="text-align:center; font-weight:800; color:#0284c7;">{{ number_format($reportData['total_qty']) }}</td>
                            <td style="text-align:right; font-weight:800; color:#059669;">TZS {{ number_format($reportData['total_sell_value'], 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @if(!empty($reportData['has_more']))
                <p style="font-size:11px; color:#94a3b8; text-align:center; margin-top:-10px; margin-bottom:15px;">
                    <em>* Showing first {{ count($reportData['items_list']) }} products. Access system web dashboard for full list.</em>
                </p>
                @endif
                @else
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 13px; background-color: #f8fafc; border-radius: 8px; margin-bottom: 20px;">
                    No inventory records found.
                </div>
                @endif

            </div>

            <div class="footer">
                <p style="font-weight:600; color:#64748b;">AMSTROOM Inventory Reporting System</p>
                <p>© {{ date('Y') }} {{ $reportData['branding']['name'] ?? 'AMSTROOM' }}. All rights reserved.</p>
            </div>
        </div>
    </div>

</body>

</html>
