<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

/** Admin-only (see the view-financials Gate) — a 30-day sales/cost/profit summary. */
class DashboardController extends Controller
{
    public function index()
    {
        $since = now()->subDays(30);

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->with('items.product')
            ->get();

        $totalSalesZar = (float) $orders->sum('subtotal_zar');

        $totalFreightCustomsZar = 0.0;
        $totalLandedCostZar = 0.0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $freightCustoms = (float) ($product->intl_freight_zar ?? 0)
                    + (float) ($product->customs_vat_zar ?? 0)
                    + (float) ($product->domestic_delivery_zar ?? 0);

                $totalFreightCustomsZar += $freightCustoms * $item->quantity;
                $totalLandedCostZar += (float) ($product->landed_cost_zar ?? 0) * $item->quantity;
            }
        }

        return view('admin.dashboard.index', [
            'since' => $since,
            'orderCount' => $orders->count(),
            'totalSalesZar' => $totalSalesZar,
            'totalFreightCustomsZar' => $totalFreightCustomsZar,
            'totalNetProfitZar' => $totalSalesZar - $totalLandedCostZar,
        ]);
    }
}
