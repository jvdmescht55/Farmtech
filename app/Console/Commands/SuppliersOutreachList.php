<?php

namespace App\Console\Commands;

use App\Services\SupplierContactExtractor;
use App\Services\SupplierOutreachCurator;
use App\Services\SupplierOutreachMessageBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Hybrid Supplier Outreach Hub — CLI report for the top {--limit=15}
 * problem-solving products (see SupplierOutreachCurator). Every row always
 * has an actionable next step: a direct WhatsApp link when a phone number is
 * on file (manually entered on the admin product page, or auto-found in
 * scraped data — see SupplierContactExtractor::resolve()), otherwise the
 * product's real Alibaba listing URL to paste the same bilingual message
 * into TradeManager. Never a fabricated phone number or listing link.
 */
class SuppliersOutreachList extends Command
{
    protected $signature = 'suppliers:outreach-list {--limit=15 : How many top problem-solving products to list}';
    protected $description = 'Hybrid outreach report for the top problem-solving products: WhatsApp link if a supplier number is on file, otherwise a direct Alibaba chat link — plus the ready-to-paste bilingual message for each.';

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

        $rows = [];
        $withPhone = 0;
        $withAlibabaOnly = 0;

        foreach ($items as $i => $item) {
            $product = $item->product;
            $phone = $contactExtractor->resolve($product);
            $waUrl = $messageBuilder->whatsAppUrl($product);
            $alibabaUrl = $messageBuilder->alibabaChatUrl($product);

            if ($phone) {
                $withPhone++;
                $status = $waUrl;
            } elseif ($alibabaUrl) {
                $withAlibabaOnly++;
                $status = 'Need Phone';
            } else {
                $status = 'Need Phone (no Alibaba link either)';
            }

            $rows[] = [
                'Rank' => $i + 1,
                'Product Name' => Str::limit($product->title, 38),
                'Target Problem' => Str::limit($item->target_problem, 40),
                'Alibaba Chat Link' => $alibabaUrl ? Str::limit($alibabaUrl, 38) : '— none on file —',
                'WhatsApp Status' => Str::limit($status, 42),
            ];
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>Hybrid Supplier Outreach Hub</> — top '.$items->count().' problem-solving products');
        $this->table(['Rank', 'Product Name', 'Target Problem', 'Alibaba Chat Link', 'WhatsApp Status'], $rows);

        $this->newLine();
        $this->line("Direct WhatsApp link: {$withPhone}/{$items->count()}.  Alibaba-only (need phone): {$withAlibabaOnly}/{$items->count()}.");

        $this->newLine();
        $this->line('<fg=green;options=bold>Ready-to-paste bilingual inquiry — one per product below</>');
        $this->line('(Identical template for every supplier; only the name/product change. Paste into WhatsApp or Alibaba TradeManager.)');

        foreach ($items as $i => $item) {
            $product = $item->product;
            $this->newLine();
            $this->line("<fg=yellow>#".($i + 1).' — '.$product->title.'</>');
            $this->line($messageBuilder->message($product));
        }

        return self::SUCCESS;
    }
}
