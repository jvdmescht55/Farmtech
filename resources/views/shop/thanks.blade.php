@extends('layouts.site')
@php
    use App\Support\SiteImages as Img;
    $reservation = $order->kind === 'reservation';
    $bank = config('shop.bank');
@endphp
@section('title', ($reservation ? 'Reserved' : 'Order placed').' — Farmtech')
@section('hero_dark', '1')
@push('head')<meta name="robots" content="noindex">@endpush
@section('content')
<section class="relative overflow-hidden bg-char text-sand">
    <img src="{{ Img::url('windpomp-pink', true) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-50 img-grade">
    <div class="absolute inset-0 bg-gradient-to-t from-char via-char/60 to-char/30"></div>
    <div class="relative wrap pt-40 pb-16">
        <div class="w-14 h-14 rounded-full bg-[#3F7A3A] grid place-items-center text-2xl">✓</div>
        <h1 class="h-display mt-6 text-[clamp(3rem,8vw,6.5rem)]">{{ $reservation ? 'Reserved. Baie dankie!' : 'Order placed. Baie dankie!' }}</h1>
        <p class="mt-4 text-lg text-sand/75">Your number is <strong class="font-num text-sand">{{ $order->order_number }}</strong>. Keep it handy.</p>
    </div>
</section>
<section class="wrap py-16 grid lg:grid-cols-[1fr_24rem] gap-10 items-start">
    <div class="space-y-6">
        <div class="rounded-[28px] bg-white border border-hairline p-7 sm:p-9">
            <h2 class="font-headline text-3xl">What happens next</h2>
            <ol class="mt-6 space-y-5">
                @foreach ($reservation
                    ? ['We phone or email you within one working day to say hello and confirm your spot.', 'When your batch is ready, we confirm the final price and delivery date.', 'You pay then, by EFT or card. Changed your mind before that? Just tell us.']
                    : ['We check your order and send an invoice'.($order->delivery_method === 'courier' ? ' with the courier cost' : '').' within one working day.', $order->payment_method === 'card' ? 'Your card payment is confirmed by PayFast.' : 'Pay by EFT using your order number '.$order->order_number.' as the reference.', 'We pack, test and send it'.($order->delivery_method === 'collect' ? ', or arrange your collection' : '').'. Your tracking number comes by SMS or email.']
                    as $i => $t)
                    <li class="flex gap-4"><span class="font-headline text-2xl text-ochre w-6 shrink-0">{{ $i + 1 }}</span><span class="pt-1">{{ $t }}</span></li>
                @endforeach
            </ol>
            @if (! $reservation && $order->payment_method === 'eft' && $bank['account_number'])
                <div class="mt-8 rounded-2xl bg-sand-light p-5 text-sm grid grid-cols-[9rem_1fr] gap-y-1.5">
                    <span class="text-stone">Bank</span><span>{{ $bank['name'] }}</span>
                    <span class="text-stone">Account name</span><span>{{ $bank['account_name'] }}</span>
                    <span class="text-stone">Account number</span><span class="font-num">{{ $bank['account_number'] }}</span>
                    <span class="text-stone">Branch code</span><span class="font-num">{{ $bank['branch_code'] }}</span>
                    <span class="text-stone">Reference</span><span class="font-num font-medium">{{ $order->order_number }}</span>
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('site.store') }}" class="btn-line">Back to the store</a>
            <a href="{{ route('landing') }}" class="btn-dark">Set up Herd Manager</a>
            @php($wa = preg_replace('/[^0-9]/', '', (string) \App\Models\Setting::get('support_whatsapp', '')))
            @if ($wa)<a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hi Farmtech, about my order '.$order->order_number) }}" target="_blank" rel="noopener" class="btn-line">WhatsApp us about it</a>@endif
        </div>
    </div>
    <aside class="rounded-[28px] bg-white border border-hairline p-7">
        <div class="font-headline text-2xl">{{ $reservation ? 'Your reservation' : 'Your order' }}</div>
        <ul class="mt-5 divide-y divide-hairline text-sm">
            @foreach ($order->items as $it)
                <li class="py-3 flex justify-between gap-3"><span>{{ $it->quantity }} × {{ $it->title_snapshot }}@if ($it->is_reservation)<span class="block text-xs text-ochre-dark">Reserved · we contact you when it's ready</span>@endif</span><span class="font-num">{{ (float) $it->line_total_zar ? 'R'.number_format($it->line_total_zar, 0, '.', ' ') : 'TBC' }}</span></li>
            @endforeach
        </ul>
        @if ((float) $order->total_zar)<div class="mt-4 pt-4 border-t border-hairline flex justify-between"><span>To pay</span><span class="font-headline text-2xl">R{{ number_format($order->total_zar, 2, '.', ' ') }}</span></div>@endif
        <p class="mt-4 text-xs text-stone">{{ $order->delivery_method === 'collect' ? 'Collection' : 'Courier' }} · {{ $order->email }}</p>
    </aside>
</section>
@endsection
