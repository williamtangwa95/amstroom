<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Follow-up Notification</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 6px 24px rgba(15,23,42,0.06);">
                    <tr>
                        <td style="background:{{ $isDue ? '#b91c1c' : '#005691' }};padding:22px 26px;color:#ffffff;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;opacity:.85;">
                                {{ $companyName }} &middot; Daily Diary Follow-up
                            </div>
                            <h2 style="margin:6px 0 0;font-size:20px;line-height:1.3;">
                                {{ $isDue ? 'Follow-up Due Date Reached' : 'Upcoming Follow-up Reminder' }}
                            </h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 26px;">
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.5;color:#334155;">
                                Hello <strong>{{ $diary->user?->name ?? 'User' }}</strong>,
                            </p>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.55;color:#475569;">
                                @if($isDue)
                                    Your diary follow-up task has reached its due date and requires your attention.
                                @else
                                    This is your scheduled advance reminder for an upcoming diary follow-up task.
                                @endif
                            </p>

                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid {{ $isDue ? '#dc2626' : '#0077b6' }};border-radius:10px;padding:16px 18px;margin-bottom:20px;">
                                <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:6px;">
                                    {{ $diary->event_name }}
                                </div>
                                <div style="font-size:13px;color:#475569;line-height:1.55;white-space:pre-line;margin-bottom:12px;">{{ $diary->event_description }}</div>

                                <table width="100%" cellpadding="4" cellspacing="0" style="font-size:13px;border-top:1px solid #e2e8f0;padding-top:10px;">
                                    <tr>
                                        <td style="color:#64748b;width:40%;">Scope:</td>
                                        <td style="font-weight:600;color:#0f172a;">
                                            {{ $diary->type === 'shop' ? ('Shop Diary (' . ($diary->shop?->shop_name ?? 'Branch') . ')') : 'Personal Diary' }}
                                        </td>
                                    </tr>
                                    @if($diary->reminder_date)
                                    <tr>
                                        <td style="color:#64748b;">Reminder Schedule:</td>
                                        <td style="font-weight:600;color:#0077b6;">{{ $diary->reminder_date->format('d M Y') }} at {{ $diary->formatted_reminder_time }}</td>
                                    </tr>
                                    @endif
                                    @if($diary->due_date)
                                    <tr>
                                        <td style="color:#64748b;">Due Date:</td>
                                        <td style="font-weight:700;color:{{ $isDue ? '#dc2626' : '#0f172a' }};">{{ $diary->due_date->format('d M Y') }}</td>
                                    </tr>
                                    @endif
                                    @if($diary->cost !== null)
                                    <tr>
                                        <td style="color:#64748b;">Recorded Cost:</td>
                                        <td style="font-weight:600;color:#0f172a;">TZS {{ number_format((float) $diary->cost, 0) }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>

                            <div style="text-align:center;margin:22px 0 8px;">
                                <a href="{{ $diaryUrl }}" style="display:inline-block;background:#0077b6;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:11px 24px;border-radius:8px;">
                                    Open Daily Diary
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:14px 26px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;">
                            You received this email because you enabled Email Notifications for this diary follow-up entry in {{ $companyName }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
