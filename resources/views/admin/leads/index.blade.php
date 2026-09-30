@extends('layouts.admin')
@section('heading', 'Leads — interest & contact')
@section('content')
<div class="grid sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase text-gray-500">Open</div><div class="text-2xl font-bold mt-1">{{ $counts['open'] }}</div></div>
    <div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase text-gray-500">Scanner interest</div><div class="text-2xl font-bold mt-1">{{ $counts['interest'] }}</div></div>
    <div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase text-gray-500">Contact messages</div><div class="text-2xl font-bold mt-1">{{ $counts['contact'] }}</div></div>
    <div class="bg-white border rounded-xl p-4 lg:col-span-2 text-xs text-gray-600">
        <div class="uppercase text-gray-500 mb-1">Interest by province</div>
        @forelse ($byProvince as $p => $n){{ $p }} ({{ $n }}){{ $loop->last ? '' : ' · ' }}@empty — @endforelse
        <div class="uppercase text-gray-500 mt-2 mb-1">By herd size</div>
        @forelse ($byHerd as $h => $n){{ $h }} ({{ $n }}){{ $loop->last ? '' : ' · ' }}@empty — @endforelse
    </div>
</div>
<div class="bg-white border rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b flex flex-wrap items-center gap-2 text-sm">
        @foreach (['' => 'All', 'interest' => 'Interest', 'contact' => 'Contact'] as $k => $l)
            <a href="{{ route('admin.leads.index', array_filter(['type' => $k, 'status' => request('status')])) }}" class="px-2.5 py-1 rounded {{ request('type', '') === $k ? 'bg-farmtech-green text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $l }}</a>
        @endforeach
        <a href="{{ route('admin.leads.index', array_filter(['type' => request('type'), 'status' => request('status') === 'open' ? null : 'open'])) }}" class="px-2.5 py-1 rounded {{ request('status') === 'open' ? 'bg-farmtech-gold text-white' : 'text-gray-600 hover:bg-gray-100' }}">Open only</a>
        <a href="{{ route('admin.leads.export') }}" class="ml-auto text-farmtech-green font-semibold">Export CSV</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs"><tr><th class="px-4 py-3">When</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Farm / herd</th><th class="px-4 py-3">Message</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y">
            @forelse ($leads as $l)
                <tr class="{{ $l->handled_at ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $l->created_at->format('d M H:i') }}</td>
                    <td class="px-4 py-3"><x-badge :color="$l->type === 'interest' ? 'green' : 'gray'">{{ $l->type }}</x-badge></td>
                    <td class="px-4 py-3 font-medium">{{ $l->name }}</td>
                    <td class="px-4 py-3"><a href="mailto:{{ $l->email }}" class="text-farmtech-green">{{ $l->email }}</a><div class="text-xs text-gray-500">{{ $l->phone }}</div></td>
                    <td class="px-4 py-3 text-gray-600">{{ $l->farm_name ?? '—' }}<div class="text-xs text-gray-400">{{ collect([$l->herd_size, $l->province])->filter()->implode(' · ') }}</div></td>
                    <td class="px-4 py-3 text-gray-600 max-w-md">{{ \Illuminate\Support\Str::limit($l->message, 160) }}</td>
                    <td class="px-4 py-3 text-right"><form method="POST" action="{{ route('admin.leads.toggle', $l) }}">@csrf<button class="text-sm font-medium {{ $l->handled_at ? 'text-gray-500' : 'text-farmtech-green' }}">{{ $l->handled_at ? 'Reopen' : 'Mark handled' }}</button></form></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No leads yet. They arrive from the store's "Bestel vroeg" form and the contact page.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $leads->links() }}</div>
@endsection
