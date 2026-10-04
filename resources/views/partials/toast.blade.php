{{-- Success messages slide in, then get out of the way. Errors stay on the page. --}}
@if (session('status'))
    <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 80); setTimeout(() => show = false, 6500); @if (session('confetti')) setTimeout(() => window.farmtechConfetti(), 250); @endif"
         x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed z-[60] left-4 right-4 sm:left-auto sm:right-6 bottom-6 sm:w-[420px]" style="margin-bottom: env(safe-area-inset-bottom, 0px)" x-cloak>
        <div class="rounded-2xl bg-char text-sand shadow-[0_24px_60px_-20px_rgba(0,0,0,.55)] px-5 py-4 flex gap-4 items-start">
            <span class="mt-0.5 w-7 h-7 shrink-0 rounded-full bg-[#3F7A3A] grid place-items-center"><svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5L20 7" stroke-dasharray="24" stroke-dashoffset="24" style="animation: draw .5s .25s ease-out forwards"/></svg></span>
            <p class="text-[15px] leading-snug flex-1">{{ session('status') }}</p>
            <button type="button" @click="show = false" class="text-sand/50 hover:text-sand text-lg leading-none">×</button>
        </div>
    </div>
@endif
@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-[#B0452F]/25 bg-[#B0452F]/5 px-5 py-4 text-sm text-[#B0452F]">
        <div class="font-medium mb-1">Eish. Something needs fixing:</div>
        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif
