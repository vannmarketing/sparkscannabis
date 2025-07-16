<?php

// This script fixes common SendGrid SMTP configuration issues

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SendGrid SMTP Configuration Fix Tool ===\n\n";

// Check current configuration
$mailDriver = config('mail.default');
$mailHost = config('mail.mailers.smtp.host');
$mailPort = config('mail.mailers.smtp.port');
$mailEncryption = config('mail.mailers.smtp.encryption');
$mailUsername = config('mail.mailers.smtp.username');
$mailPassword = config('mail.mailers.smtp.password');
$fromAddress = config('mail.from.address');
$fromName = config('mail.from.name');

echo "Current mail configuration:\n";
echo "- Driver: $mailDriver\n";
echo "- Host: $mailHost\n";
echo "- Port: $mailPort\n";
echo "- Encryption: " . ($mailEncryption ?: "Not set") . "\n";
echo "- Username: " . ($mailUsername ? "Configured" : "Not configured") . "\n";
echo "- Password: " . ($mailPassword ? "Configured" : "Not configured") . "\n";
echo "- From Address: $fromAddress\n";
echo "- From Name: $fromName\n\n";

// Check for issues
$issues = [];

if ($mailHost !== 'smtp.sendgrid.net') {
    $issues[] = "SMTP host is not set to 'smtp.sendgrid.net'";
}

if ($mailPort != 587 && $mailPort != 465) {
    $issues[] = "SMTP port should be 587 (TLS) or 465 (SSL)";
}

if (empty($mailEncryption)) {
    $issues[] = "SMTP encryption is not set (should be 'tls' for port 587 or 'ssl' for port 465)";
}

if ($mailUsername !== 'apikey') {
    $issues[] = "SMTP username should be 'apikey' for SendGrid";
}

if (empty($mailPassword)) {
    $issues[] = "SMTP password (SendGrid API key) is not configured";
}

// Display issues
if (count($issues) > 0) {
    echo "Found " . count($issues) . " issues with your SendGrid configuration:\n";
    foreach ($issues as $index => $issue) {
        echo ($index + 1) . ". $issue\n";
    }
    echo "\n";
} else {
    echo "Your SendGrid configuration looks good. The issue might be elsewhere.\n\n";
}

// Offer to fix issues
echo "Would you like to fix these issues? (yes/no): ";
$fix = trim(readline());

if (strtolower($fix) === 'yes') {
    // Read .env file
    $envPath = __DIR__ . '/.env';
    $envContent = file_get_contents($envPath);
    $updated = false;
    
    // Fix SMTP host
    if ($mailHost !== 'smtp.sendgrid.net') {
        $envContent = preg_replace('/MAIL_HOST=.*/', 'MAIL_HOST=smtp.sendgrid.net', $envContent);
        $updated = true;
    }
    
    // Fix SMTP port
    if ($mailPort != 587) {
        $envContent = preg_replace('/MAIL_PORT=.*/', 'MAIL_PORT=587', $envContent);
        $updated = true;
    }
    
    // Fix SMTP encryption
    if (empty($mailEncryption)) {
        if (preg_match('/MAIL_ENCRYPTION=.*/', $envContent)) {
            $envContent = preg_replace('/MAIL_ENCRYPTION=.*/', 'MAIL_ENCRYPTION=tls', $envContent);
        } else {
            $envContent .= "\nMAIL_ENCRYPTION=tls";
        }
        $updated = true;
    }
    
    // Fix SMTP username
    if ($mailUsername !== 'apikey') {
        $envContent = preg_replace('/MAIL_USERNAME=.*/', 'MAIL_USERNAME=apikey', $envContent);
        $updated = true;
    }
    
    // Ask for SendGrid API key if needed
    if (empty($mailPassword)) {
        echo "Enter your SendGrid API key: ";
        $apiKey = trim(readline());
        
        if (!empty($apiKey)) {
            if (preg_match('/MAIL_PASSWORD=.*/', $envContent)) {
                $envContent = preg_replace('/MAIL_PASSWORD=.*/', 'MAIL_PASSWORD=' . $apiKey, $envContent);
            } else {
                $envContent .= "\nMAIL_PASSWORD=" . $apiKey;
            }
            $updated = true;
        }
    }
    
    // Save changes
    if ($updated) {
        file_put_contents($envPath, $envContent);
        echo "Configuration updated successfully.\n";
        
        // Clear config cache
        echo "Clearing configuration cache...\n";
        Artisan::call('config:clear');
        echo Artisan::output();
        
        echo "Done. Please restart your web server for changes to take effect.\n";
    } else {
        echo "No changes were made.\n";
    }
} else {
    echo "No changes were made.\n";
}

// Test connection
echo "\nWould you like to test the connection to SendGrid? (yes/no): ";
$test = trim(readline());

if (strtolower($test) === 'yes') {
    echo "Testing connection to SendGrid SMTP server...\n";
    
    $fp = @fsockopen('smtp.sendgrid.net', 587, $errno, $errstr, 30);
    if (!$fp) {
        echo "❌ Failed to connect to SendGrid SMTP server: $errstr ($errno)\n";
        echo "This could indicate a network issue or firewall blocking outbound connections.\n";
    } else {
        echo "✅ Successfully connected to SendGrid SMTP server.\n";
        fclose($fp);
    }
}

echo "\nDone.\n";
