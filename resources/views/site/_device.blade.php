{{--
    The KraalTrac Pro, drawn: 20×4 LCD running the real firmware screens, 4×4 keypad,
    antenna wand. And a phone beside it where each saved weight lands.
--}}
<div class="relative mx-auto w-full max-w-[560px] select-none" aria-label="KraalTrac Pro demo: scan, weigh, saved to Herd Manager" role="img"
     x-data="{
        i: 0,
        screens: [
            ['KraalTrac Pro', 'Scan Tag or Type ID', 'ID: ', 'All synced. D:Info'],
            ['Sheep: 250912', 'Select Weight Type:', 'A:Birth  B:Wean', 'C:Post-W D:Mature'],
            ['Enter Weight (kg):', 'Wt: 42.5 kg', 'A:. C:Del #:Save', '*: Back'],
            ['Saved: 250912', '42.5kg  Wean', 'Sent to farmtech', ''],
            ['Sheep: 250914', 'Select Weight Type:', 'A:Birth  B:Wean', 'C:Post-W D:Mature'],
            ['Enter Weight (kg):', 'Wt: 39.0 kg', 'A:. C:Del #:Save', '*: Back'],
            ['Saved: 250914', '39.0kg  Wean', 'Sent to farmtech', ''],
        ],
        rows: [],
        pad(s) { return (s + ' '.repeat(20)).slice(0, 20); },
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.i = 3; this.rows = [['250912', '42.5', '+212 g/day']]; return; }
            setInterval(() => {
                this.i = (this.i + 1) % this.screens.length;
                if (this.i === 3) this.rows.unshift(['250912', '42.5', '+212 g/day']);
                if (this.i === 6) this.rows.unshift(['250914', '39.0', '+187 g/day']);
                if (this.i === 0) this.rows = [];
            }, 1700);
        },
     }">
    <div class="flex items-end justify-center gap-6 sm:gap-10">
        {{-- Handheld --}}
        <div class="relative shrink-0 w-[230px] sm:w-[260px]">
            {{-- antenna wand --}}
            <div class="mx-auto w-7 h-40 sm:h-48 rounded-t-full bg-gradient-to-b from-[#2a2925] to-[#1d1c18] shadow-inner relative">
                <div class="absolute top-3 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full border-[5px] border-[#2a2925] bg-transparent"></div>
                <span class="absolute top-[18px] left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-ochre animate-pulse"></span>
            </div>
            <div class="rounded-[34px] bg-gradient-to-b from-[#2b2a26] to-[#191814] p-4 pb-5 shadow-[0_40px_80px_-30px_rgba(0,0,0,.6),inset_0_1px_0_rgba(255,255,255,.08)]">
                <div class="flex justify-between items-center px-1 mb-2.5 text-[9px] tracking-[0.2em] uppercase text-sand/40"><span>KraalTrac Pro</span><span class="w-1.5 h-1.5 rounded-full bg-[#7FB069]"></span></div>
                {{-- 20×4 LCD --}}
                <div class="rounded-xl bg-[#1c2a10] p-1.5">
                    <div class="rounded-lg bg-[#9DBF3F] px-2 py-2 shadow-[inset_0_0_12px_rgba(0,0,0,.25)]">
                        <template x-for="(line, n) in screens[i]" :key="n">
                            <div class="font-num text-[10.5px] sm:text-[12px] leading-[1.35] text-[#16220a] whitespace-pre tracking-[0.02em]" x-text="pad(line)"></div>
                        </template>
                    </div>
                </div>
                {{-- 4×4 keypad --}}
                <div class="mt-4 grid grid-cols-4 gap-2">
                    @foreach (['1', '2', '3', 'A', '4', '5', '6', 'B', '7', '8', '9', 'C', '*', '0', '#', 'D'] as $k)
                        <span class="h-8 sm:h-9 rounded-lg grid place-items-center text-[12px] font-medium {{ ctype_alpha($k) ? 'bg-ochre/90 text-char' : 'bg-[#34332e] text-sand/85' }} shadow-[inset_0_-2px_0_rgba(0,0,0,.35)]">{{ $k }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Phone --}}
        <div class="hidden min-[420px]:block w-[150px] sm:w-[190px] shrink-0 rounded-[30px] bg-char p-2 shadow-[0_40px_80px_-30px_rgba(0,0,0,.55)] mb-6">
            <div class="rounded-[24px] bg-sand overflow-hidden h-[300px] sm:h-[360px] flex flex-col">
                <div class="bg-char text-sand px-3 pt-5 pb-3">
                    <div class="text-[9px] uppercase tracking-[0.18em] text-sand/50">Live view</div>
                    <div class="font-headline text-[19px] leading-tight mt-0.5">Weigh day</div>
                </div>
                <div class="flex-1 p-2 space-y-1.5 overflow-hidden">
                    <template x-for="r in rows" :key="r[0]">
                        <div class="rounded-xl bg-white border border-hairline px-2.5 py-2 pop-in">
                            <div class="flex justify-between text-[11px]"><span class="font-num font-medium" x-text="r[0]"></span><span class="font-num" x-text="r[1] + ' kg'"></span></div>
                            <div class="text-[10px] text-[#3F7A3A] mt-0.5" x-text="r[2]"></div>
                        </div>
                    </template>
                    <div x-show="!rows.length" class="text-[11px] text-stone text-center pt-10 px-2">Waiting for the first scan…</div>
                </div>
            </div>
        </div>
    </div>
</div>
