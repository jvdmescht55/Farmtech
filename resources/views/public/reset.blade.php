<x-public-shell title="Nuwe wagwoord — Kuddebestuur" image="koppie-dawn" headline="Nuwe dag,<br><em class='text-ochre-light'>nuwe wagwoord.</em>">
    <h1 class="h-display text-6xl">Nuwe wagwoord</h1>
    <div class="mt-10">@include('partials.flash')</div>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><label class="field-label" for="email">E-pos</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="field"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="password">Nuwe wagwoord</label><input id="password" type="password" name="password" required autocomplete="new-password" class="field"></div>
            <div><label class="field-label" for="password_confirmation">Weer</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field"></div>
        </div>
        <button class="btn-dark w-full">Stoor &amp; teken in</button>
    </form>
</x-public-shell>
