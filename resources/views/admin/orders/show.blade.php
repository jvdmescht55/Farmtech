@extends('layouts.admin')

@section('heading', 'Order '.$order->order_number)

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Line items --}}
            <div class="bg-white border rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Duty/VAT</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $item->title_snapshot }}</td>
                                <td class="px-4 py-3 font-mono text-gray-500">{{ $item->product?->sku ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    @if ($item->product)
                                        {{ number_format($item->product->customs_duty_rate * 100, 1) }}% / {{ number_format($item->product->vat_rate * 100, 0) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 font-mono">R{{ number_format((float) $item->unit_price_zar, 2) }}</td>
                                <td class="px-4 py-3 font-mono text-right">R{{ number_format((float) $item->line_total_zar, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t bg-gray-50">
                            <td colspan="5" class="px-4 py-3 text-right font-semibold">Subtotal</td>
                            <td class="px-4 py-3 font-mono text-right">R{{ number_format((float) $order->subtotal_zar, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-semibold">Shipping</td>
                            <td class="px-4 py-3 font-mono text-right">R{{ number_format((float) $order->shipping_zar, 2) }}</td>
                        </tr>
                        <tr class="border-t">
                            <td colspan="5" class="px-4 py-3 text-right font-bold">Total (incl. duty &amp; VAT)</td>
                            <td class="px-4 py-3 font-mono text-right font-bold">R{{ number_format((float) $order->total_zar, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Customer & delivery --}}
            <div class="grid sm:grid-cols-2 gap-6">
                <div class="bg-white border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Customer</h2>
                    <dl class="text-sm space-y-1.5">
                        <div class="flex justify-between"><dt class="text-gray-500">Name</dt><dd>{{ $order->customer_name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd>{{ $order->email }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd>{{ $order->phone }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Payment</dt><dd class="capitalize">{{ $order->payment_gateway ?? '—' }} ({{ $order->payment_status }})</dd></div>
                    </dl>
                </div>
                <div class="bg-white border rounded-xl p-5">
                    <h2 class="font-semibold mb-3">Delivery Address</h2>
                    <address class="text-sm not-italic leading-relaxed">
                        {{ $order->address_line1 }}<br>
                        @if ($order->address_line2)
                            {{ $order->address_line2 }}<br>
                        @endif
                        {{ $order->city }}, {{ $order->province }}<br>
                        {{ $order->postal_code }}
                    </address>
                    @unless ($order->billing_same_as_shipping)
                        <h3 class="font-semibold text-xs uppercase text-gray-500 mt-4 mb-1">Billing Address</h3>
                        <address class="text-sm not-italic leading-relaxed">
                            {{ $order->billing_address_line1 }}<br>
                            @if ($order->billing_address_line2)
                                {{ $order->billing_address_line2 }}<br>
                            @endif
                            {{ $order->billing_city }}, {{ $order->billing_province }}<br>
                            {{ $order->billing_postal_code }}
                        </address>
                    @endunless
                </div>
            </div>

            {{-- Notes / log timeline --}}
            <div class="bg-white border rounded-xl p-5">
                <h2 class="font-semibold mb-4">Order Notes</h2>
                <form action="{{ route('admin.orders.notes.store', $order) }}" method="POST" class="flex gap-2 mb-5">
                    @csrf
                    <input type="text" name="note" required placeholder="Add an internal note…" class="flex-1 border rounded-md px-3 py-2 text-sm">
                    <button type="submit" class="bg-gray-800 text-white text-sm font-semibold px-4 py-2 rounded-md hover:bg-gray-900">Add</button>
                </form>

                <ol class="space-y-4 border-l-2 border-gray-100 pl-4">
                    @forelse ($order->notes as $note)
                        <li class="relative">
                            <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full {{ $note->customer_notified ? 'bg-farmtech-gold' : 'bg-gray-300' }}"></span>
                            <p class="text-sm">{{ $note->note }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $note->created_at->format('d M Y, H:i') }}
                                @if ($note->user) &middot; {{ $note->user->name }} @endif
                                @if ($note->customer_notified) &middot; <span class="text-farmtech-gold font-medium">customer notified</span> @endif
                            </p>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">No notes yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white border rounded-xl p-5">
                <h2 class="font-semibold mb-1">Status</h2>
                <p class="mb-4"><x-badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</x-badge></p>

                <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-sm font-medium mb-1">Update Status</label>
                        <select name="status" class="w-full border rounded-md px-3 py-2 text-sm">
                            @foreach (\App\Enums\OrderStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($order->status === $statusOption)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="notify_customer" value="1">
                        Notify customer via email
                    </label>
                    <p class="text-xs text-gray-400 -mt-2">Only sends for Dispatched / In Customs — otherwise ignored.</p>

                    <div class="border-t pt-3">
                        <label class="block text-sm font-medium mb-1">Courier Name</label>
                        <input type="text" name="courier_name" value="{{ $order->courier_name }}" placeholder="e.g. The Courier Guy" class="w-full border rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tracking Number</label>
                        <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" placeholder="e.g. CG123456789ZA" class="w-full border rounded-md px-3 py-2 text-sm">
                    </div>

                    <button type="submit" class="w-full bg-farmtech-green text-white font-semibold px-4 py-2 rounded-md hover:bg-farmtech-green-dark">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
@endsection
