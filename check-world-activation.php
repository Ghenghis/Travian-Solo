<?php
/**
 * Check world database activation table
 */

echo "=== WORLD DATABASE ACTIVATION CHECK ===\n\n";

// Connect to world database
$worldDb = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "📋 Checking travian_testworld activation table:\n\n";

// Check activation table
$stmt = $worldDb->query("SELECT * FROM activation ORDER BY id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    echo "✓ Found " . count($users) . " user(s) in activation table:\n\n";
    foreach ($users as $user) {
        echo "Username: {$user['name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Token: {$user['token']}\n";
        echo "RefUid: {$user['refUid']}\n";
        echo "Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
        echo "---\n";
    }
} else {
    echo "✗ No users in world activation table\n";
}

// Check users table
echo "\n📋 Checking travian_testworld users table:\n\n";
$stmt = $worldDb->query("SELECT COUNT(*) FROM users");
$count = $stmt->fetchColumn();

echo "User count in users table: $count\n";

if ($count > 0) {
    $stmt = $worldDb->query("SELECT id, name, email FROM users ORDER BY id DESC LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nLatest users:\n";
    foreach ($users as $user) {
        echo "  ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}\n";
    }
}
