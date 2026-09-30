<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SupplierContactExtractor;
use App\Services\SupplierOutreachCurator;
use App\Services\SupplierOutreachMessageBuilder;

/**
 * Admin-facing mirror of `php artisan suppliers:outreach-list` — same
 * curation/contact-resolution services, browsable with clickable links and a
 * one-tap copy button instead of a terminal table.
 */
class SupplierOutreachController extends Controller
{
    public function index(
        SupplierOutreachCurator $curator,
        SupplierContactExtractor $contactExtractor,
        SupplierOutreachMessageBuilder $messageBuilder,
    ) {
        $rows = $curator->topProblemSolvers(15)->map(function ($item, $i) use ($contactExtractor, $messageBuilder) {
            $product = $item->product;
            $phone = $contactExtractor->resolve($product);

            return (object) [
                'rank' => $i + 1,
                'product' => $product,
                'target_problem' => $item->target_problem,
                'phone' => $phone,
                'whatsapp_url' => $messageBuilder->whatsAppUrl($product),
                'alibaba_url' => $messageBuilder->alibabaChatUrl($product),
                'message' => $messageBuilder->message($product),
            ];
        });

        return view('admin.suppliers.outreach', [
            'rows' => $rows,
            'withPhoneCount' => $rows->filter(fn ($r) => $r->phone)->count(),
        ]);
    }
}
