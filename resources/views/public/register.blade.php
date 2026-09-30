<x-public-shell title="Activate your reader — Farmtech RFID">
    <h2 class="font-display text-3xl font-semibold tracking-tight">Activate your reader</h2>
    <p class="text-ink-secondary mt-2">Create your account with the code that came with your device.</p>

    <div class="mt-8">@include('partials.flash')</div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div>
            <label class="app-label" for="code">Activation code</label>
            <input id="code" name="code" value="{{ old('code', request('code')) }}" required placeholder="FT-XXXX-XXXX-XXXX" class="app-input py-2.5 font-mono uppercase tracking-wider">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="app-label" for="name">Your name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="app-input py-2.5">
            </div>
            <div>
                <label class="app-label" for="farm_name">Farm / stud name</label>
                <input id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="app-input py-2.5">
            </div>
        </div>
        <div>
            <label class="app-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="app-input py-2.5">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="app-label" for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="app-input py-2.5">
            </div>
            <div>
                <label class="app-label" for="password_confirmation">Confirm</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="app-input py-2.5">
            </div>
        </div>
        <button class="btn-primary w-full py-3 text-base">Create account &amp; unlock</button>
    </form>
    <p class="mt-6 text-sm text-ink-secondary">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-900 hover:underline">Sign in</a></p>
</x-public-shell>
