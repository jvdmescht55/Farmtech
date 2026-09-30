<x-public-shell title="Sign in — Farmtech RFID">
    <h2 class="font-display text-3xl font-semibold tracking-tight">Sign in</h2>
    <p class="text-ink-secondary mt-2">Open your herd register and reader sync.</p>

    <div class="mt-8">@include('partials.flash')</div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label class="app-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="app-input py-2.5">
        </div>
        <div>
            <label class="app-label" for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="app-input py-2.5">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-secondary">
            <input type="checkbox" name="remember" class="rounded border-border text-brand-900"> Keep me signed in
        </label>
        <button class="btn-primary w-full py-3 text-base">Sign in</button>
    </form>

    <div class="mt-10 rounded-xl border border-border bg-white p-5">
        <div class="font-semibold">Bought a Farmtech reader?</div>
        <p class="text-sm text-ink-secondary mt-1">Your activation code is on the card in the box. Use it to create your account — the Herd Manager unlocks straight away.</p>
        <a href="{{ route('register') }}" class="btn-secondary mt-4">Activate my reader →</a>
    </div>
</x-public-shell>
