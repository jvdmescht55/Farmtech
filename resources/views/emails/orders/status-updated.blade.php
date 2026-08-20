@component('mail::message')
# Order {{ $order->order_number }}: {{ $order->status->label() }}

Hi {{ $order->customer_name }}, your order is now **{{ $order->status->label() }}**.

@if ($order->status->value === 'in_customs')
It's arrived in South Africa and is going through customs clearance — this is handled on your
behalf, there's nothing you need to do.
@elseif ($order->status->value === 'dispatched')
It's on its way to you.
@if ($order->courier_name || $order->tracking_number)

@component('mail::table')
| | |
|:---|:---|
@if ($order->courier_name)
| **Courier** | {{ $order->courier_name }} |
@endif
@if ($order->tracking_number)
| **Tracking number** | {{ $order->tracking_number }} |
@endif
@endcomponent
@endif
@endif

Thanks for farming with Farmtech.
{{ config('app.name') }}
@endcomponent
