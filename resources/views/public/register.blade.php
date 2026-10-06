<x-public-shell title="Activate · Herd Manager" image="flock-bakkie" headline="Let's get your<br>herd in.">
    <h1 class="h-display text-6xl">Activate your device</h1>
    <p class="text-stone mt-3">Create your account with the code from the card in your device's box.</p>

    <div class="mt-10">@include('partials.flash')</div>

    <form x-data="{ show: false }" method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div><label class="field-label" for="code">Activation code</label><input id="code" name="code" value="{{ old('code', request('code')) }}" required placeholder="FT-XXXX-XXXX-XXXX" class="field font-num uppercase tracking-wider"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="name">Your name</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field"></div>
            <div><label class="field-label" for="farm_name">Farm / stud</label><input id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="field"></div>
        </div>
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="field"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="password">Password</label><input id="password" :type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="new-password" class="field"></div>
            <div><label class="field-label" for="password_confirmation">Again</label><input id="password_confirmation" :type="show ? 'text' : 'password'" type="password" name="password_confirmation" required autocomplete="new-password" class="field"></div>
        </div>
        <label class="flex gap-3 text-sm text-stone"><input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-hairline text-char" @checked(old('terms'))> <span>I agree to the <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="underline text-char">terms of use</a> and <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="underline text-char">privacy policy</a>.</span></label>
        <label class="flex items-center gap-2 text-sm text-stone"><input type="checkbox" x-model="show" class="rounded border-hairline"> Show password</label>
        <button class="btn-dark w-full">Create account &amp; unlock</button>
    </form>
    <p class="mt-8 text-sm text-stone">Already have an account? <a href="{{ route('login') }}" class="text-char font-medium link-u">Sign in</a></p>
</x-public-shell>
