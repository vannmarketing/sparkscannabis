<?php

require __DIR__ . '/bootstrap/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get a product URL to test
$product = \Botble\Ecommerce\Models\Product::where('is_variation', 0)->first();

if ($product) {
    $url = route('public.product', $product->slug);
    echo "Testing product URL: $url\n\n";
    
    // Make a request to the product page
    $client = new \GuzzleHttp\Client();
    $response = $client->get($url);
    $html = (string) $response->getBody();
    
    // Check for schema.org JSON-LD
    if (preg_match_all('/<script\s+[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        echo "Found " . count($matches[0]) . " schema.org JSON-LD scripts\n\n";
        
        foreach ($matches[1] as $i => $json) {
            echo "--- Schema #" . ($i + 1) . " ---\n";
            echo $json . "\n\n";
        }
    } else {
        echo "No schema.org JSON-LD scripts found in the page.\n";
        
        // Check if our schema partials are being included
        if (strpos($html, 'schema.org/Product') !== false) {
            echo "Product schema is present but not in JSON-LD format.\n";
        } else {
            echo "No product schema found in the page.\n";
        }
    }
} else {
    echo "No products found in the database.\n";
}
