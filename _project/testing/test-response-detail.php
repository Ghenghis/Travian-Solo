<?php
/**
 * Test registration and show FULL response details
 */

$url = 'http://nginx/v1/register/register';
$data = [
    'gameWorld' => 1,
    'username' => 'user' . substr(time(), -6), // Keep under 15 chars: user + 6 digits = 10 chars
    'email' => 'test' . time() . '@test.com',
    'password' => 'TestPass123!',
    'termsAndConditions' => true,
    'subscribeNewsletter' => false,
    'lang' => 'en'
];

echo "Sending registration request...\n";
echo "Username: {$data['username']}\n";
echo "Email: {$data['email']}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n\n";
echo "=== FULL RESPONSE (pretty printed) ===\n";
$decoded = json_decode($response, true);
echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";

echo "\n=== DATA SECTION DETAILS ===\n";
if (isset($decoded['data'])) {
    foreach ($decoded['data'] as $key => $value) {
        echo "$key: " . json_encode($value) . "\n";
    }
} else {
    echo "No 'data' section in response!\n";
}
