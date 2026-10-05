@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    $p = fn ($k) => $m['series'][$k]['value'] ?? null;
    $kinds = [
        'lambs' => ['Lambs', 48, $p('lamb_a'), 'lamb carcass A2/3', $p('feeder_lamb'), 'feeder lambs'],
        'sheep' => ['Sheep (mutton)', 45, $p('mutton_b'), 'mutton carcass B2/3', null, null],
        'weaners' => ['Weaner calves', 56, null, null, $p('weaner'), 'weaner calves'],
        'cattle' => ['Cattle', 54, $p('beef_a'), 'beef carcass A2/3', null, null],
        'goats' => ['Goats', 45, null, null, null, null],
    ];
@endphp
@section('title', 'Free auction calculator: price per head to R/kg — Farmtech')
@section('description', 'Free livestock auction calculator for South African farmers: turn a price per head into rand per kg (and back), work out several lots, take off commission and transport, and compare with this week\'s carcass prices.')
@section('hero_dark', '1')

@section('content')
<section class="relative overflow-hidden bg-char text-white">
    <img src="{{ Img::url('flock-bakkie', true) }}" srcset="{{ Img::srcset('flock-bakkie') }}" sizes="100vw" alt="{{ Img::alt('flock-bakkie') }}" class="absolute inset-0 w-full h-full object-cover opacity-50 img-grade kenburns">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/50 to-char/30"></div>
    <div class="relative wrap pt-40 pb-14">
        <p class="text-white/70 text-lg">Free auction calculator</p>
        <h1 class="h-display mt-3 text-[clamp(3.2rem,8vw,6.5rem)]">Is that a good price?</h1>
        <p class="mt-5 text-lg text-white/80 max-w-xl">Turn a bid per head into rand per kg, or work out what a lot is worth at a price per kg. Take off commission and transport, and see how it stacks up against this week's prices.</p>
    </div>
</section>

