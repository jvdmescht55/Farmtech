<x-public-shell title="Aktiveer — Kuddebestuur" image="windpomp-storm" headline="Lekker!<br><em class='text-ochre-light'>Kom ons begin.</em>">
    <h1 class="h-display text-6xl">Aktiveer jou leser</h1>
    <p class="text-stone mt-3">Skep jou rekening met die kode wat saam met jou toestel gekom het.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="code">Aktiveringskode</label><input id="code" name="code" value="{{ old('code', request('code')) }}" required placeholder="FT-XXXX-XXXX-XXXX" class="field font-num uppercase tracking-wider"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="name">Jou naam</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field"></div>
            <div><label class="field-label" for="farm_name">Plaas / stoet</label><input id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="field"></div>
        </div>
        <div><label class="field-label" for="email">E-pos</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="field"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="password">Wagwoord</label><input id="password" type="password" name="password" required autocomplete="new-password" class="field"></div>
            <div><label class="field-label" for="password_confirmation">Weer</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field"></div>
        </div>
        <button class="btn-dark w-full">Skep rekening &amp; ontsluit</button>
    </form>
    <p class="mt-8 text-sm text-stone">Klaar 'n rekening? <a href="{{ route('login') }}" class="text-char font-medium link-u">Teken in</a></p>
</x-public-shell>
