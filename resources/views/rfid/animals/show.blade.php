@extends('layouts.rfid')
@section('title', $animal->visual_id)
@section('eyebrow'){{ $animal->sexLabel() }} · {{ $animal->breed ?? 'Dier' }} @endsection
@section('actions')
    <a href="{{ route('rfid.animals.edit', $animal) }}" class="btn-secondary">Edit</a>
@endsection
@section('content')
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="app-card p-6">
            <div class="flex flex-wrap items-start gap-4">
                <div>
                    <div class="font-mono text-2xl font-medium">{{ $animal->visual_id }} @if($animal->registered)<span class="text-xs align-middle font-bold text-stone-light">REG</span>@endif</div>
                    <div class="text-stone mt-1">{{ $animal->name ? $animal->name.' · ' : '' }}{{ $animal->sexLabel() }} · {{ $animal->breed ?? 'Breed not set' }} · {{ \App\Models\Animal::STATUSES[$animal->status] }}</div>
                </div>
                <div class="ml-auto text-right">
                    <x-tier :tier="$tier->tier" class="text-base px-3 py-1" />
                    <div class="text-xs text-stone-light mt-1">{{ $tier->official ? 'recorded' : 'computed from pedigree' }}</div>
                </div>
            </div>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                <div><dt class="app-label">EID</dt><dd class="font-mono">{{ $animal->eid ?? '—' }}</dd></div>
                <div><dt class="app-label">Born</dt><dd>{{ $animal->birth_date?->format('d/m/Y') ?? '—' }} @if($animal->ageLabel())<span class="text-stone-light">({{ $animal->ageLabel() }})</span>@endif</dd></div>
                <div><dt class="app-label">Birth type</dt><dd>{{ $animal->birth_type ? $animal->birth_type.' · '.(config('herd.birth_types')[$animal->birth_type] ?? '') : '—' }}</dd></div>
                <div><dt class="app-label">GEN</dt><dd>{{ $animal->gen_score ?? '—' }}</dd></div>
                <div><dt class="app-label">Last scanned</dt><dd>{{ $animal->last_seen_at?->format('d/m/Y H:i') ?? 'Never' }}</dd></div>
                <div><dt class="app-label">Latest weight</dt><dd>{{ $weights->last()?->weight_kg ? $weights->last()->weight_kg.' kg' : '—' }}</dd></div>
                <div><dt class="app-label">Offspring</dt><dd>{{ $offspring->count() }}</dd></div>
                <div><dt class="app-label">Catalogues</dt><dd>{{ $lots->count() ? $lots->map(fn($l) => $l->lot_number ?: '—')->implode(', ') : '—' }}</dd></div>
            </dl>
            @if ($animal->notes)<p class="mt-5 text-sm bg-sand-light rounded-lg px-4 py-3"><span class="font-medium">Comment / Opmerking:</span> {{ $animal->notes }}</p>@endif
        </div>

        <div class="app-card p-6">
            <h2 class="font-medium">Pedigree</h2>
            <div class="overflow-x-auto mt-5">
                <div class="grid grid-cols-3 gap-3 min-w-[640px] items-center">
                    @php
                        $s = $animal->sire; $d = $animal->dam;
                        $gp = [[$s?->sire, 'Sire of sire'], [$s?->dam, 'Dam of sire'], [$d?->sire, 'Sire of dam'], [$d?->dam, 'Dam of dam']];
                    @endphp
                    <div class="row-span-4 flex flex-col justify-center">
                        @include('rfid.animals._node', ['a' => $animal, 'role' => 'Animal', 'class' => 'border-char'])
                    </div>
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $s, 'role' => 'Sire / Vaar'])</div>
                    @include('rfid.animals._node', ['a' => $gp[0][0], 'role' => $gp[0][1]])
                    @include('rfid.animals._node', ['a' => $gp[1][0], 'role' => $gp[1][1]])
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $d, 'role' => 'Dam / Moer'])</div>
                    @include('rfid.animals._node', ['a' => $gp[2][0], 'role' => $gp[2][1]])
                    @include('rfid.animals._node', ['a' => $gp[3][0], 'role' => $gp[3][1]])
                </div>
            </div>
            <div class="mt-5 rounded-lg bg-sand-light px-4 py-3 text-sm">
                <div class="font-medium">Why {{ $tier->tier ?? 'unknown' }}?</div>
                <ol class="mt-1 list-decimal list-inside text-stone space-y-0.5">
                    @foreach ($tier->reasons as $r)<li>{{ $r }}</li>@endforeach
                </ol>
                @if ($tier->official && $computed->tier && $computed->tier !== $tier->tier)
                    <p class="mt-2 text-ochre-dark">Pedigree alone would give <strong>{{ $computed->tier }}</strong> — check the recorded tier or the ancestors.</p>
                @endif
            </div>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-head">
                <div class="panel-title">Groeikurwe</div>
                <form method="GET" class="flex items-center gap-2 text-sm">
                    <label for="t" class="text-stone">Teiken</label>
                    <input id="t" name="target" type="number" step="0.5" value="{{ $target }}" placeholder="kg" class="field !h-9 w-24 font-num">
                    <button class="btn-line btn-sm">Projekteer</button>
                </form>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-hairline border-b border-hairline">
                <div class="p-5"><div class="kpi-label">Huidig</div><div class="mt-2 font-headline text-3xl num">{{ $weights->last()?->weight_kg ?? '—' }}<span class="text-sm text-stone font-ui ml-1">kg</span></div></div>
                <div class="p-5"><div class="kpi-label">Onlangse groei</div><div class="mt-2 font-headline text-3xl num {{ ($recentAdg ?? 0) < 0 ? 'down' : '' }}">{{ $recentAdg !== null ? ($recentAdg > 0 ? '+' : '').$recentAdg : '—' }}<span class="text-sm text-stone font-ui ml-1">g/d</span></div></div>
                <div class="p-5"><div class="kpi-label">Lewenslank</div><div class="mt-2 font-headline text-3xl num">{{ $lifeAdg ?? '—' }}<span class="text-sm text-stone font-ui ml-1">g/d</span></div></div>
                <div class="p-5"><div class="kpi-label">100-dag gewig</div><div class="mt-2 font-headline text-3xl num">{{ $kg100 ?? '—' }}<span class="text-sm text-stone font-ui ml-1">kg</span></div></div>
            </div>
            <div class="p-6"><x-chart.line :points="$weightPoints" unit="kg" :height="240" :target="$target" /></div>
            @if ($target)
                <div class="px-6 pb-6 -mt-2 text-sm">
                    @if ($daysToTarget === 0)<span class="chip bg-ochre/15 text-ochre-dark">Reg vir die mark — al oor {{ $target }} kg</span>
                    @elseif ($daysToTarget === null)<span class="chip bg-[#B0452F]/10 text-[#B0452F]">Groei staan stil — kan nie projekteer nie</span>
                    @else<span class="chip bg-sand-deep text-char">Bereik {{ $target }} kg oor ongeveer {{ $daysToTarget }} dae · {{ now()->addDays($daysToTarget)->format('j M Y') }}</span>@endif
                </div>
            @endif
        </div>

        <div class="app-card overflow-hidden">
            <div class="px-6 py-4 border-b border-hairline flex items-center justify-between"><h2 class="font-medium">Wegings</h2></div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead><tr><th>Date</th><th>Type</th><th class="text-right">Weight</th><th class="text-right">Gain since last</th><th>Source</th></tr></thead>
                    <tbody>
                    @forelse ($weights->reverse() as $w)
                        <tr>
                            <td class="whitespace-nowrap">{{ $w->scanned_at->format('d/m/Y') }}</td>
                            <td>{{ \App\Models\Scan::WEIGH_TYPES[$w->weigh_type] ?? '—' }}</td>
                            <td class="text-right font-mono">{{ $w->weight_kg }} kg</td>
                            <td class="text-right font-mono {{ ($gains[$w->id] ?? 0) < 0 ? 'text-[#B0452F]' : '' }}">{{ $gains[$w->id] !== null ? $gains[$w->id].' g/day' : '—' }}</td>
                            <td class="text-xs text-stone">{{ $w->reader_sync_id ? 'Reader sync' : 'Manual' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-stone">No weights recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('rfid.animals.weights.store', $animal) }}" class="px-6 py-4 bg-sand-light flex flex-wrap items-end gap-3">
                @csrf
                <div><label class="app-label">Weight (kg)</label><input name="weight_kg" type="number" step="0.1" required class="app-input w-28"></div>
                <div><label class="app-label">Type</label><select name="weigh_type" class="app-input">@foreach(\App\Models\Scan::WEIGH_TYPES as $k=>$v)<option value="{{ $k }}" @selected($k==='routine')>{{ $v }}</option>@endforeach</select></div>
                <div><label class="app-label">Date</label><input name="scanned_at" type="date" value="{{ now()->toDateString() }}" required class="app-input"></div>
                <button class="btn-primary">Record weight</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-hairline"><h2 class="font-medium">Breeding values (EBV)</h2></div>
            <table class="app-table">
                <thead><tr><th>Trait</th><th class="text-right">EBV</th><th class="text-right">Acc %</th></tr></thead>
                <tbody>
                @foreach (config('herd.ebvs') as $k => $e)
                    @php($v = $animal->ebv($k))
                    <tr><td>{{ $e['label'] }} <span class="text-stone-light text-xs">/ {{ $e['af'] }}</span></td><td class="text-right font-mono">{{ $v['v'] ?? '—' }}</td><td class="text-right font-mono text-stone">{{ $v['acc'] ?? '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($animal->sex !== 'M')
        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-hairline"><h2 class="font-medium">Lamb record <span class="text-stone-light font-normal text-sm">/ Ooi lamrekord</span></h2></div>
            <div class="grid grid-cols-4 gap-px bg-border">
                @foreach (config('herd.dam_record') as $k => $label)
                    <div class="bg-white px-3 py-2.5"><div class="text-[10px] uppercase text-stone-light">{{ $label }}</div><div class="font-mono">{{ $animal->dam_record[$k] ?? '—' }}</div></div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-hairline"><h2 class="font-medium">Offspring</h2></div>
            <ul class="divide-y divide-border text-sm">
                @forelse ($offspring->take(20) as $o)
                    <li class="px-5 py-2.5 flex items-center gap-3">
                        @if($o->in_herd)<a href="{{ route('rfid.animals.show', $o) }}" class="font-mono text-char hover:underline">{{ $o->visual_id }}</a>@else<span class="font-mono">{{ $o->visual_id }}</span>@endif
                        <span class="text-stone-light text-xs">{{ $o->sexLabel() }} · {{ $o->birth_date?->format('Y') }}</span>
                        <x-tier :tier="$o->tierResult()->tier" class="ml-auto" />
                    </li>
                @empty
                    <li class="px-5 py-4 text-stone">None recorded.</li>
                @endforelse
            </ul>
        </div>

        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-hairline"><h2 class="font-medium">Scan history</h2></div>
            <ul class="divide-y divide-border text-sm">
                @forelse ($scans as $s)
                    <li class="px-5 py-2.5 flex justify-between gap-3">
                        <span>{{ $s->scanned_at->format('d/m/Y H:i') }}</span>
                        <span class="text-stone text-xs truncate">{{ $s->sync?->reader?->name ?? ($s->reader_sync_id ? 'File upload' : 'Manual') }}{{ $s->weight_kg ? ' · '.$s->weight_kg.' kg' : '' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-stone">Never scanned.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
