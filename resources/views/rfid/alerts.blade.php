@extends('layouts.rfid')
@section('title', 'Alerts')
@section('eyebrow')We keep an eye on the herd, day and night @endsection
@section('actions')<a href="{{ route('rfid.alerts.export') }}" class="btn-secondary">CSV</a>@endsection

@php
    $sev = [
        'critical' => ['Today', 'bg-[#B0452F]', 'bg-[#B0452F]/8 border-[#B0452F]/25', 'text-[#B0452F]'],
        'warning' => ['This week', 'bg-ochre', 'bg-ochre/8 border-ochre/25', 'text-ochre-dark'],
        'info' => ['Good to know', 'bg-stone-light', 'bg-white border-hairline', 'text-stone'],
    ];
@endphp

@section('content')
<div class="grid grid-cols-3 gap-2 sm:gap-4">
    @foreach ($sev as $k => [$label, $dot])
        <a href="{{ route('rfid.alerts', request('severity') === $k ? [] : ['severity' => $k]) }}" class="panel p-4 sm:p-6 flex items-center justify-between transition hover:border-char {{ request('severity') === $k ? 'border-char' : '' }}">
            <div class="min-w-0"><div class="kpi-label !text-[10px] sm:!text-[12px] flex items-center gap-1.5 sm:gap-2"><span class="w-2 h-2 shrink-0 rounded-full {{ $dot }}"></span><span class="truncate">{{ $label }}</span></div><div class="kpi-num !text-[34px] sm:!text-[44px] mt-2 sm:mt-3">{{ $counts[$k] ?? 0 }}</div></div>
            <span class="hidden sm:inline text-stone-light">→</span>
        </a>
    @endforeach
</div>

<div class="mt-6 flex flex-wrap gap-2">
    <a href="{{ route('rfid.alerts', array_filter(['severity' => request('severity')])) }}" class="chip h-9 px-4 border {{ ! request('category') ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">All</a>
    @foreach (\App\Services\Herd\HerdAlerts::CATEGORIES as $k => $l)
        @if ($byCategory[$k] ?? 0)
            <a href="{{ route('rfid.alerts', array_filter(['severity' => request('severity'), 'category' => $k])) }}" class="chip h-9 px-4 border {{ request('category') === $k ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">{{ $l }} <span class="opacity-60">{{ $byCategory[$k] }}</span></a>
        @endif
    @endforeach
    <a href="{{ route('rfid.alerts', ['dismissed' => request()->boolean('dismissed') ? null : 1]) }}" class="chip h-9 px-4 ml-auto text-stone hover:text-char">{{ request()->boolean('dismissed') ? 'Hide handled' : 'Show handled' }}</a>
</div>

@php
    // Four or more of the same alert become one card — easier to take in than a long list.
    $groups = $alerts->groupBy(fn ($a) => $a['title'].'|'.$a['severity'].'|'.(int) $a['dismissed'])
        ->map(fn ($g) => $g->count() >= 4 ? collect([['group' => $g] + $g->first()]) : $g)->flatten(1);
