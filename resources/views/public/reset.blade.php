<x-public-shell title="New password — Kuddebestuur" image="koppie-dawn" headline="New day,<br><em class='text-ochre-light'>new password.</em>">
    <h1 class="h-display text-6xl">New password</h1>
    <div class="mt-10">@include('partials.flash')</div>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="field"></div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label" for="password">New password</label><input id="password" type="password" name="password" required autocomplete="new-password" class="field"></div>
            <div><label class="field-label" for="password_confirmation">Again</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field"></div>
        </div>
        <button class="btn-dark w-full">Save</button>
    </form>
</x-public-shell>
