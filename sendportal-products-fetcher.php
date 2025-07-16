<?php
/**
 * SendPortal Products Fetcher for Sparks Cannabis
 * 
 * This script fetches featured products from the Botble CMS API
 * and formats them for use in SendPortal email templates.
 */

// Configuration
$config = [
    'api_token' => '', // Your API token goes here
    'api_base_url' => 'https://sparkscannabis.net/api/v1',
    'products_endpoint' => '/products',
    'featured_tag' => 'featured',
    'limit' => 5, // Number of products to fetch
    'output_file' => __DIR__ . '/storage/sendportal/featured_products.html',
    'cache_duration' => 3600, // Cache duration in seconds (1 hour)
    'cache_file' => __DIR__ . '/storage/sendportal/featured_products_cache.json',
];

/**
 * Fetch featured products from the API
 * 
 * @param array $config Configuration array
 * @return array Products data
 */
function fetchFeaturedProducts($config) {
    // Check if we have a valid cached response
    if (file_exists($config['cache_file'])) {
        $cacheData = json_decode(file_get_contents($config['cache_file']), true);
        if ($cacheData && isset($cacheData['timestamp']) && 
            (time() - $cacheData['timestamp'] < $config['cache_duration'])) {
            return $cacheData['products'];
        }
    }
    
    // Build the API URL with query parameters
    $apiUrl = $config['api_base_url'] . $config['products_endpoint'] . '?';
    $queryParams = [
        'is_featured' => 1,
        'status' => 'published',
        'limit' => $config['limit'],
        'include' => 'categories,tags',
    ];
    $apiUrl .= http_build_query($queryParams);
    
    // Set up the cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['api_token'],
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($error || $httpCode !== 200) {
        error_log("API Error: $error, HTTP Code: $httpCode");
        return [];
    }
    
    // Parse the response
    $data = json_decode($response, true);
    $products = isset($data['data']) ? $data['data'] : [];
    
    // Cache the results
    if (!empty($products)) {
        $cacheDir = dirname($config['cache_file']);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($config['cache_file'], json_encode([
            'timestamp' => time(),
            'products' => $products
        ]));
    }
    
    return $products;
}

/**
 * Generate HTML for featured products
 * 
 * @param array $products Array of product data
 * @return string HTML content
 */
function generateProductsHtml($products) {
    if (empty($products)) {
        return '<p>No featured products available at this time.</p>';
    }
    
    // Start building the HTML
    $html = <<<HTML
<!-- Featured Products Start -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse;">
    <tr>
        <td>
            <h2 style="color: #4CAF50; text-align: center; margin: 20px 0;">Featured Products</h2>
        </td>
    </tr>
HTML;
    
    // Add each product
    foreach ($products as $product) {
        // Extract product data
        $name = htmlspecialchars($product['name'] ?? 'Product Name');
        $price = $product['price'] ?? '$0.00';
        $originalPrice = isset($product['original_price']) && $product['original_price'] != $price 
            ? $product['original_price'] : null;
        
        // Format description
        $description = $product['short_description'] ?? $product['description'] ?? '';
        $description = strip_tags($description);
        $shortDescription = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
        
        // Get image URL
        $imageUrl = isset($product['image']) && !empty($product['image']) 
            ? $product['image'] 
            : 'https://sparkscannabis.net/placeholder.jpg';
        
        // Product URL
        $productUrl = 'https://sparkscannabis.net/products/' . ($product['slug'] ?? '');
        
        // Sale badge
        $saleBadge = $originalPrice ? '<span style="background-color: #ff6b6b; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; position: absolute; top: 10px; right: 10px;">SALE</span>' : '';
        
        // Price display
        $priceDisplay = $originalPrice 
            ? '<span style="text-decoration: line-through; color: #999; margin-right: 10px;">' . $originalPrice . '</span> <span style="color: #ff6b6b; font-weight: bold;">' . $price . '</span>' 
            : '<span style="font-weight: bold;">' . $price . '</span>';
        
        // Generate product HTML
        $html .= <<<HTML
    <tr>
        <td style="padding: 15px 0; border-bottom: 1px solid #e8e8e8;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="30%" valign="top" style="padding-right: 15px; position: relative;">
                        $saleBadge
                        <img src="$imageUrl" alt="$name" width="100%" style="max-width: 150px; display: block; border: 0; border-radius: 5px;">
                    </td>
                    <td width="70%" valign="top">
                        <h3 style="margin-top: 0; margin-bottom: 8px; color: #333333; font-size: 18px;">$name</h3>
                        <p style="margin-top: 0; margin-bottom: 8px; color: #666666; font-size: 14px;">$shortDescription</p>
                        <p style="margin-top: 0; margin-bottom: 12px; font-size: 16px;">$priceDisplay</p>
                        <a href="$productUrl" style="background-color: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 14px;">View Product</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
HTML;
    }
    
    // Close the HTML
    $html .= <<<HTML
    <tr>
        <td style="padding: 15px 0; text-align: center;">
            <a href="https://sparkscannabis.net/products" style="color: #4CAF50; text-decoration: underline; font-size: 14px;">View All Products</a>
        </td>
    </tr>
</table>
<!-- Featured Products End -->
HTML;
    
    return $html;
}

/**
 * Save HTML to file for SendPortal to use
 * 
 * @param string $html HTML content
 * @param string $outputFile Path to output file
 * @return bool Success status
 */
function saveHtmlToFile($html, $outputFile) {
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    return file_put_contents($outputFile, $html) !== false;
}

/**
 * Main execution
 */
function main($config) {
    // Check if token is provided
    if (empty($config['api_token'])) {
        echo "Error: API token is required. Please set it in the script configuration.\n";
        return false;
    }
    
    // Fetch featured products
    $products = fetchFeaturedProducts($config);
    
    if (empty($products)) {
        echo "Warning: No featured products found or API error occurred.\n";
        // Still generate empty HTML
        $html = '<p>No featured products available at this time.</p>';
    } else {
        echo "Success: Found " . count($products) . " featured products.\n";
        // Generate HTML
        $html = generateProductsHtml($products);
    }
    
    // Save HTML to file
    if (saveHtmlToFile($html, $config['output_file'])) {
        echo "Success: HTML saved to " . $config['output_file'] . "\n";
        echo "You can now include this file in your SendPortal templates.\n";
        return true;
    } else {
        echo "Error: Failed to save HTML to file.\n";
        return false;
    }
}

// Run the script if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // Check if running from command line
    $isCli = (php_sapi_name() === 'cli');
    
    // If not CLI, check for basic auth or restrict access
    if (!$isCli) {
        // Simple security check - this should be improved in production
        if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'sendportal' || $_SERVER['PHP_AUTH_PW'] !== 'secure_password') {
            header('WWW-Authenticate: Basic realm="SendPortal Integration"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Authentication required';
            exit;
        }
        
        // Set content type for browser output
        header('Content-Type: text/plain');
    }
    
    // Allow token to be passed as parameter in CLI mode
    if ($isCli && isset($argv[1])) {
        $config['api_token'] = $argv[1];
    }
    
    // Allow token to be passed as GET parameter in web mode
    if (!$isCli && isset($_GET['token'])) {
        $config['api_token'] = $_GET['token'];
    }
    
    // Run the main function
    $result = main($config);
    
    // Set exit code for CLI
    if ($isCli) {
        exit($result ? 0 : 1);
    }
}
