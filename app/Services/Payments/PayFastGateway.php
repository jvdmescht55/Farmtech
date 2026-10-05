<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * PayFast scaffold. Needs PAYFAST_MERCHANT_ID / PAYFAST_MERCHANT_KEY /
 * PAYFAST_PASSPHRASE set before it can actually process a payment —
 * until then initiate() throws so the checkout flow fails loudly
 * instead of silently redirecting to a broken PayFast form.
 */
class PayFastGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order): array
    {
        $merchantId = config('services.payfast.merchant_id');
        $merchantKey = config('services.payfast.merchant_key');

        if (! $merchantId || ! $merchantKey) {
            throw new \RuntimeException('PayFast is not configured. Set PAYFAST_MERCHANT_ID and PAYFAST_MERCHANT_KEY.');
        }

        $sandbox = config('services.payfast.sandbox');

        $fields = [
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => route('shop.thanks', $order->order_number),
            'cancel_url' => route('shop.checkout'),
            'notify_url' => route('checkout.webhook.payfast'),
            'name_first' => $order->customer_name,
            'email_address' => $order->email,
            'm_payment_id' => $order->order_number,
            'amount' => number_format((float) $order->total_zar, 2, '.', ''),
            'item_name' => "Farmtech order {$order->order_number}",
        ];

        $fields['signature'] = $this->signature($fields);

        return [
            'method' => 'POST',
            'action_url' => $sandbox
                ? 'https://sandbox.payfast.co.za/eng/process'
                : 'https://www.payfast.co.za/eng/process',
            'fields' => $fields,
        ];
    }

    public function verifyNotification(array $payload, array $headers = []): bool
    {
        $signature = $payload['signature'] ?? null;
        unset($payload['signature']);

        return $signature !== null && hash_equals($this->signature($payload), $signature);
    }

    private function signature(array $fields): string
    {
        $passphrase = config('services.payfast.passphrase');

        $pairs = [];
        foreach ($fields as $key => $value) {
            if ($key === 'signature' || $value === null || $value === '') {
                continue;
            }
            $pairs[] = $key.'='.urlencode((string) $value);
        }

        $query = implode('&', $pairs);

        if ($passphrase) {
            $query .= '&passphrase='.urlencode($passphrase);
        }

        return md5($query);
    }
}
