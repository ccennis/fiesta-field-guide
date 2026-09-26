<?php

namespace App\Console\Commands;

use App\Enums\AliasDecision;
use App\Enums\ListingSource;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\Sources\ListingRulingService;
use Illuminate\Console\Command;

/**
 * Gives every product with a mapped store name the store's wording. Mapping
 * does this on its own; this catches up products mapped before it did.
 */
class AdoptStoreNames extends Command
{
    protected $signature = 'fiesta:adopt-store-names';

    protected $description = "Rename products that have a mapped store name to the store's wording";

    public function handle(ListingRulingService $rulings): int
    {
        $source = ListingSource::FiestaFactoryDirect;

        $productIds = ProductAlias::where('source', $source)
            ->where('decision', AliasDecision::Mapped)
            ->distinct()
            ->pluck('product_id');

        $renamed = 0;

        foreach (Product::whereIn('id', $productIds)->orderBy('name')->get() as $product) {
            $result = $rulings->adoptStoreName($source, $product);

            if ($result['to'] !== null) {
                $this->line("  \"{$result['from']}\" -> \"{$result['to']}\"");
                $renamed++;
            }

            if ($result['conflict'] !== null) {
                $this->warn("  {$product->name}: {$result['conflict']}");
            }
        }

        $this->info("Renamed {$renamed} ".($renamed === 1 ? 'product' : 'products').'.');

        return self::SUCCESS;
    }
}
