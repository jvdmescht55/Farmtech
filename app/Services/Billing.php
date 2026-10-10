<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Herd Manager subscription: one price per farm, every device included.
 * Free for config('billing.trial_days') from the first device activation,
 * then paid monthly or yearly by EFT or card. When access runs out (after a
 * grace period) the farm goes read-only: records stay visible and can be
 * downloaded, nothing is deleted, and scanners keep sending their weights.
 */
class Billing
{
    public const PLANS = ['monthly' => 'Monthly', 'yearly' => 'Yearly'];

    public static function price(string $plan): int
    {
        return $plan === 'yearly' ? (int) config('billing.yearly_cents') : (int) config('billing.monthly_cents');
    }

    public static function rand(int $cents): string
    {
        return 'R'.number_format($cents / 100, $cents % 100 ? 2 : 0, '.', ' ');
    }

    /** The farm's subscription, started on first use with the free months. */
    public function for(User $user): Subscription
    {
        if ($user->relationLoaded('subscription') && $user->subscription) {
            return $user->subscription;
        }

        $sub = Subscription::firstOrCreate(['user_id' => $user->id], [
            'trial_ends_at' => ($user->licenses()->min('activated_at') ? Carbon::parse($user->licenses()->min('activated_at')) : now())
                ->addDays((int) config('billing.trial_days'))->max(now()->addDays(30)),
        ]);
        $user->setRelation('subscription', $sub);

        return $sub;
    }

    /**
     * Where this farm stands.
     *
     * @return array{state: string, until: ?Carbon, days_left: ?int, read_only: bool, label: string}
     */
    public function status(User $user): array
    {
        if ($user->role !== 'customer') {
            return ['state' => 'staff', 'until' => null, 'days_left' => null, 'read_only' => false, 'label' => 'Staff account'];
        }

        $sub = $this->for($user);
        $until = $sub->accessUntil();
        $daysLeft = $until ? (int) floor(now()->diffInDays($until, false)) : null;
        $paid = $sub->paid_until && $sub->paid_until->isFuture();
        $trial = ! $paid && $sub->trial_ends_at && $sub->trial_ends_at->isFuture();
        $grace = ! $paid && ! $trial && $until && $until->copy()->addDays((int) config('billing.grace_days'))->isFuture();

        [$state, $label] = match (true) {
            $paid => ['active', 'Paid up to '.$sub->paid_until->format('j M Y')],
            $trial => ['trial', 'Free until '.$sub->trial_ends_at->format('j M Y')],
            $grace => ['grace', 'Payment due. Full access until '.$until->copy()->addDays((int) config('billing.grace_days'))->format('j M Y')],
            default => ['read_only', 'Read-only. Your records are safe'],
        };

        return ['state' => $state, 'until' => $until, 'days_left' => $daysLeft, 'read_only' => $state === 'read_only', 'label' => $label];
    }

    /** A bill for the next period: the EFT reference and invoice, or what the card payment settles. */
    public function invoice(User $user, string $plan, string $method): SubscriptionPayment
    {
        $plan = array_key_exists($plan, self::PLANS) ? $plan : 'monthly';
        $sub = $this->for($user);

        // One open bill at a time: reuse it if the plan matches, otherwise replace it.
        $open = SubscriptionPayment::where('user_id', $user->id)->where('status', 'pending')->latest()->first();
        if ($open && $open->plan === $plan) {
            $open->update(['method' => $method]);

            return $open;
        }
        $open?->update(['status' => 'cancelled']);

        [$start, $end] = $this->nextPeriod($sub, $plan);

        return DB::transaction(function () use ($user, $plan, $method, $start, $end) {
            $p = SubscriptionPayment::create([
                'user_id' => $user->id, 'reference' => 'SUB-TMP-'.uniqid(), 'plan' => $plan, 'amount_cents' => self::price($plan),
                'method' => $method, 'period_start' => $start, 'period_end' => $end,
            ]);
            $p->update(['reference' => 'SUB-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT)]);

            return $p;
        });
    }

    /** Money arrived (EFT checked by us, or the card gateway said so): extend access. */
    public function markPaid(SubscriptionPayment $payment, ?string $gatewayRef = null): void
    {
        if ($payment->status === 'paid') {
            return;
        }
        DB::transaction(function () use ($payment, $gatewayRef) {
            $sub = $this->for($payment->user);
            [$start, $end] = $this->nextPeriod($sub, $payment->plan);
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'period_start' => $start, 'period_end' => $end, 'gateway_reference' => $gatewayRef ?? $payment->gateway_reference]);
            $sub->update(['plan' => $payment->plan, 'paid_until' => $end->copy()->endOfDay(), 'reminded_for' => null]);
        });
    }

    /** Admin gift: extra free months (a good customer, a late delivery, a friend). */
    public function giveMonths(User $user, int $months, ?string $why = null): void
    {
        $sub = $this->for($user);
        $from = max(array_filter([now(), $sub->paid_until, $sub->trial_ends_at]));
        $sub->update([
            'paid_until' => $from->copy()->addMonthsNoOverflow($months)->endOfDay(),
            'notes' => trim(($sub->notes ? $sub->notes."\n" : '').now()->format('d/m/Y').": {$months} free month(s)".($why ? " ($why)" : '')),
        ]);
    }

    /** A new period starts when the current access ends (so paying early loses nothing), or today. */
    private function nextPeriod(Subscription $sub, string $plan): array
    {
        $from = $sub->accessUntil();
        $start = $from && $from->isFuture() ? $from->copy()->addDay()->startOfDay() : now()->startOfDay();
        $end = $plan === 'yearly' ? $start->copy()->addYear()->subDay() : $start->copy()->addMonthNoOverflow()->subDay();

        return [$start, $end];
    }
}
