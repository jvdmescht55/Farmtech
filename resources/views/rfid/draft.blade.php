@extends('layouts.rfid')
@section('title', 'Sort by weight')
@section('eyebrow')Split the mob into groups by weight @endsection
@section('actions')<a href="{{ route('rfid.draft.export', request()->query()) }}" class="btn-primary">Download the list</a>@endsection

@section('content')
<form method="GET" class="panel p-5 grid sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
    <div><label class="field-label">Use weights from</label>
        <select name="source" class="field"><option value="latest">Each animal's latest</option>@foreach ($sessionDates as $d)<option value="{{ $d }}" @selected($source === $d)>Weigh day {{ \Carbon\Carbon::parse($d)->format('j M Y') }}</option>@endforeach</select></div>
    <div><label class="field-label">Animals</label>
        <select name="sex" class="field"><option value="">All</option><option value="F" @selected($sex === 'F')>Female</option><option value="M" @selected($sex === 'M')>Male</option></select></div>
    <div><label class="field-label">Split at (kg)</label><input name="cuts" value="{{ request('cuts', $cuts->implode(', ')) }}" placeholder="e.g. 35, 45" class="field font-num"></div>
    <div><label class="field-label">Target weight (kg)</label><input name="target" type="number" step="0.5" value="{{ $target }}" placeholder="e.g. 45" class="field font-num"></div>
    <button class="btn-dark h-11">Sort</button>
</form>
@if (! request('cuts') && $cuts->isNotEmpty())<p class="mt-3 text-xs text-stone">No split points given, so we made three even groups.</p>@endif

@if ($target)
    <div class="panel mt-6 grid grid-cols-3 divide-x divide-hairline overflow-hidden">
        <div class="p-6"><div class="kpi-label">Ready now</div><div class="kpi-num mt-3 text-ochre">{{ $ready }}</div><div class="text-sm text-stone mt-1">≥ {{ $target }} kg</div></div>
        <div class="p-6"><div class="kpi-label">Within 30 days</div><div class="kpi-num mt-3">{{ $within30 }}</div><div class="text-sm text-stone mt-1">at today's growth</div></div>
        <div class="p-6"><div class="kpi-label">Not growing</div><div class="kpi-num mt-3 {{ $stalled ? 'down' : '' }}">{{ $stalled }}</div><div class="text-sm text-stone mt-1">flat or going backwards</div></div>
    </div>
@endif

<div class="grid gap-6 mt-6 {{ count($bands) >= 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
    @foreach ($bands as $i => $band)
        <div class="panel overflow-hidden flex flex-col">
            <div class="px-6 pt-6 pb-5 border-b border-hairline">
                <div class="flex items-center justify-between"><span class="eyebrow">Group {{ $i + 1 }}</span><span class="chip {{ $i === count($bands) - 1 ? 'bg-ochre/15 text-ochre-dark' : 'bg-sand-deep text-stone' }}">{{ $band['stats']['n'] }} head</span></div>
                <div class="mt-3 font-headline text-4xl">{{ $band['label'] }}</div>
                <div class="text-sm text-stone mt-1 num">{{ $band['stats']['n'] ? 'average '.$band['stats']['mean'].' kg · '.round($band['stats']['n'] / max(1, $total) * 100).'% of the mob' : 'empty' }}</div>
            </div>
            <ul class="divide-y divide-hairline max-h-[28rem] overflow-y-auto">
                @foreach ($band['animals']->sortByDesc('kg') as $r)
                    <li class="px-6 py-2.5 flex items-center gap-3 text-sm">
                        <a href="{{ route('rfid.animals.show', $r->animal) }}" class="font-num font-medium link-u">{{ $r->animal->visual_id }}</a>
                        <span class="text-xs text-stone">{{ $r->animal->sexLabel() }}</span>
                        <span class="ml-auto num">{{ $r->kg }} kg</span>
                        @if ($target)
                            <span class="w-24 text-right text-xs num {{ $r->days_to_target === 0 ? 'text-ochre-dark font-medium' : ($r->days_to_target === null ? 'down' : 'text-stone') }}">
                                {{ $r->days_to_target === 0 ? 'Ready ✓' : ($r->days_to_target === null ? 'not growing' : $r->days_to_target.' days') }}
                            </span>
                        @else
                            <span class="w-20 text-right text-xs num text-stone">{{ $r->adg !== null ? ($r->adg > 0 ? '+' : '').$r->adg.' g/day' : '' }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@if ($total === 0)<p class="mt-10 text-center text-stone">No recent weights. Weigh first, then we sort.</p>@endif
@endsection
