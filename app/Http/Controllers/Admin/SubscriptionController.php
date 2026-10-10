<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Billing;
use Illuminate\Http\Request;

/** Herd Manager subscriptions: who is on trial, who paid, and EFTs to confirm. */
class SubscriptionController extends Controller
{
    public function __construct(private Billing $billing) {}

    public function index()
    {
        $customers = User::where('role', 'customer')->with('subscription')->orderBy('name')->get()
            ->map(fn (User $u) => ['user' => $u, 'status' => $this->billing->status($u)]);
        $monthly = (int) config('billing.monthly_cents');

        return view('admin.subscriptions.index', [
            'customers' => $customers,
            'pending' => SubscriptionPayment::with('user')->where('status', 'pending')->latest()->get(),
            'paid' => SubscriptionPayment::with('user')->where('status', 'paid')->latest('paid_at')->limit(30)->get(),
            'counts' => $customers->countBy(fn ($c) => $c['status']['state']),
            // Monthly recurring revenue: paying farms, yearly plans spread over 12 months.
            'mrr' => $customers->where('status.state', 'active')->sum(fn ($c) => $c['user']->subscription?->plan === 'yearly' ? (int) round(config('billing.yearly_cents') / 12) : $monthly),
        ]);
    }

    public function markPaid(SubscriptionPayment $payment)
    {
        $this->billing->markPaid($payment, 'EFT confirmed by '.auth()->user()->name);

        return back()->with('status', "{$payment->reference} marked paid. {$payment->user->name} now has access to ".$payment->fresh()->period_end->format('j M Y').'.');
    }

    public function cancel(SubscriptionPayment $payment)
    {
        abort_unless($payment->status === 'pending', 422);
        $payment->update(['status' => 'cancelled']);

        return back()->with('status', "{$payment->reference} cancelled.");
    }

    public function give(Request $request, User $user)
    {
        $data = $request->validate(['months' => ['required', 'integer', 'min:1', 'max:24'], 'why' => ['nullable', 'string', 'max:120']]);
        $this->billing->giveMonths($user, (int) $data['months'], $data['why'] ?? null);

        return back()->with('status', "{$user->name} got {$data['months']} free month(s).");
    }
}
