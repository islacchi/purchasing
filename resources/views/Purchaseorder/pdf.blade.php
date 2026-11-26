<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        @page { margin: 18mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
        h1 { font-size: 16pt; margin: 0 0 2mm; color: #0e5266; }
        .muted { color: #6b7280; font-size: 9pt; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        th { background: #0e5266; color: #fff; font-size: 9pt; text-transform: uppercase; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row td { font-weight: bold; border-top: 2px solid #0e5266; }
        .meta { margin: 6mm 0; }
        .meta td { border: none; padding: 2px 0; }
        .meta .label { color: #6b7280; width: 32%; }
    </style>
</head>
<body>
    <div class="row">
        <div class="col" style="width:60%;">
            <h1>PURCHASE ORDER</h1>
            <p class="muted">Purchasing System</p>
        </div>
        <div class="col right" style="width:40%;">
            <p style="font-size:12pt; font-weight:bold;">{{ $purchaseOrder->po_number }}</p>
            <p class="muted">Status: {{ $purchaseOrder->statusLabel() }}</p>
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Supplier</td><td>{{ $purchaseOrder->supplier?->name ?? 'Supplier removed' }}</td></tr>
        <tr><td class="label">Issued on</td><td>{{ $purchaseOrder->issued_on?->format('M j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Expected delivery</td><td>{{ $purchaseOrder->expected_delivery_on?->format('M j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Payment terms</td><td>{{ $purchaseOrder->payment_terms ?: '—' }}</td></tr>
        <tr><td class="label">Deliver to</td><td>{{ $purchaseOrder->deliver_to ?: '—' }}</td></tr>
        @if ($purchaseOrder->quotation?->rfq_number)
            <tr><td class="label">RFQ number</td><td>{{ $purchaseOrder->quotation->rfq_number }}</td></tr>
        @endif
        @if ($purchaseOrder->quotation?->preplist?->name)
            <tr><td class="label">Prep list</td><td>{{ $purchaseOrder->quotation->preplist->name }}</td></tr>
        @endif
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:30%;">Item</th>
                <th style="width:9%;" class="center">Qty</th>
                <th style="width:9%;" class="center">Unit</th>
                <th style="width:12%;" class="right">Unit price</th>
                <th style="width:12%;" class="right">Quoted / box</th>
                <th style="width:13%;" class="right">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $item)
                @php
                    $quotBox = $item->box_size === null && $item->quotationItem?->supplier_box_size !== null
                        ? (int) $item->quotationItem->supplier_box_size : 0;
                    $unitPrice = $quotBox > 0 && $item->unit_price !== null
                        ? (float) $item->unit_price / $quotBox
                        : $item->unit_price;
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->item?->name ?? 'Item removed' }}</td>
                    <td class="center">{{ $item->qty }}</td>
                    <td class="center">{{ $item->item?->unit ?? '' }}</td>
                    <td class="right">{{ $item->unit_price === null ? '—' : 'PHP '.number_format($unitPrice, 2) }}</td>
                    <td class="right">{{ $item->quotationItem?->unit_price === null ? '—' : 'PHP '.number_format((float) $item->quotationItem->unit_price, 2) }}</td>
                    <td class="right">{{ $item->unit_price === null ? '—' : 'PHP '.number_format($item->extendedTotal(), 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" class="right">Line total</td>
                <td class="right">{{ $purchaseOrder->lineTotal() === 0.0 && $purchaseOrder->items->contains(fn ($i) => $i->unit_price === null) ? '—' : 'PHP '.number_format($purchaseOrder->lineTotal(), 2) }}</td>
            </tr>
            @if ((float) $purchaseOrder->delivery_fee > 0)
                <tr>
                    <td colspan="6" class="right">Delivery fee</td>
                    <td class="right">PHP {{ number_format((float) $purchaseOrder->delivery_fee, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="6" class="right">Grand total</td>
                <td class="right">{{ $purchaseOrder->items->contains(fn ($i) => $i->unit_price === null) ? '—' : 'PHP '.number_format($purchaseOrder->grandTotal(), 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($purchaseOrder->notes)
        <p style="margin-top:6mm;"><strong>Notes:</strong> {{ $purchaseOrder->notes }}</p>
    @endif

    <p class="muted" style="margin-top:8mm;">Generated by the Purchasing System.</p>
</body>
</html>
