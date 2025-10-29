<?php
/**
 * Test User Registration Flow (Container Version)
 * This script runs inside the PHP container and tests the API directly
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing user registration flow (container version)...\n";

// Test registration endpoint directly (without HTTP)
$url = 'http://nginx/v1/register/register';  // Use nginx service name in Docker network

$data = [
    'lang' => 'en',
    'gameWorld' => 1,
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'TestPassword123',
    'termsAndConditions' => true,
    'subscribeNewsletter' => false
];

echo "Sending registration request to: {$url}\n";
echo "Username: {$data['username']}\n";
echo "Email: {$data['email']}\n";

// Enable verbose cURL output
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
echo "Full Response: {$response}\n";
if ($httpCode == 200) {
    echo "\n✓ SUCCESS! Registration worked!\n";
} else {
    echo "\n✗ Registration failed\n";
}

// Write detailed results to file
file_put_contents('/var/www/html/registration-result.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'http_code' => $httpCode,
    'response' => json_decode($response, true),
    'username' => $data['username'],
    'email' => $data['email']
], JSON_PRETTY_PRINT));

$result = json_decode($response, true);

if ($result['success'] ?? false) {
    echo "✓ Registration successful!\n";
    echo "  Username: {$data['username']}\n";
    echo "  Email: {$data['email']}\n";

    // Check database directly
    try {
        require_once __DIR__ . '/sections/api/include/Database/DB.php';
        $db = Database\DB::getInstance();
        $stmt = $db->prepare("SELECT * FROM activation WHERE name = :username");
        $stmt->execute(['username' => $data['username']]);
        $user = $stmt->fetch();

        if ($user) {
            echo "✓ User found in database\n";
            echo "  Token: {$user['token']}\n";
            echo "  Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
        } else {
            echo "✗ User NOT found in database!\n";
        }
    } catch (Exception $e) {
        echo "✗ Database check failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Registration failed\n";
    if (isset($result['error'])) {
        echo "Error: {$result['error']['errorMsg']}\n";
    }
    if (isset($result['data'])) {
        echo "Validation errors:\n";
        print_r($result['data']);
    }
}
