<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sums_sales_cost_and_profit_for_paid_orders_in_the_last_30_days(): void
    {
        $admin = $this->makeAdmin();

        $product = Product::factory()->create([
            'landed_cost_zar' => 1000,
            'intl_freight_zar' => 100,
            'customs_vat_zar' => 150,
            'domestic_delivery_zar' => 250,
            'retail_price_zar' => 1500,
        ]);

        $paidOrder = $this->makeOrder(subtotal: 3000, paymentStatus: 'paid');
        OrderItem::create([
            'order_id' => $paidOrder->id, 'product_id' => $product->id,
            'title_snapshot' => $product->title, 'unit_price_zar' => 1500, 'quantity' => 2, 'line_total_zar' => 3000,
        ]);

        // Unpaid order in range — must NOT be counted.
        $unpaidOrder = $this->makeOrder(subtotal: 9999, paymentStatus: 'pending');
        OrderItem::create([
            'order_id' => $unpaidOrder->id, 'product_id' => $product->id,
            'title_snapshot' => $product->title, 'unit_price_zar' => 1500, 'quantity' => 1, 'line_total_zar' => 1500,
        ]);

        // Paid order outside the 30-day window — must NOT be counted.
        $oldOrder = $this->makeOrder(subtotal: 5000, paymentStatus: 'paid');
        $oldOrder->forceFill(['created_at' => now()->subDays(45)])->save();
        OrderItem::create([
            'order_id' => $oldOrder->id, 'product_id' => $product->id,
            'title_snapshot' => $product->title, 'unit_price_zar' => 1500, 'quantity' => 1, 'line_total_zar' => 1500,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // Sales: R3000. Cost basis: landed_cost_zar (1000) * qty(2) = 2000. Profit: 3000-2000=1000.
        $response->assertSee('R3,000.00', false);
        $response->assertSee('R1,000.00', false);
        // Freight+customs: (100+150+250) * qty(2) = 1000
        $response->assertSee('R1,000.00', false);
    }

    public function test_dashboard_is_admin_only(): void
    {
        $staff = User::create([
            'name' => 'Staff User', 'email' => 'staff@example.com',
            'password' => Hash::make('password'), 'role' => 'staff', 'is_active' => true,
        ]);

        $this->actingAs($staff)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_profit_breakdown_card_is_hidden_from_staff_but_visible_to_admin_on_order_show(): void
    {
        $admin = $this->makeAdmin();
        $staff = User::create([
            'name' => 'Staff User', 'email' => 'staff2@example.com',
            'password' => Hash::make('password'), 'role' => 'staff', 'is_active' => true,
        ]);

        $order = $this->makeOrder(subtotal: 1000, paymentStatus: 'paid');

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertSee('Profit Breakdown');
        $this->actingAs($staff)->get(route('admin.orders.show', $order))->assertDontSee('Profit Breakdown');
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin User', 'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function makeOrder(float $subtotal, string $paymentStatus): Order
    {
        return Order::create([
            'customer_name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'phone' => '0821234567',
            'address_line1' => '1 Farm Rd',
            'city' => 'Bloemfontein',
            'province' => 'Free State',
            'postal_code' => '9300',
            'subtotal_zar' => $subtotal,
            'shipping_zar' => 0,
            'total_zar' => $subtotal,
            'payment_status' => $paymentStatus,
            'status' => 'pending_payment',
        ]);
    }
}
