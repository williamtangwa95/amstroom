@extends('sales.print_layout')

@section('doc_title', 'Proforma Invoice #' . $sale->id)

@section('content')

{{-- Header --}}
<div class="doc-header">
    <div class="brand-block">
        @if($company['logo'])
            <div class="logo-block" style="margin-bottom:6px;">
                <img src="{{ Storage::url($company['logo']) }}" alt="{{ $company['name'] }} Logo">
            </div>
        @endif
        <div class="company-name">{{ $company['name'] }}</div>
        @if($company['slogan'])<div class="slogan">{{ $company['slogan'] }}</div>@endif
        <div class="meta">
            @if($company['address']){{ $company['address'] }}<br>@endif
            @if($company['tin'])TIN: {{ $company['tin'] }}<br>@endif
            @if($company['bank_name'])
                Bank: {{ $company['bank_name'] }}
                @if($company['bank_account'])  |  Acc: {{ $company['bank_account'] }}@endif
            @endif
        </div>
    </div>
    <div class="doc-title-block">
        <div class="doc-type" style="color:#a16207;">Proforma Invoice</div>
        <div class="doc-ref">Proforma Ref #: <strong>PF-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
        <div class="doc-ref" style="margin-top:4px;">Date: <strong>{{ $sale->sale_date->format('d M Y') }}</strong></div>
        @if($sale->validity_date)
        <div class="doc-ref">Valid Until: <strong>{{ $sale->validity_date->format('d M Y') }}</strong></div>
        @endif
        @if($sale->terms_of_payment)
        <div class="doc-ref">Terms: <strong>{{ $sale->terms_of_payment }}</strong></div>
        @endif
    </div>
</div>

{{-- Client Info --}}
<div class="info-grid" style="margin-bottom:6mm;">
    <div class="info-block">
        <div class="label">Customer / Client</div>
        <div class="value" style="font-weight:700;font-size:14px;">{{ $sale->customer_name ?? 'Walk-in Customer' }}</div>
        @if($sale->customer_id)<div style="font-size:12px;color:#555;">Customer ID: {{ $sale->customer_id }}</div>@endif
        @if($sale->customer_po_box)<div style="font-size:12px;color:#555;">P.O. Box: {{ $sale->customer_po_box }}</div>@endif
    </div>
    <div class="info-block">
        <div class="label">Quoted By</div>
        <div class="value">{{ $sale->seller->name ?? '—' }}</div>
        <div style="font-size:12px;color:#555;">{{ $company['name'] }}</div>
        @if($sale->deliver_to)<div style="font-size:12px;color:#555;">Deliver To: {{ $sale->deliver_to }}</div>@endif
    </div>
</div>

{{-- Notice --}}
<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 12px;margin-bottom:6mm;font-size:12px;color:#78350f;">
    <strong>⚠ This is a Proforma Invoice (Quotation).</strong> It is not an official tax invoice. Prices are subject to change until a final invoice is issued.
    @if($sale->validity_date) Valid until <strong>{{ $sale->validity_date->format('d M Y') }}</strong>.@endif
</div>

{{-- Items Table --}}
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Unit Price (TZS)</th>
            <th class="text-right">Total (TZS)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->item->item_name }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-right">{{ number_format($item->selling_price, 2) }}</td>
            <td class="text-right">{{ number_format($item->quantity * $item->selling_price, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<div class="totals-section">
    <table class="totals-table">
        <tbody>
            <tr>
                <td>Sub Total</td>
                <td class="text-right">TZS {{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding:2px 10px;font-size:11px;color:#888;">This is an estimated total. Final invoice may differ.</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total" style="background:#78350f;">
                <td>Estimated Grand Total</td>
                <td class="text-right">TZS {{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Bank Details --}}
@if($company['bank_name'] || $company['bank_account'])
<div class="bank-section">
    <strong>Payment Instructions:</strong>
    &nbsp; Bank: {{ $company['bank_name'] }}
    @if($company['bank_account'])&nbsp;|&nbsp; Account No: {{ $company['bank_account'] }}@endif
    @if($sale->terms_of_payment)&nbsp;|&nbsp; Terms: {{ $sale->terms_of_payment }}@endif
</div>
@endif

{{-- Signatures --}}
<div class="signature-row">
    <div class="sig-box">
        <div class="sig-line">Authorized by / {{ $company['name'] }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-line">Customer Acceptance / Date</div>
    </div>
</div>

@endsection
