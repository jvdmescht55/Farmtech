<x-public-shell title="Sign in · Herd Manager">
    <h1 class="h-display text-6xl">Sign in</h1>
    <p class="text-stone mt-3">Your herd's waiting. Let's see how they're doing.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field"></div>
        <div><label class="field-label" for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password" class="field"></div>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2.5 text-sm text-stone"><input type="checkbox" name="remember" class="rounded border-hairline text-char focus:ring-char/10"> Keep me signed in</label>
            <a href="{{ route('password.request') }}" class="text-sm text-stone hover:text-char">Forgot password?</a>
        </div>
        <button class="btn-dark w-full">Sign in</button>
    </form>

    <div class="mt-12 pt-8 border-t border-hairline">
        <div class="font-medium">New KraalTrac in your hands?</div>
        <p class="text-sm text-stone mt-1">The activation card is in the box. Use it to create your account. Takes a minute.</p>
        <a href="{{ route('register') }}" class="btn-line btn-sm mt-5">Activate my device →</a>
    </div>
</x-public-shell>
