<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #SL-{{ $sale->id }} — {{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; width: 300px; margin: 0 auto; padding: 10px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 3px 0; vertical-align: top; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="text-center">
    <h2 style="margin:0 0 4px 0;font-size:16px;">AMSTROOM</h2>
    <div class="bold">{{ $sale->shop?->shop_name ?? 'Main Store (Owner)' }}</div>
    <div>{{ $sale->shop?->location ?? 'Main Store / HQ' }}</div>
    <div>Tel: {{ $sale->shop?->phone ?? '+255700000001' }}</div>
</div>

<div class="divider"></div>

<div>
    <div>Receipt No: #SL-{{ $sale->id }}</div>
    <div>Date: {{ $sale->sale_date->format('d/m/Y H:i') }}</div>
    <div>Seller: {{ $sale->seller->name }}</div>
    <div>Customer: {{ $sale->customer_name ?: 'Walk-in Customer' }}</div>
    <div>Payment: {{ strtoupper(str_replace('_',' ',$sale->payment_method)) }}</div>
</div>

<div class="divider"></div>

<table>
    <thead>
        <tr>
            <th class="text-start">Item</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $item)
        <tr>
            <td>{{ $item->display_name }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-end">{{ number_format($item->selling_price, 0) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="divider"></div>

<table>
    <tr class="bold">
        <td>TOTAL:</td>
        <td class="text-end">TZS {{ number_format($sale->total_amount, 0) }}</td>
    </tr>
</table>

<div class="divider"></div>

<div class="text-center" style="margin-top:15px;">
    <div>Thank you for shopping with us!</div>
    <div style="font-size:11px;margin-top:4px;">Goods once sold are non-refundable</div>
</div>

<div class="text-center no-print" style="margin-top:20px;">
    <button onclick="window.print()" style="padding:6px 16px;cursor:pointer;">Print Receipt</button>
    <a href="{{ route('sales.index') }}" style="display:block;margin-top:8px;">Back to Sales</a>
</div>

</body>
</html>
