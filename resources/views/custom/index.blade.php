@extends('layouts.custom')
@section('title', 'Custom devices')
@section('eyebrow')Trough, fence, pump, cold room: anything you want to keep an eye on @endsection

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
            <div class="panel overflow-hidden">
                <div class="p-7 sm:p-9">
                    <div class="font-headline text-4xl">Keep an eye on more than the animals.</div>
                    <p class="text-stone mt-3 max-w-xl">A custom device is any gadget on the farm that sends a number: a trough level, fence voltage, pump flow, a cold-room temperature. Herd Manager draws the graph and warns you when it goes outside your limits.</p>
                </div>
                <div class="grid sm:grid-cols-2 border-t border-hairline">
                    <a href="{{ route('site.custom') }}#examples" class="p-7 sm:p-8 hover:bg-sand-light transition border-b sm:border-b-0 sm:border-r border-hairline">
                        <div class="text-sm text-ochre-dark font-medium">Don't have one?</div>
                        <div class="font-headline text-2xl mt-1">We'll build it for you</div>
                        <p class="text-sm text-stone mt-2">See what we can build and ask for a quote. No obligation.</p>
                        <span class="mt-4 inline-block text-sm font-medium link-u">See custom builds →</span>
                    </a>
                    <div class="p-7 sm:p-8">
                        <div class="text-sm text-ochre-dark font-medium">Built your own?</div>
                        <div class="font-headline text-2xl mt-1">Add it on the right</div>
                        <p class="text-sm text-stone mt-2">Pick a starting point, give it a name and you'll get a key for your ESP32 or Arduino.</p>
                        <a href="{{ route('help.show', 'custom') }}" class="mt-4 inline-block text-sm font-medium link-u">How to connect it →</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="xl:col-span-2">
        <form method="POST" action="{{ route('custom.store') }}" class="panel p-6 sm:p-7 space-y-4"
              x-data="{
                name: '', location: '',
                rows: [{ label: '', unit: '', min: '', max: '' }, { label: '', unit: '', min: '', max: '' }],
                presets: {
                    'Trough level': [['Trough. North camp', 'At the windpomp'], [['Level', '%', 25, ''], ['Battery', 'V', 11.5, '']]],
                    'Fence voltage': [['Fence. Lambing camp', ''], [['Voltage', 'kV', 4, '']]],
                    'Pump flow': [['Borehole pump', ''], [['Flow', 'm³/h', 0.2, ''], ['Battery', 'V', 11.5, '']]],
                    'Cold room': [['Vaccine fridge', 'Store room'], [['Temperature', '°C', 2, 8]]],
                    'Rain gauge': [['Rain gauge. Home camp', ''], [['Rain', 'mm', '', '']]],
                },
                use(k) { const [[n, l], m] = this.presets[k]; this.name = n; this.location = l; this.rows = m.map(([label, unit, min, max]) => ({ label, unit, min, max })); },
              }">
            @csrf
            <div class="font-headline text-3xl">Add a device</div>
            <div>
                <div class="field-label">Start from</div>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="k in Object.keys(presets)" :key="k">
                        <button type="button" @click="use(k)" class="chip h-8 px-3 border border-hairline bg-white hover:border-char" :class="name && presets[k][0][0] === name && 'bg-char text-sand border-char'" x-text="k"></button>
                    </template>
                </div>
            </div>
            <div><label class="field-label">What is it?</label><input name="name" x-model="name" required placeholder="e.g. Tank — Bergkamp" class="field"></div>
            <div><label class="field-label">Where (optional)</label><input name="location" x-model="location" placeholder="e.g. Next to the windpomp" class="field"></div>
            <div>
                <div class="field-label">What does it measure? <span class="font-normal text-stone-light">We warn you below min or above max.</span></div>
                <div class="grid grid-cols-[1fr_4.5rem_4.5rem_4.5rem] gap-2 text-[11px] text-stone px-1 mb-1"><span>Reading</span><span>Unit</span><span>Min</span><span>Max</span></div>
                <div class="space-y-2">
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="grid grid-cols-[1fr_4.5rem_4.5rem_4.5rem] gap-2">
                            <input :name="`metrics[${i}][label]`" x-model="row.label" aria-label="Reading name" placeholder="Tank level" class="field !h-10 text-sm">
                            <input :name="`metrics[${i}][unit]`" x-model="row.unit" aria-label="Unit" placeholder="%" class="field !h-10 text-sm">
                            <input :name="`metrics[${i}][min]`" x-model="row.min" aria-label="Minimum" type="number" step="any" placeholder="min" class="field !h-10 text-sm font-num">
                            <input :name="`metrics[${i}][max]`" x-model="row.max" aria-label="Maximum" type="number" step="any" placeholder="max" class="field !h-10 text-sm font-num">
                        </div>
                    </template>
                </div>
                <button type="button" @click="rows.push({ label: '', unit: '', min: '', max: '' })" x-show="rows.length < 8" class="mt-2 text-sm link-u">+ another reading</button>
            </div>
            <button class="btn-dark w-full">Add &amp; get its key</button>
            <p class="text-xs text-stone">Rather have us build it? <a href="{{ route('site.custom') }}#ask" class="link-u text-char">Ask for a custom build</a>.</p>
        </form>
    </div>
</div>
@endsection

@section('tour')
    <x-tour key="custom" :auto="! auth()->user()->hasSeenTour('custom') || request()->boolean('tour')" :steps="[
        ['target' => null, 'title' => 'Your own devices', 'body' => 'Tank gauges, rain meters, cold rooms — name it, say what it measures, set your limits. We chart it and shout when something\'s off.'],
        ['target' => 'help', 'title' => 'Need the technical bit?', 'body' => 'The <strong>?</strong> button → guides has the two lines your device needs to send readings.', 'cta' => 'Lekker'],
    ]" />
@endsection
