<x-public-shell title="Forgot password · Herd Manager" image="karoo-mist" headline="Happens to<br>the best of us.">
    <h1 class="h-display text-6xl">Forgot your password?</h1>
    <p class="text-stone mt-3">Type your email and we'll send a link to choose a new one.</p>
    <div class="mt-10">@include('partials.flash')</div>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field"></div>
        <button class="btn-dark w-full">Send the link</button>
    </form>
    <p class="mt-8 text-sm text-stone">No email? <a href="{{ route('site.contact') }}" class="text-char link-u">Give us a shout</a> and we'll sort you out.</p>
    <p class="mt-2 text-sm"><a href="{{ route('login') }}" class="text-stone hover:text-char">← Back to sign in</a></p>
</x-public-shell>
