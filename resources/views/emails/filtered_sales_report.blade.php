<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtered Sales Report</title>
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

        /* ── SENDER NOTE ── */
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

        /* ── FILTER CHIPS BAR ── */
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

        .tag-badge strong {
            color: #0f172a;
        }

        /* ── SECTION HEADER ── */
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

        /* ── STAT CARDS ── */
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

        .stat-card-row.revenue {
            border-left: 5px solid #10b981;
        }

        .stat-card-row.profit {
            border-left: 5px solid #8b5cf6;
        }

        .stat-card-row.transactions {
            border-left: 5px solid #0284c7;
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

        .stat-amount.text-revenue {
            color: #059669;
        }

        .stat-amount.text-profit {
            color: #7c3aed;
        }

        .stat-amount.text-transactions {
            color: #0284c7;
        }

        /* ── TABLES ── */
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

        .item-list-pill {
            display: inline-block;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 4px;
            margin: 2px 2px 2px 0;
            border: 1px solid #e2e8f0;
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
                <div class="scope-badge">Sales Performance Report — {{ $reportData['period_label'] }}</div>
                <div class="timestamp">Generated by {{ $reportData['sender_name'] }} on {{ $reportData['generated_at'] }}</div>
            </div>

            <div class="content">

                {{-- SENDER NOTE --}}
                @if(!empty($reportData['note']))
                <div class="note-card">
                    <div class="note-title">Message from {{ $reportData['sender_name'] }}</div>
                    <div class="note-body">{{ $reportData['note'] }}</div>
                </div>
                @endif

                {{-- APPLIED FILTERS --}}
                <div class="filter-tags-box">
                    <div class="filter-tags-title">Applied Report Filters</div>
                    <span class="tag-badge">Period: <strong>{{ $reportData['period_label'] }}</strong></span>
                    <span class="tag-badge">Scope: <strong>{{ $reportData['shop_label'] }}</strong></span>
                    @if(!empty($reportData['item_label']))
                    <span class="tag-badge">Item: <strong>{{ $reportData['item_label'] }}</strong></span>
                    @endif
                    @if(!empty($reportData['stock_type_label']))
                    <span class="tag-badge">Stock Type: <strong>{{ $reportData['stock_type_label'] }}</strong></span>
                    @endif
                </div>

                {{-- FINANCIAL HIGHLIGHTS --}}
                <div class="section-header">
                    <span class="section-title">Performance Summary</span>
                </div>

                <!-- Total Revenue -->
                <table class="stat-card-row revenue">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Revenue</div>
                            <div class="subtext">Gross revenue for selected filter criteria</div>
                        </td>
                        <td class="stat-amount text-revenue">
                            TZS {{ number_format($reportData['total_revenue'], 0) }}
                        </td>
                    </tr>
                </table>

                <!-- Total Profit -->
                <table class="stat-card-row profit">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Estimated Profit</div>
                            <div class="subtext">Calculated margin for selected sales</div>
                        </td>
                        <td class="stat-amount text-profit">
                            TZS {{ number_format($reportData['total_profit'], 0) }}
                        </td>
                    </tr>
                </table>

                <!-- Sales Transactions -->
                <table class="stat-card-row transactions">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Transactions</div>
                            <div class="subtext">Total sales orders completed</div>
                        </td>
                        <td class="stat-amount text-transactions">
                            {{ number_format($reportData['total_transactions']) }} Order{{ $reportData['total_transactions'] == 1 ? '' : 's' }}
                        </td>
                    </tr>
                </table>

                {{-- BREAKDOWN BY SHOP (IF APPLICABLE) --}}
                @if(!empty($reportData['sales_by_shop']) && count($reportData['sales_by_shop']) > 1)
                <div class="section-header" style="margin-top:24px;">
                    <span class="section-title">Breakdown by Shop</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Shop</th>
                            <th style="width: 20%; text-align: center;">Orders</th>
                            <th style="width: 20%; text-align: right;">Revenue</th>
                            <th style="width: 20%; text-align: right;">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['sales_by_shop'] as $sbs)
                        <tr>
                            <td style="font-weight:700; color:#1e293b;">{{ $sbs['shop_name'] }}</td>
                            <td style="text-align:center; color:#0284c7; font-weight:700;">{{ number_format($sbs['count']) }}</td>
                            <td style="text-align:right; color:#059669; font-weight:700;">TZS {{ number_format($sbs['revenue'], 0) }}</td>
                            <td style="text-align:right; color:#7c3aed; font-weight:700;">TZS {{ number_format($sbs['profit'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="font-weight:800; text-transform:uppercase;">TOTAL</td>
                            <td style="text-align:center; font-weight:800; color:#0284c7;">{{ number_format($reportData['total_transactions']) }}</td>
                            <td style="text-align:right; font-weight:800; color:#059669;">TZS {{ number_format($reportData['total_revenue'], 0) }}</td>
                            <td style="text-align:right; font-weight:800; color:#7c3aed;">TZS {{ number_format($reportData['total_profit'], 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @endif

                {{-- FILTERED SALES TRANSACTIONS LOG --}}
                <div class="section-header" style="margin-top:26px;">
                    <span class="section-title">Sales Transactions ({{ count($reportData['sales_list']) }} Record{{ count($reportData['sales_list']) == 1 ? '' : 's' }})</span>
                </div>

                @if(count($reportData['sales_list']) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Date</th>
                            <th style="width: 22%;">Shop &amp; Seller</th>
                            <th style="width: 32%;">Customer &amp; Items</th>
                            <th style="width: 14%; text-align: right;">Revenue</th>
                            <th style="width: 14%; text-align: right;">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['sales_list'] as $sl)
                        <tr>
                            <td style="font-size:11px; color:#64748b; white-space:nowrap;">
                                <strong>{{ $sl['date'] }}</strong>
                                <div style="font-size:10px; color:#94a3b8;">{{ $sl['method'] }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#1e293b;">{{ $sl['shop'] }}</div>
                                <div style="font-size:11px; color:#64748b;">{{ $sl['seller'] }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0f172a; margin-bottom:3px;">{{ $sl['customer'] }}</div>
                                <div>
                                    @foreach($sl['items'] as $itemStr)
                                    <span class="item-list-pill">{{ $itemStr }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td style="text-align:right; color:#059669; font-weight:700; white-space:nowrap;">
                                {{ number_format($sl['revenue'], 0) }}
                            </td>
                            <td style="text-align:right; color:#7c3aed; font-weight:700; white-space:nowrap;">
                                {{ number_format($sl['profit'], 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="font-weight:800; text-transform:uppercase;">TOTAL ({{ count($reportData['sales_list']) }} Sales)</td>
                            <td style="text-align:right; font-weight:800; color:#059669; white-space:nowrap;">TZS {{ number_format($reportData['total_revenue'], 0) }}</td>
                            <td style="text-align:right; font-weight:800; color:#7c3aed; white-space:nowrap;">TZS {{ number_format($reportData['total_profit'], 0) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @if(!empty($reportData['has_more_sales']))
                <p style="font-size:11px; color:#94a3b8; text-align:center; margin-top:-10px; margin-bottom:15px;">
                    <em>* Showing first {{ count($reportData['sales_list']) }} sales records. Access system web dashboard for full log.</em>
                </p>
                @endif
                @else
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 13px; background-color: #f8fafc; border-radius: 8px; margin-bottom: 20px;">
                    No sales transactions found matching the selected filter criteria.
                </div>
                @endif

            </div>

            <!-- FOOTER -->
            <div class="footer">
                <p style="font-weight:600; color:#64748b;">AMSTROOM Sales Reporting System</p>
                <p>© {{ date('Y') }} {{ $reportData['branding']['name'] ?? 'AMSTROOM' }}. All rights reserved.</p>
                <p style="font-size:11px; color:#cbd5e1;">This is a system-generated report sent upon user request.</p>
            </div>
        </div>
    </div>

</body>

</html>
