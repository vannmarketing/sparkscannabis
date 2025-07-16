<?php
/**
 * SendPortal Integration Examples for Botble CMS API
 * 
 * This file contains examples for integrating Botble CMS API with SendPortal
 * to display featured products in email templates.
 */

/**
 * Example 1: Pre-processing approach
 * 
 * This approach involves fetching featured products before sending the email
 * and then injecting the HTML into your SendPortal template.
 */

/**
 * Function to fetch featured products from Botble CMS API
 * 
 * @param string $apiToken Your Botble CMS API token
 * @param int $limit Maximum number of products to fetch (default: 5)
 * @return array Array of featured products
 */
function getFeaturedProducts($apiToken, $limit = 5) {
    $apiUrl = 'https://sparkscannabis.net/api/v1/products?is_featured=1&limit=' . $limit;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('API Error: ' . $error);
        return [];
    }
    
    $data = json_decode($response, true);
    return isset($data['data']) ? $data['data'] : [];
}

/**
 * Generate HTML for featured products to include in email template
 * 
 * @param array $products Array of product data
 * @return string HTML content for email
 */
function generateFeaturedProductsHtml($products) {
    if (empty($products)) {
        return '<p>No featured products available at this time.</p>';
    }
    
    $html = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse;">';
    
    foreach ($products as $product) {
        // Extract product data
        $name = htmlspecialchars($product['name'] ?? 'Product Name');
        $price = $product['price'] ?? '$0.00';
        $description = htmlspecialchars($product['description'] ?? '');
        $shortDescription = substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '');
        $imageUrl = $product['image'] ?? 'https://sparkscannabis.net/placeholder.jpg';
        $productUrl = 'https://sparkscannabis.net/products/' . ($product['slug'] ?? '');
        
        // Generate product HTML
        $html .= '
        <tr>
            <td style="padding: 20px 0; border-bottom: 1px solid #e8e8e8;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="30%" valign="top" style="padding-right: 15px;">
                            <img src="' . $imageUrl . '" alt="' . $name . '" width="100%" style="max-width: 150px; display: block; border: 0;">
                        </td>
                        <td width="70%" valign="top">
                            <h3 style="margin-top: 0; margin-bottom: 10px; color: #333333; font-size: 18px;">' . $name . '</h3>
                            <p style="margin-top: 0; margin-bottom: 10px; color: #666666; font-size: 14px;">' . $shortDescription . '</p>
                            <p style="margin-top: 0; margin-bottom: 15px; color: #000000; font-size: 16px; font-weight: bold;">' . $price . '</p>
                            <a href="' . $productUrl . '" style="background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 14px;">View Product</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>';
    }
    
    $html .= '</table>';
    return $html;
}

/**
 * Example usage for pre-processing approach
 */
function examplePreProcessing() {
    // Your API token from Botble CMS
    $apiToken = 'YOUR_API_TOKEN';
    
    // Fetch featured products
    $featuredProducts = getFeaturedProducts($apiToken, 3);
    
    // Generate HTML for email template
    $productsHtml = generateFeaturedProductsHtml($featuredProducts);
    
    // Now you can use $productsHtml in your SendPortal template
    // by replacing a placeholder like {{featured_products}} with this HTML
    
    return $productsHtml;
}

/**
 * Example 2: SendPortal Custom Template Variables
 * 
 * SendPortal allows you to define custom template variables.
 * This example shows how to create a custom variable for featured products.
 */

/**
 * Register a custom template variable for SendPortal
 * 
 * This code would be placed in your Laravel application that uses SendPortal
 */
function registerSendPortalCustomVariable() {
    // Example code for registering a custom variable in SendPortal
    // This would go in a service provider or similar setup file
    
    /*
    use Sendportal\Base\Facades\Sendportal;
    
    Sendportal::registerTemplateVariable('featured_products', function ($subscriber = null) {
        $apiToken = config('services.botble.api_token');
        $featuredProducts = getFeaturedProducts($apiToken, 3);
        return generateFeaturedProductsHtml($featuredProducts);
    });
    */
    
    // Note: The above code is conceptual and depends on your SendPortal setup
}

