@extends('layouts.admin')
@section('heading', 'Insights')
@section('content')
@php
    $r = fn ($v) => 'R'.number_format($v, 0, '.', ' ');
    $pri = ['high' => ['bg-[#B0452F]/5', 'bg-[#B0452F]'], 'medium' => ['bg-ochre/5', 'bg-ochre'], 'low' => ['bg-white', 'bg-stone-light']];
    $max = max(1, $months->max('value'));
@endphp

{{-- What needs doing --}}
<div class="panel overflow-hidden">
    <div class="panel-head"><div class="panel-title">What needs your attention</div><span class="text-xs text-stone">{{ now()->format('j M H:i') }}</span></div>
    <ul class="divide-y divide-hairline">
        @forelse ($actions as [$level, $text, $url, $cta])
            <li class="px-5 py-3.5 flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-1 {{ $pri[$level][0] }}">
                <span class="w-2 h-2 shrink-0 rounded-full {{ $pri[$level][1] }}"></span>
                <span class="text-sm flex-1 min-w-0">{{ $text }}</span>
                <a href="{{ $url }}" class="text-sm font-medium link-u whitespace-nowrap pl-6 sm:pl-0">{{ $cta }} →</a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-stone">All caught up. Lekker.</li>
        @endforelse
    </ul>
</div>

{{-- Numbers --}}
<div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ([
        ['Sales this month', $r($kpis['revenueMonth']), $kpis['paidOrders'].' paid orders, '.$r($kpis['revenueAll']).' all time'],
        ['Waiting for payment', $r($kpis['awaiting']), 'EFT orders not paid yet'],
        ['Reserved for next batch', $kpis['reservedUnits'], 'units, no money in yet'],
        ['Open leads', $kpis['leadsOpen'], 'enquiries to answer'],
        ['Customers', $kpis['customers'], $kpis['newCustomers'].' joined in the last 30 days'],
        ['Devices', $kpis['devices'], $kpis['devicesOnline'].' online in the last 24 h'],
        ['Scans this week', number_format($kpis['scans7']), 'weights and reads, all farms'],
        ['Animals tracked', number_format($kpis['animals']), 'in farmers\' herd books'],
    ] as [$label, $value, $sub])
        <div class="panel p-5">
            <div class="kpi-label">{{ $label }}</div>
            <div class="kpi-num mt-2 !text-[36px]">{{ $value }}</div>
            <div class="text-xs text-stone mt-1">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="mt-6 grid xl:grid-cols-5 gap-6">
    {{-- Sales by month --}}
    <div class="xl:col-span-3 panel p-6">
        <div class="flex items-baseline justify-between"><div class="panel-title">Sales by month</div><span class="text-xs text-stone">paid orders incl. VAT</span></div>
        <div class="mt-6 h-48 flex items-end gap-2">
            @foreach ($months as $m)
                <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end" title="{{ $m['label'] }}: {{ $r($m['value']) }}">
                    <div class="w-full rounded-t-md {{ $m['value'] ? 'bg-ochre' : 'bg-sand-deep' }}" style="height: {{ max(2, $m['value'] / $max * 100) }}%"></div>
                    <span class="text-[11px] text-stone">{{ $m['label'] }}</span>
                </div>
            @endforeach
        </div>
        @if (! $months->sum('value'))<p class="mt-4 text-sm text-stone">No paid orders yet. They'll show up here as they come in.</p>@endif
    </div>

    {{-- Per product --}}
    <div class="xl:col-span-2 panel overflow-hidden">
        <div class="panel-head"><div class="panel-title">Per product</div><a href="{{ route('admin.listings.index') }}" class="text-sm link-u">Listings →</a></div>
        <ul class="divide-y divide-hairline">
            @foreach ($listings as $row)
                @php($l = $row['listing'])
                <li class="px-5 py-3.5">
                    <div class="flex justify-between gap-3"><span class="font-medium">{{ $l->name }}</span><span class="text-xs {{ $l->is_published ? 'text-[#3F7A3A]' : 'text-stone' }}">{{ $l->is_published ? 'Live' : 'Draft' }} · {{ $l->stockLabel() }}</span></div>
                    <div class="mt-1 text-sm text-stone">{{ $row['ordered'] }} ordered · {{ $row['reserved'] }} reserved · {{ $r($row['revenue']) }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- Recent orders --}}
<div class="mt-6 panel overflow-hidden">
    <div class="panel-head"><div class="panel-title">Latest orders &amp; reservations</div><a href="{{ route('admin.orders.index') }}" class="text-sm link-u">All orders →</a></div>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Number</th><th>Customer</th><th>What</th><th>Type</th><th>Status</th><th class="text-right">Total</th><th>When</th></tr></thead>
            <tbody>
                @forelse ($recent as $o)
                    <tr class="cursor-pointer" onclick="location.href='{{ route('admin.orders.show', $o) }}'">
                        <td class="font-num whitespace-nowrap">{{ $o->order_number }}</td>
                        <td>{{ $o->customer_name }}@if ($o->farm_name)<div class="text-xs text-stone">{{ $o->farm_name }}</div>@endif</td>
                        <td class="text-sm">{{ $o->items->map(fn ($i) => $i->quantity.'× '.$i->title_snapshot)->implode(', ') }}</td>
                        <td class="text-sm">{{ ['reservation' => 'Reservation', 'mixed' => 'Order + reservation'][$o->kind] ?? 'Order' }}</td>
                        <td class="text-sm">{{ $o->status->label() }}</td>
                        <td class="text-right num">{{ (float) $o->total_zar ? $r($o->total_zar) : '—' }}</td>
                        <td class="text-sm text-stone whitespace-nowrap">{{ $o->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-stone">No orders yet. Your shop is ready for them.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
