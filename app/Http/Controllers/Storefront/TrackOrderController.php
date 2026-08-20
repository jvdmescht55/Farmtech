<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index()
    {
        return view('storefront.track.index');
    }

    /**
     * Guest lookup by order_number + email — there are no customer accounts
     * in this app, so this pair is the only credential a real customer has.
     * POST rather than GET so the email never lands in a URL, browser
     * history, or referrer header.
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = Order::where('order_number', trim($validated['order_number']))
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($validated['email']))])
            ->with('items')
            ->first();

        if (! $order) {
            return back()
                ->withInput($validated)
                ->withErrors(['order_number' => "We couldn't find an order matching that order number and email address."]);
        }

        return view('storefront.track.show', ['order' => $order]);
    }
}
