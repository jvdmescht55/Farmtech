<?php

namespace App\Console\Commands;

use App\Services\MarketPrices;
use Illuminate\Console\Command;

class FetchMarketPrices extends Command
{
    protected $signature = 'market:prices';

    protected $description = 'Fetch the weekly red meat prices from RPO for the market price widgets';

    public function handle(MarketPrices $prices): int
    {
        try {
            $this->info($prices->refresh().' weeks of prices saved.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Could not refresh prices: '.$e->getMessage()); // the last good copy stays in place

            return self::FAILURE;
        }
    }
}
