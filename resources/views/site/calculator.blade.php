@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    $p = fn ($k) => $m['series'][$k]['value'] ?? null;
    // [label, dressing %, carcass benchmark, carcass label, live benchmark, live label, kg min, kg max, kg default, price/head default]
    $kinds = [
        'lambs' => ['Lambs', 48, $p('lamb_a'), 'lamb carcass A2/3', $p('feeder_lamb'), 'feeder lambs', 15, 80, 38, 2050],
        'sheep' => ['Sheep', 45, $p('mutton_b'), 'mutton carcass B2/3', null, null, 25, 120, 55, 2400],
        'weaners' => ['Weaners', 56, null, null, $p('weaner'), 'weaner calves', 100, 350, 220, 11000],
        'cattle' => ['Cattle', 54, $p('beef_a'), 'beef carcass A2/3', null, null, 200, 750, 450, 16000],
        'goats' => ['Goats', 45, null, null, null, null, 15, 90, 35, 1800],
    ];
@endphp
@section('title', 'Free auction calculator: R/kg at a live auction — Farmtech')
@section('description', 'Fast free livestock auction calculator for your phone: slide the head count and the bid, see rand per kg, per head, VAT, commission and what lands in your pocket instantly. Compared with this week\'s carcass prices.')

@section('content')
<section class="pt-[76px] pb-16"
    x-data="{
        kinds: @js($kinds),
        kind: 'lambs', side: 'sell', mode: 'head', vatIncl: false, vat: 15,
        head: 40, kg: 38, price: 2050, commission: 4, transport: 0, other: 0, minimum: '', costs: false,
        init() {
            try { Object.assign(this, JSON.parse(localStorage.getItem('ft-calc') || '{}')); } catch (e) {}
            ['kind','side','mode','vatIncl','head','kg','price','commission','transport','other','minimum'].forEach(k => this.$watch(k, () => this.save()));
        },
        save() { try { localStorage.setItem('ft-calc', JSON.stringify({ kind: this.kind, side: this.side, mode: this.mode, vatIncl: this.vatIncl, head: this.head, kg: this.kg, price: this.price, commission: this.commission, transport: this.transport, other: this.other, minimum: this.minimum })); } catch (e) {} },
        pick(k) { if (this.kind === k) return; const d = this.kinds[k]; this.kind = k; this.kg = d[8]; this.mode = 'head'; this.price = d[9]; },
        setMode(md) { if (this.mode === md) return; this.price = md === 'kg' ? +(this.perKg).toFixed(2) : Math.round(this.perHead); this.mode = md; },
        get k() { return this.kinds[this.kind]; },
        get perHead() { return this.mode === 'head' ? +this.price : this.price * this.kg; },
        get perKg() { return this.kg > 0 ? this.perHead / this.kg : 0; },
        get exFactor() { return this.vatIncl ? 1 / (1 + this.vat / 100) : 1; },
        get gross() { return this.perHead * this.head * this.exFactor; },
        get grossIncl() { return this.gross * (1 + this.vat / 100); },
        get fees() { return this.gross * this.commission / 100 + (+this.transport || 0) + (+this.other || 0) * this.head; },
        get bottom() { return this.side === 'sell' ? this.gross - this.fees : this.grossIncl + this.fees; },
        get bottomKg() { return this.head && this.kg ? this.bottom / (this.head * this.kg) : 0; },
        get carcass() { return this.kg ? (this.perHead * this.exFactor) / (this.kg * this.k[1] / 100) : 0; },
        pct(a, b) { return b ? Math.round((a - b) / b * 1000) / 10 : null; },
        bump(d) { this.price = Math.max(0, Math.round((+this.price + d) * 100) / 100); },
        r(v, d = 0) { return 'R' + (Math.round(v * 10 ** d) / 10 ** d).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }).replace(/,/g, ' '); },
    }">
    {{-- The answer: pinned under the header while you adjust --}}
    <div class="sticky z-30 bg-char text-sand shadow-[0_20px_40px_-25px_rgba(0,0,0,.6)]" style="top: calc(76px + env(safe-area-inset-top, 0px))">
        <div class="wrap pt-3 sm:pt-4 grid grid-cols-3 gap-3 items-end">
            <div>
                <div class="text-[11px] sm:text-xs text-sand/60">Per kg live</div>
                <div class="font-headline text-[34px] sm:text-6xl leading-none" x-text="r(perKg * exFactor, 2)"></div>
            </div>
            <div>
                <div class="text-[11px] sm:text-xs text-sand/60">Per head</div>
                <div class="font-headline text-[34px] sm:text-6xl leading-none" x-text="r(perHead * exFactor)"></div>
            </div>
            <div class="text-right">
                <div class="text-[11px] sm:text-xs text-sand/60" x-text="side === 'sell' ? 'In your pocket' : 'Total cost'"></div>
                <div class="font-headline text-[26px] sm:text-5xl leading-none text-ochre-light" x-text="r(bottom)"></div>
            </div>
        </div>
        <div class="wrap py-2.5 text-[11px] sm:text-xs text-sand/55 flex flex-wrap gap-x-4 gap-y-0.5">
            <span><span x-text="head"></span> head × <span x-text="kg"></span> kg</span>
            <span>excl. VAT <span x-text="r(gross)"></span></span>
            <span>incl. VAT <span x-text="r(grossIncl)"></span></span>
            <span x-show="minimum > 0" :class="perKg * exFactor >= minimum ? 'text-[#9fd59a]' : 'text-[#f0a08f]'" x-text="(perKg * exFactor >= minimum ? '▲ ' : '▼ ') + Math.abs(pct(perKg * exFactor, minimum)) + '% vs your minimum'"></span>
        </div>
    </div>

    <div class="wrap mt-6">
        <h1 class="font-headline text-3xl sm:text-4xl">Auction calculator</h1>
        <p class="text-stone text-sm mt-1">Slide or tap the bid as it moves. Everything updates instantly.</p>
    </div>

    <div class="wrap mt-5 grid lg:grid-cols-[1fr_22rem] gap-6 items-start">
        <div class="space-y-4">
            {{-- Animal & side --}}
            <div class="flex flex-wrap gap-2 items-center">
                <template x-for="(d, key) in kinds" :key="key">
                    <button type="button" @click="pick(key)" class="rounded-full px-4 h-11 text-[15px] border transition" :class="kind === key ? 'bg-char text-sand border-char' : 'bg-white border-hairline'" x-text="d[0]"></button>
                </template>
                <div class="ml-auto inline-flex rounded-full bg-white border border-hairline p-1">
                    <button type="button" @click="side = 'sell'" class="rounded-full px-4 h-9 text-sm" :class="side === 'sell' ? 'bg-char text-sand' : 'text-stone'">Selling</button>
                    <button type="button" @click="side = 'buy'" class="rounded-full px-4 h-9 text-sm" :class="side === 'buy' ? 'bg-char text-sand' : 'text-stone'">Buying</button>
                </div>
            </div>

            {{-- Bid --}}
            <div class="rounded-[24px] bg-white border border-hairline p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex rounded-full bg-sand-light border border-hairline p-1">
                        <button type="button" @click="setMode('head')" class="rounded-full px-4 h-9 text-sm" :class="mode === 'head' ? 'bg-char text-sand' : 'text-stone'">Bid per head</button>
                        <button type="button" @click="setMode('kg')" class="rounded-full px-4 h-9 text-sm" :class="mode === 'kg' ? 'bg-char text-sand' : 'text-stone'">Bid per kg</button>
                    </div>
                    <div class="inline-flex rounded-full bg-sand-light border border-hairline p-1 text-sm">
                        <button type="button" @click="vatIncl = false" class="rounded-full px-3 h-9" :class="!vatIncl ? 'bg-char text-sand' : 'text-stone'">excl. VAT</button>
                        <button type="button" @click="vatIncl = true" class="rounded-full px-3 h-9" :class="vatIncl ? 'bg-char text-sand' : 'text-stone'">incl. VAT</button>
                    </div>
                </div>
                <label for="calc-price" class="sr-only">Bid</label>
                <div class="mt-4 flex items-center gap-2">
                    <span class="font-headline text-4xl text-stone">R</span>
                    <input id="calc-price" type="number" inputmode="decimal" min="0" :step="mode === 'head' ? 10 : 0.1" x-model.number="price" @focus="$el.select()" class="flex-1 min-w-0 h-20 rounded-2xl border-2 border-hairline focus:border-char focus:outline-none px-4 font-headline text-5xl sm:text-6xl bg-white">
                    <span class="text-stone" x-text="mode === 'head' ? '/head' : '/kg'"></span>
                </div>
                <div class="mt-3 grid grid-cols-4 gap-2">
                    <template x-for="d in (mode === 'head' ? [-100, -10, 10, 100] : [-1, -0.2, 0.2, 1])" :key="d">
                        <button type="button" @click="bump(d)" class="h-12 rounded-xl border border-hairline bg-sand-light text-lg font-medium active:scale-95 transition" x-text="(d > 0 ? '+' : '−') + (mode === 'head' ? Math.abs(d) : (Math.abs(d) < 1 ? Math.round(Math.abs(d) * 100) + 'c' : 'R' + Math.abs(d)))"></button>
                    </template>
                </div>
                <input type="range" aria-label="Bid slider" min="0" :max="mode === 'head' ? k[9] * 2.5 : 150" :step="mode === 'head' ? 10 : 0.1" x-model.number="price" class="mt-4 w-full accent-[#B8732E] h-8">
            </div>

            {{-- Head & weight --}}
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach ([['head', 'Head', 1, '1', '500', 'head', [-10, -1, 1, 10]], ['kg', 'Average live weight', 0.5, 'k[6]', 'k[7]', 'kg', [-5, -1, 1, 5]]] as [$f, $label, $step, $min, $max, $unit, $bumps])
                    <div class="rounded-[24px] bg-white border border-hairline p-5">
                        <label for="calc-{{ $f }}" class="text-sm text-stone">{{ $label }}</label>
                        <div class="mt-2 flex items-center gap-2">
                            <input id="calc-{{ $f }}" type="number" inputmode="decimal" min="0" step="{{ $step }}" x-model.number="{{ $f }}" @focus="$el.select()" class="flex-1 min-w-0 h-16 rounded-2xl border-2 border-hairline focus:border-char focus:outline-none px-4 font-headline text-4xl bg-white">
                            <span class="text-stone">{{ $unit }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach ($bumps as $d)
                                <button type="button" @click="{{ $f }} = Math.max({{ $f === 'head' ? 1 : 0 }}, Math.round(({{ $f }} + {{ $d }}) * 10) / 10)" class="h-11 rounded-xl border border-hairline bg-sand-light font-medium active:scale-95 transition">{{ $d > 0 ? '+' : '−' }}{{ abs($d) }}</button>
                            @endforeach
                        </div>
                        <input type="range" aria-label="{{ $label }} slider" :min="{{ $min }}" :max="{{ $max }}" step="{{ $step }}" x-model.number="{{ $f }}" class="mt-3 w-full accent-[#B8732E] h-8">
                    </div>
                @endforeach
            </div>

            {{-- Costs & minimum --}}
            <div class="rounded-[24px] bg-white border border-hairline">
                <button type="button" @click="costs = !costs" class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left">
                    <span><span class="font-medium">Costs &amp; your minimum</span> <span class="text-sm text-stone" x-text="'· ' + commission + '% commission' + (transport ? ' · transport ' + r(transport) : '') + (minimum ? ' · min ' + r(minimum, 2) + '/kg' : '')"></span></span>
                    <span class="text-stone text-xl" x-text="costs ? '−' : '+'"></span>
                </button>
                <div x-show="costs" x-cloak class="px-5 pb-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <label class="block"><span class="field-label">Commission %</span><input type="number" inputmode="decimal" min="0" step="0.1" x-model.number="commission" class="field font-num"></label>
                    <label class="block"><span class="field-label">Transport (R total)</span><input type="number" inputmode="decimal" min="0" x-model.number="transport" class="field font-num"></label>
                    <label class="block"><span class="field-label">Levies (R/head)</span><input type="number" inputmode="decimal" min="0" step="0.01" x-model.number="other" class="field font-num"></label>
                    <label class="block"><span class="field-label">My minimum (R/kg)</span><input type="number" inputmode="decimal" min="0" step="0.1" x-model="minimum" placeholder="optional" class="field font-num"></label>
                    <p class="col-span-2 sm:col-span-4 text-xs text-stone">Commission and levies are worked on the price excl. VAT. VAT is 15%. Set your auctioneer's commission once; your phone remembers it.</p>
                </div>
            </div>
        </div>

        {{-- Breakdown & market --}}
        <aside class="space-y-4 lg:sticky lg:top-60">
            <div class="rounded-[24px] bg-white border border-hairline p-5 text-[15px]">
                <div class="font-medium">The sums</div>
                <dl class="mt-3 space-y-1.5">
                    <div class="flex justify-between gap-3"><dt class="text-stone">Lot, excl. VAT</dt><dd class="font-num" x-text="r(gross)"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone">VAT (15%)</dt><dd class="font-num" x-text="r(grossIncl - gross)"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone">Lot, incl. VAT</dt><dd class="font-num" x-text="r(grossIncl)"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone" x-text="side === 'sell' ? 'Less commission, transport, levies' : 'Plus commission, transport, levies'"></dt><dd class="font-num" x-text="(side === 'sell' ? '− ' : '+ ') + r(fees)"></dd></div>
                    <div class="flex justify-between gap-3 border-t border-hairline pt-2 font-medium"><dt x-text="side === 'sell' ? 'In your pocket (excl. VAT)' : 'Total cost (incl. VAT)'"></dt><dd class="font-num" x-text="r(bottom)"></dd></div>
                    <div class="flex justify-between gap-3 text-sm"><dt class="text-stone" x-text="side === 'sell' ? 'Net per kg · per head' : 'Real cost per kg · per head'"></dt><dd class="font-num"><span x-text="r(bottomKg, 2)"></span> · <span x-text="head ? r(bottom / head) : '—'"></span></dd></div>
                </dl>
            </div>

            <div class="rounded-[24px] bg-white border border-hairline p-5 text-sm">
                <div class="font-medium">Against this week's market</div>
                <div class="mt-3 space-y-3">
                    <div x-show="k[2]">
                        <div class="text-stone">Carcass equivalent at <span x-text="k[1]"></span>% dressing</div>
                        <div class="flex items-baseline justify-between"><span class="font-headline text-3xl" x-text="r(carcass, 2) + '/kg'"></span><span :class="pct(carcass, k[2]) >= 0 ? 'text-[#3F7A3A]' : 'text-[#B0452F]'" x-text="(pct(carcass, k[2]) >= 0 ? '▲ ' : '▼ ') + Math.abs(pct(carcass, k[2])) + '%'"></span></div>
                        <div class="text-xs text-stone">vs <span x-text="r(k[2], 2)"></span>/kg <span x-text="k[3]"></span></div>
                    </div>
                    <div x-show="k[4]" class="pt-3 border-t border-hairline">
                        <div class="text-stone">Live price vs <span x-text="k[5]"></span></div>
                        <div class="flex items-baseline justify-between"><span class="font-headline text-3xl" x-text="r(perKg * exFactor, 2) + '/kg'"></span><span :class="pct(perKg * exFactor, k[4]) >= 0 ? 'text-[#3F7A3A]' : 'text-[#B0452F]'" x-text="(pct(perKg * exFactor, k[4]) >= 0 ? '▲ ' : '▼ ') + Math.abs(pct(perKg * exFactor, k[4])) + '%'"></span></div>
                        <div class="text-xs text-stone">vs <span x-text="r(k[4], 2)"></span>/kg this week</div>
                    </div>
                    <p x-show="!k[2] && !k[4]" class="text-stone">No weekly benchmark for goats yet.</p>
                </div>
                @if ($m)<p class="mt-4 text-[11px] text-stone-light"><a href="{{ route('site.prices') }}" class="underline">RPO prices</a>, week ending {{ \Carbon\Carbon::parse($m['week'])->format('j M Y') }}.</p>@endif
            </div>
            <p class="text-xs text-stone">A guide, not financial advice. Nothing you type leaves your phone. Tip: add this page to your home screen before the auction.</p>
        </aside>
    </div>

    <div class="wrap mt-12">
        <x-market-strip />
        <div class="mt-8 rounded-[28px] bg-char text-sand overflow-hidden relative">
            <img src="{{ Img::url('flock-bakkie', true) }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-35 img-grade">
            <div class="relative p-8 sm:p-10 grid md:grid-cols-[1fr_auto] gap-6 items-center">
                <div>
                    <h2 class="font-headline text-3xl sm:text-4xl">Know the weights before the auction.</h2>
                    <p class="mt-2 text-sand/70 max-w-xl">With a KraalTrac Pro, Herd Manager sorts your mob by weight, shows who's market-ready and builds the auction book with lot numbers.</p>
                </div>
                <a href="{{ route('site.store') }}#pro" class="btn-light">See the KraalTrac Pro</a>
            </div>
        </div>
    </div>
</section>
@endsection

@section('credits')<x-photo-credits :keys="['flock-bakkie']" dark />@endsection
