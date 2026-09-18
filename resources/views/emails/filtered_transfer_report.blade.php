<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfer Requests Report</title>
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

        .stat-card-row.pending {
            border-left: 5px solid #f59e0b;
        }

        .stat-card-row.approved {
            border-left: 5px solid #10b981;
        }

        .stat-card-row.rejected {
            border-left: 5px solid #ef4444;
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

        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending { background-color: #fef3c7; color: #b45309; }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }

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
                <div class="scope-badge">Stock Transfer Requests Report</div>
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
                    <div class="filter-tags-title">Transfer Filters</div>
                    <span class="tag-badge">Filter Status: <strong>{{ ucfirst($reportData['status_filter']) }}</strong></span>
                    <span class="tag-badge">Scope: <strong>{{ $reportData['shop_label'] }}</strong></span>
                </div>

                <div class="section-header">
                    <span class="section-title">Transfer Status Summary</span>
                </div>

                <!-- Pending Requests -->
                <table class="stat-card-row pending">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Pending Transfer Requests</div>
                            <div class="subtext">Awaiting warehouse authorization & fulfillment</div>
                        </td>
                        <td class="stat-amount" style="color: #d97706;">
                            {{ number_format($reportData['stats']['pending']) }}
                        </td>
                    </tr>
                </table>

                <!-- Approved & Transferred -->
                <table class="stat-card-row approved">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Approved &amp; Completed Transfers</div>
                            <div class="subtext">Successfully dispatched to retail shops</div>
                        </td>
                        <td class="stat-amount" style="color: #059669;">
                            {{ number_format($reportData['stats']['approved']) }}
                        </td>
                    </tr>
                </table>

                <!-- Rejected Requests -->
                <table class="stat-card-row rejected">
                    <tr>
                        <td class="stat-meta">
                            <div class="label">Rejected Transfer Requests</div>
                            <div class="subtext">Denied or cancelled stock requisitions</div>
                        </td>
                        <td class="stat-amount" style="color: #dc2626;">
                            {{ number_format($reportData['stats']['rejected']) }}
                        </td>
                    </tr>
                </table>

                {{-- TRANSFER REQUESTS LOG --}}
                <div class="section-header" style="margin-top:26px;">
                    <span class="section-title">Transfer Requisitions Log ({{ count($reportData['requests_list']) }} Records)</span>
                </div>

                @if(count($reportData['requests_list']) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Req ID / Date</th>
                            <th style="width: 25%;">Shop &amp; Requester</th>
                            <th style="width: 35%;">Requested Items &amp; Qty</th>
                            <th style="width: 25%; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['requests_list'] as $req)
                        <tr>
                            <td style="font-size:11px; color:#64748b; white-space:nowrap;">
                                <div style="font-weight:700; color:#0f172a;">#{{ $req['id'] }}</div>
                                <div style="font-size:10px; color:#94a3b8;">{{ $req['date'] }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#1e293b;">{{ $req['shop'] }}</div>
                                <div style="font-size:11px; color:#64748b;">By: {{ $req['requester'] }}</div>
                            </td>
                            <td>
                                <div style="font-size:12px; color:#0f172a;">{{ $req['items_summary'] }}</div>
                                @if(!empty($req['notes']))
                                <div style="font-size:11px; color:#94a3b8; font-style:italic;">Note: {{ $req['notes'] }}</div>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <span class="badge-status {{ $req['status'] === 'approved' ? 'badge-approved' : ($req['status'] === 'rejected' ? 'badge-rejected' : 'badge-pending') }}">{{ $req['status'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="font-weight:800; text-transform:uppercase;">TOTAL REQUESTS LOGGED</td>
                            <td style="text-align:right; font-weight:800; color:#0284c7;">{{ number_format(count($reportData['requests_list'])) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @if(!empty($reportData['has_more']))
                <p style="font-size:11px; color:#94a3b8; text-align:center; margin-top:-10px; margin-bottom:15px;">
                    <em>* Showing first {{ count($reportData['requests_list']) }} records. Access system web dashboard for full log.</em>
                </p>
                @endif
                @else
                <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 13px; background-color: #f8fafc; border-radius: 8px; margin-bottom: 20px;">
                    No transfer records found matching the filter status.
                </div>
                @endif

            </div>

            <div class="footer">
                <p style="font-weight:600; color:#64748b;">AMSTROOM Stock Logistics &amp; Distribution</p>
                <p>© {{ date('Y') }} {{ $reportData['branding']['name'] ?? 'AMSTROOM' }}. All rights reserved.</p>
            </div>
        </div>
    </div>

</body>

</html>
