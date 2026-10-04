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
    case Reserved = 'reserved';
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
            self::Reserved => 'Reserved (next batch)',
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
            self::Reserved => 'blue',
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

    /**
     * 0-based index into trackingStageLabels() for the /track portal's 5-stage
     * stepper. Null for Cancelled — there's no meaningful position on a linear
     * progress stepper for an order that stopped, so the track page shows a
     * dedicated cancelled banner instead of a partially-lit stepper.
     */
    public function trackingStageIndex(): ?int
    {
        return match ($this) {
            self::Reserved, self::PendingPayment => 0,
            self::Paid => 1,
            self::ProcessingImport, self::InCustoms => 2,
            self::Dispatched => 3,
            self::Completed => 4,
            self::Cancelled => null,
        };
    }

    public static function trackingStageLabels(): array
    {
        return [
            'Order Confirmed',
            'Payment Verified',
            'Import & Customs Processing',
            'Dispatched with Courier',
            'Delivered at Farm / Site Gate',
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
