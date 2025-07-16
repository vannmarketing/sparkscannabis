<?php

namespace App\Console\Commands;

use Botble\Ecommerce\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestSchemaCommand extends Command
{
    protected $signature = 'test:schema';
    protected $description = 'Test schema.org generation';

    public function handle()
    {
        // Test with a product
        $product = Product::where('is_variation', 0)
            ->with(['brand', 'categories'])
            ->first();

        if (!$product) {
            $this->error('No products found in the database.');
            return 1;
        }

        $this->info('Testing schema generation for product: ' . $product->name);

        // Test product schema
        try {
            $productSchema = view('partials.schema.product', ['product' => $product])->render();
            $this->info('✓ Product schema generated successfully');
            $this->line($productSchema);
        } catch (\Exception $e) {
            $this->error('✗ Error generating product schema: ' . $e->getMessage());
        }

        // Test organization schema
        try {
            $orgSchema = view('partials.schema.organization')->render();
            $this->info('\n✓ Organization schema generated successfully');
            $this->line($orgSchema);
        } catch (\Exception $e) {
            $this->error('✗ Error generating organization schema: ' . $e->getMessage());
        }

        // Test breadcrumb schema
        try {
            $breadcrumbSchema = view('partials.schema.breadcrumb', ['product' => $product])->render();
            $this->info('\n✓ Breadcrumb schema generated successfully');
            $this->line($breadcrumbSchema);
        } catch (\Exception $e) {
            $this->error('✗ Error generating breadcrumb schema: ' . $e->getMessage());
        }

        return 0;
    }
}
