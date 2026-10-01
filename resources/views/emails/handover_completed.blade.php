<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Handover Confirmed & Commission Summary</title>
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
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
            padding: 32px 28px;
            text-align: center;
            color: #ffffff;
        }
        .header .brand-title {
            margin: 0;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #86efac;
        }
        .header .subtitle {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: #dcfce7;
        }
        .content {
            padding: 28px;
        }
        .alert-card {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            border-radius: 8px;
            padding: 16px 18px;
            margin-bottom: 24px;
        }
        .alert-card .alert-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #15803d;
            margin-bottom: 4px;
        }
        .alert-card .alert-body {
            font-size: 14px;
            color: #166534;
            line-height: 1.5;
        }
        .commission-banner {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15);
        }
        .commission-banner .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-bottom: 6px;
        }
        .commission-banner .amount {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .details-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .details-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .details-table td.label {
            font-weight: 600;
            color: #475569;
            width: 40%;
        }
        .details-table td.value {
            font-weight: 700;
            color: #0f172a;
            width: 60%;
        }
        .financial-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .financial-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 14px;
        }
        .financial-row:last-child {
            border-bottom: none;
            padding-top: 12px;
            font-size: 16px;
            font-weight: 800;
        }
        .attachment-notice {
            background-color: #f0fdf4;
            border: 1px dashed #22c55e;
            border-radius: 10px;
            padding: 14px 18px;
            text-align: center;
            font-size: 13px;
            color: #15803d;
            font-weight: 600;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 28px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <div class="brand-title">{{ \App\Models\Setting::get('site_title', 'Amstroom') }}</div>
                <div class="subtitle">Handover Confirmed & Completed</div>
            </div>

            <div class="content">
                <div class="alert-card">
                    <div class="alert-title">Cash Handover Confirmed</div>
                    <div class="alert-body">
                        The Shop Owner <strong>{{ $handover->receiver->name ?? 'Owner' }}</strong> has confirmed receipt of cash for Handover Report <strong>{{ $handover->handover_no }}</strong> for <strong>{{ $handover->shop->shop_name ?? 'Shop' }}</strong>.
                    </div>
                </div>

                <!-- Highlight Commission Assigned -->
                <div class="commission-banner">
                    <div class="label">Commission Assigned to You</div>
                    <div class="amount">TZS {{ number_format($handover->commission_amount ?? 0, 0) }}</div>
                </div>

                <table class="details-table">
                    <tr>
                        <td class="label">Handover No</td>
                        <td class="value">{{ $handover->handover_no }}</td>
                    </tr>
                    <tr>
                        <td class="label">Shop Name</td>
                        <td class="value">{{ $handover->shop->shop_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Shop Admin</td>
                        <td class="value">{{ $handover->shopAdmin->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Period</td>
                        <td class="value">{{ $handover->start_date ? $handover->start_date->format('Y-m-d') : '' }} to {{ $handover->end_date ? $handover->end_date->format('Y-m-d') : '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Confirmed At</td>
                        <td class="value">{{ $handover->received_at ? $handover->received_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Confirmed By</td>
                        <td class="value">{{ $handover->receiver->name ?? 'Owner' }}</td>
                    </tr>
                </table>

                <div class="financial-box">
                    <div class="financial-row">
                        <span>Total Owner Sales:</span>
                        <strong style="color: #0f172a;">TZS {{ number_format($handover->total_owner_sales, 0) }}</strong>
                    </div>
                    <div class="financial-row">
                        <span>Total Expenses:</span>
                        <strong style="color: #dc2626;">- TZS {{ number_format($handover->total_expenses, 0) }}</strong>
                    </div>
                    <div class="financial-row">
                        <span>Expected Amount:</span>
                        <strong style="color: #2563eb;">TZS {{ number_format($handover->expected_amount, 0) }}</strong>
                    </div>
                    <div class="financial-row">
                        <span>Actual Cash Submitted:</span>
                        <strong style="color: #475569;">TZS {{ number_format($handover->actual_amount, 0) }}</strong>
                    </div>
                    <div class="financial-row">
                        <span>Confirmed Cash Received:</span>
                        <strong style="color: #16a34a;">TZS {{ number_format($handover->amount_received, 0) }}</strong>
                    </div>
                    <div class="financial-row">
                        <span>Assigned Commission:</span>
                        <strong style="color: #0284c7;">TZS {{ number_format($handover->commission_amount ?? 0, 0) }}</strong>
                    </div>
                </div>

                @if($handover->received_remarks)
                <div style="margin-bottom: 24px; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px;">
                    <strong style="color: #475569;">Owner Remarks / Bank Reference:</strong>
                    <p style="margin: 4px 0 0 0; color: #1e293b;">{{ $handover->received_remarks }}</p>
                </div>
                @endif

                <div class="attachment-notice">
                    <span style="font-size: 16px; vertical-align: middle;">📎</span> An Excel spreadsheet report (<strong>Handover_Report_{{ $handover->handover_no }}.xlsx</strong>) is attached to this email.
                </div>
            </div>

            <div class="footer">
                This is an automated notification from {{ \App\Models\Setting::get('site_title', 'Amstroom Management System') }}.
            </div>
        </div>
    </div>
</body>
</html>
