<x-public-shell title="Unlock software — Farmtech RFID">
    <h2 class="font-display text-3xl font-semibold tracking-tight">Unlock the Herd Manager</h2>
    <p class="text-ink-secondary mt-2">Signed in as {{ auth()->user()->email }}. Enter the activation code from your reader's box.</p>

    <div class="mt-8">@include('partials.flash')</div>

    <form method="POST" action="{{ route('account.activate') }}" class="space-y-5">
        @csrf
        <div>
            <label class="app-label" for="code">Activation code</label>
            <input id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="FT-XXXX-XXXX-XXXX" class="app-input py-2.5 font-mono uppercase tracking-wider">
        </div>
        <button class="btn-primary w-full py-3 text-base">Unlock</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf
        <button class="text-sm text-ink-secondary hover:text-charcoal">Sign out</button>
    </form>
</x-public-shell>
