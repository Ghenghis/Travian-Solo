<?php
/**
 * Test which web server is handling requests
 */

echo "Testing which web server is handling requests...\n";

$url = 'http://localhost/test-endpoint';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Response Headers:\n";
echo substr($response, 0, 500) . "\n\n";

// Check if it's reaching Nginx in Docker
if (strpos($response, 'nginx') !== false) {
    echo "✓ Requests are going to Docker Nginx\n";
} elseif (strpos($response, 'Apache') !== false) {
    echo "✗ Requests are going to XAMPP Apache (PORT CONFLICT!)\n";
    echo "\n** ACTION REQUIRED: Stop XAMPP Apache to use Docker **\n";
} else {
    echo "? Unknown web server\n";
}
