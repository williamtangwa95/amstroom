@extends('sales.print_layout')

@php
    $isInvoice  = $document->type === 'invoice';
    $isProforma = $document->type === 'proforma';
    $isDelivery = $document->type === 'delivery';

    $typeColor = $isDelivery ? '#065f46' : ($isProforma ? '#a16207' : '#1a3c6e');
    $customerName = $document->customer_name ?: ($sale->customer_name ?: 'Walk-in Customer');
    $customerId   = $document->customer_id ?: $sale->customer_id;
    $customerPo   = $document->customer_po_box ?: $sale->customer_po_box;
    $deliverTo    = $document->deliver_to ?: $sale->deliver_to;
    $terms        = $document->terms_of_payment ?: $sale->terms_of_payment;
    $issueDate    = $document->issue_date ?: $sale->sale_date;
    $deliveryDate = $document->delivery_date ?: $sale->delivery_date;
    $deliveryTime = $document->delivery_time ?: $sale->delivery_time;
    $validityDate = $document->validity_date ?: $sale->validity_date;
    $grandTotal   = (float) ($document->total_amount ?: $sale->total_amount);
@endphp

@section('doc_title', $document->type_label . ' ' . $document->document_number)
@section('back_url', route('documents.index'))

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
            @if(!$isDelivery && $company['bank_name'])
                Bank: {{ $company['bank_name'] }}
                @if($company['bank_account'])  |  Acc: {{ $company['bank_account'] }}@endif
            @endif
        </div>
    </div>
    <div class="doc-title-block">
        <div class="doc-type" style="color:{{ $typeColor }};">{{ $document->type_label }}</div>
        <div class="doc-ref">Document No: <strong>{{ $document->document_number }}</strong></div>
        <div class="doc-ref">Sale Reference: <strong>#SL-{{ $sale->id }}</strong></div>
        @if($issueDate)
        <div class="doc-ref" style="margin-top:4px;">Issue Date: <strong>{{ $issueDate->format('d M Y') }}</strong></div>
        @endif
        @if($isProforma && $validityDate)
        <div class="doc-ref">Valid Until: <strong>{{ $validityDate->format('d M Y') }}</strong></div>
        @endif
        @if(($isDelivery || $isInvoice) && $deliveryDate)
        <div class="doc-ref">Delivery Date: <strong>{{ $deliveryDate->format('d M Y') }}</strong>@if($deliveryTime) at {{ \Carbon\Carbon::parse($deliveryTime)->format('H:i') }}@endif</div>
        @endif
    </div>
</div>

{{-- Party Details --}}
<div class="info-grid" style="margin-bottom:6mm;">
    <div class="info-block">
        <div class="label">{{ $isDelivery ? 'Dispatched From' : ($isProforma ? 'Customer / Client' : 'Bill To') }}</div>
        @if($isDelivery)
            <div class="value" style="font-weight:700;font-size:14px;">{{ $company['name'] }}</div>
            @if($company['address'])<div style="font-size:12px;color:#555;">{{ $company['address'] }}</div>@endif
            <div style="font-size:12px;color:#555;">Issued by: {{ $document->creator?->name ?? $sale->seller?->name ?? '—' }}</div>
        @else
            <div class="value" style="font-weight:700;font-size:14px;">{{ $customerName }}</div>
            @if($customerId)<div style="font-size:12px;color:#555;">Customer ID / TIN: {{ $customerId }}</div>@endif
            @if($customerPo)<div style="font-size:12px;color:#555;">P.O. Box: {{ $customerPo }}</div>@endif
        @endif
    </div>
    <div class="info-block">
        <div class="label">{{ $isDelivery ? 'Delivered To' : ($isProforma ? 'Prepared By' : 'Deliver To / Terms') }}</div>
        @if($isDelivery)
            <div class="value" style="font-weight:700;font-size:14px;">{{ $customerName }}</div>
            @if($customerId)<div style="font-size:12px;color:#555;">Customer ID / TIN: {{ $customerId }}</div>@endif
            @if($customerPo)<div style="font-size:12px;color:#555;">P.O. Box: {{ $customerPo }}</div>@endif
            @if($deliverTo)<div style="font-size:12px;color:#555;">Location: {{ $deliverTo }}</div>@endif
        @elseif($isProforma)
            <div class="value" style="font-weight:700;">{{ $document->creator?->name ?? $sale->seller?->name ?? '—' }}</div>
            <div style="font-size:12px;color:#555;">{{ $company['name'] }}</div>
            @if($deliverTo)<div style="font-size:12px;color:#555;">Deliver To: {{ $deliverTo }}</div>@endif
            @if($terms)<div style="font-size:12px;color:#555;">Terms: {{ $terms }}</div>@endif
        @else
            <div class="value">{{ $deliverTo ?: '—' }}</div>
            @if($terms)<div style="font-size:12px;color:#555;">Terms: {{ $terms }}</div>@endif
            <div style="font-size:12px;color:#555;">Prepared By: {{ $document->creator?->name ?? $sale->seller?->name ?? '—' }}</div>
        @endif
    </div>
</div>

{{-- Proforma Disclaimer --}}
@if($isProforma)
<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 12px;margin-bottom:6mm;font-size:12px;color:#78350f;">
    <strong>⚠ Proforma Invoice (Quotation):</strong> This is not an official tax invoice. Prices are subject to change until a final invoice is issued.
    @if($validityDate) Valid until <strong>{{ $validityDate->format('d M Y') }}</strong>.@endif
