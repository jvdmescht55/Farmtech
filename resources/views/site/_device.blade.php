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
            <div class="mx-auto w-16 h-40 sm:h-48 rounded-t-[22px] bg-gradient-to-b from-[#26262a] to-[#1b1b1e] relative overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-9 bg-gradient-to-b from-[#F08A2C] to-[#D9701A]"></div>
                <span class="absolute top-12 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-[#7FB069] animate-pulse"></span>
            </div>
            <div class="relative rounded-[34px] bg-gradient-to-b from-[#28282c] to-[#18181b] p-4 pb-9 shadow-[0_40px_80px_-30px_rgba(0,0,0,.6),inset_0_1px_0_rgba(255,255,255,.08)]">
                <div class="flex justify-between items-center px-1 mb-2.5 text-[9px] tracking-[0.2em] uppercase text-sand/40"><span>KraalTrac Pro</span><span class="w-1.5 h-1.5 rounded-full bg-[#7FB069]"></span></div>
                {{-- 20×4 LCD --}}
                <div class="rounded-xl bg-[#0b0f24] p-1.5">
                    <div class="rounded-lg bg-[#2346d6] px-2 py-2 shadow-[inset_0_0_14px_rgba(0,0,30,.45)]">
                        <template x-for="(line, n) in screens[i]" :key="n">
                            <div class="font-num text-[10.5px] sm:text-[12px] leading-[1.35] text-[#dfe6ff] whitespace-pre tracking-[0.02em]" x-text="pad(line)"></div>
                        </template>
                    </div>
                </div>
                <div class="absolute inset-x-0 bottom-0 h-6 rounded-b-[34px] bg-gradient-to-b from-[#F08A2C] to-[#D9701A]"></div>
                {{-- 4×4 keypad --}}
                <div class="mt-4 grid grid-cols-4 gap-2">
                    @foreach (['1', '2', '3', 'A', '4', '5', '6', 'B', '7', '8', '9', 'C', '*', '0', '#', 'D'] as $k)
                        <span class="h-8 sm:h-9 rounded-lg grid place-items-center text-[12px] font-medium text-white {{ ctype_digit($k) ? 'bg-[#2f6db3]' : 'bg-[#c9363b]' }} shadow-[inset_0_-2px_0_rgba(0,0,0,.35)]">{{ $k }}</span>
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
