<x-public-shell title="Teken in — Kuddebestuur">
    <h1 class="h-display text-6xl">Teken in</h1>
    <p class="text-stone mt-3">Jou kudde wag. Kom ons kyk hoe gaan dit.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="email">E-pos</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field"></div>
        <div><label class="field-label" for="password">Wagwoord</label><input id="password" type="password" name="password" required autocomplete="current-password" class="field"></div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2.5 text-sm text-stone"><input type="checkbox" name="remember" class="rounded border-hairline text-char focus:ring-char/10"> Hou my ingeteken</label>
            <a href="{{ route('password.request') }}" class="text-sm text-stone hover:text-char">Wagwoord vergeet?</a>
        </div>
        <button class="btn-dark w-full">Teken in</button>
    </form>

    <div class="mt-12 pt-8 border-t border-hairline">
        <div class="font-medium">Nuwe skandeerder in die hande?</div>
        <p class="text-sm text-stone mt-1">Die aktiveringskode is in die boks. Gebruik dit om jou rekening te skep.</p>
        <a href="{{ route('register') }}" class="btn-line btn-sm mt-5">Aktiveer my leser →</a>
    </div>
</x-public-shell>
