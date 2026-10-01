{{-- First-run tour. $steps: [['target' => 'tabs'|null, 'title' => …, 'body' => …], …] --}}
@props(['key', 'steps' => [], 'auto' => false])
<div x-data="tour(@js($steps), '{{ route('tour.done', $key) }}', {{ $auto ? 'true' : 'false' }})" x-cloak>
    <template x-if="open">
        <div class="fixed inset-0 z-[70]" @keydown.escape.window="finish()">
            <div x-show="!rect" class="absolute inset-0 bg-char/60 backdrop-blur-[2px]" @click="finish()"></div>
            <div x-show="rect" class="absolute rounded-2xl transition-all duration-300 ease-out ring-2 ring-ochre pointer-events-none" :style="spot" style="box-shadow: 0 0 0 9999px rgba(21,20,15,.62)"></div>
            <div class="absolute left-4 right-4 bottom-4 sm:bottom-auto sm:right-auto sm:w-[380px] rounded-[22px] bg-white text-char shadow-[0_30px_80px_-20px_rgba(0,0,0,.5)] p-6 transition-all duration-300"
                 :class="!rect && 'sm:left-1/2 sm:top-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2'" :style="card"
                 style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 0px))">
                <div class="flex items-center justify-between">
                    <span class="eyebrow" x-text="`Step ${i + 1} of ${steps.length}`"></span>
                    <button type="button" @click="finish()" class="text-sm text-stone hover:text-char">Skip</button>
                </div>
                <div class="mt-3 font-headline text-3xl leading-tight" x-text="step.title"></div>
                <p class="mt-2 text-stone leading-relaxed" x-html="step.body"></p>
                <div class="mt-5 flex items-center gap-1.5">
                    <template x-for="(s, n) in steps" :key="n"><span class="h-1.5 rounded-full transition-all" :class="n === i ? 'w-6 bg-ochre' : 'w-1.5 bg-hairline'"></span></template>
                    <div class="ml-auto flex gap-2">
                        <button type="button" x-show="i > 0" @click="back()" class="btn-line btn-sm">Back</button>
                        <button type="button" @click="next()" class="btn-dark btn-sm" x-text="i === steps.length - 1 ? (step.cta || 'Lekker, let\'s go') : 'Next'"></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
