@extends('layouts.herd')
@php use App\Services\Billing; @endphp
@section('title', 'Plan & billing')
@section('eyebrow')One price per farm. Every device and every feature included. @endsection

@section('content')
@if (request('paid'))
    <div class="panel p-5 mb-6 bg-[#3F7A3A]/10 border-[#3F7A3A]/30">Thank you. As soon as the card payment is confirmed (usually within a minute) your access is extended. Refresh to see it.</div>
@endif
<div class="grid lg:grid-cols-5 gap-6 items-start">
    <div class="lg:col-span-3 space-y-6">
        {{-- Where you stand --}}
        <div class="panel p-6 sm:p-8">
            @php($tone = ['active' => 'bg-[#3F7A3A]', 'trial' => 'bg-ochre', 'grace' => 'bg-[#C9862B]', 'read_only' => 'bg-[#B0452F]', 'staff' => 'bg-stone'][$status['state']])
            <div class="flex items-center gap-2 text-sm text-stone"><span class="w-2.5 h-2.5 rounded-full {{ $tone }}"></span>{{ ['active' => 'Paid', 'trial' => 'Free months', 'grace' => 'Payment due', 'read_only' => 'Read-only', 'staff' => 'Staff'][$status['state']] }}</div>
            <div class="font-headline text-4xl sm:text-5xl mt-2">{{ $status['label'] }}.</div>
            @if (in_array($status['state'], ['trial', 'active'], true) && $status['days_left'] !== null)
                <p class="text-stone mt-2">{{ $status['days_left'] }} day{{ $status['days_left'] === 1 ? '' : 's' }} to go. {{ $status['state'] === 'trial' ? 'No card needed until then.' : 'Paying early just adds on. You lose no days.' }}</p>
            @elseif ($status['state'] === 'read_only')
                <p class="text-stone mt-2">You can still see, search, print and download everything, and your scanner keeps sending weights. To add or change records, pay for a month.</p>
            @elseif ($status['state'] === 'grace')
                <p class="text-stone mt-2">Everything still works for now. Pay to keep it that way.</p>
            @endif
        </div>

        @if ($sub)
            {{-- Open invoice --}}
            @if ($open)
                <div class="panel p-6 sm:p-8 ring-1 ring-ochre/40">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-stone">Invoice {{ $open->reference }} · {{ Billing::PLANS[$open->plan] }}</div>
                            <div class="font-headline text-4xl mt-1">{{ $open->rand() }}</div>
                            <div class="text-sm text-stone mt-1">Covers {{ $open->period_start?->format('j M Y') }} to {{ $open->period_end?->format('j M Y') }}</div>
                        </div>
                        <a href="{{ route('billing.invoice', $open) }}" target="_blank" class="btn-line btn-sm">Print invoice</a>
                    </div>
                    <div class="mt-5 rounded-2xl bg-sand-light p-5 text-sm">
                        <div class="font-medium mb-2">Pay by EFT</div>
                        @if (filled($bank['account_number']))
                            <dl class="grid grid-cols-[8rem_1fr] gap-y-1">
                                <dt class="text-stone">Bank</dt><dd>{{ $bank['name'] }}</dd>
                                <dt class="text-stone">Account name</dt><dd>{{ $bank['account_name'] }}</dd>
                                <dt class="text-stone">Account number</dt><dd class="num">{{ $bank['account_number'] }}</dd>
                                <dt class="text-stone">Branch code</dt><dd class="num">{{ $bank['branch_code'] }}</dd>
                                <dt class="text-stone">Reference</dt><dd class="num font-semibold">{{ $open->reference }}</dd>
                            </dl>
                        @else
                            <p>Use <strong class="num">{{ $open->reference }}</strong> as the reference. We'll email you our banking details today.</p>
                        @endif
                        <p class="text-stone mt-3">We switch it on within one working day of the money arriving.</p>
                    </div>
                </div>
            @endif

            {{-- Choose and pay --}}
            <form method="POST" action="{{ route('billing.pay') }}" class="panel p-6 sm:p-8" x-data="{ plan: @js($open->plan ?? $sub->plan ?? 'monthly'), method: 'eft' }">
                @csrf
                <h2 class="font-headline text-3xl">{{ $open ? 'Change or pay another way' : ($status['state'] === 'trial' ? 'Pay ahead (optional)' : 'Pay for the next stretch') }}</h2>
                <div class="mt-5 grid sm:grid-cols-2 gap-3">
                    @foreach (Billing::PLANS as $k => $l)
                        <label class="rounded-2xl border p-5 cursor-pointer transition" :class="plan === '{{ $k }}' ? 'border-char ring-1 ring-char' : 'border-hairline hover:border-char'">
                            <input type="radio" name="plan" value="{{ $k }}" x-model="plan" class="sr-only">
                            <div class="flex items-baseline justify-between"><span class="font-medium">{{ $l }}</span>@if ($k === 'yearly')<span class="chip bg-ochre/20 text-ochre-dark">2 months free</span>@endif</div>
                            <div class="mt-2"><span class="kpi-num text-4xl">{{ Billing::rand(Billing::price($k)) }}</span><span class="text-stone text-sm"> / {{ $k === 'yearly' ? 'year' : 'month' }}</span></div>
                            @if ($k === 'yearly')<div class="text-xs text-stone mt-1">Works out to {{ Billing::rand((int) round(Billing::price('yearly') / 12)) }} a month</div>@endif
                        </label>
                    @endforeach
                </div>
                <div class="mt-5 flex flex-wrap gap-2 text-sm">
                    <label class="chip cursor-pointer" :class="method === 'eft' ? 'bg-char text-sand' : 'bg-sand-deep'"><input type="radio" name="method" value="eft" x-model="method" class="sr-only">EFT</label>
                    @if ($cardEnabled)<label class="chip cursor-pointer" :class="method === 'card' ? 'bg-char text-sand' : 'bg-sand-deep'"><input type="radio" name="method" value="card" x-model="method" class="sr-only">Card</label>@endif
                </div>
                <button class="btn-dark mt-6" x-text="method === 'card' ? 'Pay by card' : 'Get the invoice'">Get the invoice</button>
                <p class="text-xs text-stone mt-3">No contract and no debit order. Pay when you want; stop when you want.</p>
            </form>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="panel p-6">
            <div class="panel-title mb-3">What you get</div>
            <ul class="space-y-2 text-sm">
                @foreach (['Every device on the farm: scanner, Watch, custom builds', 'Herd book, pedigrees and SP/C/B tiers', 'Weigh days, daily gain and sorting by weight', 'Alerts for weight loss, withdrawal and missed drinking', 'Auction books, exports and backups', 'Updates for your scanner, and help from a person'] as $f)
                    <li class="flex gap-2"><span class="text-[#3F7A3A]">✓</span>{{ $f }}</li>
                @endforeach
            </ul>
        </div>
        <div class="panel p-6 text-sm text-stone">
            <div class="panel-title text-char mb-2">If you stop paying</div>
            <p>Nothing is deleted. After {{ config('billing.grace_days') }} days' grace Herd Manager turns read-only: you can still look, print and download everything, and your scanner keeps working and syncing. Pay again any time and carry on where you left off.</p>
        </div>
        @if ($history->isNotEmpty())
            <div class="panel overflow-hidden">
                <div class="panel-head"><div class="panel-title">Paid</div></div>
                <ul class="divide-y divide-hairline text-sm">
                    @foreach ($history as $p)
                        <li class="px-6 py-3 flex justify-between gap-3"><a href="{{ route('billing.invoice', $p) }}" class="underline" target="_blank">{{ $p->reference }}</a><span class="text-stone">{{ $p->paid_at?->format('j M Y') }} · {{ $p->rand() }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
