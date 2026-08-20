<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_view_the_orders_list(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
    }

    public function test_a_guest_cannot_view_an_order(): void
    {
        $order = $this->makeOrder();

        $this->get(route('admin.orders.show', $order))->assertRedirect(route('admin.login'));
    }

    public function test_a_guest_cannot_update_an_order(): void
    {
        $order = $this->makeOrder();

        $this->patch(route('admin.orders.update', $order), ['status' => 'paid'])
            ->assertRedirect(route('admin.login'));

        $this->assertSame('pending_payment', $order->fresh()->status->value);
    }

    public function test_a_deactivated_admin_cannot_access_orders(): void
    {
        $deactivated = $this->makeAdmin(isActive: false);

        $this->actingAs($deactivated)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_an_admin_can_see_the_orders_list(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
    }

    public function test_an_admin_can_update_status_and_assign_courier_and_tracking(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'dispatched',
            'courier_name' => 'The Courier Guy',
            'tracking_number' => 'CG999',
            'notify_customer' => '1',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertSame(\App\Enums\OrderStatus::Dispatched, $order->status);
        $this->assertSame('The Courier Guy', $order->courier_name);
        $this->assertSame('CG999', $order->tracking_number);

        // A status-change note is logged automatically, marked as having notified the customer.
        $note = OrderNote::where('order_id', $order->id)->latest()->first();
        $this->assertNotNull($note);
        $this->assertTrue($note->customer_notified);
    }

    public function test_notify_customer_is_ignored_for_a_status_that_is_not_notifiable(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();

        $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'paid',
            'notify_customer' => '1',
        ]);

        Mail::assertNotSent(\App\Mail\OrderStatusUpdatedCustomerMailable::class);
    }

    public function test_an_admin_can_add_an_internal_note(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder();

        $this->actingAs($admin)->post(route('admin.orders.notes.store', $order), [
            'note' => 'Customer called to confirm delivery window.',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_notes', [
            'order_id' => $order->id,
            'note' => 'Customer called to confirm delivery window.',
            'user_id' => $admin->id,
            'customer_notified' => false,
        ]);
    }

    private function makeAdmin(bool $isActive = true): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => $isActive,
        ]);
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'customer_name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'phone' => '0821234567',
            'address_line1' => '1 Farm Rd',
            'city' => 'Bloemfontein',
            'province' => 'Free State',
            'postal_code' => '9300',
            'subtotal_zar' => 1000,
            'shipping_zar' => 0,
            'total_zar' => 1000,
            'payment_status' => 'pending',
            'status' => 'pending_payment',
        ]);
    }
}
