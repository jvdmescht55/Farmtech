{{-- Order summary box. Needs $lines, $due, $reserved, $courier; $cta = show checkout button --}}
<aside class="rounded-[28px] bg-char text-sand p-7 lg:sticky lg:top-28">
    <div class="font-headline text-3xl">Summary</div>
    <dl class="mt-6 space-y-3 text-[15px]">
        @if ($due)
            <div class="flex justify-between"><dt class="text-sand/70">Items</dt><dd>{{ \App\Models\StoreListing::rand($due) }}</dd></div>
            <div class="flex justify-between"><dt class="text-sand/70">Courier</dt><dd>{{ $courier === null ? 'Confirmed with your order' : ($courier ? \App\Models\StoreListing::rand($courier) : 'Free') }}</dd></div>
            <div class="flex justify-between border-t border-white/10 pt-3"><dt>To pay now</dt><dd class="font-headline text-3xl">{{ \App\Models\StoreListing::rand($due + ($courier ?? 0)) }}</dd></div>
        @endif
        @if ($lines->where('reserve', true)->isNotEmpty())
            <div class="rounded-xl bg-white/10 border border-white/10 p-4 text-sm">
                <div class="font-medium">Reserved for the next batch{{ $reserved ? ': '.\App\Models\StoreListing::rand($reserved) : '' }}</div>
                <p class="mt-1 text-sand/65">Nothing to pay now. We contact you when your batch is ready, confirm the price and delivery, and you pay then. Cancel any time before.</p>
            </div>
        @endif
    </dl>
    @if ($cta ?? false)<a href="{{ route('shop.checkout') }}" class="btn-light w-full mt-7">{{ $due ? 'Checkout' : 'Confirm my reservation' }}</a>@endif
    <ul class="mt-6 space-y-1.5 text-[13px] text-sand/55">
        <li>7 days to change your mind after delivery</li>
        <li>6-month warranty on devices</li>
        <li>Prices include VAT</li>
    </ul>
</aside>
