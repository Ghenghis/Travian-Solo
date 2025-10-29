<?php
/**
 * Test Login Flow
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing login flow...\n";

// First, register a test user if not already registered
echo "Creating test user...\n";
require_once __DIR__ . '/sections/api/include/Database/DB.php';

$globalDb = Database\DB::getInstance();

// Generate unique username
$username = 'logintest_' . time();
$password = 'TestPass123';
$email = 'logintest_' . time() . '@example.com';

// Insert into activation table (simulating registration)
$stmt = $globalDb->prepare("
    INSERT INTO activation (wid, name, password, email, token, time)
    VALUES (:wid, :name, :password, :email, :token, :time)
");

$result = $stmt->execute([
    'wid' => 1,
    'name' => $username,
    'password' => sha1($password),
    'email' => $email,
    'token' => md5(uniqid()),
    'time' => time()
]);

if ($result) {
    echo "✓ Test user created in activation table\n";
} else {
    echo "✗ Failed to create test user\n";
    exit(1);
}

// Now test login
echo "Testing login...\n";
$url = 'http://nginx/v1/auth/login';

$data = [
    'lang' => 'international',
    'gameWorldId' => 1,
    'usernameOrEmail' => $username,
    'password' => $password,
    'lowResMode' => false
];

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

if (isset($result['redirect'])) {
    echo "✓ Login successful!\n";
    echo "  Redirect URL: {$result['redirect']}\n";
} else {
    echo "✗ Login failed\n";
    print_r($result);
    
    // Let's also check if we can connect directly to test the database
    echo "\nChecking database connection directly...\n";
    try {
        require_once __DIR__ . '/sections/api/include/Database/DB.php';
        $db = Database\DB::getInstance();
        echo "✓ Database connection successful\n";
        
        // Check if user exists
        $stmt = $db->prepare("SELECT * FROM activation WHERE name = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✓ User found in database\n";
            echo "  Password in DB: " . $user['password'] . "\n";
            echo "  SHA1 of test password: " . sha1($password) . "\n";
        } else {
            echo "✗ User NOT found in database!\n";
        }
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    }
}
