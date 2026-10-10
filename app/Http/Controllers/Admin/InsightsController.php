<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reader;
use App\Models\Scan;
use App\Models\StoreListing;
use App\Models\Suggestion;
use App\Models\User;

/** One page for the business: shop, customers, devices and what needs doing. */
class InsightsController extends Controller
{
    public function index()
    {
        $paid = Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Dispatched, OrderStatus::Completed, OrderStatus::ProcessingImport, OrderStatus::InCustoms]);
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $byMonth = (clone $paid)->where('created_at', '>=', $months->first())->get(['total_zar', 'created_at'])
            ->groupBy(fn ($o) => $o->created_at->format('Y-m'));

        $listings = StoreListing::orderBy('sort')->get()->map(function (StoreListing $l) {
            $items = OrderItem::where('store_listing_id', $l->id)->whereHas('order', fn ($q) => $q->where('status', '!=', OrderStatus::Cancelled));

            return [
                'listing' => $l,
                'ordered' => (int) (clone $items)->where('is_reservation', false)->sum('quantity'),
                'reserved' => (int) (clone $items)->where('is_reservation', true)->sum('quantity'),
                'revenue' => (float) (clone $items)->where('is_reservation', false)->sum('line_total_zar'),
            ];
        });

        $pendingOld = Order::where('status', OrderStatus::PendingPayment)->where('created_at', '<', now()->subDays(3))->count();
        $actions = array_values(array_filter([
            ($n = Lead::whereNull('handled_at')->count()) ? ['high', "$n lead".($n > 1 ? 's' : '').' and enquiries waiting for a reply.', route('admin.leads.index'), 'Leads'] : null,
            ($n = Order::where('status', OrderStatus::PendingPayment)->count()) ? ['high', "$n order".($n > 1 ? 's are' : ' is').' waiting for payment'.($pendingOld ? " ($pendingOld older than 3 days: follow up)" : '').'.', route('admin.orders.index', ['status' => 'pending_payment']), 'Orders'] : null,
            ($n = \App\Models\SubscriptionPayment::where('status', 'pending')->count()) ? ['high', "$n Herd Manager invoice".($n > 1 ? 's' : '').' waiting. Check the bank for SUB- references, then mark paid.', route('admin.subscriptions.index'), 'Subscriptions'] : null,
            ($n = Order::where('status', OrderStatus::Reserved)->count()) ? ['medium', "$n reservation".($n > 1 ? 's' : '').' for the next batch. Phone them when stock lands.', route('admin.orders.index', ['status' => 'reserved']), 'Reservations'] : null,
            ($n = Suggestion::where('status', 'new')->count()) ? ['medium', "$n new suggestion".($n > 1 ? 's' : '').' from farmers.', route('admin.suggestions.index'), 'Suggestions'] : null,
            ($l = StoreListing::whereNull('price_cents')->where('stock_status', '!=', 'coming_soon')->first()) ? ['medium', "{$l->name} has no price yet, so it can't be published.", route('admin.listings.edit', $l), 'Set price'] : null,
            ($n = StoreListing::where('is_published', true)->whereNull('images')->count()) ? ['low', "$n live product".($n > 1 ? 's have' : ' has').' no real photos yet.', route('admin.listings.index'), 'Add photos'] : null,
            ($n = \App\Models\Reader::where('kind', 'handheld')->whereNotNull('firmware')->get()->filter(fn ($r) => version_compare($r->firmware, json_decode(@file_get_contents(public_path('firmware/kraaltrac-pro/manifest.json')), true)['version'] ?? '0', '<') && str_contains(strtolower((string) $r->model), 'kraaltrac pro'))->count())
                ? ['low', "$n customer scanner".($n > 1 ? 's run' : ' runs').' older software. Customers can update at farmtech.site/app/install.', route('admin.licenses.index'), 'Customers'] : null,
            config('mail.default') === 'log' ? ['high', 'Email is switched off (MAIL_MAILER=log): customers get no order or password-reset emails.', route('admin.settings.edit'), 'Settings'] : null,
            blank(config('shop.bank.account_number')) ? ['medium', 'No EFT bank details set: customers see "we\'ll send banking details" instead.', route('admin.settings.edit'), 'Settings'] : null,
            blank(config('services.payfast.merchant_id')) ? ['low', 'Card payments are off until PayFast keys are added.', route('admin.settings.edit'), 'Settings'] : null,
        ]));

        return view('admin.insights.index', [
            'actions' => $actions,
            'kpis' => [
                'revenueMonth' => (float) (clone $paid)->where('created_at', '>=', now()->startOfMonth())->sum('total_zar'),
                'revenueAll' => (float) (clone $paid)->sum('total_zar'),
                'paidOrders' => (clone $paid)->count(),
                'awaiting' => (float) Order::where('status', OrderStatus::PendingPayment)->sum('total_zar'),
                'reservedUnits' => (int) OrderItem::where('is_reservation', true)->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Reserved))->sum('quantity'),
                'customers' => User::where('role', 'customer')->count(),
                'newCustomers' => User::where('role', 'customer')->where('created_at', '>=', now()->subDays(30))->count(),
                'devices' => Reader::count(),
                'devicesOnline' => Reader::where('last_synced_at', '>=', now()->subDay())->count(),
                'scans7' => Scan::where('scanned_at', '>=', now()->subDays(7))->count(),
                'animals' => Animal::where('in_herd', true)->count(),
                'leadsOpen' => Lead::whereNull('handled_at')->count(),
            ],
            'months' => $months->map(fn ($m) => ['label' => $m->format('M'), 'value' => (float) ($byMonth[$m->format('Y-m')] ?? collect())->sum('total_zar')]),
            'listings' => $listings,
            'recent' => Order::with('items')->latest()->limit(8)->get(),
        ]);
    }
}
