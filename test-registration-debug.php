<?php
/**
 * Debug Registration Test - Shows full payload and response
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "=== DEBUG Registration Test ===\n\n";

$url = 'http://travian-nginx/v1/register/register';

$username = 'testuser_' . time();
$email = 'test_' . time() . '@example.com';

$data = [
    'lang' => 'en',
    'gameWorld' => 1,
    'username' => $username,
    'email' => $email,
    'password' => 'TestPassword123',
    'termsAndConditions' => true,  // Try boolean true
    'subscribeNewsletter' => false
];

echo "Payload being sent:\n";
print_r($data);
echo "\nJSON payload:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

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
echo "Full Response:\n";
print_r(json_decode($response, true));

// Also check gameServers configuration
echo "\n\n=== Checking gameServers configuration ===\n";
$db = Database\DB::getInstance();
$stmt = $db->prepare("SELECT id, worldId, name, activation, preregistration_key_only FROM gameServers WHERE id = 1");
$stmt->execute();
$server = $stmt->fetch();
echo "Server config:\n";
print_r($server);
