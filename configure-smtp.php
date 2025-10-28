<?php
/**
 * SMTP Configuration Helper
 * Helps configure production email settings
 */

echo "=========================================\n";
echo "  SMTP CONFIGURATION HELPER\n";
echo "=========================================\n\n";

// Check if running in Docker
$inDocker = file_exists('/.dockerenv');

echo "Environment: " . ($inDocker ? "Docker Container" : "Host System") . "\n\n";

// Read current .env file
$envFile = __DIR__ . '/.env';
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

echo "=== Current SMTP Configuration ===\n\n";

$smtpVars = [
    'SMTP_HOST' => 'SMTP server hostname',
    'SMTP_PORT' => 'SMTP port (usually 587 for TLS, 465 for SSL)',
    'SMTP_USERNAME' => 'SMTP username (usually your email)',
    'SMTP_PASSWORD' => 'SMTP password or app password',
    'SMTP_ENCRYPTION' => 'Encryption type (tls or ssl)',
    'SMTP_FROM_ADDRESS' => 'From email address',
    'SMTP_FROM_NAME' => 'From name (e.g., "Your Game Name")'
];

foreach ($smtpVars as $var => $description) {
    if (preg_match("/^{$var}=(.*)$/m", $envContent, $matches)) {
        $value = trim($matches[1]);
        // Mask password
        if ($var === 'SMTP_PASSWORD' && !empty($value)) {
            $value = str_repeat('*', min(strlen($value), 12));
        }
        echo "✓ {$var}: {$value}\n";
        echo "  ({$description})\n\n";
    } else {
        echo "✗ {$var}: NOT SET\n";
        echo "  ({$description})\n\n";
    }
}

// Popular SMTP provider templates
echo "\n=== Popular SMTP Provider Settings ===\n\n";

$providers = [
    'Gmail' => [
        'SMTP_HOST' => 'smtp.gmail.com',
        'SMTP_PORT' => '587',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Requires App Password if 2FA enabled. Get from: https://myaccount.google.com/apppasswords'
    ],
    'SendGrid' => [
        'SMTP_HOST' => 'smtp.sendgrid.net',
        'SMTP_PORT' => '587',
        'SMTP_USERNAME' => 'apikey',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Password is your SendGrid API key. Get from: https://app.sendgrid.com/settings/api_keys'
    ],
    'Mailgun' => [
        'SMTP_HOST' => 'smtp.mailgun.org',
        'SMTP_PORT' => '587',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Use SMTP credentials from Mailgun dashboard'
    ],
    'AWS SES' => [
        'SMTP_HOST' => 'email-smtp.[region].amazonaws.com',
        'SMTP_PORT' => '587',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Replace [region] with your AWS region. Use SMTP credentials from SES console'
    ],
    'Outlook/Office365' => [
        'SMTP_HOST' => 'smtp.office365.com',
        'SMTP_PORT' => '587',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Use your Office365 email and password'
    ],
    'Custom SMTP' => [
        'SMTP_HOST' => 'smtp.your-provider.com',
        'SMTP_PORT' => '587',
        'SMTP_ENCRYPTION' => 'tls',
        'notes' => 'Use settings provided by your email provider'
    ]
];

$index = 1;
foreach ($providers as $name => $config) {
    echo "{$index}. {$name}\n";
    foreach ($config as $key => $value) {
        if ($key === 'notes') {
            echo "   Note: {$value}\n";
        } else {
            echo "   {$key}: {$value}\n";
        }
    }
    echo "\n";
    $index++;
}

// Test SMTP connection function
echo "=== SMTP Connection Test ===\n\n";

function testSMTPConnection($host, $port, $username, $password, $encryption) {
    echo "Testing connection to {$host}:{$port}...\n";
    
    // Basic socket test
    $timeout = 10;
    $errno = 0;
    $errstr = '';
    
    if ($encryption === 'ssl') {
        $host = 'ssl://' . $host;
    }
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        fclose($socket);
        echo "✓ Connection successful!\n";
        echo "  Server is reachable on port {$port}\n\n";
        return true;
    } else {
        echo "✗ Connection failed\n";
        echo "  Error: {$errstr} ({$errno})\n\n";
        return false;
    }
}

// Test current configuration if available
$currentHost = getenv('SMTP_HOST');
$currentPort = getenv('SMTP_PORT') ?: '587';
$currentEncryption = getenv('SMTP_ENCRYPTION') ?: 'tls';

if ($currentHost) {
    echo "Testing current SMTP configuration...\n";
    testSMTPConnection($currentHost, $currentPort, '', '', $currentEncryption);
} else {
    echo "No SMTP configuration found in environment.\n";
    echo "Set SMTP_HOST in .env to test connection.\n\n";
}

// Generate example .env configuration
echo "=== Example .env Configuration ===\n\n";

echo "# Copy these lines to your .env file and replace with your values:\n\n";
echo "# SMTP Configuration (Production Email)\n";
echo "SMTP_HOST=smtp.your-provider.com\n";
echo "SMTP_PORT=587\n";
echo "SMTP_USERNAME=your-email@example.com\n";
echo "SMTP_PASSWORD=your-password-or-app-password\n";
echo "SMTP_ENCRYPTION=tls\n";
echo "SMTP_FROM_ADDRESS=noreply@your-domain.com\n";
echo "SMTP_FROM_NAME=\"Your Game Name\"\n\n";

// Instructions to switch from Mock to Real email
echo "=== Switching from MockEmailService to Real Email ===\n\n";

echo "1. Configure SMTP settings in .env (see above)\n\n";

echo "2. Update RegisterCtrl.php:\n";
echo "   Change:\n";
echo "     use Core\\MockEmailService;\n";
echo "     MockEmailService::sendActivationMail(...);\n\n";
echo "   To:\n";
echo "     use Core\\EmailService;\n";
echo "     EmailService::sendActivationMail(...);\n\n";

echo "3. Restart PHP container:\n";
echo "   docker-compose restart php\n\n";

echo "4. Test email sending:\n";
echo "   docker-compose exec php php /var/www/html/test-email-system.php\n\n";

// Check if EmailService exists
$emailServicePath = __DIR__ . '/sections/api/include/Core/EmailService.php';
if (file_exists($emailServicePath)) {
    echo "✓ EmailService.php exists and is ready\n";
} else {
    echo "✗ EmailService.php not found at: {$emailServicePath}\n";
    echo "  You may need to create it or check the path\n";
}

echo "\n=========================================\n";
echo "  Configuration Complete!\n";
echo "=========================================\n\n";

echo "Next Steps:\n";
echo "1. Choose your SMTP provider from the list above\n";
echo "2. Add the settings to your .env file\n";
echo "3. Update code to use EmailService instead of MockEmailService\n";
echo "4. Test with test-email-system.php\n";
echo "5. Register a test user and verify email delivery\n\n";

echo "For more details, see: PRODUCTION-DEPLOYMENT.md\n";
