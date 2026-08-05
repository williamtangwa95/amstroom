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
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.3rem;">TZS {{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-label">Total Revenue ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,.12);color:#ffc107;"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value" style="color:#ffc107;font-size:1.3rem;">TZS {{ number_format($totalProfit, 0) }}</div>
            <div class="stat-label">Total Profit ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(88,166,255,.12);color:#58a6ff;"><i class="bi bi-receipt"></i></div>
            <div class="stat-value" style="color:#58a6ff;">{{ $sales->count() }}</div>
            <div class="stat-label">Total Sales Transactions</div>
        </div>
    </div>
</div>
@else
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(63,185,80,.12);color:#3fb950;"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" style="color:#3fb950;font-size:1.15rem;">TZS {{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-label">Total Revenue ({{ ucfirst($period) }})</div>
            <div class="small text-secondary mt-1" style="font-size:.7rem;">
                Transactions: <strong>{{ $sales->count() }}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(255,193,7,.12);color:#ffc107;"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-value" style="color:#ffc107;font-size:1.15rem;">TZS {{ number_format($totalProfit, 0) }}</div>
            <div class="stat-label">Total Profit ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-value" style="color:#bc8cff;font-size:1.05rem;line-height:1.2;">
                <span style="font-size:.75rem;font-weight:normal;" class="text-secondary">Rev:</span> TZS {{ number_format($totalAdminRevenue, 0) }}
                <div style="font-size:.72rem;font-weight:normal;" class="text-secondary mt-1">
                    Profit: <strong>TZS {{ number_format($totalAdminProfit, 0) }}</strong>
                </div>
            </div>
            <div class="stat-label">Admin Stock Summary</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(210,153,34,.12);color:#d29922;"><i class="bi bi-shop-window"></i></div>
            <div class="stat-value" style="color:#d29922;font-size:1.05rem;line-height:1.2;">
                <span style="font-size:.75rem;font-weight:normal;" class="text-secondary">Rev:</span> TZS {{ number_format($totalNormalRevenue, 0) }}
                <div style="font-size:.72rem;font-weight:normal;" class="text-secondary mt-1">
                    Profit: <strong>TZS {{ number_format($totalNormalProfit, 0) }}</strong>
                </div>
            </div>
            <div class="stat-label">Normal Stock Summary</div>
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
            @foreach($sales as $sl)
            <tr>
                <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                <td style="font-size:.75rem;color:var(--text-secondary);">{{ $sl->sale_date->format('M d, Y') }}</td>
                <td style="font-size:.82rem;">{{ $sl->shop?->shop_name ?? 'Main Store (Owner)' }}</td>
                <td style="font-size:.82rem;">{{ $sl->seller->name }}</td>
                <td style="font-size:.82rem;">{{ $sl->customer_name ?: 'Walk-in' }}</td>
                <td style="font-size:.78rem;">
                    @php
                        $displayItems = $sl->items;
                        if ($itemId) {
                            $displayItems = $displayItems->where('item_id', $itemId);
                        }
                        if (($stockType ?? '') === 'admin') {
                            $displayItems = $displayItems->where('is_admin_stock', true);
                        } elseif (($stockType ?? '') === 'normal') {
                            $displayItems = $displayItems->where('is_admin_stock', false);
                        } elseif (empty($stockType) && auth()->user()->isOwner()) {
                            $displayItems = $displayItems->where('is_admin_stock', false);
                        }
                    @endphp
                    @foreach($displayItems as $item)
                        <div style="font-size:.78rem;line-height:1.4;margin-bottom:2px;" class="d-flex align-items-center gap-1 flex-wrap">
                            <span>{{ $item->item?->item_name ?? 'Unknown Item' }} (x{{ $item->quantity }})</span>
                            @if(!auth()->user()->isOwner())
                                @if($item->is_admin_stock)
                                    <span class="badge bg-info text-dark" style="font-size:.65rem;padding:.15rem .3rem;"><i class="bi bi-person-fill-lock"></i> Admin</span>
                                @else
                                    <span class="badge bg-secondary" style="font-size:.65rem;padding:.15rem .3rem;"><i class="bi bi-shop"></i> Normal</span>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </td>
                <td style="font-size:.78rem;">{{ str_replace('_',' ',ucfirst($sl->payment_method)) }}</td>
                <td><strong style="color:#3fb950;">TZS {{ number_format($sl->filtered_revenue, 0) }}</strong></td>
                <td><strong style="color:#ffc107;">TZS {{ number_format($sl->filtered_profit, 0) }}</strong></td>
            </tr>
            @endforeach
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
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
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
