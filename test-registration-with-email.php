<?php
/**
 * Test Registration with Email Logging
 */

echo "=== REGISTRATION WITH EMAIL TEST ===\n\n";

// Clear email log
require __DIR__ . '/sections/api/include/Core/MockEmailService.php';
use Core\MockEmailService;
MockEmailService::clearLog();

// Register new user
$regUrl = 'http://nginx/v1/register/register';
$timestamp = time();
$username = 'emailtest' . substr($timestamp, -4);
$email = 'emailtest' . $timestamp . '@test.com';

$regData = [
    'gameWorld' => 1,
    'username' => $username,
    'email' => $email,
    'password' => 'TestPass123!',
    'termsAndConditions' => true,
    'subscribeNewsletter' => false,
    'lang' => 'en'
];

echo "Registering user: $username\n";
echo "Email: $email\n\n";

$ch = curl_init($regUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($regData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$decoded = json_decode($response, true);
echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Check if email was logged
echo "=== EMAIL LOG CHECK ===\n";
$logs = MockEmailService::getLoggedEmails();

if (strpos($logs, $email) !== false) {
    echo "✅ SUCCESS! Activation email was logged:\n\n";
    echo $logs;
} else {
    echo "⚠️ No email found in logs\n";
    echo "This might mean:\n";
    echo "  1. Registration failed\n";
    echo "  2. Email service not being called\n";
    echo "  3. Email logging to different location\n\n";
    echo "Current logs:\n";
    echo $logs;
}

// Verify user in database
echo "\n=== DATABASE VERIFICATION ===\n";
$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare("SELECT * FROM activation WHERE email = :email ORDER BY id DESC LIMIT 1");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✓ User found in database\n";
    echo "  Username: {$user['name']}\n";
    echo "  Activation Code: {$user['activationCode']}\n";
    echo "  Email should have been sent with this code!\n";
} else {
    echo "✗ User NOT found in database\n";
}