<section class="wrap py-12 sm:py-16"
    x-data="{
        kinds: @js($kinds),
        kind: 'lambs', mode: 'head', dressing: 48,
        lots: [{ name: 'Lot 1', head: 40, kg: 38, price: 2050 }],
        commission: 4, transport: 0, other: 0,
        init() {
            try { const s = JSON.parse(atob(decodeURIComponent(location.hash.slice(1)))); Object.assign(this, s); } catch (e) {}
            this.$watch('kind', k => this.dressing = this.kinds[k][1]);
        },
        rkg(l) { return l.kg > 0 ? (this.mode === 'head' ? l.price / l.kg : +l.price) : 0; },
        rhead(l) { return this.mode === 'head' ? +l.price : l.price * l.kg; },
        total(l) { return this.rhead(l) * l.head; },
        get head() { return this.lots.reduce((a, l) => a + (+l.head || 0), 0); },
        get kg() { return this.lots.reduce((a, l) => a + (l.head * l.kg || 0), 0); },
        get gross() { return this.lots.reduce((a, l) => a + (this.total(l) || 0), 0); },
        get fees() { return this.gross * this.commission / 100 + (+this.transport || 0) + (+this.other || 0) * this.head; },
        get net() { return this.gross - this.fees; },
        get carcass() { return this.kg ? this.gross / (this.kg * this.dressing / 100) : 0; },
        get compare() { return this.kinds[this.kind][2]; },
        get live() { return this.kinds[this.kind][4]; },
        pct(a, b) { return b ? Math.round((a - b) / b * 1000) / 10 : null; },
        r(v, d = 0) { return 'R' + (Math.round(v * 10 ** d) / 10 ** d).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }).replace(/,/g, ' '); },
        share() {
            const s = btoa(JSON.stringify({ kind: this.kind, mode: this.mode, dressing: this.dressing, lots: this.lots, commission: this.commission, transport: this.transport, other: this.other }));
            history.replaceState(null, '', '#' + encodeURIComponent(s));
            navigator.clipboard?.writeText(location.href); this.copied = true; setTimeout(() => this.copied = false, 2000);
        },
        copied: false,
    }">
    <div class="grid lg:grid-cols-[1fr_25rem] gap-8 items-start">
        <div class="space-y-6">
            {{-- What & how --}}
            <div class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <div class="font-headline text-2xl">1. What are you selling?</div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(k, key) in kinds" :key="key">
                        <button type="button" @click="kind = key" class="rounded-full px-4 h-10 text-sm border transition" :class="kind === key ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char'" x-text="k[0]"></button>
                    </template>
                </div>
                <div class="mt-6 font-headline text-2xl">2. You know the price…</div>
                <div class="mt-4 inline-flex rounded-full bg-sand-light border border-hairline p-1">
                    <button type="button" @click="mode = 'head'" class="rounded-full px-5 h-10 text-sm" :class="mode === 'head' ? 'bg-char text-sand' : 'text-stone'">per head</button>
                    <button type="button" @click="mode = 'kg'" class="rounded-full px-5 h-10 text-sm" :class="mode === 'kg' ? 'bg-char text-sand' : 'text-stone'">per kg</button>
                </div>
            </div>

            {{-- Lots --}}
            <div class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="font-headline text-2xl">3. Your lots</div>
                    <button type="button" @click="lots.push({ name: 'Lot ' + (lots.length + 1), head: 10, kg: lots.at(-1)?.kg || 35, price: lots.at(-1)?.price || 2000 })" class="btn-line btn-sm">+ Add a lot</button>
                </div>
                <div class="mt-5 space-y-3">
                    <template x-for="(l, i) in lots" :key="i">
                        <div class="rounded-2xl bg-sand-light border border-hairline p-4">
                            <div class="grid grid-cols-2 sm:grid-cols-[1fr_6rem_7rem_8.5rem] gap-3 items-end">
                                <label class="col-span-2 sm:col-span-1 block"><span class="field-label">Lot</span><input x-model="l.name" class="field !h-11"></label>
                                <label class="block"><span class="field-label">Head</span><input type="number" min="0" x-model.number="l.head" class="field !h-11 font-num"></label>
                                <label class="block"><span class="field-label">Avg live kg</span><input type="number" min="0" step="0.1" x-model.number="l.kg" class="field !h-11 font-num"></label>
                                <label class="col-span-2 sm:col-span-1 block"><span class="field-label" x-text="mode === 'head' ? 'Price per head (R)' : 'Price per kg (R)'"></span><input type="number" min="0" step="0.01" x-model.number="l.price" class="field !h-11 font-num"></label>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
                                <span><span class="text-stone" x-text="mode === 'head' ? 'Per kg live:' : 'Per head:'"></span> <strong class="font-num" x-text="mode === 'head' ? r(rkg(l), 2) + '/kg' : r(rhead(l))"></strong></span>
                                <span><span class="text-stone">Lot total:</span> <strong class="font-num" x-text="r(total(l))"></strong></span>
                                <button type="button" x-show="lots.length > 1" @click="lots.splice(i, 1)" class="ml-auto text-stone underline">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Costs --}}
            <div class="rounded-[28px] bg-white border border-hairline p-6 sm:p-8">
                <div class="font-headline text-2xl">4. Costs (optional)</div>
                <div class="mt-5 grid sm:grid-cols-4 gap-4">
                    <label class="block"><span class="field-label">Commission %</span><input type="number" min="0" step="0.1" x-model.number="commission" class="field font-num"></label>
                    <label class="block"><span class="field-label">Transport (R, total)</span><input type="number" min="0" x-model.number="transport" class="field font-num"></label>
                    <label class="block"><span class="field-label">Levies / other (R per head)</span><input type="number" min="0" step="0.01" x-model.number="other" class="field font-num"></label>
                    <label class="block"><span class="field-label">Dressing %</span><input type="number" min="30" max="70" step="0.5" x-model.number="dressing" class="field font-num"></label>
                </div>
                <p class="mt-3 text-xs text-stone">Commission: use your auctioneer's rate. Dressing % is carcass weight ÷ live weight; we fill in a typical figure for the animal you chose.</p>
            </div>
        </div>

        {{-- Answer --}}
        <aside class="lg:sticky lg:top-28 space-y-4">
            <div class="rounded-[28px] bg-char text-sand p-7">
                <div class="text-sand/60 text-sm"><span x-text="head"></span> head · <span x-text="Math.round(kg).toLocaleString('en-US').replace(/,/g, ' ')"></span> kg live</div>
                <div class="mt-4 text-sand/60 text-sm">Average price</div>
                <div class="font-headline text-6xl leading-none mt-1" x-text="kg ? r(gross / kg, 2) : 'R0'"></div>
                <div class="text-sand/60 text-sm mt-1">per kg live · <span x-text="head ? r(gross / head) : 'R0'"></span> per head</div>

                <dl class="mt-6 pt-5 border-t border-white/10 space-y-2 text-[15px]">
                    <div class="flex justify-between"><dt class="text-sand/70">Gross</dt><dd class="font-num" x-text="r(gross)"></dd></div>
                    <div class="flex justify-between"><dt class="text-sand/70">Less commission, transport &amp; levies</dt><dd class="font-num" x-text="'− ' + r(fees)"></dd></div>
                    <div class="flex justify-between pt-2 border-t border-white/10"><dt>In your pocket</dt><dd class="font-headline text-3xl" x-text="r(net)"></dd></div>
                    <div class="flex justify-between text-sm text-sand/70"><dt>Net per kg / per head</dt><dd class="font-num"><span x-text="kg ? r(net / kg, 2) : '—'"></span> · <span x-text="head ? r(net / head) : '—'"></span></dd></div>
                </dl>
            </div>

            <div class="rounded-[28px] bg-white border border-hairline p-6">
                <div class="font-medium">Against this week's market</div>
                <div class="mt-3 text-sm space-y-3">
                    <div x-show="compare">
                        <div class="text-stone">Carcass equivalent at <span x-text="dressing"></span>% dressing</div>
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-headline text-3xl" x-text="r(carcass, 2) + '/kg'"></span>
                            <span class="text-sm" :class="pct(carcass, compare) >= 0 ? 'text-[#3F7A3A]' : 'text-[#B0452F]'" x-text="(pct(carcass, compare) >= 0 ? '▲ ' : '▼ ') + Math.abs(pct(carcass, compare)) + '%'"></span>
                        </div>
                        <div class="text-xs text-stone">vs <span x-text="r(compare, 2)"></span>/kg <span x-text="kinds[kind][3]"></span> this week</div>
                    </div>
                    <div x-show="live" class="pt-3 border-t border-hairline">
                        <div class="text-stone">Live price vs <span x-text="kinds[kind][5]"></span></div>
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-headline text-3xl" x-text="kg ? r(gross / kg, 2) + '/kg' : '—'"></span>
                            <span class="text-sm" :class="pct(gross / kg, live) >= 0 ? 'text-[#3F7A3A]' : 'text-[#B0452F]'" x-text="kg ? (pct(gross / kg, live) >= 0 ? '▲ ' : '▼ ') + Math.abs(pct(gross / kg, live)) + '%' : ''"></span>
                        </div>
                        <div class="text-xs text-stone">vs <span x-text="r(live, 2)"></span>/kg this week</div>
                    </div>
                    <p x-show="!compare && !live" class="text-stone">No weekly benchmark for this one yet.</p>
                </div>
                @if ($m)<p class="mt-4 text-[11px] text-stone-light">Benchmarks: <a href="{{ route('site.prices') }}" class="underline">RPO prices</a>, week ending {{ \Carbon\Carbon::parse($m['week'])->format('j M Y') }}.</p>@endif
            </div>

            <div class="flex gap-2">
                <button type="button" @click="share()" class="btn-line flex-1" x-text="copied ? 'Link copied ✓' : 'Copy a link'"></button>
                <button type="button" onclick="window.print()" class="btn-line flex-1">Print</button>
            </div>
            <p class="text-xs text-stone">A guide, not financial advice. Nothing you type leaves your phone.</p>
        </aside>
    </div>
</section>

<section class="wrap pb-20">
    <x-market-strip />
    <div class="mt-10 rounded-[28px] bg-sand-deep/60 border border-hairline p-8 sm:p-10 grid md:grid-cols-[1fr_auto] gap-6 items-center">
        <div>
            <h2 class="font-headline text-3xl sm:text-4xl">Know the weights before the auction.</h2>
            <p class="mt-2 text-stone max-w-xl">With a KraalTrac Pro, Herd Manager sorts your mob by weight, shows who's market-ready and builds the auction book with lot numbers.</p>
        </div>
        <a href="{{ route('site.store') }}#pro" class="btn-dark">See the KraalTrac Pro</a>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['flock-bakkie']" dark />@endsection
