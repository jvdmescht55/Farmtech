@component('mail::message')
# Thanks, {{ $order->customer_name }} — we've got your order

Order **{{ $order->order_number }}** has been received. We'll email you again once payment is
confirmed, and again when it ships.

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->title_snapshot }} | {{ $item->quantity }} | R{{ number_format((float) $item->line_total_zar, 2) }} |
@endforeach
@endcomponent

**Total (incl. SA import duty & 15% VAT): R{{ number_format((float) $order->total_zar, 2) }}**

Nothing extra to pay on delivery — duties and VAT are already included above.

**Delivering to:**
{{ $order->address_line1 }}@if($order->address_line2), {{ $order->address_line2 }}@endif,
{{ $order->city }}, {{ $order->province }}, {{ $order->postal_code }}

Typical delivery is 7–12 business days via tracked Direct Express air freight once payment
clears.

Thanks for farming with Farmtech.
{{ config('app.name') }}
@endcomponent
