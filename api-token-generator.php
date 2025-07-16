<?php

/**
 * Botble CMS API Token Generator
 * 
 * This script generates an API token that can be used to access the Botble CMS API
 * for retrieving products with specific tags like "featured" for use in SendPortal email templates.
 */

// Disable direct access to this file
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    exit('This script can only be executed via command line.');
}

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

// Function to make API request
function makeApiRequest($url, $method = 'POST', $data = [], $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $defaultHeaders = ['Content-Type: application/json'];
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch) . PHP_EOL;
        return null;
    }
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Get user credentials
echo "Enter your admin email: ";
$email = trim(fgets(STDIN));

echo "Enter your password: ";
system('stty -echo');
$password = trim(fgets(STDIN));
system('stty echo');
echo PHP_EOL;

// Get site URL
$siteUrl = rtrim(getenv('APP_URL') ?: 'https://sparkscannabis.net', '/');
$apiEndpoint = "$siteUrl/api/v1/auth/login";

// Make login request
$response = makeApiRequest($apiEndpoint, 'POST', [
    'email' => $email,
    'password' => $password
]);

// Check response
if (!$response || $response['status'] !== 200 || empty($response['body']['data']['token'])) {
    echo "Failed to generate API token. Error: " . json_encode($response) . PHP_EOL;
    exit(1);
}

// Extract and display token
$token = $response['body']['data']['token'];
echo "API Token generated successfully!" . PHP_EOL;
echo "Token: $token" . PHP_EOL;
echo PHP_EOL;

// Display example usage for featured products
echo "Example usage to get featured products:" . PHP_EOL;
echo "curl -X GET \"$siteUrl/api/v1/products?is_featured=1\" \\" . PHP_EOL;
echo "  -H \"Authorization: Bearer $token\" \\" . PHP_EOL;
echo "  -H \"Content-Type: application/json\"" . PHP_EOL;
echo PHP_EOL;

// Save token to file (optional)
echo "Do you want to save this token to a file? (y/n): ";
$saveToFile = strtolower(trim(fgets(STDIN))) === 'y';

if ($saveToFile) {
    $tokenFile = __DIR__ . '/api-token.txt';
    file_put_contents($tokenFile, $token);
    echo "Token saved to: $tokenFile" . PHP_EOL;
    
    // Set secure permissions
    chmod($tokenFile, 0600);
}

echo PHP_EOL;
echo "For SendPortal integration, use this token in your API requests to fetch featured products." . PHP_EOL;
