@extends('layouts.custom')
@section('title', 'Custom devices')
@section('eyebrow')Your own sensors — your farm, your rules @endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-4">
        @forelse ($devices as $d)
            <a href="{{ route('custom.show', $d) }}" class="panel p-6 block hover:border-char transition">
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full {{ $d->isOnline() ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span><span class="text-xs uppercase tracking-[0.14em] text-stone">{{ $d->last_synced_at ? 'Last reading '.$d->last_synced_at->diffForHumans() : 'No readings yet' }}</span></div>
                <div class="font-headline text-3xl mt-2">{{ $d->name }}</div>
                @if ($d->location)<div class="text-sm text-stone">{{ $d->location }}</div>@endif
                <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($d->metrics ?? [] as $m)
                        @php
                            $r = $d->latest->get($m['key']);
                            $out = $r && (($m['min'] !== null && $r->value < $m['min']) || ($m['max'] !== null && $r->value > $m['max']));
                        @endphp
                        <div><div class="kpi-label">{{ $m['label'] }}</div><div class="mt-1 font-headline text-3xl num {{ $out ? 'down' : '' }}">{{ $r ? rtrim(rtrim(number_format($r->value, 2, '.', ''), '0'), '.') : '—' }}<span class="text-sm text-stone font-ui ml-1">{{ $m['unit'] }}</span></div></div>
                    @endforeach
                </div>
            </a>
        @empty
            <div class="panel p-10">
                <div class="font-headline text-4xl">Built something lekker?</div>
                <p class="text-stone mt-3 max-w-xl">A tank-level float, a rain gauge, a cold-room thermometer, a borehole pump counter — if it has a microcontroller and Wi-Fi, it can report here. Name it, say what it measures, set the limits that matter on <em>your</em> farm, and we'll shout when something's off.</p>
            </div>
        @endforelse
    </div>

    <div class="xl:col-span-2">
        <form method="POST" action="{{ route('custom.store') }}" class="panel p-6 sm:p-7 space-y-4" x-data="{ rows: [{}, {}] }">
            @csrf
            <div class="font-headline text-3xl">Add a device</div>
            <div><label class="field-label">What is it?</label><input name="name" required placeholder="e.g. Tank — Bergkamp" class="field"></div>
            <div><label class="field-label">Where (optional)</label><input name="location" placeholder="e.g. Next to the windpomp" class="field"></div>
            <div>
                <label class="field-label">What does it measure? <span class="font-normal text-stone-light">Limits are optional — we alert outside them.</span></label>
                <div class="space-y-2">
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="grid grid-cols-[1fr_4.5rem_4.5rem_4.5rem] gap-2">
                            <input :name="`metrics[${i}][label]`" placeholder="Tank level" class="field !h-10 text-sm">
                            <input :name="`metrics[${i}][unit]`" placeholder="%" class="field !h-10 text-sm">
                            <input :name="`metrics[${i}][min]`" type="number" step="any" placeholder="min" class="field !h-10 text-sm font-num">
                            <input :name="`metrics[${i}][max]`" type="number" step="any" placeholder="max" class="field !h-10 text-sm font-num">
                        </div>
                    </template>
                </div>
                <button type="button" @click="rows.push({})" x-show="rows.length < 8" class="mt-2 text-sm link-u">+ another reading</button>
            </div>
            <button class="btn-dark w-full">Add &amp; get its key</button>
            <p class="text-xs text-stone">Don't fancy building it yourself? <a href="{{ route('herd.suggest') }}" class="link-u text-char">Tell us what you need</a> — we build custom devices for farms too.</p>
        </form>
    </div>
</div>
@endsection
