@extends('layouts.rfid')
@section('title', 'Sync the scale')
@section('eyebrow')Get the weights off the KraalTrac Pro @endsection
@section('photo', 'tagged-ewe')

@section('content')
<div class="grid lg:grid-cols-5 gap-6">
    {{-- 1. Plug in (the main way when there's no Wi-Fi) --}}
    <div class="lg:col-span-3 panel overflow-hidden self-start" x-data="scaleSync(@js(route('rfid.data.usb')))">
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 rounded-2xl bg-char text-sand grid place-items-center">@include('partials.icon', ['name' => 'usb', 'class' => 'w-5 h-5'])</span>
                <div>
                    <h2 class="font-headline text-3xl leading-none">Plug in &amp; sync</h2>
                    <p class="text-sm text-stone mt-1">One click. The scale's memory clears itself once everything is safe.</p>
                </div>
            </div>

            <template x-if="supported">
                <div class="mt-7">
                    <ol class="grid sm:grid-cols-3 gap-3 text-sm">
                        @foreach (['Plug the scale into this computer with its USB cable.', 'Click the button and pick the scale (often “USB Serial” or “CP210x”).', 'Wait for the tick. Done — the scale is empty and ready.'] as $i => $t)
                            <li class="rounded-2xl bg-sand-light p-4 flex sm:block items-start gap-3"><span class="font-headline text-2xl leading-none text-ochre">{{ $i + 1 }}</span><p class="sm:mt-1 text-stone">{{ $t }}</p></li>
                        @endforeach
                    </ol>

                    <button type="button" @click="run()" :disabled="busy" class="btn-dark mt-6 w-full sm:w-auto disabled:opacity-60">
                        <span x-show="!busy">Connect the scale</span>
                        <span x-show="busy" x-cloak class="inline-flex items-center gap-2"><span class="w-4 h-4 rounded-full border-2 border-sand/30 border-t-sand animate-spin"></span>Working…</span>
                    </button>

                    {{-- Progress --}}
                    <div x-show="step !== 'idle'" x-cloak class="mt-6 space-y-2 text-sm">
                        <template x-for="[key, label] in [['connecting','Talking to the scale'],['reading','Reading its memory'],['sending','Saving to Herd Manager'],['clearing','Clearing the scale']]" :key="key">
                            <div class="flex items-center gap-3" :class="['connecting','reading','sending','clearing','done'].indexOf(step) >= ['connecting','reading','sending','clearing'].indexOf(key) || step === 'done' ? 'text-char' : 'text-stone/50'">
                                <span class="w-5 h-5 rounded-full grid place-items-center text-[11px]"
                                      :class="step === key ? 'border-2 border-ochre border-t-transparent animate-spin' : ((['connecting','reading','sending','clearing','done'].indexOf(step) > ['connecting','reading','sending','clearing'].indexOf(key)) ? 'bg-[#3F7A3A] text-white' : 'border border-hairline')">
                                    <span x-show="step !== key && ['connecting','reading','sending','clearing','done'].indexOf(step) > ['connecting','reading','sending','clearing'].indexOf(key)">✓</span>
                                </span>
                                <span x-text="label"></span>
                            </div>
                        </template>
                    </div>

                    <div x-show="step === 'done'" x-cloak class="mt-6 rounded-2xl bg-[#3F7A3A]/10 border border-[#3F7A3A]/30 p-5">
                        <div class="font-headline text-3xl">Lekker — all in.</div>
                        <p class="text-sm mt-1"><span x-text="saved"></span> new records saved<span x-show="dupes">, <span x-text="dupes"></span> were already here</span>. The scale's memory is cleared.</p>
                        <a href="{{ route('rfid.weighings.index') }}" class="btn-dark btn-sm mt-4">See the weigh day →</a>
                    </div>
                    <div x-show="step === 'empty'" x-cloak class="mt-6 rounded-2xl bg-sand-light p-5">
                        <div class="font-headline text-2xl">Nothing waiting — all caught up.</div>
                        <p class="text-sm text-stone mt-1">The scale already sent everything (probably over Wi-Fi).</p>
                    </div>
                    <div x-show="step === 'error'" x-cloak class="mt-6 rounded-2xl bg-[#B0452F]/10 border border-[#B0452F]/30 p-5">
                        <div class="font-medium">Eish, that didn't work.</div>
                        <p class="text-sm mt-1" x-text="message"></p>
                    </div>
                </div>
            </template>

            <template x-if="!supported">
                <div class="mt-7 rounded-2xl bg-sand-light p-5 text-sm">
                    <div class="font-medium">This browser can't talk to USB devices.</div>
                    <p class="text-stone mt-1">Open this page in <strong>Chrome</strong> or <strong>Edge</strong> on a laptop or desktop (iPhone and Safari can't do USB). Or use one of the other two ways on the right.</p>
                </div>
            </template>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        {{-- 2. Wi-Fi --}}
        <div class="panel p-6 sm:p-7">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-ochre text-char grid place-items-center">@include('partials.icon', ['name' => 'wifi', 'class' => 'w-5 h-5'])</span>
                <h3 class="font-headline text-2xl">Wi-Fi — automatic</h3>
            </div>
            <p class="text-sm text-stone mt-3">In range of a saved network, the scale sends every record by itself within a minute and clears it once the website says “saved”.</p>
            <p class="text-sm mt-3 rounded-xl bg-sand-light p-3"><strong>Tip:</strong> no Wi-Fi at the kraal? Add your phone's hotspot to the scale's networks — it syncs through your phone's data while you work.</p>
            <ul class="mt-4 divide-y divide-hairline text-sm">
                @forelse ($scales as $s)
                    @php($fresh = $s->last_synced_at && $s->last_synced_at->gt(now()->subMinutes(10)))
                    <li class="py-2.5 flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full {{ $fresh ? 'bg-[#3F7A3A]' : 'bg-stone-light/60' }}"></span>
                        <span class="flex-1 truncate">{{ $s->name ?: $s->serial }}</span>
                        <span class="text-xs text-stone">{{ $s->last_synced_at ? 'seen '.$s->last_synced_at->diffForHumans() : 'never synced' }}</span>
                    </li>
                @empty
                    <li class="py-2.5 text-stone">No scale paired yet. <a class="underline" href="{{ route('rfid.readers.index') }}">Pair it</a> — it takes a minute.</li>
                @endforelse
            </ul>
        </div>

        {{-- 3. Paste --}}
        <a href="{{ route('rfid.data') }}#paste" class="panel p-6 sm:p-7 block hover:border-char transition">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-sand-deep grid place-items-center">@include('partials.icon', ['name' => 'note', 'class' => 'w-5 h-5'])</span>
                <h3 class="font-headline text-2xl">Copy &amp; paste</h3>
            </div>
            <p class="text-sm text-stone mt-3">Last resort: with the Arduino Serial Monitor open, press <strong>B</strong> on the scale, copy what it prints and paste it in Import &amp; export. Duplicates are skipped. →</p>
        </a>

        @if ($recent->isNotEmpty())
            <div class="panel p-6 sm:p-7">
                <h3 class="eyebrow">Last few syncs</h3>
                <ul class="mt-3 divide-y divide-hairline text-sm">
                    @foreach ($recent as $r)
                        <li class="py-2.5 flex justify-between gap-3"><span class="truncate">{{ $r->filename ?: ['api' => 'Over Wi-Fi', 'paste' => 'Pasted', 'csv' => 'File upload'][$r->source] ?? ucfirst($r->source) }}</span><span class="text-stone shrink-0">{{ $r->scan_count }} · {{ $r->created_at->diffForHumans() }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
