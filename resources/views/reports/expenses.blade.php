@extends('layouts.app')
@section('title', 'Expenses Report')
@section('page-title', 'Expenses Breakdown Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses Report</li>
@endsection
@section('content')
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.expenses') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Time Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily (Today)</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly (This Month)</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly (This Year)</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Category</label>
                <select name="expense_category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:.75rem;">Filter Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($period === 'custom')
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:.75rem;">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-accent w-100">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(233,69,96,.12);color:#e94560;"><i class="bi bi-wallet2"></i></div>
            <div class="stat-value" style="color:#e94560;font-size:1.3rem;">TZS {{ number_format($totalAmount, 0) }}</div>
            <div class="stat-label">Total Expenses ({{ ucfirst($period) }})</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(188,140,255,.12);color:#bc8cff;"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="stat-value" style="color:#bc8cff;">{{ $expenses->count() }}</div>
            <div class="stat-label">Expense Transactions</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-tags-fill me-2" style="color:#d29922;"></i>Expenses by Category</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Category Name</th>
                    <th>Transactions</th>
                    <th>Total Expenses</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expensesByCategory as $ebc)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-weight:600;">{{ $ebc->category->name ?? 'Deleted Category' }}</td>
                    <td>{{ number_format($ebc->count) }}</td>
                    <td><strong class="text-danger">TZS {{ number_format($ebc->total_amount, 0) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-secondary py-3">No category data found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-check me-2" style="color:#58a6ff;"></i>Expenses Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsExpensesTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Activity</th>
                    <th>Recorded By</th>
                    <th>Approved By</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $exp)
                <tr>
                    <td style="font-size:.82rem;">{{ $loop->iteration }}</td>
                    <td style="font-size:.75rem;color:var(--text-secondary);">{{ $exp->activity_date->format('M d, Y') }}</td>
                    <td><span class="badge" style="background:rgba(188,140,255,.12);color:#bc8cff;">{{ $exp->category->name }}</span></td>
                    <td style="font-size:.82rem;"><strong>{{ $exp->activity }}</strong></td>
                    <td style="font-size:.82rem;">{{ $exp->recorder->name ?? '—' }}</td>
                    <td style="font-size:.82rem;">{{ $exp->approver->name ?? '—' }}</td>
                    <td><strong class="text-danger">TZS {{ number_format($exp->amount, 0) }}</strong></td>
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
        $hName    = $reportHeader['name']    ?? 'Expenses Report';
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
    const totalAmount   = {{ (int) $totalAmount }};

    function fmtTZS(n) { return 'TZS ' + n.toLocaleString('en-TZ'); }

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
        parts.push('EXPENSES BREAKDOWN REPORT');
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

    $('#reportsExpensesTable').DataTable({
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
        buttons: [
            /* ── Excel ── */
            {
                extend:    'excelHtml5',
                className: 'btn btn-sm btn-accent me-2',
                text:      '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                title:     excelTitle(),
                customize: function(xlsx) {
                    customizeExcel(xlsx, 'EXPENSES BREAKDOWN REPORT', true);
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

                    /* Strip empty title node DataTables injects */
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

                    /* ── Slogan ── */
                    var brandSlogan = headerSlogan ? {
                        text: headerSlogan,
                        fontSize: 8, color: '#cce9ff', alignment: 'center',
                        margin: [0, 3, 0, 0]
                    } : null;

                    /* ── Info row ── */
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

                    /* ── Title + date row ── */
                    var titleRow = {
                        columns: [
                            { text: 'EXPENSES BREAKDOWN REPORT', fontSize: 10, bold: true, color: BLUE, width: '*' },
                            { text: 'Generated: ' + nowEAT(), fontSize: 7, color: '#888', alignment: 'right', width: 'auto', margin: [0, 2, 0, 0] }
                        ],
                        margin: [0, 7, 0, 9]
                    };

                    /* ── Prepend header ── */
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
                        /* Cols: No | Date | Category | Activity | Recorded By | Approved By | Amount (7 cols) */
                        var colCount = tblNode.table.body[0] ? tblNode.table.body[0].length : 0;
                        if (colCount === 7) {
                            tblNode.table.widths = ['auto', 55, '*', '*', '*', '*', 80];
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
                        var colCount2 = tblNode.table.body[0] ? tblNode.table.body[0].length : 7;
                        var totalsRow = [];
                        for (var tc = 0; tc < colCount2; tc++) {
                            if (tc === 0) {
                                totalsRow.push(boldCell('TOTAL', 'center'));
                            } else if (tc === colCount2 - 1) {
                                /* Amount column (last) */
                                totalsRow.push(boldCell(fmtTZS(totalAmount), 'right'));
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
