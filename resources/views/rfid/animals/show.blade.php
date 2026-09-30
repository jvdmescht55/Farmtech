@extends('layouts.rfid')
@section('title', $animal->visual_id)
@section('actions')
    <a href="{{ route('rfid.animals.edit', $animal) }}" class="btn-secondary">Edit</a>
@endsection
@section('content')
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="app-card p-6">
            <div class="flex flex-wrap items-start gap-4">
                <div>
                    <div class="font-mono text-2xl font-semibold">{{ $animal->visual_id }} @if($animal->registered)<span class="text-xs align-middle font-bold text-ink-muted">REG</span>@endif</div>
                    <div class="text-ink-secondary mt-1">{{ $animal->name ? $animal->name.' · ' : '' }}{{ $animal->sexLabel() }} · {{ $animal->breed ?? 'Breed not set' }} · {{ \App\Models\Animal::STATUSES[$animal->status] }}</div>
                </div>
                <div class="ml-auto text-right">
                    <x-tier :tier="$tier->tier" class="text-base px-3 py-1" />
                    <div class="text-xs text-ink-muted mt-1">{{ $tier->official ? 'recorded' : 'computed from pedigree' }}</div>
                </div>
            </div>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                <div><dt class="app-label">EID</dt><dd class="font-mono">{{ $animal->eid ?? '—' }}</dd></div>
                <div><dt class="app-label">Born</dt><dd>{{ $animal->birth_date?->format('d/m/Y') ?? '—' }} @if($animal->ageLabel())<span class="text-ink-muted">({{ $animal->ageLabel() }})</span>@endif</dd></div>
                <div><dt class="app-label">Birth type</dt><dd>{{ $animal->birth_type ? $animal->birth_type.' · '.(config('herd.birth_types')[$animal->birth_type] ?? '') : '—' }}</dd></div>
                <div><dt class="app-label">GEN</dt><dd>{{ $animal->gen_score ?? '—' }}</dd></div>
                <div><dt class="app-label">Last scanned</dt><dd>{{ $animal->last_seen_at?->format('d/m/Y H:i') ?? 'Never' }}</dd></div>
                <div><dt class="app-label">Latest weight</dt><dd>{{ $weights->last()?->weight_kg ? $weights->last()->weight_kg.' kg' : '—' }}</dd></div>
                <div><dt class="app-label">Offspring</dt><dd>{{ $offspring->count() }}</dd></div>
                <div><dt class="app-label">Catalogues</dt><dd>{{ $lots->count() ? $lots->map(fn($l) => $l->lot_number ?: '—')->implode(', ') : '—' }}</dd></div>
            </dl>
            @if ($animal->notes)<p class="mt-5 text-sm bg-canvas rounded-lg px-4 py-3"><span class="font-semibold">Comment / Opmerking:</span> {{ $animal->notes }}</p>@endif
        </div>

        <div class="app-card p-6">
            <h2 class="font-semibold">Pedigree</h2>
            <div class="overflow-x-auto mt-5">
                <div class="grid grid-cols-3 gap-3 min-w-[640px] items-center">
                    @php
                        $s = $animal->sire; $d = $animal->dam;
                        $gp = [[$s?->sire, 'Sire of sire'], [$s?->dam, 'Dam of sire'], [$d?->sire, 'Sire of dam'], [$d?->dam, 'Dam of dam']];
                    @endphp
                    <div class="row-span-4 flex flex-col justify-center">
                        @include('rfid.animals._node', ['a' => $animal, 'role' => 'Animal', 'class' => 'border-brand-900'])
                    </div>
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $s, 'role' => 'Sire / Vaar'])</div>
                    @include('rfid.animals._node', ['a' => $gp[0][0], 'role' => $gp[0][1]])
                    @include('rfid.animals._node', ['a' => $gp[1][0], 'role' => $gp[1][1]])
                    <div class="row-span-2">@include('rfid.animals._node', ['a' => $d, 'role' => 'Dam / Moer'])</div>
                    @include('rfid.animals._node', ['a' => $gp[2][0], 'role' => $gp[2][1]])
                    @include('rfid.animals._node', ['a' => $gp[3][0], 'role' => $gp[3][1]])
                </div>
            </div>
            <div class="mt-5 rounded-lg bg-canvas px-4 py-3 text-sm">
                <div class="font-semibold">Why {{ $tier->tier ?? 'unknown' }}?</div>
                <ol class="mt-1 list-decimal list-inside text-ink-secondary space-y-0.5">
                    @foreach ($tier->reasons as $r)<li>{{ $r }}</li>@endforeach
                </ol>
                @if ($tier->official && $computed->tier && $computed->tier !== $tier->tier)
                    <p class="mt-2 text-alert-dark">Pedigree alone would give <strong>{{ $computed->tier }}</strong> — check the recorded tier or the ancestors.</p>
                @endif
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <div class="px-6 py-4 border-b border-border flex items-center justify-between"><h2 class="font-semibold">Weights</h2></div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead><tr><th>Date</th><th>Type</th><th class="text-right">Weight</th><th class="text-right">Gain since last</th><th>Source</th></tr></thead>
                    <tbody>
                    @forelse ($weights->reverse() as $w)
                        <tr>
                            <td class="whitespace-nowrap">{{ $w->scanned_at->format('d/m/Y') }}</td>
                            <td>{{ \App\Models\Scan::WEIGH_TYPES[$w->weigh_type] ?? '—' }}</td>
                            <td class="text-right font-mono">{{ $w->weight_kg }} kg</td>
                            <td class="text-right font-mono {{ ($gains[$w->id] ?? 0) < 0 ? 'text-error' : '' }}">{{ $gains[$w->id] !== null ? $gains[$w->id].' g/day' : '—' }}</td>
                            <td class="text-xs text-ink-secondary">{{ $w->reader_sync_id ? 'Reader sync' : 'Manual' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-ink-secondary">No weights recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('rfid.animals.weights.store', $animal) }}" class="px-6 py-4 bg-canvas/60 flex flex-wrap items-end gap-3">
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
            <div class="px-5 py-4 border-b border-border"><h2 class="font-semibold">Breeding values (EBV)</h2></div>
            <table class="app-table">
                <thead><tr><th>Trait</th><th class="text-right">EBV</th><th class="text-right">Acc %</th></tr></thead>
                <tbody>
                @foreach (config('herd.ebvs') as $k => $e)
                    @php($v = $animal->ebv($k))
                    <tr><td>{{ $e['label'] }} <span class="text-ink-muted text-xs">/ {{ $e['af'] }}</span></td><td class="text-right font-mono">{{ $v['v'] ?? '—' }}</td><td class="text-right font-mono text-ink-secondary">{{ $v['acc'] ?? '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($animal->sex !== 'M')
        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border"><h2 class="font-semibold">Lamb record <span class="text-ink-muted font-normal text-sm">/ Ooi lamrekord</span></h2></div>
            <div class="grid grid-cols-4 gap-px bg-border">
                @foreach (config('herd.dam_record') as $k => $label)
                    <div class="bg-white px-3 py-2.5"><div class="text-[10px] uppercase text-ink-muted">{{ $label }}</div><div class="font-mono">{{ $animal->dam_record[$k] ?? '—' }}</div></div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border"><h2 class="font-semibold">Offspring</h2></div>
            <ul class="divide-y divide-border text-sm">
                @forelse ($offspring->take(20) as $o)
                    <li class="px-5 py-2.5 flex items-center gap-3">
                        @if($o->in_herd)<a href="{{ route('rfid.animals.show', $o) }}" class="font-mono text-brand-900 hover:underline">{{ $o->visual_id }}</a>@else<span class="font-mono">{{ $o->visual_id }}</span>@endif
                        <span class="text-ink-muted text-xs">{{ $o->sexLabel() }} · {{ $o->birth_date?->format('Y') }}</span>
                        <x-tier :tier="$o->tierResult()->tier" class="ml-auto" />
                    </li>
                @empty
                    <li class="px-5 py-4 text-ink-secondary">None recorded.</li>
                @endforelse
            </ul>
        </div>

        <div class="app-card overflow-hidden">
            <div class="px-5 py-4 border-b border-border"><h2 class="font-semibold">Scan history</h2></div>
            <ul class="divide-y divide-border text-sm">
                @forelse ($scans as $s)
                    <li class="px-5 py-2.5 flex justify-between gap-3">
                        <span>{{ $s->scanned_at->format('d/m/Y H:i') }}</span>
                        <span class="text-ink-secondary text-xs truncate">{{ $s->sync?->reader?->name ?? ($s->reader_sync_id ? 'File upload' : 'Manual') }}{{ $s->weight_kg ? ' · '.$s->weight_kg.' kg' : '' }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-ink-secondary">Never scanned.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
