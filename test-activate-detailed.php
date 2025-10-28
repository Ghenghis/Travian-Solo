<?php
/**
 * Detailed activation test with full response
 */

echo "=== DETAILED ACTIVATION TEST ===\n\n";

// First, register a new user for testing
echo "Step 1: Register new test user\n";
$regUrl = 'http://nginx/v1/register/register';
$timestamp = time();
$regData = [
    'gameWorld' => 1,
    'username' => 'acttest' . substr($timestamp, -4),
    'email' => 'acttest' . $timestamp . '@test.com',
    'password' => 'TestPass123!',
    'termsAndConditions' => true,
    'subscribeNewsletter' => false,
    'lang' => 'en'
];

$ch = curl_init($regUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($regData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);
echo "Registration Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Get the activation code
$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare("SELECT * FROM activation WHERE name = :name");
$stmt->execute(['name' => $regData['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ERROR: User not found after registration!\n";
    exit(1);
}

echo "Step 2: Activate user\n";
echo "Username: {$user['name']}\n";
echo "Activation Code: {$user['activationCode']}\n\n";

$actUrl = 'http://nginx/v1/register/activate';
$actData = [
    'gameWorld' => $user['worldId'],
    'activationCode' => $user['activationCode'],
    'password' => 'TestPass123!',
    'captcha' => 'test',
    'lang' => 'en'
];

$ch = curl_init($actUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($actData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Activation HTTP Code: $httpCode\n";
$decoded = json_decode($response, true);
echo "Activation Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Check results
echo "Step 3: Verify Results\n";
$stmt = $pdo->prepare("SELECT * FROM activation WHERE name = :name");
$stmt->execute(['name' => $user['name']]);
$updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Global Activation Table:\n";
echo "  Used: " . ($updatedUser['used'] ? 'YES' : 'NO') . "\n\n";

$worldDb = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $worldDb->prepare("SELECT * FROM activation WHERE name = :name");
$stmt->execute(['name' => $user['name']]);
$worldUser = $stmt->fetch(PDO::FETCH_ASSOC);

echo "World Activation Table:\n";
if ($worldUser) {
    echo "  ✓ User found!\n";
    echo "  Token: {$worldUser['token']}\n";
} else {
    echo "  ✗ User NOT found\n";
}
