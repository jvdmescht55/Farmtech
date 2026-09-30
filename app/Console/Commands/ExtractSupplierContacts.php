<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\SupplierContactExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fills the direct-contact columns on `products` (supplier_whatsapp,
 * supplier_wechat_id, supplier_email) from contact strings a supplier has
 * pasted into the product's *own already-stored* spec/description text — the
 * one place Alibaba scrapes ever expose them (see SupplierContactExtractor).
 *
 * This makes NO network calls. It does not fetch Alibaba company_profile /
 * contactinfo pages — those are auth-walled, anti-bot protected, and
 * scraping them breaches Alibaba's ToS. It only re-reads what the catalog
 * already holds. A column that already has a human-entered value is never
 * touched, and nothing is ever guessed — "no contact found" for most rows is
 * the honest, expected result.
 */
class ExtractSupplierContacts extends Command
{
    protected $signature = 'suppliers:extract-contacts
        {--status=all : Product status to scan (all, approved, pending_review, ...)}
        {--live-only : Restrict to currently storefront-visible (approved + is_active) listings}
        {--sku= : Only scan the product with this SKU}
        {--dry-run : Show what would be filled without writing to the database}';

    protected $description = 'Populate supplier WhatsApp/WeChat/email columns from contact strings already present in each product\'s stored spec/description text (no network calls).';

    public function handle(SupplierContactExtractor $extractor): int
    {
        $status = (string) $this->option('status');
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($this->option('live-only')) {
            $query->where('status', 'approved')->where('is_active', true);
        }

        if ($sku = $this->option('sku')) {
            $query->where('sku', $sku);
        }

        $products = $query->orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->warn('No products matched the given filters.');

            return self::SUCCESS;
        }

        $this->line(($dryRun ? '<fg=yellow>[DRY RUN] </>' : '')
            ."Scanning {$products->count()} product(s) for embedded supplier contact strings...");

        $rows = [];
        $updated = 0;

        foreach ($products as $product) {
            $found = [
                'supplier_whatsapp' => $this->blank($product->supplier_whatsapp) ? $extractor->extract($product) : null,
                'supplier_wechat_id' => $this->blank($product->supplier_wechat_id) ? $extractor->wechatId($product) : null,
                'supplier_email' => $this->blank($product->supplier_email) ? $extractor->email($product) : null,
            ];

            $found = array_filter($found, fn ($v) => $v !== null && $v !== '');

            if ($found === []) {
                continue;
            }

            if (! $dryRun) {
                $product->fill($found)->save();
            }

            $updated++;
            $rows[] = [
                'SKU' => $product->sku,
                'Product' => Str::limit($product->title, 34),
                'WhatsApp' => $found['supplier_whatsapp'] ?? '—',
                'WeChat' => $found['supplier_wechat_id'] ?? '—',
                'Email' => Str::limit($found['supplier_email'] ?? '—', 30),
            ];
        }

        $this->newLine();

        if ($rows === []) {
            $this->info('No new contact details were found in stored product text. Nothing to update.');
        } else {
            $this->line('<fg=green;options=bold>'.($dryRun ? 'Would update' : 'Updated')." {$updated} product(s) with direct contact details</>");
            $this->table(['SKU', 'Product', 'WhatsApp', 'WeChat', 'Email'], $rows);
        }

        $withAny = Product::where(fn ($q) => $q
            ->whereNotNull('supplier_whatsapp')->where('supplier_whatsapp', '!=', '')
            ->orWhere(fn ($q) => $q->whereNotNull('supplier_wechat_id')->where('supplier_wechat_id', '!=', ''))
            ->orWhere(fn ($q) => $q->whereNotNull('supplier_email')->where('supplier_email', '!=', ''))
            ->orWhere(fn ($q) => $q->whereNotNull('supplier_phone')->where('supplier_phone', '!=', ''))
        )->count();

        $this->newLine();
        $this->line("Catalog total with at least one direct contact field on file: {$withAny}/".Product::count());

        if ($dryRun) {
            $this->comment('Dry run — no rows were written. Re-run without --dry-run to persist.');
        }

        return self::SUCCESS;
    }

    private function blank(?string $value): bool
    {
        return trim((string) $value) === '';
    }
}
