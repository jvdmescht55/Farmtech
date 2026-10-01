@php
    $l = config('legal');
    $v = fn ($key, $hint = null) => filled($l[$key] ?? null) ? e($l[$key]) : '<mark class="bg-ochre/20 text-ochre-dark px-1 rounded">['.e($hint ?? 'to be completed').']</mark>';
@endphp
<div class="rounded-2xl border border-hairline bg-white p-6 text-[15px] leading-relaxed not-prose">
    <div class="eyebrow mb-3">Who we are</div>
    <dl class="grid sm:grid-cols-[11rem_1fr] gap-x-4 gap-y-1.5 text-stone">
        <dt>Trading name</dt><dd class="text-char">{!! $v('trading_name') !!}</dd>
        <dt>Legal entity</dt><dd class="text-char">{!! $v('legal_name', 'registered name') !!}{!! filled($l['legal_status']) ? ' · '.e($l['legal_status']) : '' !!}</dd>
        <dt>Registration no.</dt><dd class="text-char">{!! $v('registration_number', 'CIPC number, if registered') !!}</dd>
        @if (filled($l['vat_number']))<dt>VAT no.</dt><dd class="text-char">{{ $l['vat_number'] }}</dd>@endif
        <dt>Physical address</dt><dd class="text-char">{!! $v('address', 'address for legal notices') !!}</dd>
        <dt>Email</dt><dd class="text-char">{!! $v('email') !!}</dd>
        <dt>Phone</dt><dd class="text-char">{!! $v('phone', 'phone number') !!}</dd>
        <dt>Website</dt><dd class="text-char">https://farmtech.site</dd>
    </dl>
</div>
