<x-public-shell title="Activate a device · Herd Manager" image="tafelberg" headline="Another device?<br>Lekker.">
    <h1 class="h-display text-6xl">Activate a device</h1>
    <p class="text-stone mt-3">Signed in as {{ auth()->user()->email }}. Type the code from the card in your device's box.</p>
    <p class="text-sm text-stone mt-2 rounded-xl bg-sand-light px-4 py-3">Two codes, two jobs: <strong class="text-char">this card code</strong> adds the device to your account (once). The <strong class="text-char">6 numbers on the scanner's screen</strong> link the scanner itself, on the <a href="{{ route('pair') }}" class="underline">Pair</a> page next.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form method="POST" action="{{ route('account.activate') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="code">Activation code</label><input id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="FT-XXXX-XXXX-XXXX" class="field font-num uppercase tracking-wider"></div>
        <button class="btn-dark w-full">Unlock</button>
    </form>
    <div class="mt-8 flex justify-between text-sm">
        <a href="{{ route('herd.hub') }}" class="text-stone hover:text-char">← All devices</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-stone hover:text-char">Sign out</button></form>
    </div>
</x-public-shell>
