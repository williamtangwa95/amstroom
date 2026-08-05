<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Summary Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #1a202c;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f6f9;
            padding: 30px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #0088cc 0%, #005f9e 100%);
            padding: 30px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2d3748;
            margin-top: 24px;
            margin-bottom: 12px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 6px;
        }
        .grid-2 {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .col {
            display: table-cell;
            width: 50%;
            padding: 8px;
            box-sizing: border-box;
            vertical-align: top;
        }
        .stat-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .stat-val {
            font-size: 20px;
            font-weight: 700;
            color: #0088cc;
            margin-bottom: 4px;
        }
        .stat-val.revenue {
            color: #10b981;
        }
        .stat-val.expense {
            color: #ef4444;
        }
        .stat-label {
            font-size: 12px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #d97706;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
        }
    </style>
</head>
<body>

<div class="email-wrapper">
    <div class="container">
        <div class="header">
            <h1>AMSTROOM SUMMARY</h1>
            <p>{{ $reportData['scope'] }} — Sales, Expenses & Stock Summary</p>
            <p style="font-size:12px; margin-top:5px; opacity:0.8;">Generated at: {{ $reportData['generated_at'] }}</p>
        </div>

        <div class="content">
            <!-- ── FINANCIAL OVERVIEW ── -->
            <div class="section-title">Financial Summary (Today)</div>
            <div class="grid-2">
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val revenue">TZS {{ number_format($reportData['sales_total'], 0) }}</div>
                        <div class="stat-label">Sales Revenue</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val expense">TZS {{ number_format($reportData['expenses_total'], 0) }}</div>
                        <div class="stat-label">Expenses</div>
                    </div>
                </div>
            </div>
            <div class="grid-2" style="margin-top:-10px;">
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val">{{ $reportData['sales_count'] }}</div>
                        <div class="stat-label">Sales Transactions</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val" style="color: #8b5cf6;">TZS {{ number_format($reportData['profit'], 0) }}</div>
                        <div class="stat-label">Estimated Net Profit</div>
                    </div>
                </div>
            </div>

            <!-- ── STOCK SUMMARY ── -->
            <div class="section-title">Stock Status</div>
            <div class="grid-2">
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val" style="color: #4a5568;">{{ number_format($reportData['stock_total_remaining'], 0) }}</div>
                        <div class="stat-label">Total Units in Stock</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card">
                        <div class="stat-val" style="color: #f59e0b;">{{ $reportData['low_stock_alerts'] }}</div>
                        <div class="stat-label">Low Stock Alerts</div>
                    </div>
                </div>
            </div>

            <!-- ── LOW STOCK ITEMS ── -->
            @if(count($reportData['low_stock_items']) > 0)
            <div class="section-title">Low Stock Items Alert</div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty Remaining</th>
                        <th>Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['low_stock_items'] as $item)
                    <tr>
                        <td style="font-weight:600;">{{ $item['name'] }}</td>
                        <td style="color:#ef4444; font-weight:700;">{{ $item['qty'] }}</td>
                        <td>{{ $item['alert'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <!-- ── EXPENSES BREAKDOWN ── -->
            @if(count($reportData['expenses_categories']) > 0)
            <div class="section-title">Expenses Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['expenses_categories'] as $cat)
                    <tr>
                        <td>{{ $cat['name'] }}</td>
                        <td style="font-weight:600; color:#ef4444;">TZS {{ number_format($cat['total'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <div class="footer">
            <p>This is an automated system summary report sent by AMSTROOM.</p>
            <p>© {{ date('Y') }} AMSTROOM. All rights reserved.</p>
        </div>
    </div>
</div>

</body>
</html>
