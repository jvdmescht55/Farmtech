@extends('layouts.admin')

@section('heading', 'RFID Licences & Customers')

@section('content')
    @if (session('status'))
        <div class="mb-6 bg-green-50 border border-green-300 text-green-900 rounded-xl px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-300 text-red-800 rounded-xl px-4 py-3 text-sm">{{ $errors->first() }}</div>
    @endif

    @if ($newCodes)
        <div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-xl p-5">
            <p class="font-semibold text-yellow-900 mb-2">New activation codes — print these on the card that ships with each reader</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">
                @foreach ($newCodes as $code)
                    <code class="block bg-white border border-yellow-300 rounded px-3 py-2 font-mono text-sm select-all">{{ $code }}</code>
                @endforeach
            </div>
            <p class="text-xs text-yellow-800 mt-3">Customers redeem them at <span class="font-mono">{{ route('register') }}</span>. Each code works once.</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center gap-2 text-sm">
                    <span class="font-semibold mr-auto">Activation codes</span>
                    @foreach (['' => 'All', 'unused' => 'Unused', 'active' => 'Activated', 'revoked' => 'Revoked'] as $k => $l)
                        <a href="{{ route('admin.licenses.index', array_filter(['filter' => $k])) }}" class="px-2.5 py-1 rounded {{ request('filter', '') === $k ? 'bg-farmtech-green text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $l }}</a>
                    @endforeach
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                            <tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Device</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($licenses as $l)
                                <tr>
                                    <td class="px-4 py-3 font-mono whitespace-nowrap">{{ $l->code }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $l->device_model ?? '—' }}<div class="text-xs font-mono text-gray-400">{{ $l->device_serial }}</div></td>
                                    <td class="px-4 py-3">{{ $l->user?->name ?? '—' }}<div class="text-xs text-gray-400">{{ $l->user?->email }}</div></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($l->revoked_at)<x-badge color="red">Revoked</x-badge>
                                        @elseif ($l->user_id)<x-badge color="green">Active</x-badge> <span class="text-xs text-gray-400">{{ $l->activated_at?->format('d/m/Y') }}</span>
                                        @else<x-badge color="gray">Unused</x-badge>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($l->revoked_at)
                                            <form method="POST" action="{{ route('admin.licenses.restore', $l) }}">@csrf<button class="text-sm font-medium text-farmtech-green">Restore</button></form>
                                        @else
                                            <form method="POST" action="{{ route('admin.licenses.revoke', $l) }}" onsubmit="return confirm('Revoke {{ $l->code }}? The customer loses access to the software.');">@csrf<button class="text-sm font-medium text-red-600">Revoke</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No codes yet — generate some on the right.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $licenses->links() }}

            <div class="bg-white border rounded-xl overflow-hidden">
                <div class="px-4 py-3 border-b font-semibold text-sm">Customers ({{ $customers->count() }})</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                            <tr><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Farm</th><th class="px-4 py-3 text-right">Licences</th><th class="px-4 py-3 text-right">Readers</th><th class="px-4 py-3 text-right">Animals</th><th class="px-4 py-3">Joined</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($customers as $c)
                                <tr>
                                    <td class="px-4 py-3">{{ $c->name }}<div class="text-xs text-gray-400">{{ $c->email }}</div></td>
                                    <td class="px-4 py-3 text-gray-600">{{ $c->farm_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $c->licenses_count }}</td>
                                    <td class="px-4 py-3 text-right">{{ $c->readers_count }}</td>
                                    <td class="px-4 py-3 text-right">{{ $c->animals_count }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $c->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No customers have activated a reader yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-5 h-fit">
            <h2 class="font-semibold mb-1">Generate activation codes</h2>
            <p class="text-xs text-gray-500 mb-4">One per reader sold. Paste serial numbers to create one code per serial, or just set a quantity.</p>
            <form method="POST" action="{{ route('admin.licenses.store') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block font-medium mb-1">Software</label>
                    <select name="module" class="w-full border rounded-md px-3 py-2">@foreach (\App\Models\License::modules() as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Reader model</label>
                    <input name="device_model" value="{{ old('device_model') }}" placeholder="e.g. FT-R200 stick reader" class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Serial numbers <span class="text-gray-400 font-normal">(optional, one per line)</span></label>
                    <textarea name="device_serials" rows="4" class="w-full border rounded-md px-3 py-2 font-mono text-xs">{{ old('device_serials') }}</textarea>
                </div>
                <div>
                    <label class="block font-medium mb-1">Quantity <span class="text-gray-400 font-normal">(ignored if serials given)</span></label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="200" class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Note</label>
                    <input name="notes" value="{{ old('notes') }}" placeholder="Batch / invoice / customer" class="w-full border rounded-md px-3 py-2">
                </div>
                <button class="w-full bg-farmtech-green text-white font-semibold px-4 py-2 rounded-md hover:bg-farmtech-green-dark">Generate</button>
            </form>
        </div>
    </div>
@endsection
