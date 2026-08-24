<?php

namespace App\Http\Controllers\Storefront;

use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    private const SA_PROVINCES = [
        'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
        'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape',
    ];

    public function __construct(private readonly Cart $cart) {}

    public function index()
    {
        $items = $this->cart->items();
        abort_if(empty($items), 404, 'Your cart is empty.');

        return view('storefront.cart.checkout', [
            'items' => $items,
            'subtotal' => $this->cart->subtotal(),
            'provinces' => self::SA_PROVINCES,
        ]);
    }

    public function store(Request $request)
    {
        $items = $this->cart->items();
        abort_if(empty($items), 404, 'Your cart is empty.');

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+27|0)[0-9]{9}$/'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'in:'.implode(',', self::SA_PROVINCES)],
            'postal_code' => ['required', 'digits:4'],
            'payment_gateway' => ['required', 'in:payfast,ozow,yoco'],
        ], [
            'phone.regex' => 'Enter a valid South African phone number, e.g. 082 123 4567 or +27 82 123 4567.',
            'postal_code.digits' => 'South African postal codes are 4 digits.',
        ]);

        $subtotal = $this->cart->subtotal();
        $shipping = 0.00; // Direct Express Delivery is duty/VAT-inclusive in retail price — flat R0 shown, courier hook wires in real quotes later.

        try {
            $order = DB::transaction(function () use ($validated, $items, $subtotal, $shipping) {
                $order = Order::create([
                    ...$validated,
                    'subtotal_zar' => $subtotal,
                    'shipping_zar' => $shipping,
                    'total_zar' => $subtotal + $shipping,
                    'payment_status' => 'pending',
                    'status' => 'pending_payment',
                ]);

                foreach ($items as $item) {
                    // Atomic per-item decrement, checked inside the same
                    // transaction as the order/line-item rows — if stock ran
                    // out between "add to cart" and "place order" (a second
                    // checkout beat this one), the whole order rolls back
                    // rather than shipping someone a unit that doesn't exist.
                    if (! $item['product']->decrementStock($item['quantity'])) {
                        throw new InsufficientStockException($item['product']);
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'title_snapshot' => $item['product']->title,
                        'unit_price_zar' => $item['unit_price_zar'],
                        'quantity' => $item['quantity'],
                        'line_total_zar' => $item['line_total'],
                        'variant_key' => $item['variant_key'] ?: null,
                        'variant_attributes' => $item['variant']['attributes'] ?? null,
                        'variant_sku' => $item['variant']['sku_suffix'] ?? null,
                    ]);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return redirect()->route('cart.index')->withErrors(['stock' => $e->getMessage()]);
        }

        OrderPlaced::dispatch($order);

        $gateway = PaymentGatewayFactory::make($validated['payment_gateway']);

        try {
            $handoff = $gateway->initiate($order);
        } catch (\RuntimeException $e) {
            // Gateway not configured with real credentials yet (sandbox scaffold).
            return redirect()->route('checkout.index')->withErrors(['payment_gateway' => $e->getMessage()]);
        }

        $this->cart->clear();

        return view('storefront.cart.gateway-redirect', [
            'order' => $order,
            'handoff' => $handoff,
        ]);
    }

    public function success(Order $order)
    {
        return view('storefront.cart.success', ['order' => $order]);
    }
}
