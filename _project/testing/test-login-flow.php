<?php
/**
 * Test Login Flow
 * Tests the login API endpoint with registered user
 */

echo "=== LOGIN FLOW TEST ===\n\n";

// First, check if we have a registered user to test with
$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Get the most recent registered user
$stmt = $pdo->query("SELECT * FROM activation WHERE used = 0 ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ NO REGISTERED USERS FOUND!\n";
    echo "Please run test-response-detail.php to create a test user first.\n";
    exit(1);
}

echo "📋 Testing with user:\n";
echo "  Username: {$user['name']}\n";
echo "  Email: {$user['email']}\n";
echo "  WorldId: {$user['worldId']}\n";
echo "  Used: " . ($user['used'] ? 'YES' : 'NO') . "\n\n";

// Test 1: Login API endpoint
echo "=== TEST 1: Login with Username ===\n";

$loginUrl = 'http://nginx/v1/auth/login';
$loginData = [
    'gameWorldId' => $user['worldId'], // Required: game world ID
    'usernameOrEmail' => $user['name'], // Required: username or email
    'password' => 'TestPass123!', // The password we used in registration
    'lang' => 'en'
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
$decoded = json_decode($response, true);
echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Login with Email
echo "=== TEST 2: Login with Email ===\n";

$loginData['usernameOrEmail'] = $user['email']; // Use email instead of username

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
$decoded = json_decode($response, true);
echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Check if login endpoint exists
echo "=== TEST 3: Verify Login Endpoint Exists ===\n";
echo "Checking if LoginCtrl.php exists...\n";

if (file_exists('/var/www/html/sections/api/include/Api/Ctrl/LoginCtrl.php')) {
    echo "✓ LoginCtrl.php exists\n";
} else {
    echo "✗ LoginCtrl.php NOT FOUND!\n";
    echo "Location: sections/api/include/Api/Ctrl/LoginCtrl.php\n";
}

// Test 4: Check login method exists
if (file_exists('/var/www/html/sections/api/include/Api/Ctrl/LoginCtrl.php')) {
    $content = file_get_contents('/var/www/html/sections/api/include/Api/Ctrl/LoginCtrl.php');
    if (strpos($content, 'function login') !== false) {
        echo "✓ login() method found in LoginCtrl\n";
    } else {
        echo "✗ login() method NOT FOUND in LoginCtrl\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Registration user available: YES\n";
echo "Login endpoint tested: YES\n";
echo "HTTP response received: " . ($httpCode ? "YES ($httpCode)" : "NO") . "\n";
