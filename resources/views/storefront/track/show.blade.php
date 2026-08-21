@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — Farmtech')

@php
    $stageLabels = \App\Enums\OrderStatus::trackingStageLabels();
    $currentStage = $order->status->trackingStageIndex();
    $courierUrl = \App\Services\CourierTrackingLinks::urlFor($order->courier_name);
@endphp

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-16">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1">Order Tracking</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-1">{{ $order->order_number }}</h1>
        <p class="text-ink-secondary mb-8">Placed {{ $order->created_at->format('j F Y') }} by {{ $order->customer_name }}</p>

        @if ($order->status === \App\Enums\OrderStatus::Cancelled)
            <div class="border border-border bg-canvas rounded-xl p-6 mb-8">
                <p class="font-semibold text-brand-900">This order was cancelled.</p>
                <p class="text-sm text-ink-secondary mt-1">If you believe this is a mistake, contact <a href="mailto:support@farmtech.co.za" class="text-mint-dark hover:underline">support@farmtech.co.za</a> with your order number.</p>
            </div>
        @else
            {{-- 5-stage shipment stepper --}}
            <div class="bg-white border border-border rounded-xl p-6 mb-8">
                <div class="flex items-start">
                    @foreach ($stageLabels as $i => $label)
                        <div class="flex-1 flex flex-col items-center text-center relative">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold border-2 {{ $i <= $currentStage ? 'bg-mint border-mint text-white' : 'bg-white border-border text-ink-muted' }}">
                                @if ($i < $currentStage)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <span class="mt-2 text-[11px] leading-tight px-1 {{ $i <= $currentStage ? 'text-brand-900 font-semibold' : 'text-ink-muted' }}">{{ $label }}</span>
                            @if (!$loop->last)
                                <div class="absolute top-3.5 left-1/2 w-full h-0.5 {{ $i < $currentStage ? 'bg-mint' : 'bg-border' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($order->status === \App\Enums\OrderStatus::Dispatched && $order->tracking_number)
                <div class="border border-border bg-white rounded-xl p-6 mb-8">
                    <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-2">Courier Details</p>
                    <p class="text-sm text-charcoal">{{ $order->courier_name ?: 'Courier' }} — <span class="font-mono">{{ $order->tracking_number }}</span></p>
                    @if ($courierUrl)
                        <a href="{{ $courierUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-3 text-sm font-semibold text-mint-dark hover:text-mint-darker transition">
                            Track on {{ $order->courier_name }}'s website
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    @else
                        <p class="text-xs text-ink-secondary mt-2">Use the tracking number above on {{ $order->courier_name }}'s own tracking page.</p>
                    @endif
                </div>
            @endif
        @endif

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="border border-border bg-white rounded-xl p-6">
                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">Delivery Address</p>
                <p class="text-sm text-charcoal leading-relaxed">
                    {{ $order->city }}<br>
                    {{ $order->province }}<br>
                    {{ $order->postal_code }}
                </p>
                <p class="text-xs text-ink-muted mt-2">Full street address withheld here for privacy — it's on your confirmation email.</p>
            </div>
            <div class="border border-border bg-white rounded-xl p-6">
                <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">Order Total</p>
                <p class="font-mono text-2xl font-bold text-brand-900">R{{ number_format($order->total_zar, 0, '', ' ') }}</p>
                <p class="text-xs text-ink-secondary mt-1">incl. duty &amp; VAT — nothing extra on delivery</p>
            </div>
        </div>

        <div class="border border-border bg-white rounded-xl p-6 mt-6">
            <p class="text-xs font-semibold text-brand-900 uppercase tracking-wide mb-3">Hardware Manifest</p>
            <div class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <span class="text-charcoal">{{ $item->title_snapshot }} <span class="text-ink-muted">&times;{{ $item->quantity }}</span></span>
                        <span class="font-mono text-brand-900">R{{ number_format($item->line_total_zar, 0, '', ' ') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
