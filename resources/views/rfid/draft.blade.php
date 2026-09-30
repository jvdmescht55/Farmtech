@extends('layouts.rfid')
@section('title', 'Sorteer')
@section('eyebrow')Trek die kudde in groepe — op gewig @endsection
@section('actions')<a href="{{ route('rfid.draft.export', request()->query()) }}" class="btn-primary">Laai sorteerlys af</a>@endsection

@section('content')
<form method="GET" class="panel p-5 grid sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
    <div><label class="field-label">Gewigte uit</label>
        <select name="source" class="field"><option value="latest">Elke dier se nuutste</option>@foreach ($sessionDates as $d)<option value="{{ $d }}" @selected($source === $d)>Sessie {{ \Carbon\Carbon::parse($d)->format('j M Y') }}</option>@endforeach</select></div>
    <div><label class="field-label">Diere</label>
        <select name="sex" class="field"><option value="">Almal</option><option value="F" @selected($sex === 'F')>Ooie</option><option value="M" @selected($sex === 'M')>Ramme</option></select></div>
    <div><label class="field-label">Snypunte (kg)</label><input name="cuts" value="{{ request('cuts', $cuts->implode(', ')) }}" placeholder="bv. 35, 45" class="field font-num"></div>
    <div><label class="field-label">Teikengewig (kg)</label><input name="target" type="number" step="0.5" value="{{ $target }}" placeholder="bv. 45" class="field font-num"></div>
    <button class="btn-dark h-11">Sorteer</button>
</form>
@if (! request('cuts') && $cuts->isNotEmpty())<p class="mt-3 text-xs text-stone">Geen snypunte gegee nie — ons het die kudde in drie ewe groot groepe verdeel.</p>@endif

@if ($target)
    <div class="panel mt-6 grid grid-cols-3 divide-x divide-hairline overflow-hidden">
        <div class="p-6"><div class="kpi-label">Reg vir die mark</div><div class="kpi-num mt-3 text-ochre">{{ $ready }}</div><div class="text-sm text-stone mt-1">≥ {{ $target }} kg</div></div>
        <div class="p-6"><div class="kpi-label">Binne 30 dae</div><div class="kpi-num mt-3">{{ $within30 }}</div><div class="text-sm text-stone mt-1">teen huidige groei</div></div>
        <div class="p-6"><div class="kpi-label">Groei staan stil</div><div class="kpi-num mt-3 {{ $stalled ? 'down' : '' }}">{{ $stalled }}</div><div class="text-sm text-stone mt-1">geen of negatiewe groei</div></div>
    </div>
@endif

<div class="grid gap-6 mt-6 {{ count($bands) >= 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
    @foreach ($bands as $i => $band)
        <div class="panel overflow-hidden flex flex-col">
            <div class="px-6 pt-6 pb-5 border-b border-hairline">
                <div class="flex items-center justify-between"><span class="eyebrow">Groep {{ $i + 1 }}</span><span class="chip {{ $i === count($bands) - 1 ? 'bg-ochre/15 text-ochre-dark' : 'bg-sand-deep text-stone' }}">{{ $band['stats']['n'] }} diere</span></div>
                <div class="mt-3 font-headline text-4xl">{{ $band['label'] }}</div>
                <div class="text-sm text-stone mt-1 num">{{ $band['stats']['n'] ? 'gem. '.$band['stats']['mean'].' kg · '.round($band['stats']['n'] / max(1, $total) * 100).'% van kudde' : 'leeg' }}</div>
            </div>
            <ul class="divide-y divide-hairline max-h-[28rem] overflow-y-auto">
                @foreach ($band['animals']->sortByDesc('kg') as $r)
                    <li class="px-6 py-2.5 flex items-center gap-3 text-sm">
                        <a href="{{ route('rfid.animals.show', $r->animal) }}" class="font-num font-medium link-u">{{ $r->animal->visual_id }}</a>
                        <span class="text-xs text-stone">{{ $r->animal->sexLabel() }}</span>
                        <span class="ml-auto num">{{ $r->kg }} kg</span>
                        @if ($target)
                            <span class="w-24 text-right text-xs num {{ $r->days_to_target === 0 ? 'text-ochre-dark font-medium' : ($r->days_to_target === null ? 'down' : 'text-stone') }}">
                                {{ $r->days_to_target === 0 ? 'Reg ✓' : ($r->days_to_target === null ? 'staan stil' : $r->days_to_target.' dae') }}
                            </span>
                        @else
                            <span class="w-20 text-right text-xs num text-stone">{{ $r->adg !== null ? ($r->adg > 0 ? '+' : '').$r->adg.' g/d' : '' }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@if ($total === 0)<p class="mt-10 text-center text-stone">Geen geweegde diere nie. Weeg eers, dan sorteer ons.</p>@endif
@endsection
