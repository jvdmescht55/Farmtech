<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Ozow scaffold. Needs OZOW_SITE_CODE / OZOW_PRIVATE_KEY / OZOW_API_KEY
 * before initiate() will do more than throw — same "fail loudly" approach
 * as PayFastGateway, so an unconfigured gateway never reaches the customer.
 */
class OzowGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order): array
    {
        $siteCode = config('services.ozow.site_code');
        $privateKey = config('services.ozow.private_key');

        if (! $siteCode || ! $privateKey) {
            throw new \RuntimeException('Ozow is not configured. Set OZOW_SITE_CODE and OZOW_PRIVATE_KEY.');
        }

        $sandbox = config('services.ozow.sandbox');

        $fields = [
            'SiteCode' => $siteCode,
            'CountryCode' => 'ZA',
            'CurrencyCode' => 'ZAR',
            'Amount' => number_format((float) $order->total_zar, 2, '.', ''),
            'TransactionReference' => $order->order_number,
            'BankReference' => "Farmtech {$order->order_number}",
            'IsTest' => $sandbox ? 'true' : 'false',
            'SuccessUrl' => route('shop.thanks', $order->order_number),
            'CancelUrl' => route('shop.checkout'),
            'ErrorUrl' => route('shop.checkout'),
            'NotifyUrl' => route('checkout.webhook.ozow'),
        ];

        $fields['HashCheck'] = $this->hash($fields, $privateKey);

        return [
            'method' => 'POST',
            'action_url' => 'https://pay.ozow.com',
            'fields' => $fields,
        ];
    }

    public function verifyNotification(array $payload, array $headers = []): bool
    {
        $hash = $payload['Hash'] ?? null;
        unset($payload['Hash']);

        $privateKey = config('services.ozow.private_key');

        return $hash !== null && $privateKey && hash_equals(
            strtolower($this->hash($payload, $privateKey)),
            strtolower($hash)
        );
    }

    private function hash(array $fields, string $privateKey): string
    {
        $concatenated = implode('', array_map('strval', array_values($fields))).$privateKey;

        return hash('sha512', strtolower($concatenated));
    }
}
