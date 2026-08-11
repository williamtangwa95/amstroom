@extends('sales.print_layout')

@section('doc_title', 'Invoice #' . $sale->id)

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
        <div class="doc-type">Invoice</div>
        <div class="doc-ref">Invoice No: <strong>#{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
        <div class="doc-ref" style="margin-top:4px;">Date: <strong>{{ $sale->sale_date->format('d M Y') }}</strong></div>
        @if($sale->delivery_date)
        <div class="doc-ref">Delivery Date: <strong>{{ $sale->delivery_date->format('d M Y') }}</strong></div>
        @endif
    </div>
</div>

{{-- Bill To / Ship To --}}
<div class="info-grid" style="margin-bottom:6mm;">
    <div class="info-block">
        <div class="label">Bill To</div>
        <div class="value" style="font-weight:700;font-size:14px;">{{ $sale->customer_name ?? 'Walk-in Customer' }}</div>
        @if($sale->customer_id)<div style="font-size:12px;color:#555;">Customer ID: {{ $sale->customer_id }}</div>@endif
        @if($sale->customer_po_box)<div style="font-size:12px;color:#555;">P.O. Box: {{ $sale->customer_po_box }}</div>@endif
    </div>
    <div class="info-block">
        <div class="label">Deliver To</div>
        <div class="value">{{ $sale->deliver_to ?? '—' }}</div>
        @if($sale->delivery_date)
            <div style="font-size:12px;color:#555;">
                {{ $sale->delivery_date->format('d M Y') }}
                @if($sale->delivery_time) at {{ \Carbon\Carbon::parse($sale->delivery_time)->format('H:i') }}@endif
            </div>
        @endif
        @if($sale->terms_of_payment)
            <div style="font-size:12px;color:#555;">Terms: {{ $sale->terms_of_payment }}</div>
        @endif
    </div>
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
        @php $rowNum = 1; @endphp
        @foreach($sale->items->where('parent_id', null) as $item)
        <tr>
            <td>{{ $rowNum++ }}</td>
            <td style="font-weight:600;">{{ $item->display_name }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-right">
                @if($item->selling_price == 0)
                    Included
                @else
                    {{ number_format($item->selling_price, 2) }}
                @endif
            </td>
            <td class="text-right">
                @if($item->selling_price == 0)
                    Included
                @else
                    {{ number_format($item->quantity * $item->selling_price, 2) }}
                @endif
            </td>
        </tr>
        @if($item->components->isNotEmpty())
            @foreach($item->components as $component)
            <tr>
                <td></td>
                <td style="padding-left: 1.5rem; color: #555; font-size: 11px;">
                    <span style="color: #bbb; margin-right: 2px;">└─</span> {{ $component->display_name }}
                </td>
                <td class="text-center" style="color: #555; font-size: 11px;">{{ $component->quantity }}</td>
                <td class="text-right" style="color: #555; font-size: 11px; font-style: italic;">Included</td>
                <td class="text-right" style="color: #555; font-size: 11px; font-style: italic;">Included</td>
            </tr>
            @endforeach
        @endif
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
                <td colspan="2" style="padding:2px 10px;font-size:11px;color:#888;">VAT / Tax not included unless stated.</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
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
        <div class="sig-line">Authorized Signature / {{ $company['name'] }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-line">Customer Signature / Date</div>
    </div>
</div>

@endsection
