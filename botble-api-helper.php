<?php

/**
 * Botble CMS API Helper
 * 
 * This script helps with generating and using API tokens for Botble CMS
 * to access featured products for SendPortal email templates.
 */

// Disable direct access if not via command line
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    exit('This script can only be executed via command line for security reasons.');
}

// Load Botble environment
require_once __DIR__ . '/vendor/autoload.php';

// Check if we're in a Laravel/Botble environment
if (!function_exists('app') || !class_exists('Illuminate\Foundation\Application')) {
    echo "Error: This script must be run within a Botble CMS environment.\n";
    exit(1);
}

// Function to check if API plugin is installed
function checkApiPlugin() {
    if (file_exists(__DIR__ . '/platform/plugins/api') && class_exists('Botble\Api\Providers\ApiServiceProvider')) {
        return true;
    }
    return false;
}

// Function to check if personal access tokens are available
function checkPersonalAccessTokens() {
    if (class_exists('Laravel\Sanctum\PersonalAccessToken')) {
        return true;
    }
    return false;
}

// Function to generate a token programmatically if possible
function generateApiToken($email, $password) {
    try {
        // Attempt to authenticate the user
        if (!auth()->attempt(['email' => $email, 'password' => $password])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        $user = auth()->user();
        
        // Check if we can create a token
        if (method_exists($user, 'createToken')) {
            // Create token with specific abilities for accessing products
            $token = $user->createToken('sendportal-featured-products', ['products:read']);
            
            return [
                'success' => true,
                'token' => $token->plainTextToken,
                'user' => $user->name
            ];
        } else {
            return [
                'success' => false,
                'message' => 'User model does not support token creation'
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Function to make an API request
function makeApiRequest($url, $method = 'GET', $token = null, $data = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return [
            'success' => false,
            'message' => 'cURL Error: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Main execution
echo "Botble CMS API Helper\n";
echo "=====================\n\n";

// Check API plugin
$apiPluginInstalled = checkApiPlugin();
echo "API Plugin installed: " . ($apiPluginInstalled ? "Yes" : "No") . "\n";

// Check personal access tokens
$personalTokensAvailable = checkPersonalAccessTokens();
echo "Personal Access Tokens available: " . ($personalTokensAvailable ? "Yes" : "No") . "\n\n";

if (!$apiPluginInstalled && !$personalTokensAvailable) {
    echo "Neither the API plugin nor Personal Access Tokens are available.\n";
    echo "Please install the API plugin from the Botble marketplace or via composer:\n";
    echo "composer require botble/api\n\n";
    exit(1);
}

// Options menu
echo "What would you like to do?\n";
echo "1. Generate an API token programmatically\n";
echo "2. Instructions for creating a token via Admin Panel\n";
echo "3. Test an existing API token\n";
echo "4. View example code for SendPortal integration\n";
echo "Choice (1-4): ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        if (!$apiPluginInstalled && !$personalTokensAvailable) {
            echo "Cannot generate token programmatically without API plugin or Personal Access Tokens.\n";
            break;
        }
        
        echo "\nEnter admin email: ";
        $email = trim(fgets(STDIN));
        
        echo "Enter password: ";
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
        
        $result = generateApiToken($email, $password);
        
        if ($result['success']) {
            echo "\nToken generated successfully!\n";
            echo "Token: " . $result['token'] . "\n\n";
            
            // Save token to file
            echo "Do you want to save this token to a file? (y/n): ";
            $saveToFile = strtolower(trim(fgets(STDIN))) === 'y';
            
            if ($saveToFile) {
                $tokenFile = __DIR__ . '/api-token.txt';
                file_put_contents($tokenFile, $result['token']);
                chmod($tokenFile, 0600); // Secure permissions
                echo "Token saved to: $tokenFile\n";
            }
        } else {
            echo "\nFailed to generate token: " . $result['message'] . "\n";
        }
        break;
        
    case '2':
        echo "\nInstructions for creating an API token via Admin Panel:\n";
        echo "1. Log in to your Botble CMS admin panel\n";
        echo "2. Navigate to 'Settings' > 'API Tokens' or 'Personal Access Tokens'\n";
        echo "3. Click 'Create New Token'\n";
        echo "4. Enter a name for your token (e.g., 'SendPortal Featured Products')\n";
        echo "5. Select the necessary permissions (at minimum 'products:read')\n";
        echo "6. Click 'Generate Token'\n";
        echo "7. Copy the generated token and store it securely\n\n";
        break;
        
    case '3':
        echo "\nEnter your API token to test: ";
        $token = trim(fgets(STDIN));
        
        $siteUrl = rtrim(getenv('APP_URL') ?: 'https://sparkscannabis.net', '/');
        
        // Test endpoints
        $endpoints = [
            '/api/v1/me' => 'User profile',
            '/api/v1/products?is_featured=1' => 'Featured products'
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            echo "\nTesting $description...\n";
            $response = makeApiRequest($siteUrl . $endpoint, 'GET', $token);
            
            if ($response['success']) {
                echo "Success! Response:\n";
                echo json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Failed with status code: " . $response['status'] . "\n";
                if (isset($response['data'])) {
                    echo json_encode($response['data'], JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
        break;
        
    case '4':
        echo "\nExample code for SendPortal integration:\n\n";
        
        echo "1. PHP Example (for custom template):\n";
        echo "```php\n";
        echo "<?php\n";
        echo "// Function to fetch featured products from Botble API\n";
        echo "function getFeaturedProducts() {\n";
        echo "    \$apiToken = 'YOUR_API_TOKEN';\n";
        echo "    \$apiUrl = 'https://sparkscannabis.net/api/v1/products?is_featured=1';\n\n";
        echo "    \$ch = curl_init();\n";
        echo "    curl_setopt(\$ch, CURLOPT_URL, \$apiUrl);\n";
        echo "    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        echo "    curl_setopt(\$ch, CURLOPT_HTTPHEADER, [\n";
        echo "        'Authorization: Bearer ' . \$apiToken,\n";
        echo "        'Content-Type: application/json',\n";
        echo "        'Accept: application/json'\n";
        echo "    ]);\n\n";
        echo "    \$response = curl_exec(\$ch);\n";
        echo "    curl_close(\$ch);\n\n";
        echo "    return json_decode(\$response, true);\n";
        echo "}\n\n";
        echo "// Get featured products\n";
        echo "\$featuredProducts = getFeaturedProducts();\n";
        echo "```\n\n";
        
        echo "2. JavaScript Example (for SendPortal template):\n";
        echo "```html\n";
        echo "<script>\n";
        echo "async function fetchFeaturedProducts() {\n";
        echo "    const apiToken = 'YOUR_API_TOKEN';\n";
        echo "    const response = await fetch('https://sparkscannabis.net/api/v1/products?is_featured=1', {\n";
        echo "        method: 'GET',\n";
        echo "        headers: {\n";
        echo "            'Authorization': `Bearer \${apiToken}`,\n";
        echo "            'Content-Type': 'application/json',\n";
        echo "            'Accept': 'application/json'\n";
        echo "        }\n";
        echo "    });\n\n";
        echo "    if (response.ok) {\n";
        echo "        const data = await response.json();\n";
        echo "        return data;\n";
        echo "    } else {\n";
        echo "        console.error('Failed to fetch featured products');\n";
        echo "        return { data: [] };\n";
        echo "    }\n";
        echo "}\n";
        echo "</script>\n";
        echo "```\n\n";
        
        echo "3. SendPortal Template Example:\n";
        echo "```html\n";
        echo "<!-- Featured Products Section -->\n";
        echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" role=\"presentation\">\n";
        echo "    <tr>\n";
        echo "        <td align=\"center\">\n";
        echo "            <h2>Featured Products</h2>\n";
        echo "            <div id=\"featured-products\">\n";
        echo "                <!-- Products will be loaded here via API -->\n";
        echo "                <p>Loading featured products...</p>\n";
        echo "            </div>\n";
        echo "            <script>\n";
        echo "                // Load featured products when email is opened (Note: this may not work in all email clients)\n";
        echo "                document.addEventListener('DOMContentLoaded', async () => {\n";
        echo "                    try {\n";
        echo "                        const result = await fetchFeaturedProducts();\n";
        echo "                        const productsContainer = document.getElementById('featured-products');\n";
        echo "                        \n";
        echo "                        if (result.data && result.data.length > 0) {\n";
        echo "                            let html = '<table width=\"100%\" cellpadding=\"10\" cellspacing=\"0\">';\n";
        echo "                            \n";
        echo "                            result.data.forEach(product => {\n";
        echo "                                html += `\n";
        echo "                                    <tr>\n";
        echo "                                        <td width=\"30%\">\n";
        echo "                                            <img src=\"\${product.image}\" alt=\"\${product.name}\" style=\"max-width:100%\">\n";
        echo "                                        </td>\n";
        echo "                                        <td width=\"70%\">\n";
        echo "                                            <h3>\${product.name}</h3>\n";
        echo "                                            <p>\${product.description}</p>\n";
        echo "                                            <p><strong>\${product.price}</strong></p>\n";
        echo "                                            <a href=\"https://sparkscannabis.net/products/\${product.slug}\" style=\"background:#4CAF50;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;\">View Product</a>\n";
        echo "                                        </td>\n";
        echo "                                    </tr>\n";
        echo "                                    <tr><td colspan=\"2\"><hr></td></tr>\n";
        echo "                                `;\n";
        echo "                            });\n";
        echo "                            \n";
        echo "                            html += '</table>';\n";
        echo "                            productsContainer.innerHTML = html;\n";
        echo "                        } else {\n";
        echo "                            productsContainer.innerHTML = '<p>No featured products available at this time.</p>';\n";
        echo "                        }\n";
        echo "                    } catch (error) {\n";
        echo "                        console.error('Error:', error);\n";
        echo "                    }\n";
        echo "                });\n";
        echo "            </script>\n";
        echo "        </td>\n";
        echo "    </tr>\n";
        echo "</table>\n";
        echo "```\n\n";
        
        echo "Note: For SendPortal, you'll likely need to use their template system and API integration features rather than direct JavaScript, as most email clients block JavaScript execution.\n";
        break;
        
    default:
        echo "\nInvalid choice.\n";
}

echo "\nThank you for using the Botble API Helper!\n";
