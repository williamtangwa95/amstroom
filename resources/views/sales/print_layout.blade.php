<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('doc_title') — {{ $company['name'] }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #f4f6fa;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 18mm 16mm 12mm;
            position: relative;
        }

        /* ── Header ──────────────────────────────────────────────── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10mm;
            padding-bottom: 6mm;
            border-bottom: 3px solid #1a1a2e;
        }
        .brand-block .company-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #1a1a2e;
        }
        .brand-block .slogan {
            font-style: italic;
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }
        .brand-block .meta {
            margin-top: 6px;
            font-size: 11px;
            color: #444;
            line-height: 1.6;
        }
        .logo-block img {
            max-height: 70px;
            max-width: 130px;
            object-fit: contain;
        }
        .doc-title-block {
            text-align: right;
        }
        .doc-title-block .doc-type {
            font-size: 20px;
            font-weight: 800;
            color: #1a3c6e;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-title-block .doc-ref {
            font-size: 12px;
            color: #555;
            margin-top: 4px;
        }
        .doc-title-block .doc-ref strong {
            color: #1a1a2e;
        }

        /* ── Info Grid ───────────────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6mm 10mm;
            margin-bottom: 8mm;
        }
        .info-block .label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #888;
            margin-bottom: 2px;
        }
        .info-block .value {
            font-size: 13px;
            font-weight: 500;
            color: #1a1a2e;
        }

        /* ── Table ───────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8mm;
        }
        thead th {
            background: #1a1a2e;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 9px 10px;
        }
        tbody tr {
            border-bottom: 1px solid #eee;
        }
        tbody tr:nth-child(even) {
            background: #f9fafe;
        }
        tbody td {
            padding: 8px 10px;
            font-size: 12.5px;
        }
        tfoot td {
            padding: 8px 10px;
            font-size: 13px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── Totals ──────────────────────────────────────────────── */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8mm;
        }
        .totals-table {
            width: 60%;
        }
        .totals-table td {
            padding: 4px 10px;
            font-size: 13px;
        }
        .grand-total {
            background: #1a1a2e;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }
        .grand-total td {
            padding: 9px 10px !important;
        }

        /* ── Bank / Payment ──────────────────────────────────────── */
        .bank-section {
            background: #f9fafe;
            border: 1px solid #e0e4ec;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 6mm;
            font-size: 12px;
        }
        .bank-section strong {
            font-size: 12px;
            font-weight: 700;
        }

        /* ── Signature ───────────────────────────────────────────── */
        .signature-row {
            display: flex;
            gap: 30mm;
            margin-top: 12mm;
        }
        .sig-box {
            flex: 1;
        }
        .sig-box .sig-line {
            border-top: 1px solid #1a1a2e;
            margin-top: 14mm;
            padding-top: 4px;
            font-size: 11px;
            color: #555;
            text-align: center;
        }

        /* ── Footer ──────────────────────────────────────────────── */
        .doc-footer {
            margin-top: 10mm;
            text-align: center;
            font-size: 10.5px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        /* ── Status badge ────────────────────────────────────────── */
        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .badge-delivered { background: #d1fae5; color: #065f46; }
        .badge-pending   { background: #fef3c7; color: #92400e; }

        /* ── Print ───────────────────────────────────────────────── */
        .print-actions {
            position: fixed;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
            z-index: 999;
        }
        .btn-print, .btn-back {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print { background: #1a3c6e; color: #fff; }
        .btn-back  { background: #eee; color: #333; }

        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .page { padding: 10mm 12mm; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="print-actions">
    <button class="btn-back" onclick="window.history.back()">← Back</button>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
</div>

<div class="page">
    @yield('content')

    <div class="doc-footer">
        {{ $company['name'] }}
        @if($company['address'])  |  {{ $company['address'] }}@endif
        @if($company['tin'])  |  TIN: {{ $company['tin'] }}@endif
        — Thank you for your business!
    </div>
</div>

</body>
</html>
