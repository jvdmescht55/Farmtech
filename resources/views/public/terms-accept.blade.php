<x-public-shell title="Updated terms · Herd Manager" image="karoo-mist" headline="We've updated<br>our terms.">
    <h1 class="h-display text-5xl">Our terms have changed</h1>
    <p class="text-stone mt-3">Please read the main points and accept to keep using Herd Manager. Updated {{ config('legal.updated') }}.</p>

    <ul class="mt-6 space-y-3 text-[15px]">
        @foreach ([
            'New: Herd Manager is free for the first '.config('billing.trial_days').' days from your first device, then '.\App\Services\Billing::rand(\App\Services\Billing::price('monthly')).' a month or '.\App\Services\Billing::rand(\App\Services\Billing::price('yearly')).' a year for the whole farm. No contract, no debit order.',
            'If you stop paying, nothing is deleted. After '.config('billing.grace_days').' days it turns read-only: you can still see and download everything.',
            'We may change, pause or stop farmtech.site, Herd Manager or any part of it. Where we reasonably can, we give at least 30 days\' notice so you can export your records.',
            'If the service stops, your devices keep storing reads offline and you can still copy them off over USB.',
            'Your records stay yours. Export everything any time under Import & export.',
            'Alerts, market prices and the auction calculator are guides, not veterinary or financial advice.',
            'Reservations cost nothing and can be cancelled before you pay.',
        ] as $t)
            <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 shrink-0 rounded-full bg-ochre"></span><span>{{ $t }}</span></li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('terms.accept.store') }}" class="mt-8 space-y-5">
        @csrf
        <label class="flex gap-3 text-sm">
            <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-hairline">
            <span>I have read and accept the <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="underline">terms of use</a>, <a href="{{ route('legal.show', 'sale') }}" target="_blank" class="underline">terms of sale</a> and <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="underline">privacy policy</a>.</span>
        </label>
        @error('terms')<p class="text-sm text-[#B0452F]">{{ $message }}</p>@enderror
        <button class="btn-dark w-full">Accept and continue</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-sm text-stone underline">Not now, sign me out</button></form>
</x-public-shell>
