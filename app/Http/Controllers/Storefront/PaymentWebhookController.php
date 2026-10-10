<?php

namespace App\Http\Controllers\Storefront;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use App\Services\Billing;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway)
    {
        $payload = $request->all();
        $reference = $payload['m_payment_id'] ?? $payload['TransactionReference'] ?? null;

        // Herd Manager subscription payments carry a SUB- reference.
        if ($reference && str_starts_with($reference, 'SUB-')) {
            return $this->subscription($gateway, $reference, $payload, $request);
        }

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

    private function subscription(string $gateway, string $reference, array $payload, Request $request)
    {
        $payment = SubscriptionPayment::where('reference', $reference)->first();
        if (! $payment) {
            Log::warning("Payment webhook for unknown subscription payment [{$gateway}]", ['reference' => $reference]);

            return response('', 404);
        }
        if (! PaymentGatewayFactory::make($gateway)->verifyNotification($payload, $request->headers->all())) {
            Log::warning("Subscription webhook signature failed [{$gateway}]", ['reference' => $reference]);

            return response('', 400);
        }
        $gross = (float) ($payload['amount_gross'] ?? 0);
        $complete = strtoupper((string) ($payload['payment_status'] ?? 'COMPLETE')) === 'COMPLETE';
        if (! $complete || abs($gross * 100 - $payment->amount_cents) > 1) {
            Log::warning('Subscription payment not complete or wrong amount', ['reference' => $reference, 'gross' => $gross, 'status' => $payload['payment_status'] ?? null]);

            return response('OK');
        }

        app(Billing::class)->markPaid($payment, $payload['pf_payment_id'] ?? null);

        return response('OK');
    }
}
