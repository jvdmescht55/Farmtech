@extends('layouts.rfid')
@section('title', 'Devices')
@section('eyebrow')Scanners paired with your farm, their software and when they last synced @endsection
@section('actions')<a href="{{ route('rfid.live') }}" class="btn-primary">Open live view</a>@endsection

@section('content')
@php($latestFw = json_decode(@file_get_contents(public_path('firmware/kraaltrac-pro/manifest.json')), true)['version'] ?? null)
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="rounded-[24px] bg-char text-sand p-7 sm:p-8">
            <div class="font-headline text-4xl">Pair a device</div>
            <p class="text-sand/70 mt-3">Switch it on. New devices first open a Wi-Fi called <em>KraalTrac-…</em>: join it on your phone and pick your farm Wi-Fi. Then the screen shows 6 numbers. Type them here (or at farmtech.site/pair).</p>
            <form method="POST" action="{{ route('rfid.readers.pair') }}" class="mt-6 space-y-3">
                @csrf
                <input type="hidden" name="kind" value="handheld">
                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required placeholder="482 913" class="w-full h-14 rounded-xl bg-white text-char text-center font-num text-2xl tracking-[0.35em] focus:outline-none focus:ring-4 focus:ring-ochre/40">
                <button class="btn-light w-full">Pair it</button>
            </form>
            <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                <a href="{{ route('pair') }}" class="underline text-sand/80">Step-by-step pairing</a>
                <a href="{{ route('firmware.install') }}" class="underline text-sand/80">Install software from the browser</a>
            </div>
        </div>

        <details class="panel p-6">
            <summary class="cursor-pointer font-medium">No signal in the kraal? Upload the file instead</summary>
            <p class="text-sm text-stone mt-3">The KraalTrac keeps every read. Plug it in and upload the session file. Any column order works.</p>
            <form method="POST" action="{{ route('rfid.sync.upload') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.txt,.tsv" required class="field !h-auto py-2.5 file:mr-3 file:rounded-full file:border-0 file:bg-char file:text-sand file:px-4 file:py-1.5 file:text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <select name="reader_id" class="field"><option value="">Which device?</option>@foreach ($readers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
                    <select name="weigh_type" class="field"><option value="">Weigh type (from file)</option>@foreach (\App\Models\Scan::WEIGH_TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                </div>
                <button class="btn-dark w-full">Upload</button>
            </form>
        </details>

        <details class="panel p-6">
            <summary class="cursor-pointer font-medium">Add a device by hand</summary>
            <form method="POST" action="{{ route('rfid.readers.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" required placeholder="Name — e.g. Kraal reader" class="field">
                <div class="grid grid-cols-2 gap-3"><input name="model" placeholder="Model" class="field"><input name="serial" placeholder="Serial" class="field font-num"></div>
                <button class="btn-dark w-full">Add &amp; show key</button>
            </form>
        </details>
    </div>

    <div class="xl:col-span-3 space-y-6">
        @forelse ($readers as $r)
            <div class="panel overflow-hidden" x-data="{ show: {{ isset($shownToken[$r->id]) ? 'true' : 'false' }} }">
                <div class="p-6 flex flex-wrap items-start gap-6">
                    <div class="flex-1 min-w-[14rem]">
                        <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full {{ $r->isOnline() ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span><span class="text-xs uppercase tracking-[0.14em] {{ $r->isOnline() ? 'text-[#3F7A3A]' : 'text-stone' }}">{{ $r->isOnline() ? 'Online' : 'Offline' }}</span></div>
                        <div class="font-headline text-3xl mt-2">{{ $r->name }}</div>
                        <div class="text-sm text-stone mt-1 font-num">{{ collect([$r->model, $r->serial ? 'SN '.$r->serial : null, $r->firmware ? 'fw '.$r->firmware : null])->filter()->implode(' · ') }}</div>
                        @if ($r->firmware && $latestFw && str_contains(strtolower((string) $r->model), 'kraaltrac pro') && version_compare($r->firmware, $latestFw, '<'))
                            <a href="{{ route('firmware.install') }}" class="mt-2 inline-flex items-center gap-2 chip bg-ochre/15 text-ochre-dark">Update available: v{{ $latestFw }} →</a>
                        @endif
                    </div>
                    <dl class="grid grid-cols-3 gap-6 text-sm">
                        <div><dt class="kpi-label">Last heard</dt><dd class="mt-1">{{ $r->last_synced_at?->diffForHumans() ?? 'Never' }}</dd></div>
                        <div><dt class="kpi-label">Battery</dt><dd class="mt-1 num">{{ $r->battery_pct !== null ? $r->battery_pct.'%' : '—' }}</dd></div>
                        <div><dt class="kpi-label">Syncs</dt><dd class="mt-1 num">{{ $r->syncs_count }}</dd></div>
                    </dl>
                </div>
                <div class="px-6 py-3 border-t border-hairline bg-sand-light flex flex-wrap items-center gap-3 text-sm">
                    <button type="button" @click="show = !show" class="link-u">Device key</button>
                    <form method="POST" action="{{ route('rfid.readers.token', $r) }}">@csrf<button class="link-u">New key</button></form>
                    <form method="POST" action="{{ route('rfid.readers.destroy', $r) }}" class="ml-auto" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Click again to remove'; }">@csrf @method('DELETE')<button class="text-stone hover:text-[#B0452F]">Remove</button></form>
                </div>
                <div x-show="show" x-cloak class="px-6 py-5 border-t border-hairline text-sm">
                    @if (isset($shownToken[$r->id]))
                        <div class="font-medium text-ochre-dark">Copy it now. We only show it once.</div>
                        <code class="mt-2 block break-all font-num bg-white border border-hairline rounded-xl px-4 py-3 select-all">{{ $shownToken[$r->id] }}</code>
                    @else
                        <p class="text-stone">Keys are only shown once. Click <strong class="text-char">New key</strong> to connect the device again (the old key stops working).</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="panel p-10"><div class="font-headline text-3xl">No KraalTrac paired yet.</div><p class="text-stone mt-2">Switch it on and type the code on the left.</p></div>
        @endforelse

        <details class="rounded-[24px] bg-char text-sand p-6 sm:p-7" x-data="{ tab: 'pair' }">
            <summary class="cursor-pointer font-medium">For builders: how devices talk to Herd Manager</summary>
            <p class="text-sm text-sand/70 mt-4 leading-relaxed">Everything goes to <span class="font-num">farmtech.site</span> over HTTPS. No local server or XAMPP needed. Reads show up on the live view within seconds, and the reply tells the device who it read and any alerts.</p>
            <div class="mt-4 text-xs text-sand/60 font-num space-y-1">
                <div>POST {{ url('/api/v1/scans') }}</div><div>GET&nbsp; {{ url('/api/v1/ping') }}</div><div>Authorization: Bearer &lt;device key&gt;</div>
            </div>
            <a href="{{ route('rfid.readers.firmware') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-sand text-char px-4 h-9 text-sm font-medium hover:bg-white">↓ KraalTrac reference firmware (.ino)</a>
            <div class="mt-5 flex flex-wrap gap-1 text-xs">
                @foreach (['pair' => 'Pairing', 'json' => 'Send a scan', 'reply' => 'The reply'] as $k => $l)
                    <button type="button" @click="tab = '{{ $k }}'" class="rounded-full px-3 h-7" :class="tab === '{{ $k }}' ? 'bg-sand text-char' : 'text-sand/60 hover:text-sand'">{{ $l }}</button>
                @endforeach
            </div>
<pre x-show="tab === 'pair'" class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>POST /api/v1/pair  {"serial":"KT-000123","model":"KraalTrac Pro"}
→ {"code":"482913","secret":"…","poll_every":5}
   show 482 913 on the LCD; farmer types it here

POST /api/v1/pair/status  {"secret":"…"}   (every 5 s)
→ {"status":"paired","token":"…"}   once. Save it in flash</code></pre>
<pre x-show="tab === 'json'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>{"compact": true,
 "device": {"battery": 87, "firmware": "1.0.3"},
 "scans": [{"ref": "KT-000124", "id": "250912", "eid": "982000123456789",
            "weight": 42.5, "weight_type": "wean",
            "sex": "F", "sire": "DVS 222435", "dam": "DVS 220120",
            "ts": 1790798104}]}

// weight_type: birth | wean | post_wean | mature
// sex, sire and dam only fill blanks — never overwrite the herd book
// "id" = your farm number (e.g. 250912) · "ref" makes retries safe</code></pre>
<pre x-show="tab === 'reply'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>{"ok":1,"n":1,"r":[{"s":1,"a":"DVS 25 5010",
 "w":66.6,"c":-6.9,"g":-1725,"x":1,
 "t":"Sharp weight loss"}]}
// s 1=saved 2=duplicate · a animal · w kg · c change
// g gain g/day · x alerts · t first alert (fits a 20×4 LCD)</code></pre>
        </details>

        @if ($syncs->isNotEmpty())
            <details class="panel overflow-hidden">
                <summary class="panel-head cursor-pointer"><span class="panel-title">Sync history</span><span class="text-xs text-stone">{{ $syncs->total() }}</span></summary>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>When</th><th>Device</th><th>How</th><th class="text-right">Reads</th><th class="text-right">New animals</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($syncs as $s)
                            <tr>
                                <td class="num whitespace-nowrap">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $s->reader?->name ?? '—' }}</td>
                                <td class="text-xs uppercase text-stone">{{ $s->source === 'api' ? 'Wi-Fi' : 'File' }}</td>
                                <td class="text-right num">{{ $s->scan_count }}</td>
                                <td class="text-right num {{ $s->new_count ? 'text-ochre-dark' : '' }}">{{ $s->new_count }}</td>
                                <td class="text-right"><a href="{{ route('rfid.sync.show', $s) }}" class="text-sm link-u">Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">{{ $syncs->links() }}</div>
            </details>
        @endif
    </div>
</div>
@endsection
