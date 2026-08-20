@extends('layouts.storefront')

@section('title', 'Track Your Order — Farmtech')

@section('content')
    <div class="max-w-lg mx-auto px-4 py-16">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold mb-1">Order Tracking</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mb-2">Track Your Order</h1>
        <p class="text-slate-600 leading-relaxed mb-8">Enter your order number and the email address used at checkout. Both are on your order confirmation email.</p>

        @if ($errors->any())
            <div class="mb-6 border border-red-200 bg-red-50 text-red-700 text-sm rounded-xl px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('track.show') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Order Number</label>
                <input type="text" name="order_number" value="{{ old('order_number') }}" required placeholder="FT-20260101-0001"
                       class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"
                       class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-mint/30 focus:border-mint">
            </div>
            <button type="submit" class="w-full bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-3 rounded-full transition">
                Track Order
            </button>
        </form>
    </div>
@endsection
