@extends('layouts.watch')
@section('title', 'Water points & gates')
@section('eyebrow')One KraalTrac Watch per trough or gate @endsection
@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="rounded-[24px] bg-char text-sand p-7 sm:p-8">
            <p class="eyebrow text-sand/50">New Watch</p>
            <div class="font-headline text-4xl mt-2">Pair it</div>
            <p class="text-sand/70 mt-3">Switch the Watch on at the water. Type the 6-digit code from its screen and say where it is.</p>
            <form method="POST" action="{{ route('rfid.readers.pair') }}" class="mt-6 space-y-3">
                @csrf
                <input type="hidden" name="kind" value="watch">
                <input name="code" inputmode="numeric" maxlength="7" required placeholder="482 913" class="w-full h-14 rounded-xl bg-white text-char text-center font-num text-2xl tracking-[0.35em] focus:outline-none focus:ring-4 focus:ring-ochre/40">
                <input name="location" placeholder="Where? e.g. Trough — Bergkamp" class="w-full h-11 rounded-xl bg-white/10 border border-white/15 px-4 text-sand placeholder:text-sand/40 focus:outline-none focus:border-white/50">
                <button class="btn-light w-full">Pair it</button>
            </form>
        </div>
        <details class="panel p-6">
            <summary class="cursor-pointer font-medium">Add by hand (shows the device key)</summary>
            <form method="POST" action="{{ route('watch.points.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" required placeholder="Name — e.g. Watch 1" class="field">
                <input name="location" placeholder="Where — e.g. Gate, Rivierkamp" class="field">
                <input name="alert_hours" type="number" min="2" max="240" placeholder="Alert after (hours) — default 24" class="field font-num">
                <button class="btn-dark w-full">Add</button>
            </form>
        </details>
    </div>
    <div class="xl:col-span-3 space-y-4">
        @forelse ($points as $p)
            <form method="POST" action="{{ route('watch.points.update', $p->point) }}" class="panel p-6">
                @csrf @method('PUT')
                <div class="flex items-center gap-2 mb-4"><span class="w-2.5 h-2.5 rounded-full {{ $p->point->isOnline() ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span><span class="text-xs uppercase tracking-[0.14em] text-stone">{{ $p->point->isOnline() ? 'Online' : 'Last heard '.($p->point->last_synced_at?->diffForHumans() ?? 'never') }}</span><span class="ml-auto text-sm text-stone">{{ $p->animals_today }} head today</span></div>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div><label class="field-label">Name</label><input name="name" value="{{ $p->point->name }}" required class="field"></div>
                    <div><label class="field-label">Where</label><input name="location" value="{{ $p->point->location }}" class="field"></div>
                    <div><label class="field-label">Shout after (hours)</label><input name="alert_hours" type="number" min="2" max="240" value="{{ $p->point->alert_hours }}" placeholder="24" class="field font-num"></div>
                </div>
                @if (isset($shownToken[$p->point->id]))
                    <div class="mt-4 rounded-xl bg-ochre/10 p-4 text-sm"><div class="font-medium text-ochre-dark">Device key — copy it now, we only show it once:</div><code class="mt-1 block break-all font-num select-all">{{ $shownToken[$p->point->id] }}</code></div>
                @endif
                <div class="mt-4 flex justify-end"><button class="btn-line btn-sm">Save</button></div>
            </form>
        @empty
            <div class="panel p-10"><div class="font-headline text-3xl">No water points yet.</div><p class="text-stone mt-2">Pair your first Watch on the left.</p></div>
        @endforelse
        <details class="panel p-6 text-sm text-stone">
            <summary class="cursor-pointer font-medium text-char">For builders: what a Watch sends</summary>
            <p class="mt-3">Exactly like the KraalTrac Pro, just without a weight: <span class="font-num text-char">POST {{ url('/api/v1/scans') }}</span> with <span class="font-num text-char">{"eid":"982000123456789"}</span> each time a tag passes. Reads of the same animal within 30 minutes count as one visit, so it's fine to send every read.</p>
            <a href="{{ route('rfid.readers.firmware', ['device' => 'watch']) }}" class="mt-4 inline-flex btn-line btn-sm">↓ KraalTrac Watch reference firmware (.ino)</a>
        </details>
    </div>
</div>
@endsection
