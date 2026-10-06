@extends('layouts.herd')
@section('title', 'Pair your device')
@section('eyebrow')Type the 6 numbers on the device's screen. That's it. @endsection
@section('photo', 'tagged-ewe')

@section('content')
<div class="grid lg:grid-cols-[1fr_22rem] gap-6 items-start"
     x-data="{
        code: @js($code), step: 'type', error: '', device: '',
        get clean() { return this.code.replace(/\D/g, '').slice(0, 6); },
        async send() {
            if (this.step !== 'type') return;
            if (this.clean.length !== 6) { this.error = 'The code has 6 numbers.'; return; }
            this.error = ''; this.step = 'sending';
            const r = await fetch(@js(route('pair.claim')), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ code: this.clean }) });
            const j = await r.json().catch(() => ({}));
            if (!r.ok || !j.ok) { this.step = 'type'; this.error = j.error || 'That didn\'t work. Check the code and try again.'; return; }
            this.device = j.device; this.step = 'waiting'; this.poll(0);
        },
        async poll(n) {
            const r = await fetch(@js(url('/pair/check')) + '/' + this.clean, { headers: { Accept: 'application/json' } }).catch(() => null);
            const j = r && r.ok ? await r.json() : {};
            if (j.delivered) { this.step = 'done'; window.farmtechConfetti?.(); return; }
            if (n < 40) setTimeout(() => this.poll(n + 1), 3000); else this.step = 'slow';
        },
     }">
    <div class="panel p-7 sm:p-10">
        {{-- 1. Type the code --}}
        <form x-show="step === 'type' || step === 'sending'" @submit.prevent="send()" data-no-busy>
            <label for="pair-code" class="font-headline text-3xl">The code on the screen</label>
            <input id="pair-code" x-model="code" x-init="$el.focus()" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="482 913"
                   class="mt-5 w-full h-20 sm:h-24 rounded-2xl border-2 border-hairline focus:border-char focus:outline-none bg-white text-center font-num text-4xl sm:text-5xl tracking-[0.3em]"
                   @input="if (clean.length === 6) send()">
            <p x-show="error" x-cloak class="mt-3 text-[#B0452F]" x-text="error"></p>
            <button class="btn-dark w-full mt-5 !h-14 text-base" :disabled="step === 'sending'" x-text="step === 'sending' ? 'Linking…' : 'Link it to my farm'"></button>
            <p class="mt-4 text-sm text-stone">No code on the screen? Switch the device off and on. If it says <em>Needs Wi-Fi</em>, set up its Wi-Fi first (step 2 on the right).</p>
        </form>

        {{-- 2. Waiting for the device to pick up its key --}}
        <div x-show="step === 'waiting' || step === 'slow'" x-cloak class="py-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-full border-4 border-sand-deep border-t-ochre animate-spin"></div>
            <div class="font-headline text-3xl mt-6">Linked. Waiting for the device…</div>
            <p class="text-stone mt-2">It checks in every few seconds. Its screen will say <strong>Paired! Lekker.</strong></p>
            <p x-show="step === 'slow'" class="mt-4 text-sm text-ochre-dark">Taking a while? Make sure it still has Wi-Fi. It will finish by itself once it's back online.</p>
        </div>

        {{-- 3. Done --}}
        <div x-show="step === 'done'" x-cloak class="py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-[#3F7A3A] text-white grid place-items-center text-3xl">✓</div>
            <div class="font-headline text-4xl mt-6">Connected. Lekker!</div>
            <p class="text-stone mt-2"><span x-text="device"></span> now sends everything straight to your Herd Manager.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('rfid.live') }}" class="btn-dark">Scan your first tag →</a>
                <a href="{{ route('herd.hub') }}" class="btn-line">Back to my devices</a>
            </div>
        </div>
    </div>

    <aside class="panel p-6 sm:p-7">
        <div class="font-medium">Setting up a new device</div>
        <ol class="mt-4 space-y-4 text-sm">
            <li class="flex gap-3"><span class="w-6 h-6 shrink-0 rounded-full bg-char text-sand grid place-items-center text-xs">1</span><span><strong>Switch it on.</strong> Plug in the power bank.</span></li>
            <li class="flex gap-3"><span class="w-6 h-6 shrink-0 rounded-full bg-char text-sand grid place-items-center text-xs">2</span><span><strong>Give it Wi-Fi from your phone.</strong> On your phone, join the Wi-Fi called <em>KraalTrac-…</em>. A page opens: choose your farm Wi-Fi or phone hotspot, type the password, Save. (Later: press <strong>D</strong> then <strong>A</strong> on the device to add another network.)</span></li>
            <li class="flex gap-3"><span class="w-6 h-6 shrink-0 rounded-full bg-ochre text-char grid place-items-center text-xs">3</span><span><strong>Type the code here.</strong> The screen says <em>Go to farmtech.site/pair</em> with 6 numbers. (No signal right now? Press <strong>*</strong> on the device to weigh offline and pair later.)</span></li>
            <li class="flex gap-3"><span class="w-6 h-6 shrink-0 rounded-full bg-char text-sand grid place-items-center text-xs">4</span><span><strong>Scan a tag.</strong> It pops up in Live view within seconds.</span></li>
        </ol>
        <div class="mt-6 pt-5 border-t border-hairline text-sm text-stone">
            Built your own scale? <a href="{{ route('firmware.install') }}" class="underline text-char">Install the KraalTrac software from your browser</a>. No Arduino needed.
        </div>
    </aside>
</div>
@endsection
