<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /** Build whatever the storefront needs to hand off to the gateway (redirect URL, form fields, etc). */
    public function initiate(Order $order): array;

    /** Verify an inbound webhook/ITN payload actually came from the gateway before trusting it. */
    public function verifyNotification(array $payload, array $headers = []): bool;
}
