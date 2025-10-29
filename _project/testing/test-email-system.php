<?php
/**
 * Test Email System - Verify mock email logging works
 */

require __DIR__ . '/sections/api/include/Core/MockEmailService.php';

use Core\MockEmailService;

echo "=== EMAIL SYSTEM TEST ===\n\n";

// Clear previous logs
MockEmailService::clearLog();
echo "Step 1: Cleared previous email logs\n\n";

// Test 1: Activation Email
echo "=== TEST 1: Activation Email ===\n";
$result = MockEmailService::sendActivationMail(
    'test@example.com',
    'ABC123XYZ',
    1
);

if ($result) {
    echo "✓ Activation email logged successfully\n";
} else {
    echo "✗ Activation email failed\n";
}

// Test 2: Password Recovery Email
echo "\n=== TEST 2: Password Recovery Email ===\n";
$result = MockEmailService::sendPasswordForgotten(
    'recovery@example.com',
    1,      // serverId
    1,      // worldId
    42,     // uid
    'RECOVER789'
);

if ($result) {
    echo "✓ Password recovery email logged successfully\n";
} else {
    echo "✗ Password recovery email failed\n";
}

// Test 3: Forgotten Accounts Email
echo "\n=== TEST 3: Forgotten Accounts Email ===\n";
$gameWorlds = [
    ['worldId' => 1, 'username' => 'player1', 'gameWorldUrl' => 'http://testworld.travian.local'],
    ['worldId' => 2, 'username' => 'player2', 'gameWorldUrl' => 'http://demo.travian.local']
];

$result = MockEmailService::sendForgottenAccounts(
    'forgotten@example.com',
    $gameWorlds
);

if ($result) {
    echo "✓ Forgotten accounts email logged successfully\n";
} else {
    echo "✗ Forgotten accounts email failed\n";
}

// Display logged emails
echo "\n=== LOGGED EMAILS ===\n";
$logs = MockEmailService::getLoggedEmails();
echo $logs;

echo "\n=== SUMMARY ===\n";
$logCount = count(MockEmailService::getEmailLog());
echo "Total emails logged in memory: $logCount\n";
echo "Email log file: storage/email-log.txt\n";

if ($logCount == 3) {
    echo "\n✅ ALL EMAIL TESTS PASSED!\n";
    echo "Mock email system working correctly.\n";
} else {
    echo "\n⚠️ Some tests may have failed\n";
}

// Test 4: Test with actual registration flow
echo "\n=== TEST 4: Integration with Registration ===\n";
echo "Run test-registration.php or test-activate-detailed.php\n";
echo "to see email logging in action during user registration.\n";
