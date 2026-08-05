@extends('layouts.app')
@section('title', 'Stock Report')
@section('page-title', 'Stock Valuation & Inventory Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Stock Report</li>
@endsection
@section('content')
@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('reports.stock') }}?type=main" class="btn {{ $type==='main' ? 'btn-accent' : 'btn-outline-custom' }}">Main Store Stock</a>
            <a href="{{ route('reports.stock') }}?type=shop" class="btn {{ $type==='shop' ? 'btn-accent' : 'btn-outline-custom' }}">Shop Stock Distribution</a>
        </div>
    </div>
</div>
@endif

@if($type === 'main')
<div class="card">
    <div class="card-header"><i class="bi bi-building-fill me-2" style="color:#d29922;"></i>Main Warehouse Stock Summary</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsMainStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Total Remaining Qty</th>
                    <th>Stock Value (Cost)</th>
                    <th>Expected Sales Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mainStocks as $ms)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ms->item->item_name }}</td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $ms->item->category->category_name }}</span></td>
                    <td><strong style="color:{{ $ms->qty > 0 ? '#3fb950' : '#e94560' }}">{{ $ms->qty }}</strong></td>
                    <td>TZS {{ number_format($ms->value, 0) }}</td>
                    <td><strong style="color:#58a6ff;">TZS {{ number_format($ms->sell_value, 0) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-header"><i class="bi bi-shop me-2" style="color:#3fb950;"></i>Shop Stocks Inventory</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsShopStockTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Shop</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Remaining Qty</th>
                    <th>Selling Price</th>
                    <th>Total Valuation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shopStocks as $ss)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ss->shop->shop_name }}</td>
                    <td>{{ $ss->item->item_name }}</td>
                    <td><span style="background:rgba(188,140,255,.12);color:#bc8cff;padding:.2rem .5rem;border-radius:6px;font-size:.73rem;">{{ $ss->item->category->category_name }}</span></td>
                    <td><strong style="color:{{ $ss->isLowStock() ? '#e94560' : '#3fb950' }}">{{ $ss->remaining_quantity }}</strong></td>
                    <td>TZS {{ number_format($ss->selling_price, 0) }}</td>
                    <td><strong style="color:#58a6ff;">TZS {{ number_format($ss->remaining_quantity * $ss->selling_price, 0) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(() => {
    @php
        $hName    = $reportHeader['name']    ?? 'Stock Report';
        $hSlogan  = $reportHeader['slogan']  ?? '';
        $hAddress = $reportHeader['address'] ?? '';
        $hTin     = $reportHeader['tin']     ?? '';
        $hPhone   = $reportHeader['phone']   ?? '';
    @endphp

    const headerName     = @json($hName);
    const headerSlogan   = @json($hSlogan);
    const headerAddress  = @json($hAddress);
    const headerTin      = @json($hTin);
    const headerPhone    = @json($hPhone);

    /* Main stock totals */
    const mainTotalQty       = {{ (int) $mainTotalQty }};
    const mainTotalValue     = {{ (int) $mainTotalValue }};
    const mainTotalSellValue = {{ (int) $mainTotalSellValue }};

    /* Shop stock totals */
    const shopTotalQty       = {{ (int) $shopTotalQty }};
    const shopTotalValuation = {{ (int) $shopTotalValuation }};

    function fmtTZS(n) { return 'TZS ' + n.toLocaleString('en-TZ'); }

    function nowEAT() {
        return new Date().toLocaleString('en-TZ', {
            timeZone: 'Africa/Dar_es_Salaam',
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    /* ─── Shared PDF builder ─── */
    function buildPdf(doc, reportTitle, colWidths, totalsRow) {
        var BLUE = '#0088cc';
        doc.pageMargins = [30, 30, 30, 40];

        /* Strip DataTables empty title node */
        while (doc.content.length && doc.content[0].text === '') { doc.content.shift(); }

        /* ── Blue banner ── */
        var banner = { canvas: [{ type: 'rect', x: 0, y: 0, w: 782, h: 60, r: 3, color: BLUE }], margin: [0,0,0,0] };

        var brandName = { text: headerName, fontSize: 19, bold: true, color: '#ffffff', alignment: 'center', margin: [0,-54,0,0] };

        var brandSlogan = headerSlogan ? { text: headerSlogan, fontSize: 8, color: '#cce9ff', alignment: 'center', margin: [0,3,0,0] } : null;

        var infoParts = [];
        if (headerAddress) infoParts.push(headerAddress);
        if (headerTin)     infoParts.push('TIN: ' + headerTin);
        if (headerPhone)   infoParts.push('Tel: ' + headerPhone);
        var infoRow = infoParts.length ? { text: infoParts.join('   \u2022   '), fontSize: 7.5, color: '#555', alignment: 'center', margin: [0,10,0,0] } : null;

        var separator = { canvas: [{ type: 'line', x1: 0, y1: 0, x2: 782, y2: 0, lineWidth: 1, lineColor: BLUE }], margin: [0,7,0,0] };

        var titleRow = {
            columns: [
                { text: reportTitle, fontSize: 10, bold: true, color: BLUE, width: '*' },
                { text: 'Generated: ' + nowEAT(), fontSize: 7, color: '#888', alignment: 'right', width: 'auto', margin: [0,2,0,0] }
            ],
            margin: [0,7,0,9]
        };

        var headerBlock = [banner, brandName];
        if (brandSlogan) headerBlock.push(brandSlogan);
        if (infoRow)     headerBlock.push(infoRow);
        headerBlock.push(separator);
        headerBlock.push(titleRow);
        doc.content.unshift(...headerBlock);

        /* ── Table styles ── */
        doc.styles.tableHeader = { fillColor: BLUE, color: '#ffffff', bold: true, fontSize: 8, alignment: 'center' };
        doc.defaultStyle.fontSize = 7.5;

        /* ── Find table, apply widths + zebra + totals ── */
        var tblNode = null;
        for (var ci = doc.content.length - 1; ci >= 0; ci--) {
            if (doc.content[ci] && doc.content[ci].table) { tblNode = doc.content[ci]; break; }
        }
        if (tblNode && tblNode.table && tblNode.table.body) {
            tblNode.table.widths      = colWidths;
            tblNode.table.dontBreakRows = true;

            tblNode.table.body.forEach(function(row, i) {
                if (i === 0) return;
                row.forEach(function(cell) {
                    if (typeof cell === 'object') {
                        cell.fillColor = (i % 2 === 0) ? '#eef6ff' : '#ffffff';
                        cell.margin    = [3, 3, 3, 3];
                    }
                });
            });

            if (totalsRow) tblNode.table.body.push(totalsRow);
        }

        /* ── Footer ── */
        doc.footer = function(currentPage, pageCount) {
            return { margin: [30,8,30,0], columns: [
                { text: headerName + ' \u2014 Confidential', fontSize: 6.5, color: '#aaa' },
                { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'right', fontSize: 6.5, color: '#aaa' }
            ]};
        };
    }

    /* ─── Bold cell helper for totals row ─── */
    function bc(txt, align) {
        return { text: txt, bold: true, fontSize: 8, color: '#ffffff', fillColor: '#005f99', alignment: align || 'left', margin: [4,4,4,4] };
    }

    /* ─── Excel title builder ─── */
    function excelTitle(reportLabel) {
        var parts = [headerName];
        if (headerSlogan)  parts.push(headerSlogan);
        if (headerAddress) parts.push(headerAddress);
        var row = [];
        if (headerTin)   row.push('TIN: ' + headerTin);
        if (headerPhone) row.push('Tel: ' + headerPhone);
        if (row.length)  parts.push(row.join('   |   '));
        parts.push(reportLabel.toUpperCase());
        parts.push('Generated: ' + nowEAT());
        return parts.join('\n');
    }

    function customizeExcel(xlsx, reportTitleText, hasTotals) {
        var sheet = xlsx.xl.worksheets['sheet1.xml'];
        var titleLines = [headerName];
        if (headerSlogan) titleLines.push(headerSlogan);
        if (headerAddress) titleLines.push(headerAddress);
        var details = [];
        if (headerTin) details.push('TIN: ' + headerTin);
        if (headerPhone) details.push('Tel: ' + headerPhone);
        if (details.length) titleLines.push(details.join('   |   '));
        titleLines.push(reportTitleText);
        titleLines.push('Generated: ' + nowEAT());

        var titleRowCount = titleLines.length;

        $('row', sheet).each(function(rowIndex) {
            var row = $(this);
            var cells = row.find('c');

            if (rowIndex < titleRowCount) {
                cells.each(function() {
                    if (rowIndex === 0) {
                        $(this).attr('s', '2'); // Bold
                    } else if (rowIndex === titleRowCount - 2) {
                        $(this).attr('s', '2'); // Bold
                    } else {
                        $(this).attr('s', '3'); // Italic
                    }
                });
            } else if (rowIndex === titleRowCount) {
                cells.each(function() {
                    $(this).attr('s', '22'); // Blue header
                });
            } else {
                var isLastRow = (rowIndex === $('row', sheet).length - 1);
                if (isLastRow && hasTotals) {
                    cells.each(function() {
                        $(this).attr('s', '62'); // Bold double underline
                    });
                }
            }
        });
    }

    /* ════════════════════════════════════════
       MAIN WAREHOUSE STOCK TABLE
       Cols: No | Product | Category | Qty | Stock Value | Expected Sales Value  (6 cols)
       ════════════════════════════════════════ */
    if ($('#reportsMainStockTable').length) {
        var mainTotalsRow = [
            bc('TOTAL', 'center'),
            bc('', 'left'),
            bc('', 'left'),
            bc(mainTotalQty.toLocaleString('en-TZ'), 'right'),
            bc(fmtTZS(mainTotalValue), 'right'),
            bc(fmtTZS(mainTotalSellValue), 'right'),
        ];

        $('#reportsMainStockTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'btn btn-sm btn-accent me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                    title: excelTitle('Main Warehouse Stock Report'),
                    customize: function(xlsx) {
                        customizeExcel(xlsx, 'MAIN WAREHOUSE STOCK REPORT', true);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'btn btn-sm btn-outline-custom',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                    title: '',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    customize: function(doc) {
                        /* No | Product | Category | Qty | Stock Value | Expected Sales Value */
                        buildPdf(doc, 'MAIN WAREHOUSE STOCK REPORT', ['auto', '*', '*', 60, 90, 100], mainTotalsRow);
                    }
                }
            ]
        });
    }

    /* ════════════════════════════════════════
       SHOP STOCKS TABLE
       Cols: No | Shop | Product | Category | Qty | Selling Price | Total Valuation  (7 cols)
       ════════════════════════════════════════ */
    if ($('#reportsShopStockTable').length) {
        var shopTotalsRow = [
            bc('TOTAL', 'center'),
            bc('', 'left'),
            bc('', 'left'),
            bc('', 'left'),
            bc(shopTotalQty.toLocaleString('en-TZ'), 'right'),
            bc('', 'left'),
            bc(fmtTZS(shopTotalValuation), 'right'),
        ];

        $('#reportsShopStockTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    className: 'btn btn-sm btn-accent me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                    title: excelTitle('Shop Stocks Inventory Report'),
                    customize: function(xlsx) {
                        customizeExcel(xlsx, 'SHOP STOCKS INVENTORY REPORT', true);
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'btn btn-sm btn-outline-custom',
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                    title: '',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    customize: function(doc) {
                        /* No | Shop | Product | Category | Qty | Selling Price | Total Valuation */
                        buildPdf(doc, 'SHOP STOCKS INVENTORY REPORT', ['auto', '*', '*', '*', 50, 70, 90], shopTotalsRow);
                    }
                }
            ]
        });
    }
});
</script>
@endpush
