<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'customer_name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'phone' => '0821234567',
            'address_line1' => '1 Farm Rd',
            'address_line2' => 'Plot 4',
            'city' => 'Bloemfontein',
            'province' => 'Free State',
            'postal_code' => '9300',
            'subtotal_zar' => 1000,
            'shipping_zar' => 0,
            'total_zar' => 1000,
            'payment_status' => 'paid',
            'status' => 'paid',
        ], $overrides));

        $product = Product::factory()->create(['title' => 'RFID Stick Reader']);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'title_snapshot' => 'RFID Stick Reader',
            'unit_price_zar' => 1000,
            'quantity' => 1,
            'line_total_zar' => 1000,
        ]);

        return $order->fresh();
    }

    public function test_lookup_form_renders(): void
    {
        $this->get(route('track.index'))->assertOk()->assertSee('Track Your Order');
    }

    public function test_correct_order_number_and_email_shows_the_order(): void
    {
        $order = $this->makeOrder();

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('RFID Stick Reader');
    }

    public function test_email_lookup_is_case_insensitive(): void
    {
        $order = $this->makeOrder(['email' => 'Farmer@Example.com']);

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertOk()->assertSee($order->order_number);
    }

    public function test_wrong_email_for_a_real_order_number_is_rejected(): void
    {
        $order = $this->makeOrder();

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'someone-else@example.com',
        ]);

        $response->assertSessionHasErrors();
        $response->assertDontSee($order->order_number);
    }

    public function test_nonexistent_order_number_is_rejected(): void
    {
        $response = $this->post(route('track.show'), [
            'order_number' => 'FT-20200101-9999',
            'email' => 'farmer@example.com',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_delivery_address_is_masked_to_city_province_postal_only(): void
    {
        $order = $this->makeOrder();

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertSee('Bloemfontein');
        $response->assertSee('Free State');
        $response->assertSee('9300');
        $response->assertDontSee('1 Farm Rd');
        $response->assertDontSee('Plot 4');
    }

    public function test_a_paid_order_shows_stage_two_active_on_the_stepper(): void
    {
        $order = $this->makeOrder(['status' => 'paid']);

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertSee('Payment Verified');
        $response->assertSee('Order Confirmed');
    }

    public function test_a_cancelled_order_shows_a_cancelled_banner_not_the_stepper(): void
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertSee('cancelled', false);
        $response->assertDontSee('Delivered at Farm / Site Gate');
    }

    public function test_dispatched_order_with_known_courier_shows_a_tracking_link(): void
    {
        $order = $this->makeOrder([
            'status' => 'dispatched',
            'courier_name' => 'The Courier Guy',
            'tracking_number' => 'CG123456789ZA',
        ]);

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertSee('CG123456789ZA');
        $response->assertSee('https://thecourierguy.co.za/tracking/', false);
    }

    public function test_dispatched_order_with_unrecognized_courier_shows_number_without_a_broken_link(): void
    {
        $order = $this->makeOrder([
            'status' => 'dispatched',
            'courier_name' => 'Local Bakkie Express',
            'tracking_number' => 'LBX999',
        ]);

        $response = $this->post(route('track.show'), [
            'order_number' => $order->order_number,
            'email' => 'farmer@example.com',
        ]);

        $response->assertSee('LBX999');
        $response->assertSee('Local Bakkie Express');
    }

    public function test_lookup_is_rate_limited(): void
    {
        $payload = ['order_number' => 'FT-20200101-9999', 'email' => 'nobody@example.com'];

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('track.show'), $payload);
        }

        $this->post(route('track.show'), $payload)->assertStatus(429);
    }
}
