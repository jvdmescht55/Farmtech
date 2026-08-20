<?php

namespace App\Http\Controllers\Storefront;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway)
    {
        $payload = $request->all();
        $reference = $payload['m_payment_id'] ?? $payload['TransactionReference'] ?? null;

        $order = $reference ? Order::where('order_number', $reference)->first() : null;

        if (! $order) {
            Log::warning("Payment webhook for unknown order [{$gateway}]", $payload);

            return response('', 404);
        }

        if (! PaymentGatewayFactory::make($gateway)->verifyNotification($payload, $request->headers->all())) {
            Log::warning("Payment webhook signature verification failed [{$gateway}]", ['order' => $order->order_number]);

            return response('', 400);
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_reference' => $payload['pf_payment_id'] ?? $payload['TransactionId'] ?? null,
        ]);

        OrderPaid::dispatch($order);

        return response('OK');
    }
}
