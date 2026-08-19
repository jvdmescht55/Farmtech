<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Yoco scaffold using their Checkout API. Needs YOCO_SECRET_KEY before
 * initiate() will do more than throw.
 */
class YocoGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order): array
    {
        $secretKey = config('services.yoco.secret_key');

        if (! $secretKey) {
            throw new \RuntimeException('Yoco is not configured. Set YOCO_SECRET_KEY.');
        }

        $response = Http::withToken($secretKey)
            ->post('https://payments.yoco.com/api/checkouts', [
                'amount' => (int) round((float) $order->total_zar * 100), // cents
                'currency' => 'ZAR',
                'successUrl' => route('checkout.success', $order),
                'cancelUrl' => route('checkout.index'),
                'failureUrl' => route('checkout.index'),
                'metadata' => ['order_number' => $order->order_number],
            ])
            ->throw()
            ->json();

        return [
            'method' => 'REDIRECT',
            'action_url' => $response['redirectUrl'],
            'fields' => [],
        ];
    }

    public function verifyNotification(array $payload, array $headers = []): bool
    {
        // Yoco webhooks are signed via the `webhook-signature` header (HMAC).
        // Verification requires the raw request body at receipt time, so the
        // real check lives in the webhook controller, not here.
        return true;
    }
}
