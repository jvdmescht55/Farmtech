@extends('layouts.admin')

@section('heading', 'Insights')

@php
    $r = fn ($n) => 'R '.number_format((float) $n, 0, '.', ' ');
    $bar = fn ($v, $max) => max(2, $max ? $v / $max * 100 : 0);
    $pri = ['high' => ['bg-red-50 border-red-200', 'bg-red-500', 'High'], 'medium' => ['bg-amber-50 border-amber-200', 'bg-amber-500', 'Medium'], 'low' => ['bg-gray-50 border-gray-200', 'bg-gray-400', 'Low']];
@endphp

@section('content')
<div class="space-y-6">

    {{-- Action list --}}
    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 class="font-semibold">What needs your attention</h2>
            <span class="text-xs text-gray-400 whitespace-nowrap">computed {{ now()->format('d M H:i') }}</span>
        </div>
        <ul class="divide-y">
            @forelse ($actions as [$level, $text, $url, $cta])
                <li class="px-5 py-3 flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-1 {{ $pri[$level][0] }} border-l-4">
                    <span class="w-2 h-2 rounded-full {{ $pri[$level][1] }} shrink-0"></span>
                    <span class="text-sm flex-1 min-w-0">{{ $text }}</span>
                    <a href="{{ $url }}" class="text-sm font-semibold text-farmtech-green whitespace-nowrap hover:underline pl-6 sm:pl-0">{{ $cta }} →</a>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-gray-500">Nothing flagged — everything checks out.</li>
            @endforelse
        </ul>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        @foreach ([
            ['Live products', number_format($kpis['live']), $kpis['rfidLive'].' RFID'],
            ['Revenue · 30 days', $r($kpis['revenue30']), $kpis['orders30'].' orders'],
            ['Revenue · all time', $r($kpis['revenueAll']), $kpis['ordersAll'].' orders'],
            ['Avg order value', $kpis['aov'] ? $r($kpis['aov']) : '—', 'paid orders'],
            ['Avg margin (live)', $kpis['avgMargin'].'%', 'across catalogue'],
            ['Product clicks', number_format($kpis['clicks']), number_format($kpis['reviews']).' reviews · ★ '.$kpis['avgRating']],
        ] as [$label, $value, $sub])
            <div class="bg-white border rounded-xl p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-bold mt-1 tabular-nums">{{ $value }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    {{-- Revenue by month --}}
    <div class="bg-white border rounded-xl p-5">
        <div class="flex items-baseline justify-between">
            <h2 class="font-semibold">Revenue by month</h2>
            <span class="text-xs text-gray-400">paid &amp; fulfilled orders, last 12 months</span>
        </div>
        @php($maxRev = $months->max('revenue'))
        @if ($maxRev > 0)
            <div class="mt-5 h-44 flex items-end gap-2">
                @foreach ($months as $m => $v)
                    <div class="flex-1 h-full flex flex-col justify-end items-center gap-1" title="{{ $r($v['revenue']) }} · {{ $v['orders'] }} orders">
                        <div class="w-full rounded-t bg-farmtech-green" style="height: {{ $bar($v['revenue'], $maxRev) }}%"></div>
                        <div class="text-[10px] text-gray-400">{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M') }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 mt-4">No paid orders yet. The first one will start this chart. Until then, clicks (below) are the best demand signal.</p>
        @endif
    </div>

    <div class="grid xl:grid-cols-3 gap-6">
        {{-- Category mix --}}
        <div class="xl:col-span-2 bg-white border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b"><h2 class="font-semibold">Category performance</h2><p class="text-xs text-gray-500 mt-0.5">Clicks per product shows where demand beats supply — add products where it's high.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <tr><th class="px-4 py-2.5">Category</th><th class="px-4 py-2.5 text-right">Live</th><th class="px-4 py-2.5 text-right">Avg price</th><th class="px-4 py-2.5 text-right">Avg margin</th><th class="px-4 py-2.5">Clicks</th><th class="px-4 py-2.5 text-right">Per product</th><th class="px-4 py-2.5 text-right">Sold</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @php($maxClicks = $categoryMix->max('clicks'))
                        @foreach ($categoryMix as $c)
                            <tr class="{{ $c['is_rfid'] ? 'bg-emerald-50/60' : '' }}">
                                <td class="px-4 py-2.5 font-medium whitespace-nowrap"><a href="{{ route('category.show', $c['slug']) }}" class="hover:underline" target="_blank">{{ $c['label'] }}</a>@if($c['is_rfid'])<span class="ml-1 text-[10px] font-bold text-emerald-700">LEAD</span>@endif</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $c['count'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums whitespace-nowrap">{{ $r($c['avg_price']) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $c['avg_margin'] < 15 ? 'text-red-600' : '' }}">{{ $c['avg_margin'] }}%</td>
                                <td class="px-4 py-2.5 w-40"><div class="flex items-center gap-2"><div class="h-2 rounded bg-farmtech-gold" style="width: {{ $bar($c['clicks'], $maxClicks) }}%"></div><span class="text-xs tabular-nums text-gray-500">{{ number_format($c['clicks']) }}</span></div></td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $c['clicks_per_product'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $c['units'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            @foreach (['Margin spread (live)' => [$marginBuckets, 'bg-farmtech-green'], 'Price bands (live)' => [$priceBuckets, 'bg-farmtech-gold']] as $title => [$buckets, $color])
                <div class="bg-white border rounded-xl p-5">
                    <h2 class="font-semibold mb-4">{{ $title }}</h2>
                    @php($maxB = $buckets->max())
                    @foreach ($buckets as $label => $n)
                        <div class="flex items-center gap-3 text-sm mb-2">
                            <span class="w-20 text-gray-500 text-xs">{{ $label }}</span>
                            <div class="flex-1 h-2.5 bg-gray-100 rounded"><div class="h-full rounded {{ $color }}" style="width: {{ $bar($n, $maxB) }}%"></div></div>
                            <span class="w-8 text-right tabular-nums">{{ $n }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="bg-white border rounded-xl p-5 text-sm">
                <h2 class="font-semibold mb-2">Pricing inputs</h2>
                <div class="flex justify-between"><span class="text-gray-500">USD/ZAR</span><span class="tabular-nums font-semibold">{{ $fx ? number_format($fx->rate, 4) : '—' }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-gray-500">Rate updated</span><span class="{{ $fx && $fx->updated_at->lt(now()->subDays(7)) ? 'text-red-600' : '' }}">{{ $fx?->updated_at->diffForHumans() ?? '—' }}</span></div>
                <div class="flex justify-between mt-1"><span class="text-gray-500">Live with video</span><span>{{ $kpis['withVideo'] }} / {{ $kpis['live'] }}</span></div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b"><h2 class="font-semibold">Most viewed products</h2></div>
            <ol class="divide-y text-sm">
                @foreach ($topClicked as $p)
                    <li class="px-5 py-2.5 flex items-center gap-3">
                        <span class="text-gray-400 w-5 tabular-nums">{{ $loop->iteration }}</span>
                        <a href="{{ route('products.show', $p->slug) }}" target="_blank" class="flex-1 truncate hover:underline">{{ $p->title }}</a>
                        <span class="text-xs text-gray-500 tabular-nums">{{ number_format($p->click_count) }} clicks</span>
                        <span class="text-xs tabular-nums w-24 text-right">{{ $r($p->retail_price_zar) }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b"><h2 class="font-semibold">Best sellers</h2></div>
            <ol class="divide-y text-sm">
                @forelse ($topSellers as $s)
                    <li class="px-5 py-2.5 flex items-center gap-3">
                        <span class="text-gray-400 w-5">{{ $loop->iteration }}</span>
                        <span class="flex-1 truncate">{{ $s['product']?->title ?? 'Deleted product' }}</span>
                        <span class="text-xs text-gray-500">{{ $s['units'] }} sold</span>
                        <span class="text-xs tabular-nums w-24 text-right">{{ $r($s['revenue']) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-gray-500">No sales yet.</li>
                @endforelse
            </ol>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-4">Sourcing pipeline</h2>
            @php($maxP = $pipeline->max())
            @foreach (['pending_review' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'archived' => 'Archived'] as $k => $l)
                <div class="flex items-center gap-3 text-sm mb-2">
                    <span class="w-28 text-gray-500 text-xs">{{ $l }}</span>
                    <div class="flex-1 h-2.5 bg-gray-100 rounded"><div class="h-full rounded bg-farmtech-green" style="width: {{ $bar($pipeline[$k] ?? 0, $maxP) }}%"></div></div>
                    <span class="w-10 text-right tabular-nums">{{ $pipeline[$k] ?? 0 }}</span>
                </div>
            @endforeach
            <h3 class="text-xs uppercase text-gray-500 mt-5 mb-2">Approved per week</h3>
            <div class="flex items-end gap-1 h-16">
                @php($maxW = $approvedPerWeek->max())
                @forelse ($approvedPerWeek as $w => $n)
                    <div class="flex-1 bg-farmtech-gold/80 rounded-t" style="height: {{ $bar($n, $maxW) }}%" title="Week of {{ $w }}: {{ $n }}"></div>
                @empty
                    <span class="text-xs text-gray-400">No approvals in 8 weeks.</span>
                @endforelse
            </div>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-3">Top rejection reasons</h2>
            <ul class="text-sm space-y-2">
                @forelse ($rejectionReasons as $reason => $n)
                    <li class="flex gap-3"><span class="tabular-nums font-semibold w-6">{{ $n }}</span><span class="text-gray-600">{{ $reason }}</span></li>
                @empty
                    <li class="text-gray-500">None recorded.</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-3">Lowest margins (live)</h2>
            <ul class="text-sm space-y-2">
                @forelse ($lowMargin as $p)
                    <li class="flex gap-3"><span class="tabular-nums font-semibold text-red-600 w-12">{{ (float) $p->profit_margin_pct }}%</span><a href="{{ route('products.show', $p->slug) }}" target="_blank" class="truncate hover:underline">{{ $p->title }}</a></li>
                @empty
                    <li class="text-gray-500">All live products are at or above 15%.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-3">Orders by province</h2>
            <ul class="text-sm space-y-2">
                @forelse ($provinces as $p)
                    <li class="flex justify-between"><span>{{ $p->province ?: 'Unknown' }}</span><span class="tabular-nums">{{ $p->c }} · {{ $r($p->t) }}</span></li>
                @empty
                    <li class="text-gray-500">No orders yet.</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white border rounded-xl p-5">
            <h2 class="font-semibold mb-3">Order status</h2>
            <ul class="text-sm space-y-2">
                @forelse ($orderStatus as $s => $n)
                    <li class="flex justify-between"><span>{{ \App\Enums\OrderStatus::tryFrom($s)?->label() ?? $s }}</span><span class="tabular-nums">{{ $n }}</span></li>
                @empty
                    <li class="text-gray-500">No orders yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Herd Management --}}
    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold">Herd Management platform</h2>
            <a href="{{ route('admin.licenses.index') }}" class="text-sm font-semibold text-farmtech-green hover:underline">Licences &amp; customers →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 divide-x divide-y md:divide-y-0">
            @foreach ([
                ['Codes issued', $licences->total ?? 0],
                ['Activated', ($licences->active ?? 0).(($licences->total ?? 0) ? ' ('.round(($licences->active ?? 0) / $licences->total * 100).'%)' : '')],
                ['Unused', $licences->unused ?? 0],
                ['Customers', $herd['customers']],
                ['Synced · 7 days', $herd['active7']],
                ['Animals managed', number_format($herd['animals'])],
                ['Scans · 30 days', number_format($herd['scans30'])],
            ] as [$l, $v])
                <div class="p-4"><div class="text-xs uppercase text-gray-500">{{ $l }}</div><div class="text-xl font-bold mt-1 tabular-nums">{{ $v }}</div></div>
            @endforeach
        </div>
        <div class="overflow-x-auto border-t">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                    <tr><th class="px-4 py-2.5">Customer</th><th class="px-4 py-2.5 text-right">Herd</th><th class="px-4 py-2.5 text-right">Readers</th><th class="px-4 py-2.5 text-right">Catalogues</th><th class="px-4 py-2.5">Last sync</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($customers as $c)
                        <tr>
                            <td class="px-4 py-2.5">{{ $c->farm_name ?: $c->name }}<div class="text-xs text-gray-400">{{ $c->email }}</div></td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $c->herd_count }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $c->readers_count }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $c->catalogues_count }}</td>
                            <td class="px-4 py-2.5 {{ $c->readers_max_last_synced_at ? '' : 'text-red-600' }}">{{ $c->readers_max_last_synced_at ? \Carbon\Carbon::parse($c->readers_max_last_synced_at)->diffForHumans() : 'Never' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-gray-500">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($herd['breeds']->isNotEmpty())
            <div class="px-5 py-3 border-t text-xs text-gray-500">Breeds managed: @foreach ($herd['breeds'] as $b => $n){{ $b }} ({{ $n }}){{ $loop->last ? '' : ' · ' }}@endforeach</div>
        @endif
    </div>
</div>
@endsection
