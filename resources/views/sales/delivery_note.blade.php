@extends('sales.print_layout')

@section('doc_title', 'Delivery Note #' . $sale->id)

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
            @if($company['tin'])TIN: {{ $company['tin'] }}@endif
        </div>
    </div>
    <div class="doc-title-block">
        <div class="doc-type" style="color:#065f46;">Delivery Note</div>
        <div class="doc-ref">Delivery Note #: <strong>DN-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
        <div class="doc-ref" style="margin-top:4px;">Associated Invoice #: <strong>#{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
        @if($sale->delivery_date)
        <div class="doc-ref">Delivery Date: <strong>{{ $sale->delivery_date->format('d M Y') }}</strong>
            @if($sale->delivery_time) at {{ \Carbon\Carbon::parse($sale->delivery_time)->format('H:i') }}@endif
        </div>
        @endif
    </div>
</div>

{{-- Dispatch / Deliver Info --}}
<div class="info-grid" style="margin-bottom:6mm;">
    <div class="info-block">
        <div class="label">Dispatched From</div>
        <div class="value" style="font-weight:700;">{{ $company['name'] }}</div>
        @if($company['address'])<div style="font-size:12px;color:#555;">{{ $company['address'] }}</div>@endif
    </div>
    <div class="info-block">
        <div class="label">Delivered To</div>
        <div class="value" style="font-weight:700;">{{ $sale->customer_name ?? 'Walk-in Customer' }}</div>
        @if($sale->customer_id)<div style="font-size:12px;color:#555;">Customer ID: {{ $sale->customer_id }}</div>@endif
        @if($sale->customer_po_box)<div style="font-size:12px;color:#555;">P.O. Box: {{ $sale->customer_po_box }}</div>@endif
        @if($sale->deliver_to)<div style="font-size:12px;color:#555;">Location: {{ $sale->deliver_to }}</div>@endif
    </div>
</div>

{{-- Items Table --}}
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
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-center">
                <span class="badge-status badge-delivered">Delivered</span>
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
                <td class="text-center" style="color: #555; font-size: 11px;">{{ $component->quantity }}</td>
                <td class="text-center" style="color: #555; font-size: 11px;">
                    <span class="badge-status badge-delivered" style="opacity: 0.7;">Delivered</span>
                </td>
            </tr>
            @endforeach
        @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background:#f0fdf4;">
            <td colspan="2" style="font-weight:700;padding:8px 10px;">Total Items</td>
            <td class="text-center" style="font-weight:700;padding:8px 10px;">{{ $sale->items->sum('quantity') }}</td>
            <td class="text-center" style="font-weight:700;padding:8px 10px;">{{ $sale->items->sum('quantity') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- Goods Receipt --}}
<div style="background:#f0fdf4;border:1px solid #a7f3d0;border-radius:6px;padding:10px 14px;margin-bottom:8mm;">
    <strong style="font-size:12px;">Goods Receipt Confirmation</strong>
    <p style="font-size:12px;color:#374151;margin-top:4px;">
        I/We confirm that the goods described above have been received in good condition and correct quantities
        as per the delivery note above.
    </p>
</div>

{{-- Signatures --}}
<div class="signature-row">
    <div class="sig-box">
        <div class="sig-line">Dispatched by / {{ $company['name'] }}</div>
    </div>
    <div class="sig-box">
        <div class="sig-line">Received by / Name & Date</div>
    </div>
    <div class="sig-box">
        <div class="sig-line">Driver / Courier Signature</div>
    </div>
</div>

@endsection
