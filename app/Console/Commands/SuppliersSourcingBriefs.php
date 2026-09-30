<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\SupplierOutreachMessageBuilder;
use Illuminate\Console\Command;

/**
 * Prints the bilingual (English + 简体中文) first-contact sourcing brief for
 * every research-candidate product — the ready-to-paste inquiry an admin
 * sends to a shortlisted supplier (OEM video drive link, app/SDK docs,
 * sample DDP air rate, tiered price, MOQ, lead time, + one category-specific
 * ask). See SupplierOutreachMessageBuilder::sourcingBriefMessage().
 */
class SuppliersSourcingBriefs extends Command
{
    protected $signature = 'suppliers:sourcing-briefs
        {--tier=research_candidate : verification_tier to target}
        {--sku= : Only print the brief for this SKU}';

    protected $description = 'Print the bilingual supplier sourcing brief for each research-candidate product (OEM video, SDK/app, sample DDP air rate).';

    public function handle(SupplierOutreachMessageBuilder $builder): int
    {
        $query = Product::query()->where('verification_tier', $this->option('tier'));

        if ($sku = $this->option('sku')) {
            $query->where('sku', $sku);
        }

        $products = $query->orderBy('category')->orderBy('title')->get();

        if ($products->isEmpty()) {
            $this->warn('No products matched (verification_tier='.$this->option('tier').').');

            return self::SUCCESS;
        }

        $this->info("Bilingual sourcing briefs — {$products->count()} product(s)");

        foreach ($products as $i => $product) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>#'.($i + 1).' — ['.$product->sku.'] '.$product->title.'</>');
            $this->line('<fg=gray>'.$product->category->label().'</>');
            $this->newLine();
            $this->line($builder->sourcingBriefMessage($product));
            $this->line(str_repeat('─', 72));
        }

        return self::SUCCESS;
    }
}
