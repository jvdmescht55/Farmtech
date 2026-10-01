<x-public-shell title="Admin — Farmtech" image="tafelberg" headline="Behind the<br><em class='text-ochre-light'>scenes.</em>">
    <p class="eyebrow">Farmtech admin</p>
    <h1 class="h-display text-6xl mt-3">Sign in</h1>
    <div class="mt-10">@include('partials.flash')</div>
    <form action="{{ route('admin.login') }}" method="POST" class="space-y-5">
        @csrf
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="field"></div>
        <div><label class="field-label" for="password">Password</label><input id="password" type="password" name="password" required class="field"></div>
        <label class="flex items-center gap-2.5 text-sm text-stone"><input type="checkbox" name="remember" class="rounded border-hairline text-char"> Keep me signed in</label>
        <button class="btn-dark w-full">Sign in</button>
    </form>
</x-public-shell>
