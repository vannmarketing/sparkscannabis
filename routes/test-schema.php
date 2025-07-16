<?php

use Botble\Ecommerce\Models\Product;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Theme as ThemeFacade;

Route::get('/test-schema', function () {
    // Get a product to test with
    $product = Product::where('is_variation', 0)->with(['brand', 'categories'])->first();
    
    if (!$product) {
        return 'No products found in the database.';
    }
    
    // Render the product schema partial
    $productSchema = view('partials.schema.product', compact('product'))->render();
    
    // Render the organization schema
    $organizationSchema = view('partials.schema.organization')->render();
    
    // Render the breadcrumb schema
    $breadcrumbSchema = view('partials.schema.breadcrumb', compact('product'))->render();
    
    // Output the schemas for inspection
    $output = "<h1>Schema Test</h1>";
    
    $output .= "<h2>Product Schema</h2>";
    $output .= "<pre>" . htmlspecialchars($productSchema) . "</pre>";
    
    $output .= "<h2>Organization Schema</h2>";
    $output .= "<pre>" . htmlspecialchars($organizationSchema) . "</pre>";
    
    $output .= "<h2>Breadcrumb Schema</h2>";
    $output .= "<pre>" . htmlspecialchars($breadcrumbSchema) . "</pre>";
    
    // Also output the raw JSON for easier inspection
    $output .= "<h2>Raw JSON</h2>";
    $output .= "<h3>Product Schema JSON</h3>";
    $output .= "<pre>";
    ob_start();
    include resource_path('views/partials/schema/product.blade.php');
    $output .= ob_get_clean();
    $output .= "</pre>";
    
    return $output;
})->name('test-schema');
