<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Billing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function farmer(string $activated = 'now'): User
    {
        $u = User::create(['name' => 'Test Boer', 'email' => 'boer@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'customer', 'is_active' => true, 'terms_accepted_at' => now(), 'species' => 'sheep']);
        License::create(['code' => 'TEST-'.uniqid(), 'module' => 'rfid', 'user_id' => $u->id, 'activated_at' => \Carbon\Carbon::parse($activated)]);

        return $u;
    }

    private function expire(User $u): void
    {
        app(Billing::class)->for($u)->update(['trial_ends_at' => now()->subDays(config('billing.grace_days') + 1)]);
    }

    public function test_a_new_farmer_gets_the_free_months_and_full_access(): void
    {
        $u = $this->farmer();
        $s = app(Billing::class)->status($u);
        $this->assertSame('trial', $s['state']);
        $this->assertEqualsWithDelta(config('billing.trial_days'), $s['days_left'], 1);
        $this->actingAs($u)->post(route('rfid.animals.store'), ['visual_id' => 'A1', 'species' => 'sheep', 'status' => 'active'])->assertSessionDoesntHaveErrors();
    }

    public function test_when_it_runs_out_the_farm_is_read_only_but_nothing_is_hidden(): void
    {
        $u = $this->farmer('-1 year');
        $this->expire($u);

        $this->actingAs($u)->get(route('rfid.animals.index'))->assertOk()->assertSee('Read-only');
        $this->get(route('rfid.data', ['export' => 'herd']))->assertOk();
        $this->post(route('rfid.animals.store'), ['visual_id' => 'A1', 'species' => 'sheep', 'status' => 'active'])->assertRedirect(route('billing.show'));
        $this->assertDatabaseMissing('animals', ['visual_id' => 'A1', 'species' => 'sheep', 'status' => 'active']);
    }

    public function test_grace_days_keep_everything_working(): void
    {
        $u = $this->farmer('-1 year');
        app(Billing::class)->for($u)->update(['trial_ends_at' => now()->subDays(3)]);
        $this->assertSame('grace', app(Billing::class)->status($u)['state']);
        $this->actingAs($u)->post(route('rfid.animals.store'), ['visual_id' => 'A1', 'species' => 'sheep', 'status' => 'active'])->assertSessionDoesntHaveErrors();
    }

    public function test_eft_invoice_then_admin_marks_it_paid(): void
    {
        $u = $this->farmer('-1 year');
        $this->expire($u);
        $this->actingAs($u)->post(route('billing.pay'), ['plan' => 'yearly', 'method' => 'eft'])->assertRedirect(route('billing.show'));
        $p = SubscriptionPayment::firstOrFail();
        $this->assertSame('SUB-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT), $p->reference);
        $this->assertSame(config('billing.yearly_cents'), $p->amount_cents);
        $this->get(route('billing.show'))->assertSee($p->reference);
        $this->get(route('billing.invoice', $p))->assertOk()->assertSee($p->reference);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.subscriptions.paid', $p))->assertRedirect();

        $s = app(Billing::class)->status($u->fresh());
        $this->assertSame('active', $s['state']);
        $this->assertSame(now()->addYear()->subDay()->toDateString(), $u->fresh()->subscription->paid_until->toDateString());
    }

    public function test_paying_early_adds_on_after_the_free_months(): void
    {
        $u = $this->farmer();
        $trialEnd = app(Billing::class)->for($u)->trial_ends_at;
        $p = app(Billing::class)->invoice($u, 'monthly', 'eft');
        app(Billing::class)->markPaid($p);
        $this->assertSame($trialEnd->copy()->addDay()->addMonthNoOverflow()->subDay()->toDateString(), $u->fresh()->subscription->paid_until->toDateString());
    }

    public function test_card_payment_notification_extends_access(): void
    {
        config(['services.payfast.merchant_id' => '10000100', 'services.payfast.merchant_key' => 'key', 'services.payfast.passphrase' => null]);
        $u = $this->farmer('-1 year');
        $this->expire($u);
        $p = app(Billing::class)->invoice($u, 'monthly', 'card');

        $payload = ['m_payment_id' => $p->reference, 'pf_payment_id' => '123', 'payment_status' => 'COMPLETE', 'amount_gross' => number_format($p->amount_cents / 100, 2, '.', '')];
        $pairs = [];
        foreach ($payload as $k => $v) {
            $pairs[] = $k.'='.urlencode($v);
        }
        $payload['signature'] = md5(implode('&', $pairs));

        $this->post(route('checkout.webhook.payfast'), $payload)->assertOk();
        $this->assertSame('paid', $p->fresh()->status);
        $this->assertSame('active', app(Billing::class)->status($u->fresh())['state']);
    }

    public function test_a_wrong_amount_does_not_unlock(): void
    {
        config(['services.payfast.merchant_id' => '10000100', 'services.payfast.merchant_key' => 'key', 'services.payfast.passphrase' => null]);
        $u = $this->farmer();
        $p = app(Billing::class)->invoice($u, 'yearly', 'card');
        $payload = ['m_payment_id' => $p->reference, 'payment_status' => 'COMPLETE', 'amount_gross' => '1.00'];
        $payload['signature'] = md5('m_payment_id='.urlencode($p->reference).'&payment_status=COMPLETE&amount_gross=1.00');
        $this->post(route('checkout.webhook.payfast'), $payload)->assertOk();
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_reminders_go_once(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $u = $this->farmer();
        app(Billing::class)->for($u)->update(['trial_ends_at' => now()->addDays(7)->addHours(2)]);
        $this->artisan('billing:remind')->assertSuccessful();
        $this->artisan('billing:remind')->assertSuccessful();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SubscriptionReminderMailable::class, 1);
    }
}
