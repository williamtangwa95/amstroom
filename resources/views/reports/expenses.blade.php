@extends('layouts.app')
@section('title', 'Expenses Report')
@section('page-title', 'Expenses Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Expenses Report</li>
@endsection

@push('styles')
@include('reports._styles')
@endpush

@section('content')
{{-- Compact Header --}}
<div class="rpt-header">
    <div class="rpt-title-row">
        <h5 class="rpt-title">
            <i class="bi bi-wallet2 text-danger me-1"></i>Expenses Breakdown
        </h5>
        <span class="rpt-pill" style="background:rgba(225,29,72,.08);color:#e11d48;">
            <i class="bi bi-calendar3"></i>{{ ucfirst($period) }}
        </span>
    </div>
    <div class="rpt-controls">
        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
            <i class="bi bi-envelope-at"></i> Send via Email
        </button>
    </div>
</div>

{{-- Filter Bar --}}
<div class="rpt-card mb-3">
    <div class="p-3">
        <form method="GET" action="{{ route('reports.expenses') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="rpt-filter-label">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Today</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="rpt-filter-label">Category</label>
                <select name="expense_category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->isOwner())
            <div class="col-12 col-md-3">
                <label class="rpt-filter-label">Shop</label>
                <select name="shop_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Shops</option>
                    <option value="owner" {{ request('shop_id') === 'owner' ? 'selected' : '' }}>Main Store (Owner)</option>
                    @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id && request('shop_id') !== 'owner' ? 'selected' : '' }}>{{ $s->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-12 col-md-3">
                <label class="rpt-filter-label">Shop</label>
                <select name="shop_id" class="form-select form-select-sm" disabled>
                    <option value="{{ auth()->user()->shop_id }}" selected>{{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</option>
                </select>
            </div>
            @endif
            @if($period === 'custom')
            <div class="col-6 col-md-2">
                <label class="rpt-filter-label">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="rpt-filter-label">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-accent w-100" style="height:31px;">Apply</button>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Executive KPI Cards --}}
<div class="rpt-kpi-grid rpt-kpi-grid-2">
    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:rgba(225,29,72,.1);color:#e11d48;">
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">Total Expenses ({{ ucfirst($period) }})</p>
            <div class="rpt-kpi-val is-currency" style="color:#e11d48;">TZS {{ number_format($totalAmount, 0) }}</div>
            <div class="rpt-kpi-sub">Approved expenditure</div>
        </div>
    </div>

    <div class="rpt-kpi-card">
        <div class="rpt-kpi-icon" style="background:rgba(124,58,237,.12);color:#7c3aed;">
            <i class="bi bi-receipt-cutoff"></i>
        </div>
        <div class="rpt-kpi-body">
            <p class="rpt-kpi-label">Transactions</p>
            <div class="rpt-kpi-val" style="color:#7c3aed;">{{ number_format($expenses->count()) }}</div>
            <div class="rpt-kpi-sub">{{ $expensesByCategory->count() }} active categories</div>
        </div>
    </div>
</div>

<div class="rpt-card mb-4">
    <div class="rpt-card-header">
        <h6 class="rpt-card-title"><i class="bi bi-tags-fill text-warning"></i>Expenses by Category</h6>
    </div>
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="expensesByCategoryTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="1" class="all primary-col">Category Name</th>
                    <th data-priority="2" class="min-tablet-l">Transactions</th>
                    <th data-priority="1" class="min-tablet-l">Total Expenses</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expensesByCategory as $ebc)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="fw-600">{{ $ebc->category->name ?? 'Deleted Category' }}</td>
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

<div class="rpt-card">
    <div class="rpt-card-header">
        <h6 class="rpt-card-title"><i class="bi bi-list-check text-accent"></i>Expenses Log</h6>
    </div>
    <div class="p-3 p-md-4">
        <table class="table table-hover align-middle w-100 dt-mobile-table" id="reportsExpensesTable">
            <thead>
                <tr>
                    <th data-priority="1" class="all iter-col text-center" style="width: 45px;">#</th>
                    <th data-priority="2" class="min-tablet-l">Date</th>
                    <th data-priority="1" class="all primary-col">Category</th>
                    <th data-priority="3" class="min-tablet-l">Activity</th>
                    <th data-priority="4" class="min-tablet-l">Recorded By</th>
                    <th data-priority="4" class="min-tablet-l">Approved By</th>
                    <th data-priority="2" class="min-tablet-l">Amount</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Send Filtered Expenses Report to Email -->
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white border-bottom border-secondary">
                <h5 class="modal-title fs-6 fw-bold" id="emailReportModalLabel">
                    <i class="bi bi-envelope-paper me-2 text-info"></i> Send Expenses Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendExpensesReportEmailForm">
                @csrf
                <div class="modal-body py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Report Filter Summary</div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-calendar3 me-1"></i> {{ ucfirst($period) }} @if($period === 'custom' && request('date_from')) ({{ request('date_from') }} to {{ request('date_to') }}) @endif</span>
                            @if(auth()->user()->isOwner())
                                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-shop me-1"></i> {{ request('shop_id') === 'owner' ? 'Main Store (Owner)' : (request('shop_id') ? ($shops->firstWhere('id', request('shop_id'))?->shop_name ?? 'Shop') : 'All Shops') }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-shop me-1"></i> {{ auth()->user()->shop?->shop_name ?? 'My Shop' }}</span>
                            @endif
                            @if(request('expense_category_id'))
                                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-tag me-1"></i> {{ $categories->firstWhere('id', request('expense_category_id'))?->name ?? 'Selected Category' }}</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 0.8rem;">
                            <span class="text-muted">Total Expenses: <strong class="text-danger">TZS {{ number_format($totalAmount, 0) }}</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reportRecipientEmails" class="form-label fw-bold small text-dark mb-1">
                            Recipient Email Address(es) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="reportRecipientEmails" name="emails" 
                               value="{{ auth()->user()->email }}" placeholder="e.g. owner@example.com, manager@example.com" required>
                        <div class="form-text" style="font-size: 0.72rem;">Separate multiple email addresses with a comma.</div>
                    </div>

                    <div class="mb-2">
                        <label for="reportEmailNote" class="form-label fw-bold small text-dark mb-1">
                            Custom Note / Message <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="reportEmailNote" name="note" rows="3" 
                                  placeholder="Add an optional comment or explanation to be included at the top of the email..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-accent d-flex align-items-center gap-1" id="btnSubmitSendExpensesReportEmail">
                        <i class="bi bi-send-fill"></i>
                        <span>Send Email Report</span>
                    </button>
                </div>
            </form>
        </div>
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

    if ($('#expensesByCategoryTable tbody tr').length > 0 && !$('#expensesByCategoryTable tbody tr td[colspan]').length) {
        $('#expensesByCategoryTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            dom: 'rt'
        });
    }

    $('#reportsExpensesTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('reports.expenses.data') }}",
            data: function(d) {
                d.period = "{{ $period }}";
                d.shop_id = "{{ request('shop_id') }}";
                d.expense_category_id = "{{ request('expense_category_id') }}";
                d.date_from = "{{ request('date_from') }}";
                d.date_to = "{{ request('date_to') }}";
            }
        },
        columns: [
            { data: 'iteration', name: 'iteration' },
            { data: 'date', name: 'date' },
            { data: 'category', name: 'category' },
            { data: 'activity', name: 'activity' },
            { data: 'recorder', name: 'recorder' },
            { data: 'approver', name: 'approver' },
            { data: 'amount', name: 'amount' }
        ],
        order: [[1, 'desc']],
        dom: '<"d-flex justify-content-between align-items-center p-3 border-bottom" <"d-flex align-items-center gap-3"lB> f>rt<"d-flex justify-content-between align-items-center p-3 border-top"ip>',
        buttons: [
            /* ── Send to Email ── */
            {
                text:      '<i class="bi bi-envelope-at me-1"></i> Email Report',
                className: 'btn btn-sm btn-primary me-2',
                action:    function() {
                    $('#emailReportModal').modal('show');
                }
            },

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

    /* ── Send Expenses Report AJAX ── */
    $('#sendExpensesReportEmailForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSubmitSendExpensesReportEmail');
        const originalBtnHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...');

        const formData = {
            _token: $('input[name="_token"]').val() || '{{ csrf_token() }}',
            emails: $('#reportRecipientEmails').val(),
            note: $('#reportEmailNote').val(),
            period: "{{ $period }}",
            shop_id: "{{ request('shop_id') }}",
            expense_category_id: "{{ request('expense_category_id') }}",
            date_from: "{{ request('date_from') }}",
            date_to: "{{ request('date_to') }}"
        };

        $.ajax({
            url: "{{ route('reports.expenses.send-email') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                $('#emailReportModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Report Sent!',
                    text: res.message || 'Expenses report has been successfully sent to email.',
                    confirmButtonColor: '#0088cc'
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = 'Failed to send expenses report email.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Sending Failed',
                    html: msg,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });
});
</script>
@endpush
