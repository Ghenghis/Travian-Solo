<?php
/**
 * Check registered users in activation table
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "Checking registered users...\n\n";

    $stmt = $pdo->query("SELECT * FROM activation ORDER BY time DESC LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        echo "✓ Found " . count($users) . " users in activation table:\n\n";
        foreach ($users as $user) {
            echo "Username: {$user['name']}\n";
            echo "Email: {$user['email']}\n";
            echo "WorldId: {$user['worldId']}\n";
            echo "Activation Code: {$user['activationCode']}\n";
            echo "Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
            echo "Used: " . ($user['used'] ? 'YES' : 'NO') . "\n";
            echo "---\n";
        }
    } else {
        echo "✗ No users found in activation table\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
