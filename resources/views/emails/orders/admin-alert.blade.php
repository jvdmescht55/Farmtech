@component('mail::message')
# New paid order — {{ $order->order_number }}

**{{ $order->customer_name }}** ({{ $order->email }}, {{ $order->phone }}) — R{{ number_format((float) $order->total_zar, 0, '', ' ') }} via {{ ucfirst($order->payment_gateway ?? 'unknown gateway') }}

@component('mail::table')
| Item | SKU | Qty | Line Total |
|:-----|:----|:---:|-----------:|
@foreach ($order->items as $item)
| {{ $item->title_snapshot }} | {{ $item->product?->sku ?? '—' }} | {{ $item->quantity }} | R{{ number_format((float) $item->line_total_zar, 0, '', ' ') }} |
@endforeach
@endcomponent

**Ship to:** {{ $order->address_line1 }}@if($order->address_line2), {{ $order->address_line2 }}@endif, {{ $order->city }}, {{ $order->province }} {{ $order->postal_code }}

@component('mail::button', ['url' => route('admin.orders.show', $order)])
Review Order
@endcomponent
@endcomponent
