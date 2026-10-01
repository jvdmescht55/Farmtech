@extends('layouts.rfid')
@section('title', 'Toestelle')
@section('eyebrow')Jou skandeerders — en hoe hulle praat @endsection
@section('actions')<a href="{{ route('rfid.live') }}" class="btn-primary">Maak lewendige skerm oop</a>@endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-6">
        @forelse ($readers as $r)
            @php($online = $r->last_synced_at && $r->last_synced_at->gt(now()->subMinutes(10)))
            <div class="panel overflow-hidden" x-data="{ show: {{ isset($shownToken[$r->id]) ? 'true' : 'false' }} }">
                <div class="p-6 sm:p-7 flex flex-wrap items-start gap-6">
                    <div class="flex-1 min-w-[14rem]">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $online ? 'bg-[#3F7A3A]' : 'bg-stone-light/50' }}"></span>
                            <span class="text-xs uppercase tracking-[0.14em] {{ $online ? 'text-[#3F7A3A]' : 'text-stone' }}">{{ $online ? 'Aanlyn' : 'Vanlyn' }}</span>
                        </div>
                        <div class="font-headline text-3xl mt-2">{{ $r->name }}</div>
                        <div class="text-sm text-stone mt-1 font-num">{{ collect([$r->model, $r->serial ? 'SN '.$r->serial : null, $r->firmware ? 'fw '.$r->firmware : null])->filter()->implode(' · ') ?: 'Geen model/reeksnommer nie' }}</div>
                    </div>
                    <dl class="grid grid-cols-3 gap-6 text-sm">
                        <div><dt class="kpi-label">Laas gehoor</dt><dd class="mt-1">{{ $r->last_synced_at?->diffForHumans() ?? 'Nooit' }}</dd></div>
                        <div><dt class="kpi-label">Battery</dt><dd class="mt-1 num">{{ $r->battery_pct !== null ? $r->battery_pct.'%' : '—' }}</dd></div>
                        <div><dt class="kpi-label">Sinchs</dt><dd class="mt-1 num">{{ $r->syncs_count }}</dd></div>
                    </dl>
                </div>
                <div class="px-6 sm:px-7 py-4 border-t border-hairline bg-sand-light flex flex-wrap items-center gap-3">
                    <button type="button" @click="show = !show" class="btn-line btn-sm">Sinch-sleutel</button>
                    <form method="POST" action="{{ route('rfid.readers.token', $r) }}">@csrf<button class="btn-line btn-sm">Nuwe sleutel</button></form>
                    <form method="POST" action="{{ route('rfid.readers.destroy', $r) }}" class="ml-auto" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Klik weer om te verwyder'; }">@csrf @method('DELETE')<button class="text-sm text-stone hover:text-[#B0452F]">Verwyder</button></form>
                </div>
                <div x-show="show" x-cloak class="px-6 sm:px-7 py-5 border-t border-hairline text-sm">
                    @if (isset($shownToken[$r->id]))
                        <div class="font-medium text-ochre-dark">Kopieer dit nou — dit word net een keer gewys.</div>
                        <code class="mt-2 block break-all font-num bg-white border border-hairline rounded-xl px-4 py-3 select-all">{{ $shownToken[$r->id] }}</code>
                    @else
                        <p class="text-stone">Sleutels word net een keer gewys. Klik <strong class="text-char">Nuwe sleutel</strong> om die toestel weer te koppel (die ou sleutel hou dan op werk).</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="panel p-10"><div class="font-headline text-3xl">Nog geen toestelle nie.</div><p class="text-stone mt-2">Voeg jou skandeerder regs by, of aktiveer een met sy kode.</p></div>
        @endforelse

        <div class="panel p-6 sm:p-8">
            <h2 class="font-headline text-3xl">Laai 'n sessielêer op</h2>
            <p class="text-stone mt-2">Geen internet in die kraal nie? Stoor die sessie op die toestel en laai dit later op. Enige kolomvolgorde werk — ons herken oormerke, gewigte en datums self.</p>
            <form method="POST" action="{{ route('rfid.sync.upload') }}" enctype="multipart/form-data" class="mt-6 grid sm:grid-cols-3 gap-4 items-end">
                @csrf
                <div class="sm:col-span-3"><input type="file" name="file" accept=".csv,.txt,.tsv" required class="field !h-auto py-2.5 file:mr-3 file:rounded-full file:border-0 file:bg-char file:text-sand file:px-4 file:py-1.5 file:text-sm"></div>
                <div><label class="field-label">Toestel</label><select name="reader_id" class="field"><option value="">—</option>@foreach ($readers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                <div><label class="field-label">Weegtipe</label><select name="weigh_type" class="field"><option value="">Roetine / uit lêer</option>@foreach (\App\Models\Scan::WEIGH_TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <button class="btn-dark">Laai op</button>
            </form>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Sinch-geskiedenis</div></div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Wanneer</th><th>Toestel</th><th>Bron</th><th class="text-right">Lesings</th><th class="text-right">Bekend</th><th class="text-right">Nuut</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($syncs as $s)
                        <tr>
                            <td class="num whitespace-nowrap">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $s->reader?->name ?? '—' }}</td>
                            <td class="text-xs uppercase text-stone">{{ $s->source === 'api' ? 'Direk' : 'Lêer' }}{{ $s->filename ? ' · '.\Illuminate\Support\Str::limit($s->filename, 24) : '' }}</td>
                            <td class="text-right num">{{ $s->scan_count }}</td>
                            <td class="text-right num">{{ $s->matched_count }}</td>
                            <td class="text-right num {{ $s->new_count ? 'text-ochre-dark' : '' }}">{{ $s->new_count }}</td>
                            <td class="text-right"><a href="{{ route('rfid.sync.show', $s) }}" class="text-sm link-u">Oop</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-stone">Nog geen sinchs nie.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4">{{ $syncs->links() }}</div>
        </div>
    </div>

    <div class="xl:col-span-2 space-y-6">
        <div class="panel p-6 sm:p-7 border-char">
            <p class="eyebrow">Maklikste manier</p>
            <div class="font-headline text-3xl mt-2">Koppel met kode</div>
            <p class="text-sm text-stone mt-2">Skakel die skandeerder aan. Dit wys 'n 6-syfer kode — tik dit hier in. Die toestel haal self sy sleutel.</p>
            <form method="POST" action="{{ route('rfid.readers.pair') }}" class="mt-5 flex gap-2">
                @csrf
                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required placeholder="482 913" class="field font-num text-xl tracking-[0.3em] text-center">
                <button class="btn-dark shrink-0">Koppel</button>
            </form>
        </div>

        <div class="panel p-6">
            <div class="panel-title">Of: voeg met die hand by</div>
            <form method="POST" action="{{ route('rfid.readers.store') }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="field-label">Naam</label><input name="name" required placeholder="Kraal-skandeerder" class="field"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="field-label">Model</label><input name="model" placeholder="RFID Scanner V1" class="field"></div>
                    <div><label class="field-label">Reeksnommer</label><input name="serial" class="field font-num"></div>
                </div>
                <button class="btn-dark w-full">Voeg by &amp; kry sleutel</button>
            </form>
        </div>

        <div class="rounded-2xl bg-char text-sand p-6 sm:p-7" x-data="{ tab: 'pair' }">
            <p class="eyebrow text-sand/50">Vir jou eie skandeerder</p>
            <div class="font-headline text-3xl mt-2">Stuur direk na Kuddebestuur</div>
            <p class="text-sm text-sand/70 mt-3 leading-relaxed">Elke lesing wat die toestel stuur, verskyn binne sekondes op die <a href="{{ route('rfid.live') }}" class="underline">lewendige skerm</a>. Die antwoord sê vir die toestel wie dit was, die vorige gewig, groei en waarskuwings — wys dit op jou skerm.</p>
            <div class="mt-5 text-xs text-sand/60 font-num space-y-1">
                <div>POST {{ url('/api/v1/scans') }}</div>
                <div>GET&nbsp; {{ url('/api/v1/ping') }}</div>
                <div>Authorization: Bearer &lt;sinch-sleutel&gt;</div>
            </div>
            <a href="{{ route('rfid.readers.firmware') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-sand text-char px-4 h-9 text-sm font-medium hover:bg-white">↓ Volledige ESP32-firmware (.ino)</a>
            <div class="mt-5 flex flex-wrap gap-1 text-xs">
                @foreach (['pair' => 'Koppel', 'json' => 'JSON', 'csv' => 'CSV-reël', 'esp32' => 'ESP32', 'reply' => 'Antwoord'] as $k => $l)
                    <button type="button" @click="tab = '{{ $k }}'" class="rounded-full px-3 h-7" :class="tab === '{{ $k }}' ? 'bg-sand text-char' : 'text-sand/60 hover:text-sand'">{{ $l }}</button>
                @endforeach
            </div>
