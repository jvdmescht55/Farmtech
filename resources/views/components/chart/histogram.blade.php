{{-- Histogram from WeighStats::histogram(). --}}
@props(['bins' => [], 'unit' => 'kg', 'height' => 180, 'highlightFrom' => null])
@php($max = max(1, collect($bins)->max('count') ?? 1))
@if (empty($bins))
    <div class="grid place-items-center text-sm text-stone" style="height: {{ $height }}px">Nog nie genoeg data nie.</div>
@else
<div>
    <div class="flex items-end gap-[3px]" style="height: {{ $height }}px">
        @foreach ($bins as $b)
            <div class="group relative flex-1 h-full flex items-end">
                <div class="w-full rounded-t-[4px] transition-colors {{ $highlightFrom !== null && $b['from'] >= $highlightFrom ? 'bg-ochre' : 'bg-char/85 group-hover:bg-char' }}" style="height: {{ max(2, $b['count'] / $max * 100) }}%"></div>
                <div class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-char px-2 py-1 text-[11px] text-sand opacity-0 group-hover:opacity-100 transition">{{ $b['count'] }} · {{ $b['from'] }}–{{ $b['to'] }} {{ $unit }}</div>
            </div>
        @endforeach
    </div>
    <div class="mt-2 flex justify-between font-num text-[11px] text-stone-light"><span>{{ $bins[0]['from'] }}</span><span>{{ end($bins)['to'] }} {{ $unit }}</span></div>
</div>
@endif