/**
 * Example 3: Using an API Proxy
 * 
 * If SendPortal can't directly access your API, you can create a proxy endpoint
 * that fetches the products and returns them in a format SendPortal can use.
 */

/**
 * Example API proxy endpoint
 * 
 * This would be a route in your Laravel application
 */
function apiProxyExample() {
    // Example route definition
    // Route::get('/api/sendportal/featured-products', 'SendPortalController@getFeaturedProducts');
    
    // Example controller method
    /*
    public function getFeaturedProducts()
    {
        $apiToken = config('services.botble.api_token');
        $featuredProducts = $this->fetchFeaturedProducts($apiToken, 3);
        
        // Return JSON response that SendPortal can consume
        return response()->json([
            'products' => $featuredProducts
        ]);
    }
    
    private function fetchFeaturedProducts($apiToken, $limit)
    {
        // Implementation similar to getFeaturedProducts() above
    }
    */
}

/**
 * Example 4: SendPortal Email Template Example
 * 
 * This shows how your SendPortal email template might look
 * with the featured products section included.
 */
function sendPortalTemplateExample() {
    $template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Sparks Cannabis Newsletter</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 20px; text-align: center; background-color: #4CAF50; border-top-left-radius: 5px; border-top-right-radius: 5px;">
                            <img src="https://sparkscannabis.net/logo.png" alt="Sparks Cannabis" width="200" style="display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    
                    <!-- Email Content -->
                    <tr>
                        <td style="padding: 20px;">
                            <h1 style="color: #333333; margin-top: 0;">Hello {{ subscriber.first_name }},</h1>
                            <p style="color: #666666; font-size: 16px; line-height: 1.5;">Check out our featured products this week:</p>
                            
                            <!-- Featured Products Section -->
                            <div style="margin: 30px 0;">
                                <!-- This is where your featured products will appear -->
                                {{ featured_products }}
                            </div>
                            
                            <p style="color: #666666; font-size: 16px; line-height: 1.5;">Don't miss out on these great deals!</p>
                            <p style="color: #666666; font-size: 16px; line-height: 1.5;">Thank you for being a valued customer.</p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px; text-align: center; background-color: #f9f9f9; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; color: #666666; font-size: 14px;">
                            <p>&copy; {{ date('Y') }} Sparks Cannabis. All rights reserved.</p>
                            <p>
                                <a href="{{ unsubscribe_url }}" style="color: #4CAF50; text-decoration: none;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    
    return $template;
}

/**
 * Example 5: Webhook Integration
 * 
 * If SendPortal supports webhooks, you can set up a webhook that triggers
 * before an email is sent to inject the featured products.
 */
function webhookExample() {
    // Example webhook handler
    /*
    public function handleSendPortalWebhook(Request $request)
    {
        $emailContent = $request->input('content');
        $apiToken = config('services.botble.api_token');
        
        // Fetch featured products
        $featuredProducts = $this->getFeaturedProducts($apiToken);
        $productsHtml = $this->generateFeaturedProductsHtml($featuredProducts);
        
        // Replace placeholder with actual content
        $modifiedContent = str_replace('{{featured_products}}', $productsHtml, $emailContent);
        
        return response()->json([
            'content' => $modifiedContent
        ]);
    }
    */
}

/**
 * Example 6: Creating a SendPortal Plugin
 * 
 * For advanced integration, you might want to create a custom SendPortal plugin
 * that handles the API integration with Botble CMS.
 */
function pluginExample() {
    // This would be a more complex implementation that extends SendPortal
    // The specifics depend on how SendPortal's plugin system works
}

// Example of how to save the API token securely in your .env file
// Add this to your .env file:
// BOTBLE_API_TOKEN=your_api_token_here

// Then in your config/services.php file:
/*
'botble' => [
    'api_token' => env('BOTBLE_API_TOKEN'),
    'api_url' => env('BOTBLE_API_URL', 'https://sparkscannabis.net/api/v1'),
],
*/
