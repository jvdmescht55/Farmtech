@extends('layouts.rfid')
@section('title', 'Live view')
@section('eyebrow')Scan in the kraal. It pops up here within seconds @endsection

@section('content')
<div x-data="liveFeed('{{ route('rfid.live.feed') }}')" x-init="start()">
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Current animal --}}
        <div class="lg:col-span-2 relative overflow-hidden rounded-[28px] bg-char text-sand min-h-[420px] p-8 sm:p-12 flex flex-col">
            <div class="flex items-center gap-3 text-sm text-sand/60">
                <span class="relative flex w-2.5 h-2.5"><span class="absolute inset-0 rounded-full bg-ochre animate-ping opacity-60"></span><span class="relative w-2.5 h-2.5 rounded-full bg-ochre"></span></span>
                <span x-text="current ? 'Last read · ' + current.time : 'Waiting for the KraalTrac…'"></span>
            </div>

            <template x-if="!current">
                <div class="my-auto">
                    <div class="h-display text-6xl sm:text-7xl">Scan an animal.</div>
                    <p class="mt-4 text-sand/60 max-w-md">As soon as your KraalTrac reads a tag and sends it, the animal shows up here with its weight, gain and any alerts.</p>
                </div>
            </template>

            <template x-if="current">
                <div class="mt-auto">
                    <div class="flex flex-wrap items-center gap-2 mb-5">
                        <template x-if="current.is_new"><span class="chip bg-ochre text-char">New animal</span></template>
                        <template x-for="a in current.alerts" :key="a.title">
                            <span class="chip" :class="a.level === 'critical' ? 'bg-[#B0452F] text-white' : (a.level === 'warning' ? 'bg-ochre/90 text-char' : 'bg-white/10 text-sand')" x-text="a.title"></span>
                        </template>
                    </div>
                    <a :href="current.url" class="h-display text-[clamp(3.5rem,9vw,7.5rem)] block hover:text-ochre-light transition-colors" x-text="current.animal || current.eid"></a>
                    <div class="mt-2 text-sand/60 font-num text-sm" x-text="[current.eid, current.sex, current.age].filter(Boolean).join('  ·  ')"></div>
                    <div class="mt-10 grid grid-cols-3 gap-6 border-t border-white/10 pt-8">
                        <div><div class="kpi-label !text-sand/50">Weight</div><div class="mt-2 font-headline text-5xl num" x-text="current.weight !== null ? current.weight + ' kg' : '—'"></div></div>
                        <div><div class="kpi-label !text-sand/50">Vs last time</div><div class="mt-2 font-headline text-5xl num" :class="current.change === null ? '' : (current.change >= 0 ? 'text-[#9BC48F]' : 'text-[#E58B74]')" x-text="current.change === null ? '—' : (current.change > 0 ? '+' : '') + current.change + ' kg'"></div></div>
                        <div><div class="kpi-label !text-sand/50">Daily gain</div><div class="mt-2 font-headline text-5xl num" x-text="current.adg === null ? '—' : (current.adg > 0 ? '+' : '') + current.adg"></div><div class="text-xs text-sand/50" x-show="current.adg !== null">g/day</div></div>
                    </div>
                </div>
            </template>
        </div>

        <div class="space-y-6">
            <div class="panel p-6 grid grid-cols-3 gap-4">
                <div><div class="kpi-label">Today</div><div class="mt-2 font-headline text-4xl num" x-text="today.scans ?? 0"></div><div class="text-xs text-stone">reads</div></div>
                <div><div class="kpi-label">Animals</div><div class="mt-2 font-headline text-4xl num" x-text="today.animals ?? 0"></div></div>
                <div><div class="kpi-label">Average</div><div class="mt-2 font-headline text-4xl num" x-text="today.avg_kg ?? '—'"></div><div class="text-xs text-stone">kg</div></div>
            </div>
            <div class="panel p-6">
                <div class="panel-title">Devices</div>
                <ul class="mt-4 space-y-3 text-sm">
                    @forelse ($readers as $r)
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $r->last_synced_at && $r->last_synced_at->gt(now()->subMinutes(10)) ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span>
                            <span class="flex-1 truncate">{{ $r->name }}</span>
                            <span class="text-xs text-stone">{{ $r->last_synced_at?->diffForHumans(short: true) ?? 'never' }}{{ $r->battery_pct !== null ? ' · '.$r->battery_pct.'%' : '' }}</span>
                        </li>
                    @empty
                        <li class="text-stone">No scanner paired yet. <a href="{{ route('pair') }}" class="link-u text-char">Pair one</a>.</li>
                    @endforelse
                </ul>
                <p class="mt-5 text-xs text-stone">This page checks for new reads every 2 seconds.</p>
            </div>
        </div>
    </div>

    <div class="panel mt-6 overflow-hidden">
        <div class="panel-head"><div class="panel-title">This session</div><span class="text-xs text-stone" x-text="scans.length + ' reads'"></span></div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Time</th><th>Animal</th><th>EID</th><th class="text-right">Weight</th><th class="text-right">Change</th><th class="text-right">Gain</th><th>Alerts</th></tr></thead>
                <tbody>
                    <template x-for="s in [...scans].reverse()" :key="s.id">
                        <tr>
                            <td class="num text-stone" x-text="s.time"></td>
                            <td><a :href="s.url" class="font-num font-medium link-u" x-text="s.animal || '—'"></a></td>
                            <td class="num text-xs text-stone" x-text="s.eid || ''"></td>
                            <td class="text-right num" x-text="s.weight !== null ? s.weight + ' kg' : '—'"></td>
                            <td class="text-right num" :class="s.change === null ? 'text-stone-light' : (s.change >= 0 ? 'up' : 'down')" x-text="s.change === null ? '—' : (s.change > 0 ? '+' : '') + s.change + ' kg'"></td>
                            <td class="text-right num" x-text="s.adg === null ? '—' : (s.adg > 0 ? '+' : '') + s.adg + ' g/d'"></td>
                            <td><template x-for="a in s.alerts" :key="a.title"><span class="chip mr-1" :class="a.level === 'critical' ? 'bg-[#B0452F]/10 text-[#B0452F]' : (a.level === 'warning' ? 'bg-ochre/15 text-ochre-dark' : 'bg-sand-deep text-stone')" x-text="a.title"></span></template></td>
                        </tr>
                    </template>
                    <tr x-show="!scans.length"><td colspan="7" class="py-12 text-center text-stone">No reads in the last 12 hours.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function liveFeed(url) {
        return {
            scans: [], current: null, today: {}, last: 0, timer: null,
            async poll() {
                try {
                    const res = await fetch(url + (this.last ? '?after=' + this.last : ''), { headers: { Accept: 'application/json' } });
                    if (!res.ok) return;
                    const d = await res.json();
                    this.today = d.today;
                    if (d.scans.length) {
                        this.scans = [...this.scans, ...d.scans].slice(-200);
                        this.current = d.scans[d.scans.length - 1];
                    }
                    this.last = d.last_id;
                } catch (e) { /* offline for a moment — try again next tick */ }
            },
            start() {
                this.poll();
                this.timer = setInterval(() => { if (!document.hidden) this.poll(); }, 2000);
            },
        };
    }
</script>
@endsection
