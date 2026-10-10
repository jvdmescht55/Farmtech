<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionReminderMailable;
use App\Models\User;
use App\Services\Billing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Daily: tell farmers before their free months or paid period run out, and once when it went read-only. */
class BillingReminders extends Command
{
    protected $signature = 'billing:remind';

    protected $description = 'Email Herd Manager subscription reminders (7 days, 1 day, and when read-only starts)';

    public function handle(Billing $billing): int
    {
        $sent = 0;
        User::where('role', 'customer')->where('is_active', true)->whereHas('licenses')->with('subscription')->each(function (User $u) use ($billing, &$sent) {
            $s = $billing->status($u);
            $sub = $billing->for($u);
            $until = $s['until'];
            if (! $until) {
                return;
            }

            $kind = match (true) {
                $s['read_only'] => 'ended',
                in_array($s['days_left'], config('billing.remind_days'), true) => $s['state'] === 'trial' ? 'trial' : 'paid',
                default => null,
            };
            // One email per kind per period end.
            $key = $kind ? $kind.':'.($kind === 'ended' ? '' : $s['days_left'].':').$until->toDateString() : null;
            if (! $kind || $sub->reminded_for === $key) {
                return;
            }

            Mail::to($u->email)->send(new SubscriptionReminderMailable($u, $kind, (int) $s['days_left'], $until->format('j F Y')));
            $sub->update(['reminded_for' => $key]);
            $sent++;
        });
        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
