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

    function fmtTZS(n) {
        return 'TZS ' + n.toLocaleString('en-TZ');
    }

    function nowEAT() {
        return new Date().toLocaleString('en-TZ', {
            timeZone: 'Africa/Dar_es_Salaam',
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    /* ─── Shared PDF builder ─── */
    function buildPdf(doc, reportTitleText, colWidths, totalsRow) {
        var BLUE = '#0088cc';
        doc.pageMargins = [30, 30, 30, 40];

        while (doc.content.length && doc.content[0].text === '') {
            doc.content.shift();
        }

        var banner = {
            canvas: [{ type: 'rect', x: 0, y: 0, w: 782, h: 60, r: 3, color: BLUE }],
            margin: [0, 0, 0, 0]
        };

        var titleStack = [
            { text: headerName, fontSize: 13, bold: true, color: '#ffffff' }
        ];
        if (headerSlogan)  titleStack.push({ text: headerSlogan,  fontSize: 8,  italic: true, color: '#d0ebff' });
        if (headerAddress) titleStack.push({ text: headerAddress, fontSize: 7.5, color: '#ffffff' });

        var metaStack = [];
        if (headerTin)   metaStack.push({ text: 'TIN: ' + headerTin,   fontSize: 7.5, color: '#ffffff' });
        if (headerPhone) metaStack.push({ text: 'Tel: ' + headerPhone, fontSize: 7.5, color: '#ffffff' });
        metaStack.push({ text: reportTitleText, fontSize: 9, bold: true, color: '#ffe066' });
        metaStack.push({ text: 'Generated: ' + nowEAT(), fontSize: 7, color: '#d0ebff' });

        var bannerContent = {
            margin: [12, -52, 12, 12],
            columns: [
                { stack: titleStack, width: '*' },
                { stack: metaStack,  width: 'auto', alignment: 'right' }
            ]
        };

        doc.content.unshift(bannerContent);
        doc.content.unshift(banner);

        var tblNode = doc.content.find(function(c) { return c.table; });
        if (tblNode) {
            tblNode.margin = [0, 12, 0, 0];
            tblNode.table.widths = colWidths;

            tblNode.table.body[0].forEach(function(cell) {
                cell.fillColor = BLUE;
                cell.color = '#ffffff';
                cell.bold = true;
                cell.fontSize = 8.5;
                cell.margin = [4, 4, 4, 4];
            });

            tblNode.table.body.forEach(function(row, i) {
                if (i === 0) return;
                row.forEach(function(cell) {
                    cell.fontSize = 8;
                    cell.fillColor = (i % 2 === 0) ? '#eef6ff' : '#ffffff';
                    cell.margin = [3, 3, 3, 3];
                });
            });

            if (totalsRow) tblNode.table.body.push(totalsRow);
        }

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
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('reports.stock.data') }}",
                data: function(d) {
                    d.type = "main";
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration' },
                { data: 'product', name: 'product' },
                { data: 'category', name: 'category' },
                { data: 'qty', name: 'qty' },
                { data: 'value', name: 'value' },
                { data: 'sell_value', name: 'sell_value' }
            ],
            dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom" <"d-flex align-items-center gap-3"lB> f>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
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
                        buildPdf(doc, 'MAIN WAREHOUSE STOCK REPORT', ['auto', '*', '*', 60, 90, 100], mainTotalsRow);
                    }
                }
            ]
        });
    }

    /* ════════════════════════════════════════
       SHOP STOCKS TABLE
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
            processing: true,
            serverSide: true,
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: "{{ route('reports.stock.data') }}",
                data: function(d) {
                    d.type = "shop";
                }
            },
            columns: [
                { data: 'iteration', name: 'iteration' },
                { data: 'shop', name: 'shop' },
                { data: 'product', name: 'product' },
                { data: 'category', name: 'category' },
                { data: 'qty', name: 'qty' },
                { data: 'price', name: 'price' },
                { data: 'total_valuation', name: 'total_valuation' }
            ],
            dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom" <"d-flex align-items-center gap-3"lB> f>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
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
                        buildPdf(doc, 'SHOP STOCKS INVENTORY REPORT', ['auto', '*', '*', '*', 50, 70, 90], shopTotalsRow);
                    }
                }
            ]
        });
    }
});
</script>
@endpush
