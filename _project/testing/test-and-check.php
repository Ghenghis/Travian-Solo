<?php
/**
 * Register and immediately check database
 */

echo "=== REGISTRATION TEST ===\n";

$url = 'http://nginx/v1/register/register';
$data = [
    'gameWorld' => 1,
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'TestPassword123!',
    'termsAndConditions' => true,
    'subscribeNewsletter' => false,
    'lang' => 'en'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Username: {$data['username']}\n";
echo "Email: {$data['email']}\n";
echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n\n";

echo "=== DATABASE CHECK ===\n";

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM activation WHERE name = :username ORDER BY id DESC LIMIT 1");
    $stmt->execute(['username' => $data['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✓ USER FOUND IN DATABASE!\n";
        echo "  ID: {$user['id']}\n";
        echo "  WorldId: {$user['worldId']}\n";
        echo "  Username: {$user['name']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Activation Code: {$user['activationCode']}\n";
        echo "  Used: " . ($user['used'] ? 'YES' : 'NO') . "\n";
        echo "  Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
    } else {
        echo "✗ USER NOT FOUND IN DATABASE!\n";
        
        // Show last 3 registrations
        $stmt = $pdo->query("SELECT * FROM activation ORDER BY id DESC LIMIT 3");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent) > 0) {
            echo "\nLast 3 registrations:\n";
            foreach ($recent as $r) {
                echo "  - {$r['name']} ({$r['email']}) - " . date('Y-m-d H:i:s', $r['time']) . "\n";
            }
        } else {
            echo "\nNo users in activation table at all!\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