@endphp
<div class="mt-6 space-y-3">
    @forelse ($groups as $a)
        @php($g = $a['group'] ?? null)
        <div class="rounded-2xl border {{ $sev[$a['severity']][2] }} p-5 sm:p-6 grid sm:grid-cols-[1fr_auto] gap-4 items-start {{ $a['dismissed'] ? 'opacity-50' : '' }}" @if ($g) x-data="{ open: false }" @endif>
            <div class="flex gap-4 min-w-0">
                <span class="mt-2 w-2.5 h-2.5 shrink-0 rounded-full {{ $sev[$a['severity']][1] }}"></span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-medium text-lg">{{ $a['title'] }}</span>
                        @if ($g)
                            <span class="chip bg-char text-sand">{{ $g->count() }} animals</span>
                        @elseif ($a['animal'])
                            <a href="{{ route('rfid.animals.show', $a['animal']) }}" class="font-num text-sm link-u">{{ $a['animal']->visual_id }}</a>
                        @endif
                        <span class="text-xs {{ $sev[$a['severity']][3] }}">{{ \App\Services\Herd\HerdAlerts::CATEGORIES[$a['category']] }}</span>
                    </div>
                    @if ($g)
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($g->take(12) as $x)
                                @if ($x['animal'])<a href="{{ route('rfid.animals.show', $x['animal']) }}" class="chip bg-white border border-hairline font-num hover:border-char">{{ $x['animal']->visual_id }}</a>@endif
                            @endforeach
                            @if ($g->count() > 12)<button type="button" @click="open = !open" class="chip text-stone underline" x-text="open ? 'Show less' : '+ {{ $g->count() - 12 }} more'"></button>@endif
                        </div>
                        <ul x-show="open" x-cloak class="mt-3 text-sm text-stone space-y-1">
                            @foreach ($g->slice(12) as $x)
                                <li>@if ($x['animal'])<a href="{{ route('rfid.animals.show', $x['animal']) }}" class="font-num link-u text-char">{{ $x['animal']->visual_id }}</a> — @endif{{ $x['detail'] }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-stone text-sm">e.g. {{ $a['detail'] }}</p>
                    @else
                        <p class="mt-1 text-stone">{{ $a['detail'] }}</p>
                    @endif
                    @if ($a['action'])<p class="mt-2 text-sm"><span class="text-stone-light">What to do:</span> {{ $a['action'] }}</p>@endif
                </div>
            </div>
            <div class="flex gap-2 sm:justify-end">
                @if ($a['dismissed'])
                    @unless ($g)<form method="POST" action="{{ route('rfid.alerts.restore') }}">@csrf<input type="hidden" name="key" value="{{ $a['key'] }}"><button class="btn-line btn-sm">Show again</button></form>@endunless
                @else
                    @foreach ([[7, 'btn-line', 'Snooze 7 days'], [null, 'btn-dark', $g ? 'All done ✓' : 'Done ✓']] as [$days, $cls, $label])
                        <form method="POST" action="{{ route('rfid.alerts.dismiss') }}">@csrf
                            @if ($g) @foreach ($g as $x)<input type="hidden" name="keys[]" value="{{ $x['key'] }}">@endforeach @else <input type="hidden" name="key" value="{{ $a['key'] }}"> @endif
                            @if ($days)<input type="hidden" name="days" value="{{ $days }}">@endif
                            <button class="{{ $cls }} btn-sm">{{ $label }}</button>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>
    @empty
        <div class="panel p-16 text-center">
            <div class="h-display text-5xl">All lekker.</div>
            <p class="mt-3 text-stone">No alerts. The herd looks healthy — keep it up.</p>
        </div>
    @endforelse
</div>

<details class="mt-10 panel p-6 text-sm text-stone">
    <summary class="cursor-pointer text-char font-medium">What do we watch for?</summary>
    <ul class="mt-4 grid md:grid-cols-2 gap-x-10 gap-y-2 leading-relaxed">
        <li><strong class="text-char">Weight loss</strong> — any drop since the last weighing; 5% or more is urgent. It's the earliest sign of illness.</li>
        <li><strong class="text-char">Not thriving</strong> — growing at less than half the rate of others the same age.</li>
        <li><strong class="text-char">Low birth weight</strong> — lambs under 3 kg, kids under 2.5 kg, calves under 25 kg.</li>
        <li><strong class="text-char">Twins &amp; triplets</strong> — about 15% and 33% losses against 10% for singles.</li>
        <li><strong class="text-char">Due / overdue</strong> — from mating dates (147 days sheep, 150 goats, 283 cattle).</li>
        <li><strong class="text-char">Pregnancy scans</strong> — twins, triplets and empties.</li>
        <li><strong class="text-char">Inbreeding</strong> — parents that are close family on the pedigree.</li>
        <li><strong class="text-char">Withdrawal periods</strong> — don't sell or slaughter before the date.</li>
        <li><strong class="text-char">Missed drinks</strong> (KraalTrac Watch) and <strong class="text-char">not seen</strong> — 60+ days unscanned; 120+ days is a warning.</li>
        <li><strong class="text-char">Scanned after death/sale</strong>, <strong class="text-char">duplicate tags</strong> and <strong class="text-char">odd weight jumps</strong> (probably misreads).</li>
        <li><strong class="text-char">Weaning overdue</strong>, <strong class="text-char">lost lambs</strong>, <strong class="text-char">old ewes</strong> to cull, and <strong class="text-char">custom device limits</strong>.</li>
    </ul>
    <p class="mt-4">Every farm's different — tell us if a limit doesn't suit yours and we'll tune it. This is a helping hand, not a vet.</p>
</details>
@endsection
