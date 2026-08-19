<?php

namespace App\Services\Payments;

class PaymentGatewayFactory
{
    public static function make(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'payfast' => new PayFastGateway,
            'ozow' => new OzowGateway,
            'yoco' => new YocoGateway,
            default => throw new \InvalidArgumentException("Unknown payment gateway [{$gateway}]."),
        };
    }
}
