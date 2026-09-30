<?php

namespace App\Console\Commands;

use App\Services\SupplierContactExtractor;
use App\Services\SupplierOutreachCurator;
use App\Services\SupplierOutreachMessageBuilder;
use Illuminate\Console\Command;

/**
 * Supplier WhatsApp Outreach Hub — CLI report for the top {--limit=15}
 * highest-margin, value-dense, problem-solving products (see
 * SupplierOutreachCurator), each with a 1-click wa.me link pre-filled with
 * the bilingual raw-asset request (see SupplierOutreachMessageBuilder).
 *
 * A product only gets a link when a real supplier phone/WhatsApp number was
 * actually found in its own scraped data (see SupplierContactExtractor) —
 * real Alibaba scrapes rarely expose one (Alibaba routes contact through its
 * own encrypted messaging), so "no number found" for most rows is the
 * honest, expected result, not a bug — never a fabricated number.
 */
class SuppliersWhatsappLinks extends Command
{
    protected $signature = 'suppliers:whatsapp-links {--limit=15 : How many top problem-solving products to list}';
    protected $description = 'Lists the top problem-solving products with a 1-click WhatsApp link to ask their supplier for raw marketing/technical assets.';

    public function handle(
        SupplierOutreachCurator $curator,
        SupplierContactExtractor $contactExtractor,
        SupplierOutreachMessageBuilder $messageBuilder,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $items = $curator->topProblemSolvers($limit);

        if ($items->isEmpty()) {
            $this->warn('No active products currently match the outreach-hub curation criteria (margin >= '
                .SupplierOutreachCurator::MIN_MARGIN_PCT.'%, weight < 25kg, one of the target problem categories).');

            return self::SUCCESS;
        }

        $found = 0;
        $rows = $items->values()->map(function ($item, int $i) use ($contactExtractor, $messageBuilder, &$found) {
            $product = $item->product;
            $phone = $contactExtractor->extract($product);
            $link = $messageBuilder->whatsAppUrl($product);

            if ($phone) {
                $found++;
            }

            return [
                '#' => $i + 1,
                'Product Name' => \Illuminate\Support\Str::limit($product->title, 42),
                'Target Problem' => \Illuminate\Support\Str::limit($item->target_problem, 44),
                'Supplier Phone' => $phone ?? '— not found —',
                '1-Click WhatsApp Link' => $link ?? '— no supplier number on file —',
            ];
        });

        $this->newLine();
        $this->line('<fg=green;options=bold>Supplier WhatsApp Outreach Hub</> — top '.$items->count().' problem-solving products');
        $this->table(['#', 'Product Name', 'Target Problem', 'Supplier Phone', '1-Click WhatsApp Link'], $rows->toArray());

        $this->newLine();
        $this->line("Supplier contact on file: {$found}/{$items->count()}.");

        if ($found < $items->count()) {
            $this->comment(
                'Most listings never carry a real supplier phone number — Alibaba routes buyer/supplier '
                .'contact through its own encrypted messaging by design. A number only turns up here when a '
                .'seller pasted one directly into a spec value or the description (e.g. "Whatsapp for '
                .'Discount"). Rows without one need a manually-sourced number before they can be reached this way.'
            );
        }

        return self::SUCCESS;
    }
}
