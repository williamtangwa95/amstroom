<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Report</title>
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
            padding: 40px 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
        }

        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 36px 28px;
            text-align: center;
            color: #ffffff;
        }

        .header .brand-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #38bdf8;
        }

        .header .scope-badge {
            display: inline-block;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        .section-header {
            display: table;
            width: 100%;
            margin-top: 24px;
            margin-bottom: 14px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── SINGLE ROW STAT CARDS ── */
        .stat-card-row {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }

        .stat-card-row td {
            padding: 16px 20px;
            vertical-align: middle;
            border: none;
        }

        .stat-card-row.revenue {
            border-left: 5px solid #10b981;
        }

        .stat-card-row.expense {
            border-left: 5px solid #ef4444;
        }

        .stat-card-row.profit {
            border-left: 5px solid #8b5cf6;
        }

        .stat-card-row.transactions {
            border-left: 5px solid #0284c7;
        }

        .stat-card-row.stock {
            border-left: 5px solid #64748b;
        }

        .stat-card-row.alert {
            border-left: 5px solid #f59e0b;
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
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .stat-amount.text-revenue {
            color: #059669;
        }

        .stat-amount.text-expense {
            color: #dc2626;
        }

        .stat-amount.text-profit {
            color: #7c3aed;
        }

        .stat-amount.text-transactions {
            color: #0284c7;
        }

        .stat-amount.text-stock {
            color: #334155;
        }

        .stat-amount.text-alert {
            color: #d97706;
        }

        /* ── TABLES ── */
        .data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            word-wrap: break-word;
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
            padding: 10px 10px;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            word-wrap: break-word;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #b45309;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
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
                <div class="brand-title">SALES REPORT GENERATED BY SYSTEM</div>
                <div class="scope-badge">{{ $reportData['scope'] }} — Summary Report</div>
                <div class="timestamp">Generated at: {{ $reportData['generated_at'] }}</div>
            </div>

            <div class="content">
                <!-- ── FINANCIAL OVERVIEW (SINGLE ROWS) ── -->
                <div class="section-header">
                    <span class="section-title">Financial Summary (Today)</span>
                </div>

                <!-- Row 1: Sales Revenue -->
                <table class="stat-card-row revenue">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Sales Revenue</div>
                            <div class="subtext">Total sales revenue generated today</div>
                        </td>
                        <td class="stat-amount text-revenue">
                            TZS {{ number_format($reportData['sales_total'], 0) }}
                        </td>
                    </tr>
                </table>

                <!-- Row 2: Expenses -->
                <table class="stat-card-row expense">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Expenses</div>
                            <div class="subtext">Total operational expenses recorded today</div>
                        </td>
                        <td class="stat-amount text-expense">
                            TZS {{ number_format($reportData['expenses_total'], 0) }}
                        </td>
                    </tr>
                </table>

                <!-- Row 3: Estimated Net Profit -->
                <table class="stat-card-row profit">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Estimated Net Profit</div>
                            <div class="subtext">Estimated net margin for today</div>
                        </td>
                        <td class="stat-amount text-profit">
                            TZS {{ number_format($reportData['profit'], 0) }}
                        </td>
                    </tr>
                </table>

                <!-- Row 4: Sales Transactions -->
                <table class="stat-card-row transactions">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Sales Transactions</div>
                            <div class="subtext">Total completed sales orders today</div>
                        </td>
                        <td class="stat-amount text-transactions">
                            {{ number_format($reportData['sales_count']) }} Order{{ $reportData['sales_count'] == 1 ? '' : 's' }}
                        </td>
                    </tr>
                </table>

                <!-- ── STOCK SUMMARY (SINGLE ROWS) ── -->
                <div class="section-header">
                    <span class="section-title">Inventory & Stock Status</span>
                </div>

                <!-- Row 5: Total Units in Stock -->
                <table class="stat-card-row stock">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Total Units in Stock</div>
                            <div class="subtext">Available inventory remaining</div>
                        </td>
                        <td class="stat-amount text-stock">
                            {{ number_format($reportData['stock_total_remaining'], 0) }} Units
                        </td>
                    </tr>
                </table>

                <!-- Row 6: Low Stock Alerts -->
                <table class="stat-card-row alert">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Low Stock Alerts</div>
                            <div class="subtext">Items at or below reorder threshold</div>
                        </td>
                        <td class="stat-amount text-alert">
                            @if($reportData['low_stock_alerts'] > 0)
                            <span class="badge-warning">{{ $reportData['low_stock_alerts'] }} Alert{{ $reportData['low_stock_alerts'] == 1 ? '' : 's' }}</span>
                            @else
                            0 Alerts
                            @endif
                        </td>
                    </tr>
                </table>

                <!-- ── LOW STOCK ITEMS BREAKDOWN TABLE ── -->
                @if(count($reportData['low_stock_items']) > 0)
                <div class="section-header" style="margin-top:28px;">
                    <span class="section-title" style="color:#d97706;">Low Stock Items Alert</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Product / Item</th>
                            <th style="width: 25%; text-align: center;">Qty Left</th>
                            <th style="width: 25%; text-align: right;">Min Alert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['low_stock_items'] as $item)
                        <tr>
                            <td style="font-weight:600; color:#1e293b; word-wrap:break-word;">{{ $item['name'] }}</td>
                            <td style="text-align:center; color:#dc2626; font-weight:700;">{{ number_format($item['qty']) }}</td>
                            <td style="text-align:right; color:#64748b;">{{ number_format($item['alert']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                <!-- ── EXPENSES BREAKDOWN TABLE ── -->
                @if(count($reportData['expenses_categories']) > 0)
                <div class="section-header" style="margin-top:28px;">
                    <span class="section-title">Expenses Breakdown</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Expense Category</th>
                            <th style="width: 40%; text-align: right;">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['expenses_categories'] as $cat)
                        <tr>
                            <td style="font-weight:600; color:#334155; word-wrap:break-word;">{{ $cat['name'] }}</td>
                            <td style="text-align:right; font-weight:700; color:#dc2626;">TZS {{ number_format($cat['total'], 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            <!-- FOOTER -->
            <div class="footer">
                <p style="font-weight:600; color:#64748b;">Automated Daily Summary Report — AMSTROOM System</p>
                <p>© {{ date('Y') }} AMSTROOM Technology Innovations. All rights reserved.</p>
            </div>
        </div>
    </div>

</body>

</html>