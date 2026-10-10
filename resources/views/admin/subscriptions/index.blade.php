@extends('layouts.admin')
@php use App\Services\Billing; @endphp
@section('heading', 'Subscriptions')
@section('content')
<div class="grid sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase text-gray-500">Monthly income</div><div class="text-2xl font-bold mt-1">{{ Billing::rand($mrr) }}</div></div>
    @foreach (['active' => 'Paying', 'trial' => 'Free months', 'grace' => 'Payment due', 'read_only' => 'Read-only'] as $k => $l)
        <div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase text-gray-500">{{ $l }}</div><div class="text-2xl font-bold mt-1">{{ $counts[$k] ?? 0 }}</div></div>
    @endforeach
</div>
<p class="text-sm text-gray-500 mb-6">Price: {{ Billing::rand(Billing::price('monthly')) }} a month or {{ Billing::rand(Billing::price('yearly')) }} a year per farm. {{ config('billing.trial_days') }} free days from the first device activation, then {{ config('billing.grace_days') }} days' grace before read-only. Change in .env (BILLING_*).</p>

<div class="bg-white border rounded-xl overflow-hidden mb-6">
    <div class="px-4 py-3 border-b font-semibold">EFTs to check ({{ $pending->count() }})</div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Farmer</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Created</th><th class="px-4 py-3"></th></tr></thead>
        <tbody class="divide-y">
        @forelse ($pending as $p)
            <tr>
                <td class="px-4 py-3 font-mono"><a href="{{ route('billing.invoice', $p) }}" target="_blank" class="text-farmtech-green">{{ $p->reference }}</a></td>
                <td class="px-4 py-3">{{ $p->user->name }}<div class="text-xs text-gray-500">{{ $p->user->farm_name }} · {{ $p->user->email }}</div></td>
                <td class="px-4 py-3">{{ Billing::PLANS[$p->plan] }}</td>
                <td class="px-4 py-3 font-semibold">{{ $p->rand() }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $p->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <form method="POST" action="{{ route('admin.subscriptions.paid', $p) }}" class="inline" onsubmit="return confirm('Money for {{ $p->reference }} is in the bank?')">@csrf<button class="font-semibold text-farmtech-green">Mark paid</button></form>
                    <form method="POST" action="{{ route('admin.subscriptions.cancel', $p) }}" class="inline ml-3">@csrf<button class="text-gray-500">Cancel</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nothing waiting. Farmers get an invoice with a SUB- reference on their Plan &amp; billing page.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white border rounded-xl overflow-hidden mb-6">
    <div class="px-4 py-3 border-b font-semibold">Farms</div>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><tr><th class="px-4 py-3">Farmer</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Access until</th><th class="px-4 py-3">Give free months</th></tr></thead>
        <tbody class="divide-y">
        @forelse ($customers as $c)
            <tr>
                <td class="px-4 py-3">{{ $c['user']->name }}<div class="text-xs text-gray-500">{{ $c['user']->farm_name }} · {{ $c['user']->email }}</div></td>
                <td class="px-4 py-3"><x-badge :color="['active' => 'green', 'trial' => 'yellow', 'grace' => 'yellow', 'read_only' => 'red'][$c['status']['state']] ?? 'gray'">{{ $c['status']['label'] }}</x-badge>@if ($c['user']->subscription?->notes)<div class="text-xs text-gray-400 mt-1 whitespace-pre-line">{{ $c['user']->subscription->notes }}</div>@endif</td>
                <td class="px-4 py-3 text-gray-600">{{ $c['status']['until']?->format('j M Y') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.subscriptions.give', $c['user']) }}" class="flex items-center gap-2">@csrf
                        <input type="number" name="months" value="1" min="1" max="24" class="w-16 border rounded px-2 py-1">
                        <input type="text" name="why" placeholder="Why (optional)" class="w-40 border rounded px-2 py-1">
                        <button class="text-farmtech-green font-semibold">Give</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No farmers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@if ($paid->isNotEmpty())
<div class="bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b font-semibold">Recent payments</div>
    <ul class="divide-y text-sm">
        @foreach ($paid as $p)
            <li class="px-4 py-3 flex justify-between gap-3"><span><a href="{{ route('billing.invoice', $p) }}" target="_blank" class="font-mono text-farmtech-green">{{ $p->reference }}</a> · {{ $p->user->name }}</span><span class="text-gray-500">{{ $p->paid_at?->format('j M Y') }} · {{ $p->method }} · {{ $p->rand() }}</span></li>
        @endforeach
    </ul>
</div>
@endif
@endsection
