@extends('layouts.app')
@section('title', 'Sales Report')
@section('page-title', 'Sales Performance Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Sales Report</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" disabled>
                    <option value="{{ auth()->user()->shop_id }}" selected>{{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</option>
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Item</label>
                <select name="item_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Items</option>
                    @foreach($items as $i)
                    <option value="{{ $i->id }}" {{ request('item_id') == $i->id ? 'selected' : '' }}>{{ $i->item_name }}</option>
                    @endforeach
                </select>
            </div>
            @if(!auth()->user()->isOwner())
            <div class="col-md-2.5 col-lg-2">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Stock Type</label>
                <select name="stock_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ $stockType === '' ? 'selected' : '' }}>All Stocks</option>
                    <option value="normal" {{ $stockType === 'normal' ? 'selected' : '' }}>Normal Stock Only</option>
                    <option value="admin" {{ $stockType === 'admin' ? 'selected' : '' }}>Admin Stock Only</option>
                </select>
            </div>
            @endif
            @if($period === 'custom')
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

@if(auth()->user()->isOwner())
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Revenue ({{ ucfirst($period) }})</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalRevenue, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f1c40f !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Profit ({{ ucfirst($period) }})</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">TZS {{ number_format($totalProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #f1c40f; opacity: 0.25;"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #3498db !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Sales Transactions</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $sales->count() }}</h3>
                </div>
                <div class="fs-2" style="color: #3498db; opacity: 0.25;"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
    </div>
</div>
@else
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Revenue ({{ ucfirst($period) }})</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.45rem;">TZS {{ number_format($totalRevenue, 0) }}</h3>
                    <div class="small text-secondary mt-1" style="font-size:.7rem;">
                        Transactions: <strong>{{ $sales->count() }}</strong>
                    </div>
                </div>
                <div class="fs-2" style="color: #10b981; opacity: 0.25;"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #f1c40f !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Profit ({{ ucfirst($period) }})</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.45rem;">TZS {{ number_format($totalProfit, 0) }}</h3>
                </div>
                <div class="fs-2" style="color: #f1c40f; opacity: 0.25;"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #bc8cff !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Admin Stock Summary</h6>
                    <div class="text-dark fw-700" style="font-size:0.95rem;">
                        <span class="text-secondary small font-normal">Rev:</span> TZS {{ number_format($totalAdminRevenue, 0) }}
                        <div class="text-secondary small font-normal mt-1">
                            Profit: <strong class="text-dark">TZS {{ number_format($totalAdminProfit, 0) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="fs-2" style="color: #bc8cff; opacity: 0.25;"><i class="bi bi-person-badge-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #d29922 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Normal Stock Summary</h6>
                    <div class="text-dark fw-700" style="font-size:0.95rem;">
                        <span class="text-secondary small font-normal">Rev:</span> TZS {{ number_format($totalNormalRevenue, 0) }}
                        <div class="text-secondary small font-normal mt-1">
                            Profit: <strong class="text-dark">TZS {{ number_format($totalNormalProfit, 0) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="fs-2" style="color: #d29922; opacity: 0.25;"><i class="bi bi-shop-window"></i></div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shop me-2" style="color:#bc8cff;"></i>Revenue &amp; Profit Breakdown by Shop</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>No</th><th>Shop Name</th><th>Total Transactions</th><th>Revenue Generated</th><th>Profit Generated</th></tr></thead>
            <tbody>
            @foreach($salesByShop as $sbs)
            <tr>
                <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                <td style="font-weight:600;">{{ $sbs->shop ? $sbs->shop->shop_name : ($sbs->shop_id === null ? 'Main Store (Owner)' : 'Deleted Shop') }}</td>
                <td>{{ number_format($sbs->count) }}</td>
                <td><strong style="color:#3fb950;">TZS {{ number_format($sbs->revenue, 0) }}</strong></td>
                <td><strong style="color:#ffc107;">TZS {{ number_format($sbs->profit, 0) }}</strong></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-check me-2" style="color:#58a6ff;"></i>Sales Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="salesReportLogTable">
            <thead><tr><th>No</th><th>Date</th><th>Shop</th><th>Seller</th><th>Customer</th><th>Items Sold</th><th>Method</th><th>Revenue</th><th>Profit</th></tr></thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(() => {
    @php
        $hName    = $reportHeader['name']    ?? 'Sales Report';
        $hSlogan  = $reportHeader['slogan']  ?? '';
        $hAddress = $reportHeader['address'] ?? '';
        $hTin     = $reportHeader['tin']     ?? '';
        $hPhone   = $reportHeader['phone']   ?? '';
    @endphp

    const headerName    = @json($hName);
    const headerSlogan  = @json($hSlogan);
    const headerAddress = @json($hAddress);
    const headerTin     = @json($hTin);
    const headerPhone   = @json($hPhone);
    const totalRevenue  = {{ (int) $totalRevenue }};
    const totalProfit   = {{ (int) $totalProfit }};

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

    function excelTitle() {
        let parts = [headerName];
        if (headerSlogan)  parts.push(headerSlogan);
        if (headerAddress) parts.push(headerAddress);
        let row = [];
        if (headerTin)   row.push('TIN: ' + headerTin);
        if (headerPhone) row.push('Tel: ' + headerPhone);
        if (row.length)  parts.push(row.join('   |   '));
        parts.push('SALES REPORT');
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

    $('#salesReportLogTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('reports.sales.data') }}",
            data: function(d) {
                d.period = "{{ $period }}";
                d.shop_id = "{{ request('shop_id') }}";
                d.item_id = "{{ request('item_id') }}";
                d.stock_type = "{{ $stockType }}";
                d.date_from = "{{ request('date_from') }}";
                d.date_to = "{{ request('date_to') }}";
            }
        },
        columns: [
            { data: 'iteration', name: 'iteration' },
            { data: 'sale_date', name: 'sale_date' },
            { data: 'shop', name: 'shop' },
            { data: 'seller', name: 'seller' },
            { data: 'customer', name: 'customer' },
            { data: 'items', name: 'items', orderable: false, searchable: false },
            { data: 'method', name: 'method' },
            { data: 'revenue', name: 'revenue' },
            { data: 'profit', name: 'profit' }
        ],
        order: [[1, 'desc']],
        dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom" <"d-flex align-items-center gap-3"lB> f>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
        buttons: [
            /* ── Excel ── */
            {
                extend:    'excelHtml5',
                className: 'btn btn-sm btn-accent me-2',
                text:      '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                title:     excelTitle(),
                customize: function(xlsx) {
                    customizeExcel(xlsx, 'SALES REPORT', true);
                }
            },

            /* ── PDF ── */
            {
                extend:      'pdfHtml5',
                className:   'btn btn-sm btn-outline-custom',
                text:        '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                title:       '',
                orientation: 'landscape',
                pageSize:    'A4',
                customize:   function(doc) {
                    var BLUE = '#0088cc';
                    doc.pageMargins = [30, 30, 30, 40];

                    /* Strip the empty title node DataTables injects — but NOT the table */
                    while (doc.content.length && doc.content[0].text === '') {
                        doc.content.shift();
                    }

                    /* ── Blue banner ── */
                    var banner = {
                        canvas: [{ type: 'rect', x: 0, y: 0, w: 782, h: 60, r: 3, color: BLUE }],
                        margin: [0, 0, 0, 0]
                    };

                    /* ── Company name on banner ── */
                    var brandName = {
                        text: headerName,
                        fontSize: 19, bold: true, color: '#ffffff',
                        alignment: 'center',
                        margin: [0, -54, 0, 0]
                    };

                    /* ── Slogan (light blue, below name) ── */
                    var brandSlogan = headerSlogan ? {
                        text: headerSlogan,
                        fontSize: 8, color: '#cce9ff', alignment: 'center',
                        margin: [0, 3, 0, 0]
                    } : null;

                    /* ── Info row: address • TIN • phone ── */
                    var infoParts = [];
                    if (headerAddress) infoParts.push(headerAddress);
                    if (headerTin)     infoParts.push('TIN: ' + headerTin);
                    if (headerPhone)   infoParts.push('Tel: ' + headerPhone);
                    var infoRow = infoParts.length ? {
                        text: infoParts.join('   \u2022   '),
                        fontSize: 7.5, color: '#555', alignment: 'center',
                        margin: [0, 10, 0, 0]
                    } : null;

                    /* ── Blue separator ── */
                    var separator = {
                        canvas: [{ type: 'line', x1: 0, y1: 0, x2: 782, y2: 0, lineWidth: 1, lineColor: BLUE }],
                        margin: [0, 7, 0, 0]
                    };

                    /* ── Title + generated date ── */
                    var titleRow = {
                        columns: [
                            { text: 'SALES REPORT', fontSize: 10, bold: true, color: BLUE, width: '*' },
                            { text: 'Generated: ' + nowEAT(), fontSize: 7, color: '#888', alignment: 'right', width: 'auto', margin: [0, 2, 0, 0] }
                        ],
                        margin: [0, 7, 0, 9]
                    };

                    /* ── Prepend header to doc ── */
                    var headerBlock = [banner, brandName];
                    if (brandSlogan) headerBlock.push(brandSlogan);
                    if (infoRow)     headerBlock.push(infoRow);
                    headerBlock.push(separator);
                    headerBlock.push(titleRow);
                    doc.content.unshift(...headerBlock);

                    /* ── Table header style ── */
                    doc.styles.tableHeader = {
                        fillColor: BLUE, color: '#ffffff',
                        bold: true, fontSize: 8, alignment: 'center'
                    };
                    doc.defaultStyle.fontSize = 7.5;

                    /* ── Zebra-stripe data rows + full-width columns ── */
                    var tblNode = null;
                    for (var ci = doc.content.length - 1; ci >= 0; ci--) {
                        if (doc.content[ci] && doc.content[ci].table) { tblNode = doc.content[ci]; break; }
                    }
                    if (tblNode && tblNode.table && tblNode.table.body) {

                        /* Stretch table to fill full page width using valid pdfmake widths.
                           No | Date | Shop | Seller | Customer | Items Sold | Method | Revenue | Profit */
                        var colCount = tblNode.table.body[0] ? tblNode.table.body[0].length : 0;
                        if (colCount === 9) {
                            tblNode.table.widths = ['auto', 55, '*', '*', '*', 145, 40, 60, 60];
                        } else {
                            tblNode.table.widths = Array(colCount).fill('*');
                        }
                        tblNode.table.dontBreakRows = true;

                        tblNode.table.body.forEach(function(row, i) {
                            if (i === 0) return;
                            row.forEach(function(cell) {
                                if (typeof cell === 'object') {
                                    cell.fillColor = (i % 2 === 0) ? '#eef6ff' : '#ffffff';
                                    cell.margin = [3, 3, 3, 3];
                                }
                            });
                        });

                        /* ── TOTALS row ── */
                        function boldCell(txt, align) {
                            return { text: txt, bold: true, fontSize: 8, color: '#ffffff',
                                     fillColor: '#005f99', alignment: align || 'left', margin: [4, 4, 4, 4] };
                        }
                        var colCount2 = tblNode.table.body[0] ? tblNode.table.body[0].length : 9;
                        var totalsRow = [];
                        for (var tc = 0; tc < colCount2; tc++) {
                            if (tc === 0) {
                                totalsRow.push(boldCell('TOTAL', 'center'));
                            } else if (tc === colCount2 - 2) {
                                /* Revenue column (2nd from last) */
                                totalsRow.push(boldCell(fmtTZS(totalRevenue), 'right'));
                            } else if (tc === colCount2 - 1) {
                                /* Profit column (last) */
                                totalsRow.push(boldCell(fmtTZS(totalProfit), 'right'));
                            } else {
                                totalsRow.push(boldCell('', 'left'));
                            }
                        }
                        tblNode.table.body.push(totalsRow);
                    }

                    /* ── Footer: branding left, page number right ── */
                    doc.footer = function(currentPage, pageCount) {
                        return {
                            margin: [30, 8, 30, 0],
                            columns: [
                                { text: headerName + ' \u2014 Confidential', fontSize: 6.5, color: '#aaa' },
                                { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'right', fontSize: 6.5, color: '#aaa' }
                            ]
                        };
                    };
                }
            }
        ]
    });
});
</script>
@endpush
