<x-public-shell title="Wagwoord vergeet — Kuddebestuur" image="karoo-mist" headline="Gebeur met<br><em class='text-ochre-light'>die beste.</em>">
    <h1 class="h-display text-6xl">Wagwoord vergeet?</h1>
    <p class="text-stone mt-3">Tik jou e-pos in. Ons stuur 'n skakel om 'n nuwe wagwoord te kies.</p>
    <div class="mt-10">@include('partials.flash')</div>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="email">E-pos</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field"></div>
        <button class="btn-dark w-full">Stuur skakel</button>
    </form>
    <p class="mt-8 text-sm text-stone">Geen e-pos ontvang nie? <a href="{{ route('site.contact') }}" class="text-char link-u">Kontak ons</a> en ons help jou.</p>
    <p class="mt-2 text-sm"><a href="{{ route('login') }}" class="text-stone hover:text-char">← Terug na teken in</a></p>
</x-public-shell>
