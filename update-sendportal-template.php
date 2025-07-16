<?php
/**
 * Update SendPortal Template with Featured Products
 * 
 * This script fetches featured products from Botble CMS API and updates
 * the SendPortal email template with the latest products.
 * 
 * Usage:
 * - Set up as a cron job to run daily or weekly
 * - php update-sendportal-template.php YOUR_API_TOKEN
 */

// Configuration
$config = [
    'api_token' => '', // Will be populated from command line or .env
    'api_base_url' => 'https://sparkscannabis.net/api/v1',
    'products_endpoint' => '/products',
    'template_file' => __DIR__ . '/sendportal-featured-products-template.html',
    'output_file' => __DIR__ . '/storage/sendportal/featured-products-email.html',
    'placeholder' => '<!-- FEATURED_PRODUCTS_PLACEHOLDER -->',
    'limit' => 3, // Number of featured products to show
    'log_file' => __DIR__ . '/storage/logs/sendportal-update.log',
];

/**
 * Write to log file
 * 
 * @param string $message Message to log
 * @param array $config Configuration array
 */
function writeLog($message, $config) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    $logDir = dirname($config['log_file']);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
    
    // If running in CLI, also output to console
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

/**
 * Fetch featured products from the API
 * 
 * @param array $config Configuration array
 * @return array Products data
 */
function fetchFeaturedProducts($config) {
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
        writeLog("API Error: $error, HTTP Code: $httpCode", $config);
        return [];
    }
    
    // Parse the response
    $data = json_decode($response, true);
    $products = isset($data['data']) ? $data['data'] : [];
    
    writeLog("Fetched " . count($products) . " featured products from API", $config);
    return $products;
}

/**
 * Generate HTML for featured products
 * 
 * @param array $products Array of product data
 * @return string HTML content
 */
function generateProductsHtml($products, $config) {
    if (empty($products)) {
        writeLog("No products found, generating empty HTML", $config);
        return '<p style="text-align: center; color: #666;">No featured products available at this time.</p>';
    }
    
    // Start building the HTML
    $html = <<<HTML
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
                    <td width="30%" valign="top" style="padding-right: 15px; position: relative;" class="mobile-stack mobile-center">
                        $saleBadge
                        <img src="$imageUrl" alt="$name" width="100%" style="max-width: 150px; display: block; border: 0; border-radius: 5px;" class="product-image">
                    </td>
                    <td width="70%" valign="top" class="mobile-stack mobile-center">
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
HTML;
    
    return $html;
}

/**
 * Update the template file with featured products
 * 
 * @param string $templateFile Path to template file
 * @param string $outputFile Path to output file
 * @param string $productsHtml HTML content for products
 * @param string $placeholder Placeholder to replace
 * @return bool Success status
 */
function updateTemplate($templateFile, $outputFile, $productsHtml, $placeholder, $config) {
    // Check if template file exists
    if (!file_exists($templateFile)) {
        writeLog("Template file not found: $templateFile", $config);
        return false;
    }
    
    // Read template content
    $templateContent = file_get_contents($templateFile);
    if ($templateContent === false) {
        writeLog("Failed to read template file", $config);
        return false;
    }
    
    // Replace placeholder with products HTML
    $updatedContent = str_replace($placeholder, $productsHtml, $templateContent);
    
    // Create output directory if it doesn't exist
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Write updated content to output file
    $result = file_put_contents($outputFile, $updatedContent);
    if ($result === false) {
        writeLog("Failed to write output file: $outputFile", $config);
        return false;
    }
    
    writeLog("Template updated successfully: $outputFile", $config);
    return true;
}

/**
 * Main execution
 */
function main($config) {
    writeLog("Starting SendPortal template update", $config);
    
    // Check if token is provided
    if (empty($config['api_token'])) {
        writeLog("Error: API token is required", $config);
        return false;
    }
    
    // Fetch featured products
    $products = fetchFeaturedProducts($config);
    
    // Generate HTML for products
    $productsHtml = generateProductsHtml($products, $config);
    
    // Update template
    $result = updateTemplate(
        $config['template_file'],
        $config['output_file'],
        $productsHtml,
        $config['placeholder'],
        $config
    );
    
    writeLog("Template update " . ($result ? "completed successfully" : "failed"), $config);
    return $result;
}

// Get API token from command line argument or .env file
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $config['api_token'] = $argv[1];
} else {
    // Try to load from .env file
    if (file_exists(__DIR__ . '/.env')) {
        $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (strpos($line, 'BOTBLE_API_TOKEN=') === 0) {
                $config['api_token'] = trim(substr($line, strlen('BOTBLE_API_TOKEN=')));
                break;
            }
        }
    }
}

// Run the main function
$exitCode = main($config) ? 0 : 1;

// Exit with appropriate code if running from CLI
if (php_sapi_name() === 'cli') {
    exit($exitCode);
}
