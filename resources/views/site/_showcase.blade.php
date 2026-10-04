{{-- Clickable Herd Manager screens (sample data) used on the store. --}}
<div x-data="{ tab: 'weigh' }">
            <div class="flex flex-wrap gap-2 mb-5">
                @foreach (['weigh' => 'Weigh day', 'animal' => 'One animal', 'book' => 'Auction book', 'alerts' => 'Alerts'] as $k => $l)
                    <button type="button" @click="tab = '{{ $k }}'" class="rounded-full px-4 h-9 text-sm border transition" :class="tab === '{{ $k }}' ? 'bg-char text-sand border-char' : 'bg-white border-hairline text-stone hover:text-char hover:border-char'">{{ $l }}</button>
                @endforeach
            </div>
            <div class="rounded-[24px] bg-char/5 text-char p-2 shadow-[0_40px_100px_-40px_rgba(0,0,0,.35)]">
                <div class="rounded-[18px] bg-white border border-hairline overflow-hidden min-h-[330px]">
                    <div class="flex items-center gap-2 px-5 h-11 border-b border-hairline text-[12px] text-stone">
                        <span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span><span class="w-2.5 h-2.5 rounded-full bg-hairline"></span>
                        <span class="ml-3" x-text="{ weigh: 'Weigh day · 28 Sep', animal: 'DVS 25 5004 · Ewe', book: 'Auction book · Ram & ewe sale 2026', alerts: 'Needs your attention' }[tab]"></span>
                    </div>

                    {{-- Weigh day --}}
                    <div x-show="tab === 'weigh'" x-transition.opacity>
                        <div class="grid grid-cols-3 divide-x divide-hairline border-b border-hairline">
                            @foreach ([['Average', '42.8', 'kg'], ['Daily gain', '+214', 'g'], ['Weighed', '186', 'head']] as [$l, $v, $u])
                                <div class="p-4 sm:p-5"><div class="kpi-label !text-[10px]">{{ $l }}</div><div class="mt-2 font-headline text-3xl sm:text-4xl">{{ $v }}<span class="text-sm sm:text-base text-stone ml-1 font-ui">{{ $u }}</span></div></div>
                            @endforeach
                        </div>
                        <div class="p-5 grid sm:grid-cols-2 gap-6">
                            <div>
                                <div class="kpi-label !text-[10px] mb-3">Average weight · 6 weigh days</div>
                                <svg viewBox="0 0 300 110" class="w-full h-28" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="g1" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#B8732E" stop-opacity=".25"/><stop offset="1" stop-color="#B8732E" stop-opacity="0"/></linearGradient></defs><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22 L300,110 L0,110Z" fill="url(#g1)"/><path d="M0,95 L60,82 L120,70 L180,52 L240,38 L300,22" fill="none" stroke="#B8732E" stroke-width="2.5"/></svg>
                            </div>
                            <div>
                                <div class="kpi-label !text-[10px] mb-3">Sort by weight</div>
                                @foreach ([['Market', '≥ 45 kg', 58, 'bg-[#3F7A3A]'], ['Feed on', '38–45 kg', 92, 'bg-ochre'], ['Watch', '< 38 kg', 36, 'bg-[#B0452F]']] as [$g, $r, $n, $c])
                                    <div class="flex items-center gap-3 text-[13px] py-1.5"><span class="w-2 h-2 rounded-full {{ $c }}"></span><span class="w-16">{{ $g }}</span><span class="text-stone flex-1">{{ $r }}</span><span class="font-medium">{{ $n }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- One animal --}}
                    <div x-show="tab === 'animal'" x-cloak x-transition.opacity class="p-5 grid sm:grid-cols-2 gap-6">
                        <div>
                            <div class="font-headline text-4xl">DVS 25 5004</div>
                            <div class="text-sm text-stone mt-1">Ewe · born Sep 2025 · <span class="chip bg-char text-sand">SP</span></div>
                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                                @foreach ([['Weight', '70.1 kg'], ['Daily gain', '+196 g'], ['Sire', 'DVS 22 2435'], ['Dam', 'DVS 21 3107'], ['Lambs', '3 (1 set of twins)'], ['Last drink', '2 h ago']] as [$k, $v])
                                    <div><dt class="text-[11px] uppercase tracking-[0.12em] text-stone">{{ $k }}</dt><dd class="mt-0.5">{{ $v }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                        <div>
                            <div class="kpi-label !text-[10px] mb-3">Growth curve</div>
                            <svg viewBox="0 0 300 140" class="w-full h-36" preserveAspectRatio="none" aria-hidden="true"><path d="M0,130 C60,110 90,80 150,62 S250,30 300,24" fill="none" stroke="#15140F" stroke-width="2.5"/><path d="M0,130 C60,118 100,96 150,82 S250,58 300,52" fill="none" stroke="#B8732E" stroke-dasharray="5 5" stroke-width="2"/></svg>
                            <div class="flex gap-4 text-[12px] text-stone"><span>— this ewe</span><span class="text-ochre">- - flock average</span></div>
                        </div>
                    </div>

                    {{-- Auction book --}}
                    <div x-show="tab === 'book'" x-cloak x-transition.opacity class="p-5">
                        <table class="w-full text-[13px]">
                            <thead><tr class="text-left text-[10px] uppercase tracking-[0.12em] text-stone"><th class="pb-2">Lot</th><th class="pb-2">Animal</th><th class="pb-2">Tier</th><th class="pb-2">Sire × Dam</th><th class="pb-2 text-right">Weight</th></tr></thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ([['66A', 'DVS 25 5001', 'SP', '222435 × 213107', '84.1'], ['66B', 'DVS 25 5004', 'SP', '222435 × 220120', '70.1'], ['66C', 'DVS 25 5023', 'C', '230017 × 219988', '70.2'], ['67A', 'DVS 25 5042', 'B', '230017 × 221402', '54.2']] as [$lot, $id, $t, $p, $w])
                                    <tr><td class="py-2.5 font-headline text-xl">{{ $lot }}</td><td class="font-mono">{{ $id }}</td><td><span class="chip {{ $t === 'SP' ? 'bg-char text-sand' : 'bg-sand-deep' }}">{{ $t }}</span></td><td class="font-mono text-[11px] text-stone">{{ $p }}</td><td class="text-right">{{ $w }} kg</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4 text-[12px] text-stone">Lot numbers in one click · prints in the Logix layout</div>
                    </div>

                    {{-- Alerts --}}
                    <div x-show="tab === 'alerts'" x-cloak x-transition.opacity class="p-5 space-y-3 text-[13px]">
                        @foreach ([['#B0452F', 'Sharp weight loss', 'DVS 25 5010 lost 6.9 kg (9.4%) in two weeks. Check her first.'], ['#B8732E', 'Hasn\'t been to drink', 'Ewe 3107 last seen at the north trough 27 h ago.'], ['#B8732E', 'Lambing this week', '3 ewes scanned with twins are due. Move them to the lambing camp.'], ['#3F7A3A', 'Withdrawal done', 'Group B is clear of Closantel from Friday. Safe to sell.']] as [$c, $t, $d])
                            <div class="flex gap-3 rounded-xl border border-hairline p-3.5"><span class="mt-1.5 w-2 h-2 shrink-0 rounded-full" style="background: {{ $c }}"></span><div><div class="font-medium">{{ $t }}</div><div class="text-stone mt-0.5">{{ $d }}</div></div></div>
                        @endforeach
                    </div>
                </div>
            </div>
</div>
