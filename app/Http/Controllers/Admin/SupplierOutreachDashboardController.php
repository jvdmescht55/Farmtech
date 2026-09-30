<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SupplierOutreachMessageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "One-click" supplier outreach dashboard — the browsable worklist for
 * chasing listing videos. Rows are live products that still have neither a
 * video on file nor a supplier WhatsApp number, each with a copy-the-script
 * + open-the-real-listing action (no message is ever sent automatically —
 * the admin pastes and sends it themselves in Alibaba/WhatsApp) and an
 * inline field to record whatever comes back.
 */
class SupplierOutreachDashboardController extends Controller
{
    public function index(SupplierOutreachMessageBuilder $messageBuilder)
    {
        $products = Product::query()
            ->storefrontVisible()
            ->where('has_video', false)
            ->where(fn ($q) => $q->whereNull('supplier_whatsapp')->orWhere('supplier_whatsapp', ''))
            ->orderBy('title')
            ->get();

        $rows = $products->map(fn (Product $product) => (object) [
            'product' => $product,
            'listing_url' => trim((string) $product->source_url) ?: null,
            'script' => $messageBuilder->demoVideoMessage($product),
        ]);

        return view('admin.outreach.index', [
            'rows' => $rows,
            'totalLive' => Product::storefrontVisible()->count(),
        ]);
    }

    /**
     * Record what came back from an outreach chat: one free-text field that's
     * routed by shape — a URL lands in video_url, anything else is treated as
     * a WhatsApp/phone number and lands in supplier_whatsapp. Either way the
     * row drops off the worklist on the next load.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        $value = trim($data['reply']);

        if (Str::startsWith(Str::lower($value), ['http://', 'https://'])) {
            $product->video_url = $value; // has_video synced by Product::saving hook
            $saved = 'listing video URL';
        } else {
            $product->supplier_whatsapp = $value;
            $saved = 'supplier WhatsApp number';
        }

        $product->save();

        return redirect()
            ->route('admin.outreach.index')
            ->with('status', "Saved {$saved} for “{$product->title}”.");
    }
}
