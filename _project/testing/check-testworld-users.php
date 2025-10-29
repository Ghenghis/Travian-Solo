<?php
/**
 * Check if users saved to testworld database
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "Checking testworld activation table...\n\n";

    // Check if activation table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'activation'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Activation table exists in testworld\n\n";
        
        $stmt = $pdo->query("SELECT * FROM activation ORDER BY time DESC LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) > 0) {
            echo "✓ Found " . count($users) . " users:\n\n";
            foreach ($users as $user) {
                echo "Username: {$user['name']}\n";
                echo "Email: {$user['email']}\n";
                echo "Token: {$user['token']}\n";
                echo "Time: " . date('Y-m-d H:i:s', $user['time']) . "\n";
                echo "---\n";
            }
        } else {
            echo "✗ No users found\n";
        }
    } else {
        echo "✗ Activation table does not exist in testworld\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
