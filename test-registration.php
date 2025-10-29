<?php
/**
 * FIX-03: Test User Registration
 * Tests the registration API endpoint and database insertion
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "=== Testing User Registration ===\n\n";

// Test registration endpoint (inside container, use Nginx service/container name)
$url = 'http://travian-nginx/v1/register/register';

// Username max 15 chars, so use shorter format
$username = 'test' . substr(time(), -6);  // test123456 = 10 chars
$email = 'test_' . time() . '@example.com';

$data = [
    'lang' => 'en',  // Use 'en' instead of 'international'
    'gameWorld' => 1,
    'username' => $username,
    'email' => $email,
    'password' => 'TestPassword123',
    'termsAndConditions' => 1,  // Send as 1 instead of boolean
    'subscribeNewsletter' => 0
];

echo "Attempting registration...\n";
echo "  Username: {$username}\n";
echo "  Email: {$email}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

$result = json_decode($response, true);

if ($result['success'] ?? false) {
    echo "✓ Registration successful!\n";
    echo "  Username: {$username}\n";
    echo "  Email: {$email}\n\n";
    
    // Check database
    echo "Verifying database entry...\n";
    require_once __DIR__ . '/sections/api/include/Database/DB.php';
    $db = Database\DB::getInstance();
    $stmt = $db->prepare("SELECT * FROM activation WHERE name = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ User found in activation table\n";
        echo "  Token: {$user['token']}\n";
        echo "  Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
        echo "  World ID: {$user['wid']}\n\n";
        echo "=== Registration Test PASSED ===\n";
    } else {
        echo "✗ User NOT found in database!\n";
        echo "=== Registration Test FAILED ===\n";
        exit(1);
    }
} else {
    echo "✗ Registration failed\n";
    if (isset($result['error'])) {
        echo "Error Type: " . ($result['error']['errorType'] ?? 'unknown') . "\n";
        echo "Error Message: " . ($result['error']['errorMsg'] ?? 'unknown') . "\n";
    }
    print_r($result);
    echo "\n=== Registration Test FAILED ===\n";
    exit(1);
}
