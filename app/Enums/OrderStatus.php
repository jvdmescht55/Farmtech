<?php

namespace App\Enums;

/**
 * Full order lifecycle, from checkout through delivery — one column instead
 * of splitting fulfillment state across `status` and `payment_status`.
 * `payment_status` still exists separately for narrower payment-gateway
 * bookkeeping (pending/paid/failed/refunded); this enum is what the admin
 * team and the customer-facing emails actually track day to day.
 */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case ProcessingImport = 'processing_import';
    case InCustoms = 'in_customs';
    case Dispatched = 'dispatched';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Paid => 'Paid',
            self::ProcessingImport => 'Processing Import',
            self::InCustoms => 'In Customs',
            self::Dispatched => 'Dispatched',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Badge color for the admin order list/detail. */
    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'gray',
            self::Paid, self::ProcessingImport, self::InCustoms => 'yellow',
            self::Dispatched => 'green',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /** Statuses a "notify customer" checkbox actually makes sense for — see OrderStatusUpdatedCustomerMailable. */
    public function customerNotifiable(): bool
    {
        return in_array($this, [self::Dispatched, self::InCustoms], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
