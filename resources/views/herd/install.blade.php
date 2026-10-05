@extends('layouts.herd')
@section('title', 'Install KraalTrac software')
@section('eyebrow')Put the latest KraalTrac Pro software on an ESP32 straight from this page. No Arduino needed. @endsection

@push('head')@endpush
@section('content')
<script type="module" src="https://unpkg.com/esp-web-tools@10/dist/web/install-button.js?module"></script>
<div class="grid lg:grid-cols-[1fr_22rem] gap-6 items-start">
    <div class="panel p-7 sm:p-10">
        <div class="font-headline text-3xl">KraalTrac Pro <span class="text-stone text-xl">v3.2.0</span></div>
        <ol class="mt-6 space-y-5">
            @foreach ([
                ['Use Chrome or Edge on a laptop', 'Phones and Safari can\'t do this part.'],
                ['Plug the scale\'s ESP32 into the laptop', 'Use a data USB cable (some cables only charge).'],
                ['Click Install below and pick the port', 'Usually "CP210x" or "USB Serial". Keep it plugged in for about a minute.'],
                ['Done. It restarts and opens its Wi-Fi setup', 'Join "KraalTrac-…" on your phone, choose your Wi-Fi, then pair it at farmtech.site/pair.'],
            ] as $i => [$t, $d])
                <li class="flex gap-4"><span class="w-8 h-8 shrink-0 rounded-full bg-char text-sand grid place-items-center text-sm">{{ $i + 1 }}</span><div><div class="font-medium">{{ $t }}</div><div class="text-sm text-stone mt-0.5">{{ $d }}</div></div></li>
            @endforeach
        </ol>
        <div class="mt-8">
            <esp-web-install-button manifest="{{ asset('firmware/kraaltrac-pro/manifest.json') }}">
                <button slot="activate" class="btn-dark !h-14 px-8 text-base">Install on my scale</button>
                <span slot="unsupported" class="block rounded-2xl bg-sand-light p-4 text-sm">This browser can't talk to USB. Open this page in <strong>Chrome</strong> or <strong>Edge</strong> on a laptop or desktop.</span>
                <span slot="not-allowed" class="block rounded-2xl bg-sand-light p-4 text-sm">This page needs a secure connection (https) to install.</span>
            </esp-web-install-button>
        </div>
        <p class="mt-6 text-sm text-stone">Updating a scale that already has records on it? When it asks, choose <strong>not</strong> to erase, so the queue and its pairing stay. Records are only ever cleared once Herd Manager has them.</p>
    </div>
    <aside class="panel p-6 sm:p-7 text-sm">
        <div class="font-medium">Prefer Arduino IDE?</div>
        <p class="mt-2 text-stone">Download the source, install the <em>LiquidCrystal I2C</em>, <em>Keypad</em> and <em>ArduinoJson</em> libraries, choose board <em>ESP32 Dev Module</em> and partition scheme <em>Minimal SPIFFS</em>, then upload.</p>
        <a href="{{ route('rfid.readers.firmware') }}" class="btn-line btn-sm mt-4">↓ kraaltrac_pro.ino</a>
        <div class="mt-6 pt-5 border-t border-hairline text-stone">Already installed? <a href="{{ route('pair') }}" class="underline text-char">Pair it →</a></div>
    </aside>
</div>
@endsection
