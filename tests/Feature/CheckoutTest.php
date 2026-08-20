<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdminAlertMailable;
use App\Mail\OrderPlacedCustomerMailable;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private array $validAddress = [
        'customer_name' => 'Test Farmer',
        'email' => 'farmer@example.com',
        'phone' => '0821234567',
        'address_line1' => '1 Farm Rd',
        'city' => 'Bloemfontein',
        'province' => 'Free State',
        'postal_code' => '9300',
        'payment_gateway' => 'payfast',
    ];

    public function test_valid_checkout_creates_an_order_and_sends_the_customer_confirmation(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['retail_price_zar' => 1000]);

        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('checkout.store'), $this->validAddress);

        $order = Order::first();
        $this->assertNotNull($order, 'checkout should have created an order');
        $this->assertSame('Test Farmer', $order->customer_name);
        $this->assertSame('Free State', $order->province);
        $this->assertEquals(1000, (float) $order->total_zar);

        Mail::assertSent(OrderPlacedCustomerMailable::class, fn ($mail) => $mail->order->is($order));
    }

    public function test_checkout_rejects_a_postal_code_that_is_not_four_digits(): void
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $response = $this->from(route('checkout.index'))
            ->post(route('checkout.store'), [...$this->validAddress, 'postal_code' => '123']);

        $response->assertSessionHasErrors('postal_code');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_a_province_that_is_not_a_real_south_african_province(): void
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add', $product), ['quantity' => 1]);

        $response = $this->from(route('checkout.index'))
            ->post(route('checkout.store'), [...$this->validAddress, 'province' => 'Gautang']);

        $response->assertSessionHasErrors('province');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_alert_fires_only_once_the_payment_webhook_confirms_payment_not_at_checkout(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['retail_price_zar' => 1000]);
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('checkout.store'), $this->validAddress);

        $order = Order::first();

        // Not sent yet — an order being placed isn't the same as it being paid.
        Mail::assertNotSent(NewOrderAdminAlertMailable::class);

        $payload = ['m_payment_id' => $order->order_number, 'pf_payment_id' => 'PF123'];
        $payload['signature'] = $this->payFastSignature($payload);

        $webhookResponse = $this->post(route('checkout.webhook.payfast'), $payload);

        $webhookResponse->assertOk();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(\App\Enums\OrderStatus::Paid, $order->status);

        Mail::assertSent(NewOrderAdminAlertMailable::class, fn ($mail) => $mail->order->is($order));
    }

    public function test_payment_webhook_rejects_a_forged_signature(): void
    {
        Mail::fake();
        $product = Product::factory()->create();
        $this->post(route('cart.add', $product), ['quantity' => 1]);
        $this->post(route('checkout.store'), $this->validAddress);
        $order = Order::first();

        $response = $this->post(route('checkout.webhook.payfast'), [
            'm_payment_id' => $order->order_number,
            'signature' => 'not-a-real-signature',
        ]);

        $response->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->payment_status);
        Mail::assertNotSent(NewOrderAdminAlertMailable::class);
    }

    /** Mirrors PayFastGateway's private signature() algorithm — same public spec PayFast itself documents, no secret needed beyond the (unset-in-testing) passphrase. */
    private function payFastSignature(array $fields): string
    {
        $pairs = [];
        foreach ($fields as $key => $value) {
            if ($key === 'signature' || $value === null || $value === '') {
                continue;
            }
            $pairs[] = $key.'='.urlencode((string) $value);
        }

        return md5(implode('&', $pairs));
    }
}
