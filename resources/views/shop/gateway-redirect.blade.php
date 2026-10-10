@extends('layouts.storefront')

@section('title', 'Redirecting to payment · Farmtech')

@section('content')
    <div class="max-w-md mx-auto text-center py-16">
        <h1 class="text-xl font-bold mb-2">Redirecting you to your payment provider…</h1>
        <p class="text-gray-500 mb-6">{{ isset($order) ? 'Order '.$order->order_number.' · R'.number_format($order->total_zar, 0, '', ' ') : ($label ?? '') }}</p>

        <form id="gateway-form" action="{{ $handoff['action_url'] }}" method="{{ $handoff['method'] === 'REDIRECT' ? 'GET' : 'POST' }}">
            @foreach ($handoff['fields'] as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="bg-farmtech-green text-white font-semibold px-8 py-3 rounded-md hover:bg-farmtech-green-dark">
                Continue to Payment
            </button>
        </form>
    </div>

    <script>
        document.getElementById('gateway-form').submit();
    </script>
@endsection