</div>
@endif

{{-- Items Table --}}
@if($isDelivery)
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item Description</th>
            <th class="text-center">Qty Ordered</th>
            <th class="text-center">Qty Delivered</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @php $rowNum = 1; @endphp
        @foreach($sale->items->where('parent_id', null) as $item)
        <tr>
            <td>{{ $rowNum++ }}</td>
            <td style="font-weight:600;">{{ $item->display_name }}</td>
            <td class="text-center">{{ $item->quantity }} <span style="font-size:10px;color:#555;">{{ $item->unit_symbol }}</span></td>
            <td class="text-center">{{ $item->quantity }} <span style="font-size:10px;color:#555;">{{ $item->unit_symbol }}</span></td>
            <td class="text-center"><span class="badge-status badge-delivered">Delivered</span></td>
        </tr>
        @if($item->components->isNotEmpty())
            @foreach($item->components as $component)
            <tr>
                <td></td>
                <td style="padding-left:1.5rem;color:#555;font-size:11px;">
                    <span style="color:#bbb;margin-right:2px;">└─</span> {{ $component->display_name }}
                </td>
                <td class="text-center" style="color:#555;font-size:11px;">{{ $component->quantity }} <span style="font-size:9px;">{{ $component->unit_symbol }}</span></td>
                <td class="text-center" style="color:#555;font-size:11px;">{{ $component->quantity }} <span style="font-size:9px;">{{ $component->unit_symbol }}</span></td>
                <td class="text-center" style="color:#555;font-size:11px;"><span class="badge-status badge-delivered" style="opacity:.7;">Delivered</span></td>
            </tr>
            @endforeach
        @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background:#f0fdf4;">
            <td colspan="2" style="font-weight:700;padding:8px 10px;">Total Units</td>
            <td class="text-center" style="font-weight:700;padding:8px 10px;">{{ $sale->items->sum('quantity') }}</td>
            <td class="text-center" style="font-weight:700;padding:8px 10px;">{{ $sale->items->sum('quantity') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
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
            <td class="text-center">{{ $item->quantity }} <span style="font-size:10px;color:#555;">{{ $item->unit_symbol }}</span></td>
            <td class="text-right">
                @if($item->selling_price == 0) Included @else {{ number_format($item->selling_price, 2) }} @endif
            </td>
            <td class="text-right">
                @if($item->selling_price == 0) Included @else {{ number_format($item->quantity * $item->selling_price, 2) }} @endif
            </td>
        </tr>
        @if($item->components->isNotEmpty())
            @foreach($item->components as $component)
            <tr>
                <td></td>
                <td style="padding-left:1.5rem;color:#555;font-size:11px;">
                    <span style="color:#bbb;margin-right:2px;">└─</span> {{ $component->display_name }}
                </td>
                <td class="text-center" style="color:#555;font-size:11px;">{{ $component->quantity }} <span style="font-size:9px;">{{ $component->unit_symbol }}</span></td>
                <td class="text-right" style="color:#555;font-size:11px;font-style:italic;">Included</td>
                <td class="text-right" style="color:#555;font-size:11px;font-style:italic;">Included</td>
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
                <td class="text-right">TZS {{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding:2px 10px;font-size:11px;color:#888;">
                    {{ $isProforma ? 'This is an estimated quotation total. Final invoice may differ.' : 'VAT / Tax not included unless stated.' }}
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total" style="{{ $isProforma ? 'background:#78350f;' : '' }}">
                <td>{{ $isProforma ? 'Estimated Grand Total' : 'Grand Total' }}</td>
                <td class="text-right">TZS {{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Bank / Payment Instructions --}}
@if($company['bank_name'] || $company['bank_account'])
<div class="bank-section">
    <strong>Payment Instructions:</strong>
    &nbsp; Bank: {{ $company['bank_name'] }}
    @if($company['bank_account'])&nbsp;|&nbsp; Account No: {{ $company['bank_account'] }}@endif
    @if($terms)&nbsp;|&nbsp; Terms: {{ $terms }}@endif
</div>
@endif
@endif

{{-- Optional Notes --}}
@if($document->notes)
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;margin-bottom:6mm;font-size:12px;color:#334155;">
    <strong>Notes / Remarks:</strong> {{ $document->notes }}
</div>
@endif

{{-- Delivery Receipt Box --}}
@if($isDelivery)
<div style="background:#f0fdf4;border:1px solid #a7f3d0;border-radius:6px;padding:10px 14px;margin-bottom:8mm;">
    <strong style="font-size:12px;">Goods Receipt Confirmation</strong>
    <p style="font-size:12px;color:#374151;margin-top:4px;">
        I/We confirm that the goods described above have been received in good condition and correct quantities as per Delivery Note <strong>{{ $document->document_number }}</strong>.
    </p>
</div>
@endif

{{-- Signatures --}}
<div class="signature-row">
    @if($isDelivery)
        <div class="sig-box"><div class="sig-line">Dispatched by / {{ $company['name'] }}</div></div>
        <div class="sig-box"><div class="sig-line">Received by / Name &amp; Date</div></div>
        <div class="sig-box"><div class="sig-line">Driver / Courier Signature</div></div>
    @else
        <div class="sig-box"><div class="sig-line">Authorized Signature / {{ $company['name'] }}</div></div>
        <div class="sig-box"><div class="sig-line">{{ $isProforma ? 'Customer Acceptance / Date' : 'Customer Signature / Date' }}</div></div>
    @endif
</div>

@endsection
