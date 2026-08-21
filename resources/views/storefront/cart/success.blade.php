@extends('layouts.storefront')

@section('title', 'Order Received — Farmtech')

@section('content')
    <div class="max-w-lg mx-auto text-center py-16">
        <h1 class="text-2xl font-bold mb-2">Thank you, {{ $order->customer_name }}!</h1>
        <p class="text-gray-600 mb-6">Your order <strong>{{ $order->order_number }}</strong> has been received. We'll email {{ $order->email }} once payment is confirmed.</p>
        <div class="bg-white border rounded-xl p-6 text-left">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-500">Order Total</span>
                <span class="font-semibold">R{{ number_format($order->total_zar, 0, '', ' ') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Status</span>
                <span class="font-semibold capitalize">{{ $order->payment_status }}</span>
            </div>
        </div>
        <a href="{{ route('home') }}" class="inline-block mt-6 text-farmtech-green font-medium">Continue shopping</a>
    </div>
@endsection
