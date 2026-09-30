<x-public-shell title="Ontsluit — Kuddebestuur" image="tafelberg" headline="Nog 'n toestel?<br><em class='text-ochre-light'>Sit hom by.</em>">
    <h1 class="h-display text-6xl">Ontsluit sagteware</h1>
    <p class="text-stone mt-3">Ingeteken as {{ auth()->user()->email }}. Tik die kode uit jou toestel se boks in.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form method="POST" action="{{ route('account.activate') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="code">Aktiveringskode</label><input id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="FT-XXXX-XXXX-XXXX" class="field font-num uppercase tracking-wider"></div>
        <button class="btn-dark w-full">Ontsluit</button>
    </form>
    <div class="mt-8 flex justify-between text-sm">
        <a href="{{ route('herd.hub') }}" class="text-stone hover:text-char">← My kraal</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-stone hover:text-char">Teken uit</button></form>
    </div>
</x-public-shell>