<pre x-show="tab === 'pair'" class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>// 1. toestel vra 'n kode
POST /api/v1/pair
{"serial":"FT1-000123","model":"RFID Scanner V1"}
→ {"code":"482913","secret":"…","poll_every":5}

// 2. boer tik 482 913 hier in

// 3. toestel vra elke 5 sek.
POST /api/v1/pair/status  {"secret":"…"}
→ {"status":"paired","token":"…"}   // net een keer
// stoor die token in flash (Preferences)</code></pre>
<pre x-show="tab === 'json'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>{"device": {"battery": 87, "firmware": "1.0.3"},
 "scans": [
  {"id": "s-000124", "eid": "982000123456789",
   "weight": 42.5, "ts": 1790798104}
 ]}

// of net een lesing:
{"eid": "982000123456789", "weight": 42.5}</code></pre>
<pre x-show="tab === 'csv'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>Content-Type: text/plain

982000123456789,42.5,1790798104
982000123456790,38.9,2026-09-30 08:16

// oormerk eerste; gewig en tyd in enige volgorde
// ?token=&lt;sleutel&gt; werk ook i.p.v. die header</code></pre>
<pre x-show="tab === 'esp32'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>#include &lt;WiFi.h&gt;
#include &lt;HTTPClient.h&gt;
#define TOKEN "jou-sinch-sleutel"

