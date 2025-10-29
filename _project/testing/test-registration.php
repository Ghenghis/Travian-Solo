<?php
/**
 * Test User Registration Flow
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing user registration flow...\n";

// Test registration endpoint
$url = 'http://nginx/v1/register/register';

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
    echo "  Username: {$data['username']}\n";
    echo "  Email: {$data['email']}\n";
    
    // Check database
    require_once __DIR__ . '/sections/api/include/Database/DB.php';
    
    // Load environment variables for database connection
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }
    
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
} else {
    echo "✗ Registration failed\n";
    print_r($result);
    
    // Let's also check if we can connect directly to test the database
    echo "\nChecking database connection directly...\n";
    try {
        require_once __DIR__ . '/sections/api/include/Database/DB.php';
        $db = Database\DB::getInstance();
        echo "✓ Database connection successful\n";
        
        // Check if tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'activation'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Activation table exists\n";
        } else {
            echo "✗ Activation table does not exist\n";
        }
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    }
}
