<?php

namespace App\Providers;

use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Listeners\SendNewOrderAdminAlert;
use App\Listeners\SendOrderPlacedEmail;
use App\Listeners\SendOrderStatusUpdatedEmail;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.farmtech');

        Event::listen(OrderPlaced::class, SendOrderPlacedEmail::class);
        Event::listen(OrderPaid::class, SendNewOrderAdminAlert::class);
        Event::listen(OrderStatusUpdated::class, SendOrderStatusUpdatedEmail::class);

        $this->registerGates();
        $this->registerRateLimiters();
    }

    /**
     * Admin (full access) vs. Staff (order fulfillment + own profile only —
     * see EnsureUserIsAdmin, which already gates panel entry for both
     * roles). These sections are Admin-only.
     */
    private function registerGates(): void
    {
        Gate::define('manage-catalog', fn (User $user) => $user->isAdmin()); // Sourcing + Products
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        // Profit/margin/cost data — Staff can work Orders without seeing what Farmtech
        // actually makes on each one. Gates both the /admin/dashboard route and the
        // "Profit Breakdown" card on the (otherwise Staff-visible) order detail page.
        Gate::define('view-financials', fn (User $user) => $user->isAdmin());
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('cart', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Order lookup is a two-field guess surface (order_number + email) —
        // same bucket size as cart/checkout, keyed by IP same as the others.
        RateLimiter::for('track', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Support form sends a real email per submission — same bucket size
        // as the other write-y storefront forms, to stop it being spammed.
        RateLimiter::for('support', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
    }
}
