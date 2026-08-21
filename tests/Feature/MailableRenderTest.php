<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdminAlertMailable;
use App\Mail\OrderPlacedCustomerMailable;
use App\Mail\OrderStatusUpdatedCustomerMailable;
use App\Mail\ScrapedBatchSummaryMailable;
use App\Mail\SupportRequestMailable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every real Mailable actually compiles — proves the markdown views don't
 * have a broken variable reference or Blade error, which a "config looks
 * right" check alone wouldn't catch. Doesn't test real delivery (this
 * environment has no real SMTP credentials — see .env.example), only that
 * ->render() produces real HTML without throwing.
 */
class MailableRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $order = Order::create([
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
        ]);

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

    public function test_new_order_admin_alert_renders(): void
    {
        $html = (new NewOrderAdminAlertMailable($this->makeOrder()))->render();

        $this->assertStringContainsString('New paid order', (new NewOrderAdminAlertMailable($this->makeOrder()))->envelope()->subject);
        $this->assertNotEmpty($html);
    }

    public function test_order_placed_customer_mail_renders(): void
    {
        $html = (new OrderPlacedCustomerMailable($this->makeOrder()))->render();

        $this->assertStringContainsString('Test Farmer', $html);
    }

    public function test_order_status_updated_customer_mail_renders(): void
    {
        $html = (new OrderStatusUpdatedCustomerMailable($this->makeOrder()))->render();

        $this->assertNotEmpty($html);
    }

    public function test_scraped_batch_summary_mail_renders(): void
    {
        $html = (new ScrapedBatchSummaryMailable(
            pending: [['title' => 'Test Product', 'sku' => 'FT-1']],
            rejected: [],
            errored: [],
        ))->render();

        $this->assertNotEmpty($html);
    }

    public function test_support_request_mail_renders(): void
    {
        $mailable = new SupportRequestMailable(
            senderName: 'Test Farmer',
            senderEmail: 'farmer@example.com',
            topic: 'Order status',
            message: 'When will my order ship?',
        );

        $html = $mailable->render();

        $this->assertStringContainsString('Test Farmer', $html);
        $this->assertStringContainsString('When will my order ship?', $html);
    }
}
