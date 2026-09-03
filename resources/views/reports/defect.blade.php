@extends('layouts.app')
@section('title', 'Defect Report')
@section('page-title', 'Defective Items Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Defect Report</li>
@endsection
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #e94560 !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Defective Units Reported</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ number_format($totalDefective) }}</h3>
                </div>
                <div class="fs-2" style="color: #e94560; opacity: 0.25;"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-white h-100 premium-stat-card" style="border-left: 4px solid #3498db !important;">
            <div class="card-body d-flex align-items-center justify-content-between py-3">
                <div>
                    <h6 class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Incidents Reported</h6>
                    <h3 class="mb-0 fw-800 text-dark" style="font-size: 1.6rem;">{{ $defects->count() }}</h3>
                </div>
                <div class="fs-2" style="color: #3498db; opacity: 0.25;"><i class="bi bi-shield-exclamation"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-exclamation-octagon me-2" style="color:#e94560;"></i>Defective Items Audit Log</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="reportsDefectTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Shop / Warehouse</th>
                    <th>Product</th>
                    <th>Qty Defective</th>
                    <th>Reason</th>
                    <th>Reported By</th>
                    <th>Status</th>
                </tr>
            </thead>
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
        $hName    = $reportHeader['name']    ?? 'Defect Report';
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
    const totalDefective = {{ (int) $totalDefective }};

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
        parts.push('DEFECTIVE ITEMS REPORT');
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

    $('#reportsDefectTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('reports.defect.data') }}",
            data: function(d) {
                d.shop_id = "{{ request('shop_id') }}";
                d.status = "{{ request('status') }}";
            }
        },
        columns: [
            { data: 'iteration', name: 'iteration' },
            { data: 'date', name: 'date' },
            { data: 'location', name: 'location' },
            { data: 'product', name: 'product' },
            { data: 'qty', name: 'qty' },
            { data: 'reason', name: 'reason' },
            { data: 'reporter', name: 'reporter' },
            { data: 'status', name: 'status' }
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
                    customizeExcel(xlsx, 'DEFECTIVE ITEMS REPORT', true);
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

                    /* Strip DataTables empty title */
                    while (doc.content.length && doc.content[0].text === '') {
                        doc.content.shift();
                    }

                    /* ── Blue banner ── */
                    var banner = {
                        canvas: [{ type: 'rect', x: 0, y: 0, w: 782, h: 60, r: 3, color: BLUE }],
                        margin: [0, 0, 0, 0]
                    };

                    /* ── Company name ── */
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

                    /* ── Title row ── */
                    var titleRow = {
                        columns: [
                            { text: 'DEFECTIVE ITEMS REPORT', fontSize: 10, bold: true, color: BLUE, width: '*' },
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
                        /* Cols: No | Date | Shop | Product | Qty Defective | Reason | Reported By | Status (8 cols) */
                        var colCount = tblNode.table.body[0] ? tblNode.table.body[0].length : 0;
                        if (colCount === 8) {
                            tblNode.table.widths = ['auto', 55, '*', '*', 65, '*', '*', 50];
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
                        var colCount2 = tblNode.table.body[0] ? tblNode.table.body[0].length : 8;
                        var totalsRow = [];
                        for (var tc = 0; tc < colCount2; tc++) {
                            if (tc === 0) {
                                totalsRow.push(boldCell('TOTAL', 'center'));
                            } else if (tc === 4) {
                                /* Qty Defective column (5th column, index 4) */
                                totalsRow.push(boldCell(totalDefective.toLocaleString('en-TZ'), 'right'));
                            } else {
                                totalsRow.push(boldCell('', 'left'));
                            }
                        }
                        tblNode.table.body.push(totalsRow);
                    }

                    /* ── Footer ── */
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
