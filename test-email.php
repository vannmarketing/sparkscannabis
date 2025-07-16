<?php

// This script tests sending emails with Laravel Mail facade using SendGrid SMTP

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

echo "=== SendGrid SMTP Email Testing Tool ===\n\n";

// Display current mail configuration
$mailDriver = config('mail.default');
$mailHost = config('mail.mailers.smtp.host');
$mailPort = config('mail.mailers.smtp.port');
$mailEncryption = config('mail.mailers.smtp.encryption');
$mailUsername = config('mail.mailers.smtp.username');
$fromAddress = config('mail.from.address');
$fromName = config('mail.from.name');

echo "Current mail configuration:\n";
echo "- Driver: $mailDriver\n";
echo "- Host: $mailHost\n";
echo "- Port: $mailPort\n";
echo "- Encryption: $mailEncryption\n";
echo "- Username: " . ($mailUsername ? "Configured" : "Not configured") . "\n";
echo "- From Address: $fromAddress\n";
echo "- From Name: $fromName\n\n";

// Test email address
$testEmail = 'vandavidn@vann.ca'; // You can change this or make it a parameter

// 1. Test using Laravel Mail facade
echo "1. Testing with Laravel Mail facade...\n";
try {
    Mail::raw('This is a test email from Laravel using SendGrid SMTP.', function (Message $message) use ($testEmail) {
        $message->to($testEmail)
                ->subject('Test Email from Laravel');
    });
    echo "✓ Email sent successfully using Laravel Mail facade!\n\n";
} catch (\Exception $e) {
    echo "✗ Error sending email with Laravel Mail facade: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// 2. Test password reset email
echo "2. Testing password reset email...\n";
try {
    $status = Password::broker()->sendResetLink(['email' => $testEmail]);
    
    if ($status === Password::RESET_LINK_SENT) {
        echo "✓ Password reset email sent successfully!\n\n";
    } else {
        echo "✗ Failed to send password reset email. Status: $status\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error sending password reset email: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Provide recommendations for SendGrid
echo "=== Troubleshooting Tips for SendGrid SMTP ===\n";
echo "1. Verify your SendGrid API key has full access to 'Mail Send' permissions.\n";
echo "2. Check that your 'From' email address is verified in SendGrid.\n";
echo "3. Ensure your SendGrid account is active and not suspended.\n";
echo "4. Check for any SendGrid sending limits you might be hitting.\n";
echo "5. Verify the password reset template is properly formatted.\n\n";

// Check for specific SendGrid configuration issues
echo "=== SendGrid Configuration Check ===\n";
if ($mailHost !== 'smtp.sendgrid.net') {
    echo "⚠️ Your SMTP host is not set to 'smtp.sendgrid.net'. This should be corrected.\n";
}

if ($mailPort != 587 && $mailPort != 465) {
    echo "⚠️ Your SMTP port should typically be 587 (TLS) or 465 (SSL) for SendGrid.\n";
}

if ($mailEncryption !== 'tls' && $mailEncryption !== 'ssl') {
    echo "⚠️ Your SMTP encryption should be 'tls' or 'ssl' for SendGrid.\n";
}

if (!$mailUsername) {
    echo "⚠️ Your SMTP username is not configured. For SendGrid, this should be 'apikey'.\n";
}

echo "\nDone testing emails.\n";
