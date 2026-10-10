<x-mail::message>
# Hi {{ $user->name }}

@if ($kind === 'ended')
Your Herd Manager access ran out, so it is now **read-only**. Nothing is deleted: you can still see, print and download every record, and your scanner keeps sending weights.

To carry on adding records, pay for a month ({{ \App\Services\Billing::rand(\App\Services\Billing::price('monthly')) }}) or a year ({{ \App\Services\Billing::rand(\App\Services\Billing::price('yearly')) }}).
@else
{{ $kind === 'trial' ? 'Your free months' : 'Your paid period' }} of Herd Manager end{{ $kind === 'trial' ? '' : 's' }} on **{{ $until }}**.

To keep everything running, pay for a month ({{ \App\Services\Billing::rand(\App\Services\Billing::price('monthly')) }}) or a year ({{ \App\Services\Billing::rand(\App\Services\Billing::price('yearly')) }}, two months free). Paying early loses no days.
@endif

<x-mail::button :url="route('billing.show')">
Plan & billing
</x-mail::button>

No contract, no debit order. Questions? Just reply to this email.

Farmtech
</x-mail::message>
