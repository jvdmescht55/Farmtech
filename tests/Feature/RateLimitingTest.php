<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('search:127.0.0.1');
    }

    public function test_admin_login_locks_out_after_five_failed_attempts(): void
    {
        User::create([
            'name' => 'Real Admin', 'email' => 'real@example.com',
            'password' => Hash::make('correct-password'), 'role' => 'admin', 'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('admin.login'), ['email' => 'real@example.com', 'password' => 'wrong']);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt — even with the CORRECT password, the account is locked for this email+IP.
        $response = $this->post(route('admin.login'), ['email' => 'real@example.com', 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_a_successful_login_clears_the_failed_attempt_counter(): void
    {
        User::create([
            'name' => 'Real Admin', 'email' => 'real2@example.com',
            'password' => Hash::make('correct-password'), 'role' => 'admin', 'is_active' => true,
        ]);

        $this->post(route('admin.login'), ['email' => 'real2@example.com', 'password' => 'wrong']);
        $this->post(route('admin.login'), ['email' => 'real2@example.com', 'password' => 'correct-password']);

        $this->assertAuthenticated();
    }

    public function test_the_search_route_is_rate_limited_to_30_per_minute(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get(route('search.index', ['q' => 'scale']))->assertOk();
        }

        $this->get(route('search.index', ['q' => 'scale']))->assertStatus(429);
    }

    public function test_cart_and_checkout_routes_are_rate_limited_to_10_per_hour(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('cart.index'))->assertOk();
        }

        $this->get(route('cart.index'))->assertStatus(429);
        // Same limiter bucket covers the whole group — checkout is blocked too, not just cart.
        $this->get(route('checkout.index'))->assertStatus(429);
    }
}
