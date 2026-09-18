@extends('layouts.app')
@section('title', 'Stock Report')
@section('page-title', 'Stock Valuation & Inventory Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Stock Report</li>
@endsection
@section('content')
@if(auth()->user()->isOwner())
<div class="card mb-4">
    <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('reports.stock') }}?type=main" class="btn {{ $type==='main' ? 'btn-accent' : 'btn-outline-custom' }}">Main Store Stock</a>
            <a href="{{ route('reports.stock') }}?type=shop" class="btn {{ $type==='shop' ? 'btn-accent' : 'btn-outline-custom' }}">Shop Stock Distribution</a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
            <i class="bi bi-envelope-at"></i>
            <span>Send via Email</span>
        </button>
    </div>
</div>
@else
<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#emailReportModal">
        <i class="bi bi-envelope-at"></i>
        <span>Send via Email</span>
    </button>
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

<!-- Modal: Send Filtered Stock Report to Email -->
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white border-bottom border-secondary">
                <h5 class="modal-title fs-6 fw-bold" id="emailReportModalLabel">
                    <i class="bi bi-envelope-paper me-2 text-info"></i> Send Stock Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendStockReportEmailForm">
                @csrf
                <div class="modal-body py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Report Summary</div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-tag me-1"></i> {{ $type === 'main' ? 'Main Store Stock' : 'Shop Stock Distribution' }}</span>
                            <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-boxes me-1"></i> Total Units: {{ number_format($type === 'main' ? $mainTotalQty : $shopTotalQty) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 0.8rem;">
                            @if($type === 'main')
                            <span class="text-muted">Cost Value: <strong class="text-primary">TZS {{ number_format($mainTotalValue, 0) }}</strong></span>
                            <span class="text-muted">Expected Selling Value: <strong class="text-success">TZS {{ number_format($mainTotalSellValue, 0) }}</strong></span>
                            @else
                            <span class="text-muted">Total Valuation: <strong class="text-success">TZS {{ number_format($shopTotalValuation, 0) }}</strong></span>
                            @endif
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
                    <button type="submit" class="btn btn-sm btn-accent d-flex align-items-center gap-1" id="btnSubmitSendStockReportEmail">
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
                    text: '<i class="bi bi-envelope-at me-1"></i> Email Report',
                    className: 'btn btn-sm btn-primary me-2',
                    action: function() {
                        $('#emailReportModal').modal('show');
                    }
                },
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

    /* ── Send Stock Report AJAX ── */
    $('#sendStockReportEmailForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSubmitSendStockReportEmail');
        const originalBtnHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...');

        const formData = {
            _token: $('input[name="_token"]').val() || '{{ csrf_token() }}',
            emails: $('#reportRecipientEmails').val(),
            note: $('#reportEmailNote').val(),
            type: "{{ $type }}"
        };

        $.ajax({
            url: "{{ route('reports.stock.send-email') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                $('#emailReportModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Report Sent!',
                    text: res.message || 'Stock report has been successfully sent to email.',
                    confirmButtonColor: '#0088cc'
                });
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                let msg = 'Failed to send stock report email.';
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
