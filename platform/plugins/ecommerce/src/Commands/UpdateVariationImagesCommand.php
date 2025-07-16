<?php

namespace Botble\Ecommerce\Commands;

use Botble\Ecommerce\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateVariationImagesCommand extends Command
{
    protected $signature = 'ecommerce:update-variation-images';

    protected $description = 'Update all variation product images to match their parent product images';

    public function handle(): int
    {
        $this->info('Starting to update variation product images...');

        $variations = Product::query()
            ->where('is_variation', 1)
            ->with(['variationInfo.configurableProduct'])
            ->get();

        $count = 0;
        $total = $variations->count();
        
        $this->output->progressStart($total);

        foreach ($variations as $variation) {
            $parentProduct = $variation->original_product;
            
            // Skip if parent product not found or is the same as the variation
            if (!$parentProduct || $parentProduct->id === $variation->id) {
                $this->output->progressAdvance();
                continue;
            }
            
            // Update the variation image to match parent image
            $variation->image = $parentProduct->image;
            $variation->images = $parentProduct->images;
            $variation->save();
            
            $count++;
            $this->output->progressAdvance();
        }
        
        $this->output->progressFinish();
        $this->info("Successfully updated images for {$count} variation products out of {$total}.");

        return self::SUCCESS;
    }
}
