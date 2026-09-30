<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\ExchangeRate;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Reader;
use App\Models\SaleCatalogue;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One page of real, computed signals across the store, the sourcing
 * pipeline and the Herd Management platform — plus an action list built
 * from the same numbers. Nothing here is estimated or benchmarked against
 * data we don't have.
 */
class InsightsController extends Controller
{
    private const LOW_MARGIN_PCT = 15;

    public function index()
    {
        $live = Product::query()->where('status', 'approved')->where('is_active', true);
        $liveProducts = (clone $live)->withCount('images')->get([
            'id', 'title', 'slug', 'category', 'retail_price_zar', 'landed_cost_zar', 'profit_margin_pct', 'click_count',
            'stock_status', 'stock_quantity', 'low_stock_threshold', 'supplier_last_checked_at', 'supplier_phone',
            'supplier_whatsapp', 'supplier_email', 'has_video', 'featured_score', 'lead_time_days',
        ]);

        $paid = Order::query()->whereIn('status', ['paid', 'processing_import', 'in_customs', 'dispatched', 'completed']);
        $revenue30 = (float) (clone $paid)->where('created_at', '>=', now()->subDays(30))->sum('total_zar');
        $orders30 = (clone $paid)->where('created_at', '>=', now()->subDays(30))->count();
        $revenueAll = (float) (clone $paid)->sum('total_zar');
        $ordersAll = (clone $paid)->count();

        $unitsSold = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['paid', 'processing_import', 'in_customs', 'dispatched', 'completed']))
            ->groupBy('product_id')->selectRaw('product_id, sum(quantity) units, sum(line_total_zar) revenue')
            ->get()->keyBy('product_id');

        $categoryMix = $liveProducts->groupBy('category')->map(function (Collection $g, $cat) use ($unitsSold) {
            $enum = ProductCategory::tryFrom($cat);

            return [
                'label' => $enum?->label() ?? $cat,
                'slug' => $cat,
                'count' => $g->count(),
                'avg_price' => round($g->avg('retail_price_zar')),
                'avg_margin' => round($g->avg('profit_margin_pct'), 1),
                'clicks' => (int) $g->sum('click_count'),
                'clicks_per_product' => round($g->sum('click_count') / max(1, $g->count()), 1),
                'units' => (int) $g->sum(fn ($p) => $unitsSold[$p->id]->units ?? 0),
                'is_rfid' => in_array($cat, [ProductCategory::Rfid->value, ProductCategory::IndustrialRfid->value], true),
            ];
        })->sortByDesc('clicks')->values();

        $marginBuckets = collect(['< 10%' => [null, 10], '10–15%' => [10, 15], '15–25%' => [15, 25], '25–35%' => [25, 35], '35%+' => [35, null]])
            ->map(fn ($r) => $liveProducts->filter(fn ($p) => $p->profit_margin_pct !== null
                && ($r[0] === null || $p->profit_margin_pct >= $r[0]) && ($r[1] === null || $p->profit_margin_pct < $r[1]))->count());

        $priceBuckets = collect(['< R1k' => [0, 1000], 'R1k–5k' => [1000, 5000], 'R5k–15k' => [5000, 15000], 'R15k–50k' => [15000, 50000], 'R50k+' => [50000, null]])
            ->map(fn ($r) => $liveProducts->filter(fn ($p) => $p->retail_price_zar !== null
                && $p->retail_price_zar >= $r[0] && ($r[1] === null || $p->retail_price_zar < $r[1]))->count());

        $monthly = Order::query()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['total_zar', 'status', 'created_at'])
            ->groupBy(fn ($o) => $o->created_at->format('Y-m'));
        $months = collect(range(11, 0))->mapWithKeys(function ($i) use ($monthly) {
            $key = now()->subMonths($i)->format('Y-m');
            $g = $monthly->get($key, collect())->where('status', '!=', 'cancelled')->where('status', '!=', 'pending_payment');

            return [$key => ['revenue' => (float) $g->sum('total_zar'), 'orders' => $g->count()]];
        });

        $pipeline = Product::query()->groupBy('status')->selectRaw('status, count(*) c')->pluck('c', 'status');
        $pendingOldest = Product::where('status', 'pending_review')->min('created_at');
        $rejectionReasons = Product::where('status', 'rejected')->whereNotNull('rejection_reason')
            ->get(['rejection_reason'])
            ->map(fn ($p) => mb_strimwidth(trim(strtok($p->rejection_reason, ".\n")), 0, 70, '…'))
            ->countBy()->sortDesc()->take(6);
        $approvedPerWeek = Product::whereNotNull('approved_at')->where('approved_at', '>=', now()->subWeeks(8))
            ->get(['approved_at'])->groupBy(fn ($p) => $p->approved_at->startOfWeek()->format('d M'))->map->count();

        $fx = ExchangeRate::where('currency_pair', 'USDZAR')->first();

        // Herd Management platform.
        $licences = License::query()->selectRaw("
            count(*) total,
            sum(case when user_id is not null and revoked_at is null then 1 else 0 end) active,
            sum(case when user_id is null and revoked_at is null then 1 else 0 end) unused,
            sum(case when revoked_at is not null then 1 else 0 end) revoked")->first();
        $customers = User::where('role', 'customer')
            ->withCount(['animals as herd_count' => fn ($q) => $q->where('in_herd', true)->where('status', 'active'), 'readers', 'catalogues'])
            ->withMax('readers', 'last_synced_at')
            ->get();
        $herd = [
            'customers' => $customers->count(),
            'active7' => Reader::where('last_synced_at', '>=', now()->subDays(7))->distinct('user_id')->count('user_id'),
            'animals' => Animal::where('in_herd', true)->where('status', 'active')->count(),
            'scans30' => Scan::where('scanned_at', '>=', now()->subDays(30))->count(),
            'catalogues' => SaleCatalogue::count(),
            'neverSynced' => $customers->filter(fn ($c) => ! $c->readers_max_last_synced_at)->count(),
            'breeds' => Animal::where('in_herd', true)->whereNotNull('breed')->groupBy('breed')->selectRaw('breed, count(*) c')->orderByDesc('c')->limit(5)->pluck('c', 'breed'),
        ];

        $rfidLive = $categoryMix->where('is_rfid', true)->sum('count');
        $noImages = $liveProducts->where('images_count', 0);
        $lowMargin = $liveProducts->filter(fn ($p) => $p->profit_margin_pct !== null && $p->profit_margin_pct < self::LOW_MARGIN_PCT);
        $staleSupplier = $liveProducts->filter(fn ($p) => ! $p->supplier_last_checked_at || $p->supplier_last_checked_at->lt(now()->subDays(60)));
        $noContact = $liveProducts->filter(fn ($p) => ! $p->supplier_phone && ! $p->supplier_whatsapp && ! $p->supplier_email);
        $lowStock = $liveProducts->filter(fn ($p) => $p->stock_status !== 'in_stock'
            || ($p->stock_quantity !== null && $p->low_stock_threshold !== null && $p->stock_quantity <= $p->low_stock_threshold));
        $stuckOrders = Order::where('status', 'pending_payment')->where('created_at', '<', now()->subDays(2))->count();
        $zeroClickLive = $liveProducts->where('click_count', 0);
        $unusedOld = License::whereNull('user_id')->whereNull('revoked_at')->where('created_at', '<', now()->subDays(30))->count();

        $actions = collect([
            $rfidLive < 20 ? ['high', "Only {$rfidLive} RFID products are live — RFID is now the lead range. Source more readers, tags and accessories.", route('admin.source.create'), 'Source products'] : null,
            ($pipeline['pending_review'] ?? 0) ? ['high', ($pipeline['pending_review']).' products waiting for review'.($pendingOldest ? ' (oldest '.\Carbon\Carbon::parse($pendingOldest)->diffForHumans(short: true).')' : '').'.', route('admin.products.index'), 'Review queue'] : null,
            $stuckOrders ? ['high', "{$stuckOrders} orders stuck on Pending Payment for 2+ days — follow up or cancel.", route('admin.orders.index'), 'Orders'] : null,
            $noImages->count() ? ['high', $noImages->count().' live products have no images.', route('admin.products.live'), 'Live products'] : null,
            $lowMargin->count() ? ['medium', $lowMargin->count().' live products earn under '.self::LOW_MARGIN_PCT.'% margin — reprice or drop.', route('admin.products.live'), 'Live products'] : null,
            $fx && $fx->updated_at->lt(now()->subDays(7)) ? ['medium', 'USD/ZAR rate last updated '.$fx->updated_at->diffForHumans().' — prices may be out of date.', route('admin.settings.edit'), 'Settings'] : null,
            $staleSupplier->count() ? ['medium', $staleSupplier->count().' live products have no supplier check in 60+ days.', route('admin.suppliers.outreach'), 'Supplier outreach'] : null,
            $noContact->count() ? ['low', $noContact->count().' live products have no direct supplier contact on file.', route('admin.suppliers.outreach'), 'Supplier outreach'] : null,
            $lowStock->count() ? ['medium', $lowStock->count().' live products are out of or low on stock.', route('admin.products.live'), 'Live products'] : null,
            $zeroClickLive->count() ? ['low', $zeroClickLive->count().' live products have never been clicked — improve titles/images or archive.', route('admin.products.live'), 'Live products'] : null,
            $herd['neverSynced'] ? ['medium', $herd['neverSynced'].' Herd Management customers have never synced a reader — reach out with setup help.', route('admin.licenses.index'), 'Customers'] : null,
            $unusedOld ? ['low', "{$unusedOld} activation codes unused for 30+ days — check the readers were delivered.", route('admin.licenses.index', ['filter' => 'unused']), 'Licences'] : null,
            (int) ($licences->total ?? 0) === 0 ? ['medium', 'No activation codes generated yet — create one per RFID reader before shipping.', route('admin.licenses.index'), 'Licences'] : null,
        ])->filter()->values();

        return view('admin.insights.index', [
            'kpis' => [
                'live' => $liveProducts->count(),
                'rfidLive' => $rfidLive,
                'revenue30' => $revenue30,
                'orders30' => $orders30,
                'revenueAll' => $revenueAll,
                'ordersAll' => $ordersAll,
                'aov' => $ordersAll ? $revenueAll / $ordersAll : null,
                'avgMargin' => round($liveProducts->avg('profit_margin_pct'), 1),
                'clicks' => (int) $liveProducts->sum('click_count'),
                'reviews' => ProductReview::count(),
                'avgRating' => round((float) ProductReview::avg('rating'), 2),
                'withVideo' => $liveProducts->where('has_video', true)->count(),
            ],
            'actions' => $actions,
            'categoryMix' => $categoryMix,
            'marginBuckets' => $marginBuckets,
            'priceBuckets' => $priceBuckets,
            'topClicked' => $liveProducts->sortByDesc('click_count')->take(10),
            'topSellers' => $unitsSold->sortByDesc('units')->take(10)->map(fn ($r) => ['units' => (int) $r->units, 'revenue' => (float) $r->revenue, 'product' => Product::find($r->product_id)]),
            'months' => $months,
            'provinces' => Order::whereNotIn('status', ['cancelled', 'pending_payment'])->groupBy('province')->selectRaw('province, count(*) c, sum(total_zar) t')->orderByDesc('t')->get(),
            'orderStatus' => Order::groupBy('status')->selectRaw('status, count(*) c')->pluck('c', 'status'),
            'pipeline' => $pipeline,
            'rejectionReasons' => $rejectionReasons,
            'approvedPerWeek' => $approvedPerWeek,
            'fx' => $fx,
            'licences' => $licences,
            'herd' => $herd,
            'customers' => $customers->sortByDesc('herd_count')->take(10),
            'lowMargin' => $lowMargin->sortBy('profit_margin_pct')->take(8),
        ]);
    }
}
