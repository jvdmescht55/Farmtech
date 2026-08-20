@extends('layouts.admin')

@section('heading', 'Orders')

@section('content')
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order # or customer…" class="border rounded-md px-3 py-2 text-sm w-56">

        <select name="status" onchange="this.form.submit()" class="border rounded-md px-3 py-2 text-sm">
            <option value="">Any Status</option>
            @foreach (\App\Enums\OrderStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? '') === $statusOption->value)>{{ $statusOption->label() }}</option>
            @endforeach
        </select>

        <select name="payment_gateway" onchange="this.form.submit()" class="border rounded-md px-3 py-2 text-sm">
            <option value="">Any Gateway</option>
            @foreach (['payfast' => 'PayFast', 'ozow' => 'Ozow', 'yoco' => 'Yoco'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['payment_gateway'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded-md px-3 py-2 text-sm">
        <span class="self-center text-gray-400 text-sm">to</span>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border rounded-md px-3 py-2 text-sm">

        <button type="submit" class="bg-gray-800 text-white text-sm font-semibold px-4 py-2 rounded-md hover:bg-gray-900">Filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 self-center hover:underline">Clear</a>
        @endif
    </form>

    <div class="bg-white border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Gateway</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Placed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-mono font-medium">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $order->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $order->items_count }}</td>
                        <td class="px-4 py-3 font-mono">R{{ number_format((float) $order->total_zar, 2) }}</td>
                        <td class="px-4 py-3 capitalize">{{ $order->payment_gateway ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-badge></td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-farmtech-green font-medium">View →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
