<?php
/**
 * Test Activation API - Tests /v1/register/activate endpoint
 */

echo "=== ACTIVATION API TEST ===\n\n";

$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("SELECT * FROM activation WHERE used = 0 ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ NO REGISTERED USERS FOUND!\n";
    exit(1);
}

echo "📋 User to Activate:\n";
echo "  Username: {$user['name']}\n";
echo "  WorldId: {$user['worldId']}\n";
echo "  Activation Code: {$user['activationCode']}\n\n";

echo "=== Calling Activation API ===\n";
echo "Note: Will likely fail due to captcha requirement\n\n";

$activateUrl = 'http://nginx/v1/register/activate';
$activateData = [
    'gameWorld' => $user['worldId'],
    'activationCode' => $user['activationCode'],
    'password' => 'TestPass123!',
    'captcha' => 'test-bypass',
    'lang' => 'en'
];

$ch = curl_init($activateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($activateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
$decoded = json_decode($response, true);
echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";

// Check result
if ($httpCode == 200 && isset($decoded['data']['success']) && $decoded['data']['success']) {
    echo "✅ ACTIVATION SUCCESSFUL!\n";
    if (isset($decoded['data']['redirect'])) {
        echo "Redirect: {$decoded['data']['redirect']}\n";
    }
} else {
    echo "⚠️ ACTIVATION FAILED or requires additional steps\n";
    if (isset($decoded['data']['fields'])) {
        echo "Errors: " . json_encode($decoded['data']['fields']) . "\n";
    }
}
