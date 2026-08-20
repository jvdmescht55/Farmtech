@extends('layouts.admin')

@section('heading', 'Dashboard')

@section('content')
    <p class="text-sm text-gray-500 mb-6">Last 30 days ({{ $since->format('d M Y') }} – {{ now()->format('d M Y') }}) · {{ $orderCount }} paid order{{ $orderCount === 1 ? '' : 's' }}</p>

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Sales</p>
            <p class="text-2xl font-bold">R{{ number_format($totalSalesZar, 2) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Freight &amp; Customs Costs</p>
            <p class="text-2xl font-bold">R{{ number_format($totalFreightCustomsZar, 2) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Net Profit</p>
            <p class="text-2xl font-bold text-farmtech-green-dark">R{{ number_format($totalNetProfitZar, 2) }}</p>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Sales are the subtotal of orders with a confirmed (paid) payment status only — pending/unpaid orders aren't counted. Cost figures come from each sold line item's product landed-cost breakdown; products without one contribute R0, so net profit is understated rather than guessed.</p>
@endsection
