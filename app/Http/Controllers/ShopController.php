<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreListing;
use App\Services\Shop\ShopCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Cart, checkout and reservations for Farmtech's own products (store listings). */
class ShopController extends Controller
{
    public function __construct(private readonly ShopCart $cart) {}

    public function cart()
    {
        $lines = $this->cart->lines();

        return view('shop.cart', ['lines' => $lines, 'due' => $this->cart->dueNow($lines), 'reserved' => $this->cart->reserved($lines), 'courier' => $this->courier($this->cart->dueNow($lines))]);
    }

    public function add(Request $request, StoreListing $listing)
    {
        abort_unless($listing->is_published && ($listing->price_cents !== null || $listing->reservable()), 404);
        $this->cart->add($listing, (int) $request->input('qty', 1));

        return back()->with('cart_added', $listing->name.($listing->reservable() ? ' reserved' : ' added'));
    }

    public function update(Request $request, StoreListing $listing)
    {
        $this->cart->set($listing, (int) $request->input('qty', 0));

        return back();
    }

    public function checkout()
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }
        $due = $this->cart->dueNow($lines);

        return view('shop.checkout', [
            'lines' => $lines, 'due' => $due, 'reserved' => $this->cart->reserved($lines),
            'courier' => $this->courier($due), 'provinces' => SiteController::PROVINCES,
            'cardEnabled' => filled(config('services.payfast.merchant_id')) && filled(config('services.payfast.merchant_key')),
        ]);
    }

    public function place(Request $request)
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }
        $buying = $lines->where('reserve', false)->isNotEmpty();

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+27|0)[\d ]{9,12}$/'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['required', 'in:courier,collect'],
            'address_line1' => ['required_if:delivery_method,courier', 'nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required_if:delivery_method,courier', 'nullable', 'string', 'max:255'],
            'province' => ['required_if:delivery_method,courier', 'nullable', 'in:'.implode(',', SiteController::PROVINCES)],
            'postal_code' => ['required_if:delivery_method,courier', 'nullable', 'digits:4'],
            'payment_method' => [$buying ? 'required' : 'nullable', 'in:eft,card'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'phone.regex' => 'Enter a South African number, e.g. 082 123 4567.',
            'terms.accepted' => 'Please accept the terms of sale.',
            '*.required_if' => 'We need this for courier delivery.',
        ]);

        $due = $this->cart->dueNow($lines);
        $courier = $data['delivery_method'] === 'courier' && $buying ? ($this->courier($due) ?? 0) : 0;

        try {
            $order = DB::transaction(function () use ($data, $lines, $due, $courier, $buying) {
                $reserving = $lines->where('reserve', true)->isNotEmpty();
                $order = Order::create([
                    ...collect($data)->except(['terms', 'website', 'phone'])->all(),
                    'phone' => preg_replace('/\s+/', '', $data['phone']),
                    'kind' => $buying && $reserving ? 'mixed' : ($buying ? 'order' : 'reservation'),
                    'payment_method' => $buying ? $data['payment_method'] : 'none',
                    'payment_gateway' => $buying && $data['payment_method'] === 'card' ? 'payfast' : null,
                    'subtotal_zar' => $due / 100,
                    'shipping_zar' => $courier / 100,
                    'total_zar' => ($due + $courier) / 100,
                    'payment_status' => 'pending',
                    'status' => $buying ? OrderStatus::PendingPayment : OrderStatus::Reserved,
                ]);

                foreach ($lines as $line) {
                    $l = $line['listing'];
                    if (! $line['reserve'] && $l->stock_qty !== null) {
                        $ok = StoreListing::whereKey($l->id)->where('stock_qty', '>=', $line['qty'])->decrement('stock_qty', $line['qty']);
                        if (! $ok) {
                            throw new \RuntimeException("Sorry, {$l->name} just sold out. You can still reserve one from the next batch.");
                        }
                        if ($l->fresh()->stock_qty === 0) {
                            $l->update(['stock_status' => 'sold_out']);
                        }
                    }
                    OrderItem::create([
                        'order_id' => $order->id,
                        'store_listing_id' => $l->id,
                        'title_snapshot' => $l->name.($l->unit_label ? ' ('.$l->unit_label.')' : ''),
                        'unit_price_zar' => $line['unit'] / 100,
                        'quantity' => $line['qty'],
                        'is_reservation' => $line['reserve'],
                        'line_total_zar' => $line['line'] / 100,
                    ]);
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('shop.cart')->withErrors(['stock' => $e->getMessage()]);
        }

        $this->cart->clear();
        session()->push('my_orders', $order->order_number);

        if ($order->payment_gateway === 'payfast') {
            try {
                $handoff = \App\Services\Payments\PaymentGatewayFactory::make('payfast')->initiate($order);

                return view('shop.gateway-redirect', ['order' => $order, 'handoff' => $handoff]);
            } catch (\Throwable) {
                $order->update(['payment_method' => 'eft', 'payment_gateway' => null]); // card not set up: fall back to EFT
            }
        }

        return redirect()->route('shop.thanks', $order->order_number);
    }

    public function thanks(string $number)
    {
        abort_unless(in_array($number, session('my_orders', []), true) || auth()->user()?->canAccessAdminPanel(), 404);
        $order = Order::with('items')->where('order_number', $number)->firstOrFail();

        return view('shop.thanks', ['order' => $order]);
    }

    /** Courier cost in cents for what's paid now; null = confirmed per order. */
    private function courier(int $due): ?int
    {
        $free = config('shop.free_courier_over_cents');
        if ($free && $due >= $free) {
            return 0;
        }

        return config('shop.courier_cents');
    }
}
