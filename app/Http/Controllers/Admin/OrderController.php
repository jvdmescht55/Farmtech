<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->withCount('items')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($gateway = $request->query('payment_gateway')) {
            $query->where('payment_gateway', $gateway);
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'payment_gateway', 'date_from', 'date_to', 'q']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'notes.user']);

        return view('admin.orders.show', ['order' => $order]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'courier_name' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);

        $previousStatus = $order->status;
        $newStatus = OrderStatus::from($validated['status']);

        $order->update([
            'status' => $newStatus,
            'courier_name' => $validated['courier_name'] ?? $order->courier_name,
            'tracking_number' => $validated['tracking_number'] ?? $order->tracking_number,
        ]);

        $willNotify = $request->boolean('notify_customer') && $newStatus->customerNotifiable();

        if ($previousStatus !== $newStatus) {
            OrderNote::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'note' => "Status changed from \"{$previousStatus->label()}\" to \"{$newStatus->label()}\".",
                'customer_notified' => $willNotify,
            ]);
        }

        if ($willNotify) {
            OrderStatusUpdated::dispatch($order);
        }

        return back()->with('status', "Order {$order->order_number} updated.".($willNotify ? ' Customer notified.' : ''));
    }

    public function addNote(Request $request, Order $order)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        OrderNote::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'note' => $validated['note'],
            'customer_notified' => false,
        ]);

        return back()->with('status', 'Note added.');
    }
}