void sendScan(String eid, float kg) {
  HTTPClient http;
  http.begin("{{ url('/api/v1/scans') }}");
  http.addHeader("Authorization", "Bearer " TOKEN);
  http.addHeader("Content-Type", "application/json");
  String body = "{\"eid\":\"" + eid + "\",\"weight\":"
              + String(kg, 1) + ",\"id\":\"" + String(millis()) + "\"}";
  int code = http.POST(body);   // 201 = gestoor
  String reply = http.getString();
  http.end();
}</code></pre>
<pre x-show="tab === 'reply'" x-cloak class="mt-3 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>// met "compact": true — klein genoeg vir 'n ESP32
{"ok":1,"n":1,"r":[{"s":1,"a":"DVS 25 5010",
 "w":66.6,"c":-6.9,"g":-1725,"x":1,
 "t":"Skerp gewigsverlies"}]}
// s 1=gestoor 2=dubbel · a dier · w kg · c verandering
// g g/dag · x waarskuwings · t eerste waarskuwing

// volle antwoord:
{"ok": true, "stored": 1, "duplicates": 0,
 "results": [{
   "status": "ok",
   "animal": "DVS 25 5003",
   "weight": 71.4, "previous_weight": 73.5,
   "change": -2.1, "adg": -700,
   "alerts": [{"level": "warning",
               "title": "Verloor gewig"}]
 }]}</code></pre>
            <ul class="mt-5 text-xs text-sand/60 space-y-1.5 leading-relaxed">
                <li>· Stuur <span class="font-num">id</span> saam — dan is herprobeer veilig (geen dubbels nie).</li>
                <li>· Tyd as ISO-datum of Unix-sekondes/-millisekondes; sonder tyd gebruik ons nou.</li>
                <li>· Dieselfde dier + gewig binne 20 sek. = dubbel-lesing, word geïgnoreer.</li>
                <li>· Lesings binne 3 uur van mekaar vorm een sessie.</li>
                <li>· <span class="font-num">"weigh_type":"wean"</span> bo-aan 'n bondel geld vir elke lesing daarin.</li>
                <li>· Stoor elke lesing eers in flash; stuur dan. So gaan niks verlore sonder sein nie.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
