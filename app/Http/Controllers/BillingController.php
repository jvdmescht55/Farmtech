<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\Billing;
use App\Services\Payments\PayFastGateway;
use Illuminate\Http\Request;

/** "Plan & billing": where the farm stands, and paying for the next month or year. */
class BillingController extends Controller
{
    public function __construct(private Billing $billing) {}

    public function show(Request $request)
    {
        $u = $request->user();

        return view('billing.show', [
            'status' => $this->billing->status($u),
            'sub' => $u->role === 'customer' ? $this->billing->for($u) : null,
            'open' => SubscriptionPayment::where('user_id', $u->id)->where('status', 'pending')->latest()->first(),
            'history' => SubscriptionPayment::where('user_id', $u->id)->where('status', 'paid')->latest('paid_at')->limit(24)->get(),
            'cardEnabled' => $this->cardEnabled(),
            'bank' => config('shop.bank'),
        ]);
    }

    public function pay(Request $request)
    {
        abort_unless($request->user()->role === 'customer', 403);
        $data = $request->validate(['plan' => ['required', 'in:monthly,yearly'], 'method' => ['required', 'in:eft,card']]);
        $method = $data['method'] === 'card' && $this->cardEnabled() ? 'card' : 'eft';
        $payment = $this->billing->invoice($request->user(), $data['plan'], $method);

        if ($method === 'card') {
            return view('shop.gateway-redirect', ['handoff' => app(PayFastGateway::class)->initiateSubscription($payment), 'label' => $payment->reference.' · '.$payment->rand()]);
        }

        return redirect()->route('billing.show')->with('status', "Invoice {$payment->reference} is ready. Pay {$payment->rand()} by EFT with {$payment->reference} as the reference.");
    }

    public function invoice(Request $request, SubscriptionPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id || $request->user()->canAccessAdminPanel(), 404);

        return view('billing.invoice', ['p' => $payment->load('user'), 'bank' => config('shop.bank')]);
    }

    private function cardEnabled(): bool
    {
        return filled(config('services.payfast.merchant_id')) && filled(config('services.payfast.merchant_key'));
    }
}
